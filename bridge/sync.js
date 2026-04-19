'use strict';
require('dotenv').config();

/**
 * sync.js — SerbIsko Bridge
 * ─────────────────────────────────────────────────────────────────────────────
 * Firestore `responses` collection  →  Heuristic Validation  →  MySQL (serbisko_db)
 *
 * Tables written:  users · students · pre_enrollments
 * Tables written:  sync_conflicts  (flagged records)
 * Tables written:  sync_histories  (per-run audit log)
 *
 * Conflict types
 *   identity_mismatch   — LRN found but name changed too much
 *   birthday_mismatch   — name matches but birthday diverges
 *   lrn_change_request  — same name+birthday, different LRN submitted
 *
 * isSynced states written back to Firestore
 *   true        — successfully upserted
 *   'conflict'  — queued for admin review
 *   'locked'    — admin has manually edited this record; bridge will not touch it
 *   'rejected'  — failed basic field validation (bad LRN, missing name, bad date)
 * ─────────────────────────────────────────────────────────────────────────────
 */

const admin          = require('firebase-admin');
const mysql          = require('mysql2/promise');
const bcrypt         = require('bcryptjs');
const serviceAccount = require('./serviceAccountKey.json');

// ─────────────────────────────────────────────────────────────────────────────
// 1.  Initialise Firebase & MySQL
// ─────────────────────────────────────────────────────────────────────────────

admin.initializeApp({ credential: admin.credential.cert(serviceAccount) });
const db = admin.firestore();

const pool = mysql.createPool({
  host:             process.env.MYSQL_HOST     || '127.0.0.1',
  port:    parseInt(process.env.MYSQL_PORT     || '3307'),
  user:             process.env.MYSQL_USER     || 'root',
  password:         process.env.MYSQL_PASS     || '',
  database:         process.env.MYSQL_DATABASE || 'serbisko_db',
  waitForConnections: true,
  connectionLimit:    10,
  queueLimit:          0,
  timezone:          'Z', // store all datetimes as UTC
});

// Verify the MySQL connection once on startup so we fail fast rather than
// silently discarding every document that arrives.
(async () => {
  try {
    const conn = await pool.getConnection();
    conn.release();
    console.log('✅  MySQL connection verified.');
  } catch (err) {
    console.error('❌  Cannot connect to MySQL on startup:', err.message);
    process.exit(1);
  }
})();

console.log('🚀  SerbIsko Bridge — HEURISTIC VALIDATION ACTIVE');

// ─────────────────────────────────────────────────────────────────────────────
// 2.  Pure helpers (no I/O)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Normalise a string for fuzzy comparison.
 *
 * Steps:
 *  1. NFD decomposition  — splits a composed character into base + combining mark
 *                          "n" (U+00F1)  ->  "n" + combining-tilde (U+0303)
 *                          "e" (U+00E9)  ->  "e" + combining-acute  (U+0301)
 *  2. Strip all combining marks (Unicode category Mn, range U+0300-U+036F)
 *                          "n" + combining-tilde  ->  "n"
 *  3. Lowercase + strip remaining non-alphanumeric (spaces, hyphens, punctuation)
 *
 * Result examples:
 *   "Castaneda" (written with n-tilde) -> "castaneda"
 *   "Castaneda" (written with plain n) -> "castaneda"   (same output, dist = 0)
 *   "Delos Reyes"                      -> "delosreyes"
 *
 * Both sides of every comparison go through this, so an n-tilde vs plain-n
 * mismatch costs 0 edits instead of 2 (deletion + insertion).
 */
const normalize = (str) =>
  String(str || '')
    .normalize('NFD')                // decompose: n-tilde -> n + combining-tilde
    .replace(/[\u0300-\u036f]/g, '') // strip all combining diacritical marks
    .toLowerCase()
    .replace(/[^a-z0-9]/g, '')       // strip spaces, hyphens, punctuation
    .trim();

/**
 * Levenshtein distance (Wagner–Fischer).
 * Works on already-normalised strings.
 */
function levenshtein(a, b) {
  const m = a.length, n = b.length;
  // Use two rolling rows instead of an m×n matrix — O(n) space.
  let prev = Array.from({ length: n + 1 }, (_, i) => i);
  let curr = new Array(n + 1);
  for (let i = 1; i <= m; i++) {
    curr[0] = i;
    for (let j = 1; j <= n; j++) {
      curr[j] = a[i - 1] === b[j - 1]
        ? prev[j - 1]
        : 1 + Math.min(prev[j], curr[j - 1], prev[j - 1]);
    }
    [prev, curr] = [curr, prev];
  }
  return prev[n];
}

