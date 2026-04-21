'use strict';
require('dotenv').config();

const admin          = require('firebase-admin');
const mysql          = require('mysql2/promise');
const bcrypt         = require('bcryptjs');
const serviceAccount = require('./serviceAccountKey.json');

// Initialize Firestore
admin.initializeApp({ credential: admin.credential.cert(serviceAccount) });
const db = admin.firestore();
console.log('🔥 [FIRESTORE] Connected and listening for changes...');

// Initialize MySQL Pool
const pool = mysql.createPool({
  host:             process.env.MYSQL_HOST     || '127.0.0.1',
  port:    parseInt(process.env.MYSQL_PORT     || '3307'),
  user:             process.env.MYSQL_USER     || 'root',
  password:         process.env.MYSQL_PASS     || '',
  database:         process.env.MYSQL_DATABASE || 'serbisko_db',
  waitForConnections: true,
  connectionLimit:    10,
  queueLimit:          0,
  timezone:          'Z',
});

// Explicitly check MySQL Connection at startup
(async () => {
  try {
    const conn = await pool.getConnection();
    console.log('🐬 [MYSQL] Connection pool established successfully.');
    conn.release();
  } catch (err) {
    console.error('❌ [MYSQL] Failed to connect to the database:', err.message);
    process.exit(1);
  }
})();

// ─────────────────────────────────────────────────────────────────────────────
// SANITIZATION
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Converts all undefined values in a flat object to null.
 * MySQL2 does not accept undefined as a bind parameter — this must be called
 * on every payload object before any conn.execute() call.
 */
function sanitizePayload(obj) {
  const out = {};
  for (const key of Object.keys(obj)) {
    const val = obj[key];
    out[key] = (val === undefined || val === 'undefined') ? null : val;
  }
  return out;
}

/**
 * Safely coerce a single value: undefined → null.
 * Use for individual variables that feed into SQL bind arrays.
 */
const safe = (v) => (v === undefined || v === 'undefined') ? null : v;

// ─────────────────────────────────────────────────────────────────────────────
// HELPERS & HEURISTICS
// ─────────────────────────────────────────────────────────────────────────────

const normalize = (str) =>
  String(str || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]/g, '')
    .trim();