/**
 * True when the edit distance is > 40 % of the longer string.
 * Tuned for Filipino names where short suffixes like "-lyn", "-ito" are common.
 *
 *   "Rhea"    → "Rhealyn"  dist=3, longer=7, ratio=43 % → MAJOR  (flag)
 *   "Rhealyn" → "Realyn"   dist=1, longer=7, ratio=14 % → minor  (allow)
 *   "Santos"  → "Santoz"   dist=1, longer=6, ratio=17 % → minor  (allow)
 */
function isMajorNameEdit(incoming, existing) {
  const a = normalize(incoming);
  const b = normalize(existing);
  if (!a || !b) return true; // missing name → always flag
  const longer = Math.max(a.length, b.length);
  return (levenshtein(a, b) / longer) > 0.40;
}

/**
 * Detects first/last name swap:  submitted first_name=Santos last_name=Juan
 * when the record has first_name=Juan last_name=Santos.
 * Treated as a typo — bridge auto-corrects without flagging.
 */
const isNameSwap = (inF, inL, exF, exL) =>
  normalize(inF) === normalize(exL) && normalize(inL) === normalize(exF);

/**
 * Count character-position differences between two YYYY-MM-DD strings.
 * Returns 99 if either date is absent (forces a flag).
 *
 * Why character diff and not Date comparison?
 * A transposition like 1998-03-21 → 1998-03-12 is 2 diffs (likely a typo),
 * while 1998-03-21 → 2001-07-15 is 7 diffs (clearly a different person).
 */
function birthdayDiff(dateA, dateB) {
  if (!dateA || !dateB) return 99;
  const a = String(dateA), b = String(dateB);
  let diffs = 0;
  for (let i = 0; i < Math.max(a.length, b.length); i++) {
    if (a[i] !== b[i]) diffs++;
  }
  return diffs;
}

/** True for YYYY-MM-DD strings. */
const isValidDate = (d) => Boolean(d && /^\d{4}-\d{2}-\d{2}$/.test(d));

/** Coerce to string, trim, enforce max length.  Returns '' for null/undefined. */
const safeStr = (val, max = 255) => String(val ?? '').trim().substring(0, max);

/** Returns null instead of NaN so MySQL receives a proper NULL. */
const safeInt = (val) => {
  const n = parseInt(val, 10);
  return Number.isNaN(n) ? null : n;
};

/**
 * Title-case a name segment: "JUAN DE LA CRUZ" -> "Juan De La Cruz".
 *
 * Uses Unicode-aware lowercasing so accented and special characters are
 * handled correctly by the JS engine's built-in locale rules:
 *   "CASTANEDA" (with N-tilde) -> "Castaneda" (N-tilde preserved, lowercased)
 *   "DELOS REYES"              -> "Delos Reyes"
 *
 * Guard: if the string already has at least one lowercase letter it was
 * probably intentionally cased by the user — leave it alone.
 */
const toTitleCase = (str) => {
  const s = safeStr(str);
  if (!s) return s;

  return s.toLowerCase().replace(/(^|[\s-])(\S)/gu, (match, sep, ch) => {
    return sep + ch.toUpperCase();
  });
};

// ─────────────────────────────────────────────────────────────────────────────
// 3.  Firestore helpers
// ─────────────────────────────────────────────────────────────────────────────

/** Write back to Firestore with a retry on transient errors. */
async function fsUpdate(docId, payload, retries = 3) {
  for (let attempt = 1; attempt <= retries; attempt++) {
    try {
      await db.collection('responses').doc(docId).update(payload);
      return;
    } catch (err) {
      if (attempt === retries) {
        console.error(`⚠️  [FS-UPDATE] Failed after ${retries} attempts for doc ${docId}: ${err.message}`);
      } else {
        await new Promise((r) => setTimeout(r, 300 * attempt));
      }
    }
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// 4.  MySQL writers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Record a completed sync run in sync_histories.
 * Called once per snapshot batch (not once per document).
 */
async function writeSyncHistory(conn, { schoolYear, newRecords, updatedRecords, status }) {
  await conn.execute(
    `INSERT INTO sync_histories
       (school_year, records_synced, new_records, updated_records, status, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, NOW(), NOW())`,
    [schoolYear, newRecords + updatedRecords, newRecords, updatedRecords, status]
  );
}

/**
 * Upsert a conflict record.
 * ON DUPLICATE KEY works because sync_conflicts has UNIQUE(lrn, school_year).
 * Includes raw_sheet_row so admins can always see the original Firestore payload.
 */
async function writeConflict(conn, { lrn, schoolYear, userId, raw, conflictType }) {
  // Fetch the current record so we can store a before/after snapshot
  const [existing] = await conn.execute(
    `SELECT u.first_name, u.last_name, u.birthday, s.lrn
     FROM users u
     JOIN students s ON s.user_id = u.id
     WHERE s.lrn = ? AND s.school_year = ?
     LIMIT 1`,
    [lrn, schoolYear]
  );

  await conn.execute(
    `INSERT INTO sync_conflicts
       (lrn, school_year, existing_user_id,
        existing_data_json, incoming_data_json, raw_sheet_row,
        conflict_type, status, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())
     ON DUPLICATE KEY UPDATE
       existing_user_id   = VALUES(existing_user_id),
       existing_data_json = VALUES(existing_data_json),
       incoming_data_json = VALUES(incoming_data_json),
       raw_sheet_row      = VALUES(raw_sheet_row),
       conflict_type      = VALUES(conflict_type),
       status             = 'pending',
       updated_at         = NOW()`,
    [
      lrn,
      schoolYear,
      userId   || null,
      existing.length ? JSON.stringify(existing[0]) : null,
      JSON.stringify(raw),
      JSON.stringify(raw), // raw_sheet_row: verbatim Firestore payload
      conflictType,
    ]
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// 5.  Core per-document processor
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Process one Firestore document.
 * Returns one of: 'synced' | 'conflict' | 'locked' | 'rejected' | 'skipped'
 */
async function processDocument(docId, raw) {
  // ── Early guards ─────────────────────────────────────────────────────────

  // Belt-and-suspenders: skip anything the previous run already handled.
  const terminalStates = [true, 'conflict', 'locked', 'rejected'];
  if (terminalStates.includes(raw.isSynced)) return 'skipped';

  const lrn        = safeStr(raw.lrn);
  const schoolYear = safeStr(raw.school_year || '2026-2027', 20);
  const bday       = isValidDate(raw.birthday) ? raw.birthday : null;

  // ── Field-level validation (reject garbage before touching the DB) ───────

  if (!lrn || lrn.length < 6 || lrn.length > 20) {
    console.warn(`⚠️  [REJECTED] Bad LRN "${lrn}" — doc ${docId}`);
    await fsUpdate(docId, { isSynced: 'rejected', rejectedReason: 'invalid_lrn' });
    return 'rejected';
  }

  if (!safeStr(raw.first_name) || !safeStr(raw.last_name)) {
    console.warn(`⚠️  [REJECTED] Missing name for LRN ${lrn} — doc ${docId}`);
    await fsUpdate(docId, { isSynced: 'rejected', rejectedReason: 'missing_name_fields' });
    return 'rejected';
  }

  if (raw.birthday && !isValidDate(raw.birthday)) {
    console.warn(`⚠️  [REJECTED] Invalid birthday "${raw.birthday}" for LRN ${lrn}`);
    await fsUpdate(docId, { isSynced: 'rejected', rejectedReason: 'invalid_birthday_format' });
    return 'rejected';
  }

  // ── Normalise name casing before any DB work ─────────────────────────────
  // Only touch obviously all-caps values (e.g. straight from a form field).
  const firstName  = toTitleCase(raw.first_name);
  const lastName   = toTitleCase(raw.last_name);
  const middleName = toTitleCase(raw.middle_name);
  const extName    = safeStr(raw.extension_name, 10);

  console.log(`📡 [${raw.isSynced === false ? 'NEW' : 'MODIFIED'}] LRN: ${lrn} | SY: ${schoolYear}`);

  const conn = await pool.getConnection();

  try {
    await conn.beginTransaction();

    // ── STEP A: Manual-lock check ─────────────────────────────────────────
    // is_manually_edited lives only on the students row. The dashboard must
    // set this flag whenever an admin edits ANY field on this person —
    // whether they corrected a name in users or an address in students.
    // One flag on students covers the whole record; no extra column needed.
    const [[lockRow]] = await conn.execute(
      'SELECT id, is_manually_edited FROM students WHERE lrn = ? AND school_year = ? LIMIT 1',
      [lrn, schoolYear]
    );

    if (lockRow?.is_manually_edited) {
      console.log(`🔒  [LOCKED] LRN ${lrn} — admin-edited record, skipping.`);
      await conn.commit();
      await fsUpdate(docId, { isSynced: 'locked' });
      return 'locked';
    }

    let userId       = null;
    let studentId    = lockRow?.id ?? null;  // may already exist from the lock check
    let conflictType = null;

    // ── STEP B: LRN match — verify identity still matches ────────────────
    const [[lrnRow]] = await conn.execute(
      `SELECT u.id, u.first_name, u.last_name, u.birthday
       FROM users u
       JOIN students s ON s.user_id = u.id
       WHERE s.lrn = ?
       LIMIT 1`,
      [lrn]
    );

    if (lrnRow) {
      userId = lrnRow.id;

      // RULE 2: First/last names swapped → treat as a self-correcting typo.
      const swapped = isNameSwap(firstName, lastName, lrnRow.first_name, lrnRow.last_name);

      if (!swapped) {
        // RULE 1: Proportional name-edit check.
        const majorFirst = isMajorNameEdit(firstName, lrnRow.first_name);
        const majorLast  = isMajorNameEdit(lastName,  lrnRow.last_name);

        if (majorFirst || majorLast) {
          conflictType = 'identity_mismatch';
          console.log(`   ↳ [CONFLICT] Name change too large.`
            + ` incoming="${firstName} ${lastName}"`
            + ` existing="${lrnRow.first_name} ${lrnRow.last_name}"`);
        }
      }

      // RULE 4: Birthday check — only worth checking if name passed.
      if (!conflictType && bday) {
        const bdDiff = birthdayDiff(bday, lrnRow.birthday);
        if (bdDiff > 1) {
          conflictType = 'birthday_mismatch';
          console.log(`   ↳ [CONFLICT] Birthday diverges too much.`
            + ` incoming="${bday}" existing="${lrnRow.birthday}" diffs=${bdDiff}`);
        }
      }

    // ── STEP C: No LRN match — check name+birthday for RULE 3 (LRN change) ─
    } else {
      const [[nameRow]] = await conn.execute(
        `SELECT u.id, u.birthday, s.lrn AS existing_lrn
         FROM users u
         JOIN students s ON s.user_id = u.id
         WHERE u.first_name = ? AND u.last_name = ?
         LIMIT 1`,
        [firstName, lastName]
      );

      if (nameRow) {
        const bdDiff = birthdayDiff(bday, nameRow.birthday);

        if (bdDiff <= 1 && nameRow.existing_lrn !== lrn) {
          // RULE 3: Same person, different LRN — request admin approval before
          // we touch the primary key derivative (password = hash(LRN)).
          userId       = nameRow.id;
          conflictType = 'lrn_change_request';
          console.log(`   ↳ [CONFLICT] LRN change detected.`
            + ` existing="${nameRow.existing_lrn}" incoming="${lrn}"`);

        } else if (bdDiff > 1) {
          // Same name but birthday diverges — could be a different person.
          userId       = nameRow.id;
          conflictType = 'birthday_mismatch';
          console.log(`   ↳ [CONFLICT] Name match but birthday mismatch. diffs=${bdDiff}`);
        }
        // If bdDiff <= 1 and LRNs are already the same → fall through to new-student creation.
      }
      // No name match at all → brand-new student, fall through to creation.
    }

    // ── STEP D: Route on conflict or clean write ──────────────────────────

    if (conflictType) {

      // ── D1: Flag & queue ────────────────────────────────────────────────
      await writeConflict(conn, { lrn, schoolYear, userId, raw, conflictType });

      await conn.commit();
      await fsUpdate(docId, { isSynced: 'conflict' });
      console.log(`🚨 [CONFLICT: ${conflictType}] LRN: ${lrn} | SY: ${schoolYear} → queued for admin review.`);
      return 'conflict';

    } else {

      // ── D2: Clean write path ────────────────────────────────────────────
      const isNewUser = !userId;

      if (isNewUser) {
        // New student — create user account; password = bcrypt(LRN).
        const hashedLrn = await bcrypt.hash(lrn, 10); // async is faster in a loop
        const [newUser] = await conn.execute(
          `INSERT INTO users
             (first_name, last_name, middle_name, extension_name,
              birthday, password, role, created_at, updated_at)
           VALUES (?, ?, ?, ?, ?, ?, 'student', NOW(), NOW())`,
          [firstName, lastName, middleName, extName, bday, hashedLrn]
        );
        userId = newUser.insertId;

      } else {
        // Returning student — update identity fields.
        // (Only reached on the typo-correction path, i.e. isMajorNameEdit = false.)
        await conn.execute(
          `UPDATE users
           SET first_name     = ?,
               last_name      = ?,
               middle_name    = ?,
               extension_name = ?,
               birthday       = ?,
               updated_at     = NOW()
           WHERE id = ?`,
          [firstName, lastName, middleName, extName, bday, userId]
        );
      }

      // ── Upsert students row (all fields) ──────────────────────────────
      // UNIQUE(lrn, school_year) drives ON DUPLICATE KEY UPDATE.
      const [stuRes] = await conn.execute(
        `INSERT INTO students (
           user_id, lrn, school_year,
           sex, age, place_of_birth, mother_tongue,
           curr_house_number, curr_street, curr_barangay, curr_city,
           curr_province, curr_zip_code, curr_country,
           is_perm_same_as_curr,
           perm_house_number, perm_street, perm_barangay, perm_city,
           perm_province, perm_zip_code, perm_country,
           mother_last_name,   mother_first_name,   mother_middle_name,   mother_contact_number,
           father_last_name,   father_first_name,   father_middle_name,   father_contact_number,
           guardian_last_name, guardian_first_name, guardian_middle_name, guardian_contact_number,
           created_at, updated_at
         ) VALUES (
           ?,?,?,
           ?,?,?,?,
           ?,?,?,?,?,?,?,
           ?,
           ?,?,?,?,?,?,?,
           ?,?,?,?,
           ?,?,?,?,
           ?,?,?,?,
           NOW(), NOW()
         )
         ON DUPLICATE KEY UPDATE
           sex                     = VALUES(sex),
           age                     = VALUES(age),
           place_of_birth          = VALUES(place_of_birth),
           mother_tongue           = VALUES(mother_tongue),
           curr_house_number       = VALUES(curr_house_number),
           curr_street             = VALUES(curr_street),
           curr_barangay           = VALUES(curr_barangay),
           curr_city               = VALUES(curr_city),
           curr_province           = VALUES(curr_province),
           curr_zip_code           = VALUES(curr_zip_code),
           curr_country            = VALUES(curr_country),
           is_perm_same_as_curr    = VALUES(is_perm_same_as_curr),
           perm_house_number       = VALUES(perm_house_number),
           perm_street             = VALUES(perm_street),
           perm_barangay           = VALUES(perm_barangay),
           perm_city               = VALUES(perm_city),
           perm_province           = VALUES(perm_province),
           perm_zip_code           = VALUES(perm_zip_code),
           perm_country            = VALUES(perm_country),
           mother_last_name        = VALUES(mother_last_name),
           mother_first_name       = VALUES(mother_first_name),
           mother_middle_name      = VALUES(mother_middle_name),
           mother_contact_number   = VALUES(mother_contact_number),
           father_last_name        = VALUES(father_last_name),
           father_first_name       = VALUES(father_first_name),
           father_middle_name      = VALUES(father_middle_name),
           father_contact_number   = VALUES(father_contact_number),
           guardian_last_name      = VALUES(guardian_last_name),
           guardian_first_name     = VALUES(guardian_first_name),
           guardian_middle_name    = VALUES(guardian_middle_name),
           guardian_contact_number = VALUES(guardian_contact_number),
           updated_at              = NOW()`,
        [
          userId, lrn, schoolYear,
          safeStr(raw.sex) || null,
          safeInt(raw.age),
          safeStr(raw.place_of_birth),
          safeStr(raw.mother_tongue),
          safeStr(raw.curr_house_number),
          safeStr(raw.curr_street),
          safeStr(raw.curr_barangay),
          safeStr(raw.curr_city),
          safeStr(raw.curr_province),
          safeStr(raw.curr_zip_code),
          safeStr(raw.curr_country)   || 'Philippines',
          raw.is_perm_same_as_curr    ? 1 : 0,
          safeStr(raw.perm_house_number),
          safeStr(raw.perm_street),
          safeStr(raw.perm_barangay),
          safeStr(raw.perm_city),
          safeStr(raw.perm_province),
          safeStr(raw.perm_zip_code),
          safeStr(raw.perm_country)   || 'Philippines',
          toTitleCase(raw.mother_last_name),
          toTitleCase(raw.mother_first_name),
          toTitleCase(raw.mother_middle_name),
          safeStr(raw.mother_contact_number),
          toTitleCase(raw.father_last_name),
          toTitleCase(raw.father_first_name),
          toTitleCase(raw.father_middle_name),
          safeStr(raw.father_contact_number),
          toTitleCase(raw.guardian_last_name),
          toTitleCase(raw.guardian_first_name),
          toTitleCase(raw.guardian_middle_name),
          safeStr(raw.guardian_contact_number),
        ]
      );

      // insertId is 0 on ON DUPLICATE KEY UPDATE — fall back to SELECT.
      studentId = stuRes.insertId || studentId;
      if (!studentId) {
        const [[existRow]] = await conn.execute(
          'SELECT id FROM students WHERE lrn = ? AND school_year = ? LIMIT 1',
          [lrn, schoolYear]
        );
        if (!existRow) throw new Error(`Cannot resolve student ID for LRN: ${lrn}`);
        studentId = existRow.id;
      }

      // ── Versioned pre_enrollment archive ──────────────────────────────
      // Each successful sync creates a new row — this is intentional.
      // pre_enrollments acts as a full submission history.
      // Do NOT add UNIQUE(student_id) here.
      //
      // submission_version: count existing rows + 1 so the dashboard can
      // display "Submission #3" without a separate counter.
      const [[{ versionCount }]] = await conn.execute(
        'SELECT COUNT(*) AS versionCount FROM pre_enrollments WHERE student_id = ?',
        [studentId]
      );
      await conn.execute(
        `INSERT INTO pre_enrollments
           (student_id, submission_version, responses, status, created_at, updated_at)
         VALUES (?, ?, ?, 'Synced', NOW(), NOW())`,
        [studentId, versionCount + 1, JSON.stringify(raw)]
      );

      await conn.commit();
      await fsUpdate(docId, { isSynced: true });
      console.log(`✅ [SYNCED] ${firstName} ${lastName}`
        + ` | LRN: ${lrn} | SY: ${schoolYear}`
        + ` | userID: ${userId} | studentID: ${studentId}`
        + ` | ${isNewUser ? 'NEW' : 'UPDATED'}`);

      return isNewUser ? 'new' : 'updated';
    }

  } catch (err) {
    await conn.rollback();
    console.error(`❌ [FAILED] LRN: ${lrn} | SY: ${schoolYear} → ${err.message}`);
    console.error(err.stack);
    throw err; // re-throw so the batch loop can count failures

  } finally {
    conn.release();
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// 6.  Snapshot listener
// ─────────────────────────────────────────────────────────────────────────────

db.collection('responses')
  .where('isSynced', '==', false)
  .onSnapshot(
    async (snapshot) => {
      const changes = snapshot.docChanges().filter(
        (c) => c.type === 'added' || c.type === 'modified'
      );

      if (changes.length === 0) return;

      console.log(`\n📦 Batch: ${changes.length} document(s) to process.`);

      // Per-batch counters for sync_histories
      const counter = { newRecords: 0, updatedRecords: 0, failed: 0 };
      // Track school_year per batch (take the most common value in the batch)
      let batchSchoolYear = null;

      for (const change of changes) {
        const result = await processDocument(change.doc.id, change.doc.data()).catch(() => 'error');

        if (result === 'new')     counter.newRecords++;
        if (result === 'updated') counter.updatedRecords++;
        if (result === 'error')   counter.failed++;
        if (!batchSchoolYear && result !== 'skipped' && result !== 'rejected') {
          batchSchoolYear = safeStr(change.doc.data().school_year || '2026-2027', 20);
        }
      }

      // Only write a history row when something actionable happened
      const actionable = counter.newRecords + counter.updatedRecords;
      if (actionable > 0) {
        const conn = await pool.getConnection();
        try {
          await writeSyncHistory(conn, {
            schoolYear:    batchSchoolYear,
            newRecords:    counter.newRecords,
            updatedRecords: counter.updatedRecords,
            status:        counter.failed > 0 ? 'Partial' : 'Success',
          });
        } catch (histErr) {
          console.error('⚠️  Could not write sync history:', histErr.message);
        } finally {
          conn.release();
        }
      }

      console.log(`📊 Batch complete — new: ${counter.newRecords}`
        + ` | updated: ${counter.updatedRecords}`
        + ` | failed: ${counter.failed}\n`);
    },

    (err) => {
      // Firestore listener error — log and attempt reconnect.
      console.error('❌ [LISTENER ERROR]', err.message);
      console.error('   Reconnecting in 5 s…');
      setTimeout(() => {
        // Re-attach by restarting the process (PM2 / nodemon will restart it).
        process.exit(1);
      }, 5_000);
    }
  );