function levenshtein(a, b) {
  const m = a.length, n = b.length;
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
 * Returns true when the name change is a major edit requiring admin approval.
 *
 * Rules:
 *  • Swapped first/last → NOT a major edit (treat as typo, auto-accept)
 *  • Levenshtein distance on either name > 2 → major edit
 *  • Distance ≤ 2 on both → minor edit (typo-level, auto-accept)
 */
function isMajorNameEdit(inF, inL, exF, exL) {
  const nInF = normalize(inF), nInL = normalize(inL);
  const nExF = normalize(exF), nExL = normalize(exL);

  // Name swap detection — treat as minor / typo, no approval needed
  if (nInF === nExL && nInL === nExF) return false;

  const distF = levenshtein(nInF, nExF);
  const distL = levenshtein(nInL, nExL);

  // Major edit: either name differs by more than 2 characters
  return distF > 2 || distL > 2;
}

/**
 * Returns true when the birthday change requires admin approval.
 *
 * Rules:
 *  • 0–1 digit difference → auto-accept (minor typo)
 *  • 2+ digit differences → major edit, flag for admin
 */
function isMajorBirthdayEdit(dateA, dateB) {
  if (!dateA || !dateB) return false;
  const a = String(dateA), b = String(dateB);
  let diffs = 0;
  for (let i = 0; i < Math.max(a.length, b.length); i++) {
    if (a[i] !== b[i]) diffs++;
  }
  return diffs >= 2; // 2 or more digit differences → requires admin approval
}

const isValidDate = (d) => Boolean(d && /^\d{4}-\d{2}-\d{2}$/.test(d));

const toTitleCase = (str) => {
  const s = String(str || '').trim();
  if (!s) return s;
  return s.toLowerCase().replace(/(^|[\s-])(\S)/gu, (m, sep, ch) => sep + ch.toUpperCase());
};

// ─────────────────────────────────────────────────────────────────────────────
// KNOWN STUDENT-TABLE COLUMNS
// These are the only fields that map directly onto the `students` table.
// Everything else is treated as an extra_field and stored in pre_enrollments.
// ─────────────────────────────────────────────────────────────────────────────

const STUDENT_COLUMNS = new Set([
  'sex', 'age', 'place_of_birth', 'mother_tongue',
  'curr_house_number', 'curr_street', 'curr_barangay', 'curr_city',
  'curr_province', 'curr_zip_code', 'curr_country',
  'is_perm_same_as_curr',
  'perm_house_number', 'perm_street', 'perm_barangay', 'perm_city',
  'perm_province', 'perm_zip_code', 'perm_country',
  'mother_last_name', 'mother_first_name', 'mother_middle_name', 'mother_contact_number',
  'father_last_name', 'father_first_name', 'father_middle_name', 'father_contact_number',
  'guardian_last_name', 'guardian_first_name', 'guardian_middle_name', 'guardian_contact_number',
]);

// Fields that are identity-sensitive and must NEVER trigger a sync conflict
// (they are handled separately through the conflict pipeline)
const IDENTITY_FIELDS = new Set(['first_name', 'last_name', 'birthday', 'lrn']);

// ALL keys that belong to users/students tables OR are Firestore/system meta.
// Anything NOT in this set is an "extra field" → stored flat in pre_enrollments.responses.
const EXCLUDED_FROM_EXTRA = new Set([
  // Identity / users table
  'first_name', 'last_name', 'middle_name', 'extension_name',
  'birthday', 'lrn', 'password', 'role',
  // students table
  'sex', 'age', 'school_year', 'place_of_birth', 'mother_tongue',
  'curr_house_number', 'curr_street', 'curr_barangay', 'curr_city',
  'curr_province', 'curr_zip_code', 'curr_country',
  'is_perm_same_as_curr',
  'perm_house_number', 'perm_street', 'perm_barangay', 'perm_city',
  'perm_province', 'perm_zip_code', 'perm_country',
  'mother_last_name', 'mother_first_name', 'mother_middle_name', 'mother_contact_number',
  'father_last_name', 'father_first_name', 'father_middle_name', 'father_contact_number',
  'guardian_last_name', 'guardian_first_name', 'guardian_middle_name', 'guardian_contact_number',
  // Firestore / sync meta
  'isSynced', 'extra_fields', 'form_id', 'submitted_at',
]);

// ─────────────────────────────────────────────────────────────────────────────
// CORE PROCESSOR
// ─────────────────────────────────────────────────────────────────────────────

async function processDocument(docId, rawInput) {
  // ── 1. Guard: skip already-terminal documents ──────────────────────────────
  const terminalStates = [true, 'conflict', 'locked', 'rejected'];
  if (terminalStates.includes(rawInput.isSynced)) return 'skipped';

  // ── 2. Sanitize entire payload upfront — eliminates undefined bind errors ──
  const raw = sanitizePayload(rawInput);

  // ── 3. Extract & normalize core identity fields ────────────────────────────
  const lrn        = String(raw.lrn || '').trim();
  const schoolYear = String(raw.school_year || '2026-2027');
  const bday       = isValidDate(raw.birthday) ? raw.birthday : null;
  const firstName  = toTitleCase(raw.first_name);
  const lastName   = toTitleCase(raw.last_name);

  if (!lrn) {
    console.warn(`⚠️  [SKIP] Document ${docId} has no LRN — skipping.`);
    return 'skipped';
  }

  // ── 4. Determine which field categories are present in this update ─────────
  const hasIdentityFields =
    rawInput.hasOwnProperty('first_name') ||
    rawInput.hasOwnProperty('last_name')  ||
    rawInput.hasOwnProperty('birthday');

  // Collect ONLY the fields that are not already stored in users/students tables
  // and are not Firestore/system meta keys.
  // If the student view still sends an "extra_fields": { ... } wrapper, we flatten
  // its contents into the top level first so nothing ends up double-nested.
  // Stored flat — no wrapper object — so the JSON column looks like:
  //   { "cluster_of_electives": "STEM", "track": "Academic", ... }
  const flatRaw = { ...raw };
  if (flatRaw.extra_fields && typeof flatRaw.extra_fields === 'object') {
    Object.assign(flatRaw, flatRaw.extra_fields);
    delete flatRaw.extra_fields;
  }
  const extraFieldsOnly = {};
  for (const [key, val] of Object.entries(flatRaw)) {
    if (!EXCLUDED_FROM_EXTRA.has(key)) {
      extraFieldsOnly[key] = val;
    }
  }

  const conn = await pool.getConnection();

  try {
    await conn.beginTransaction();

    // ── 5. Look up existing student record ─────────────────────────────────
    const [[existingUser]] = await conn.execute(
      `SELECT u.id, u.first_name, u.last_name, u.birthday,
              s.id as student_id, s.lrn as existing_lrn, s.is_manually_edited
       FROM users u
       JOIN students s ON s.user_id = u.id
       WHERE s.lrn = ? AND s.school_year = ?
       LIMIT 1`,
      [lrn, schoolYear]
    );

    // ── 6. Skip records that have been manually locked by an admin ──────────
    if (existingUser && existingUser.is_manually_edited) {
      await conn.rollback();
      console.log(`🔒 [LOCKED] LRN ${lrn} is manually edited — skipping auto-sync.`);
      return 'skipped';
    }

    let conflictType = null;
    let userId = existingUser?.id || null;

    // ── 7. EXISTING RECORD: validate identity changes ───────────────────────
    if (existingUser) {
      if (hasIdentityFields) {
        const majorName = isMajorNameEdit(
          firstName, lastName,
          existingUser.first_name, existingUser.last_name
        );
        const majorBday = isMajorBirthdayEdit(bday, existingUser.birthday);

        if (majorName) {
          conflictType = 'identity_mismatch';
        } else if (majorBday) {
          conflictType = 'birthday_mismatch';
        }
        // Minor name edits (distance ≤ 2) and 1-digit birthday diffs
        // fall through without setting conflictType → auto-accepted below
      }
    } else {
      // ── 8. NEW RECORD: check for identity collision (same person, diff LRN) ─
      const [[nameMatch]] = await conn.execute(
        `SELECT u.id FROM users u
         JOIN students s ON s.user_id = u.id
         WHERE u.first_name = ? AND u.last_name = ? AND u.birthday = ?
         LIMIT 1`,
        [safe(firstName), safe(lastName), safe(bday)]
      );

      if (nameMatch) {
        userId = nameMatch.id;
        conflictType = 'lrn_change_request';
      }
    }

    // ── 9. CONFLICT PATH: log and mark Firestore document ──────────────────
    if (conflictType) {
      await conn.execute(
        `INSERT INTO sync_conflicts
           (lrn, school_year, existing_user_id, incoming_data_json, conflict_type, status, created_at)
         VALUES (?, ?, ?, ?, ?, 'pending', NOW())
         ON DUPLICATE KEY UPDATE
           conflict_type      = VALUES(conflict_type),
           incoming_data_json = VALUES(incoming_data_json),
           status             = 'pending'`,
        [
          lrn,
          schoolYear,
          safe(userId),
          JSON.stringify(raw),
          conflictType,
        ]
      );
      await conn.commit();
      await db.collection('responses').doc(docId).update({ isSynced: 'conflict' });
      console.log(`🚨 [CONFLICT] ${conflictType} for LRN: ${lrn}`);
      return 'conflict';
    }

    // ── 10. DATA WRITE PATH ────────────────────────────────────────────────

    // 10a. Create user if brand-new
    if (!userId) {
      // Rule §4: students use their LRN as password (hashed with bcrypt)
      // bcryptjs generates a $2a$ prefix; PHP's password_verify requires $2y$.
      // Replacing the prefix makes the hash fully compatible with Laravel's Hash::check().
      const rawHash   = await bcrypt.hash(lrn, 10);
      // bcryptjs may produce $2a$ or $2b$ depending on version — PHP only accepts $2y$.
      const hashedLrn = rawHash.replace(/^\$2[ab]\$/, '$2y$');
      const [newUser] = await conn.execute(
        `INSERT INTO users (first_name, last_name, birthday, password, role, created_at)
         VALUES (?, ?, ?, ?, 'student', NOW())`,
        [safe(firstName), safe(lastName), safe(bday), hashedLrn]
      );
      userId = newUser.insertId;
    } else if (hasIdentityFields) {
      // 10b. Minor identity update — auto-accept (distance ≤ 2 / 1-digit bday diff)
      await conn.execute(
        `UPDATE users SET
           first_name = COALESCE(NULLIF(?, ''), first_name),
           last_name  = COALESCE(NULLIF(?, ''), last_name),
           birthday   = COALESCE(?, birthday),
           updated_at = NOW()
         WHERE id = ?`,
        [safe(firstName), safe(lastName), safe(bday), userId]
      );
    }

    // 10c. Upsert student row (sex, age, and all other student-table columns)
    //      Build the SET clause dynamically so only present fields are written.
    //      Non-identity student columns are ALWAYS directly overwritten — no conflict triggered.
    const studentUpdateFields = {};
    for (const col of STUDENT_COLUMNS) {
      if (rawInput.hasOwnProperty(col)) {
        studentUpdateFields[col] = safe(raw[col]);
      }
    }
    // sex and age are always included when present
    if (rawInput.hasOwnProperty('sex')) studentUpdateFields['sex'] = safe(raw.sex);
    if (rawInput.hasOwnProperty('age')) studentUpdateFields['age'] = safe(raw.age);

    // Always upsert with at minimum user_id, lrn, school_year
    const baseInsertCols  = ['user_id', 'lrn', 'school_year', 'updated_at'];
    const baseInsertVals  = [userId, lrn, schoolYear, new Date()];
    const extraCols       = Object.keys(studentUpdateFields);
    const extraVals       = Object.values(studentUpdateFields);

    const allCols = [...baseInsertCols, ...extraCols];
    const allVals = [...baseInsertVals, ...extraVals];

    const placeholders  = allVals.map(() => '?').join(', ');
    const colList       = allCols.join(', ');

    // ON DUPLICATE KEY: update every non-key column that arrived in this payload
    const updateClause = [...extraCols, 'updated_at']
      .map(c => `${c} = VALUES(${c})`)
      .join(', ');

    const [insertResult] = await conn.execute(
      `INSERT INTO students (${colList}) VALUES (${placeholders})
       ON DUPLICATE KEY UPDATE ${updateClause}`,
      allVals
    );
    console.log(`📋 [STUDENT INSERT] affectedRows=${insertResult.affectedRows} insertId=${insertResult.insertId} for LRN=${lrn} school_year=${schoolYear}`);

    // 10d. Fetch student PK for pre_enrollment versioning
    // Fetch by user_id as well as lrn+school_year — if the INSERT was a no-op
    // due to a duplicate key on a different school_year, fall back to user_id lookup.
    let [[stu]] = await conn.execute(
      `SELECT id FROM students WHERE lrn = ? AND school_year = ?`,
      [lrn, schoolYear]
    );
    if (!stu) {
      // Fallback: the row may exist under a different school_year for this user
      const [[stuFallback]] = await conn.execute(
        `SELECT id FROM students WHERE user_id = ? ORDER BY id DESC LIMIT 1`,
        [userId]
      );
      stu = stuFallback;
    }
    if (!stu) {
      throw new Error(`Student row not found after INSERT for LRN ${lrn} / school_year ${schoolYear}. Possible duplicate key conflict.`);
    }

    // 10e. Always insert a new pre_enrollment row (never overwrite history)
    const [[{ v }]] = await conn.execute(
      `SELECT COUNT(*) as v FROM pre_enrollments WHERE student_id = ?`,
      [stu.id]
    );

    await conn.execute(
      `INSERT INTO pre_enrollments (student_id, submission_version, responses, status, created_at)
       VALUES (?, ?, ?, 'Synced', NOW())`,
      [stu.id, v + 1, JSON.stringify(extraFieldsOnly)]
    );

    await conn.commit();
    await db.collection('responses').doc(docId).update({ isSynced: true });
    console.log(`✅ [SYNCED] LRN ${lrn} — version ${v + 1} committed.`);
    return 'success';

  } catch (err) {
    if (conn) await conn.rollback();
    console.error(`❌ [ERROR] DocID ${docId} | LRN ${lrn} | ${err.message}`);
    throw err;
  } finally {
    if (conn) conn.release();
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// LISTENER
// ─────────────────────────────────────────────────────────────────────────────

db.collection('responses')
  .where('isSynced', '==', false)
  .onSnapshot(async (snap) => {
    for (const change of snap.docChanges()) {
      if (change.type === 'added' || change.type === 'modified') {
        await processDocument(change.doc.id, change.doc.data()).catch(console.error);
      }
    }
  });