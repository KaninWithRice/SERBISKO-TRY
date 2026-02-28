<?php

namespace App\Http\Controllers;

use Google\Client; 
use Google\Service\Sheets; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    // The constructor has been removed! Security is now handled in web.php

    // This function handles the /dashboard route
    public function index(){ return view('admin.dashboard');}
    
    public function systemsync()
    {
        $history = DB::table('sync_histories')->orderBy('created_at', 'desc')->get();
        
        $lastSync = DB::table('sync_histories')
            ->where('status', 'Success') 
            ->latest()
            ->first();

        // --- HEARTBEAT CHECK ---
        $isConnected = false;
        try {
            $client = new \Google\Client();
            $client->setAuthConfig(storage_path('app/google-credentials.json'));
            $client->addScope(\Google\Service\Sheets::SPREADSHEETS_READONLY);
            
            // If this doesn't throw an error, we are "Active"
            $service = new \Google\Service\Sheets($client);
            $spreadsheetId = '1pUdqUbAMQEZ4Kg2V6A05orHY9xnDCJLp2QWLQaXXmSk';
            $service->spreadsheets->get($spreadsheetId);
            $isConnected = true;
        } catch (\Exception $e) {
            $isConnected = false;
        }

        $formUrl = "https://forms.gle/7wrtrGWf2nDCWcz9A";

        return view('admin.systemsync', [
            'syncHistory' => $history,
            'lastSync' => $lastSync,
            'formUrl' => $formUrl,
            'isConnected' => $isConnected // Pass this to your blade
        ]);
    }
    
    public function students(){ return view('admin.students'); }
    public function verification(){ return view('admin.verification'); }
    public function requirementhub(){ return view('admin.requirementhub');}
    public function accountsettings(){ return view('admin.accountsettings'); }

    public function performSync() {
        set_time_limit(120);
        $spreadsheetId = '1pUdqUbAMQEZ4Kg2V6A05orHY9xnDCJLp2QWLQaXXmSk'; 
        $range = 'Form_Responses2!A2:BC'; 

        try {
            $client = new \Google\Client(); 
            $client->setAuthConfig(storage_path('app/google-credentials.json'));
            $client->addScope(\Google\Service\Sheets::SPREADSHEETS_READONLY);

            $service = new \Google\Service\Sheets($client);
            
            // This is where the data actually gets fetched
            $response = $service->spreadsheets_values->get($spreadsheetId, $range);
            $values = $response->getValues();

            if (empty($values)) {
                return back()->with('info', 'The Google Sheet is currently empty.');
            }

            $newCount = 0;
            foreach ($values as $row) { 
                // Ensures we don't crash if a row is shorter than expected
                $row = array_pad($row, 55, null);
                
                // 2. SKIP if every single box in the row is empty
                if (empty(array_filter($row))) {
                    continue; 
                }

                // 3. SKIP if required fields (NOT NULL in your database) are empty
                // Index 2 = Last Name, Index 3 = First Name, Index 6 = Birthday
                if (empty(trim($row[2])) || empty(trim($row[3])) || empty(trim($row[6]))) {
                    continue; 
                }

                try {
                    // Birthday is Column G (Index 6)
                    $formattedDob = Carbon::parse($row[6])->format('Y-m-d');
                } catch (\Exception $e) {
                    continue; // Skip row if date is unreadable
                }

                // Existence check to prevent duplicates
                $exists = DB::table('users')
                    ->where('last_name', trim($row[2]))
                    ->where('first_name', trim($row[3]))
                    ->where('birthday', $formattedDob)
                    ->exists();

                if (!$exists) {
                    DB::transaction(function () use ($row, $formattedDob, &$newCount) {
                        $userId = DB::table('users')->insertGetId([
                            'last_name'   => $row[2], 
                            'first_name'  => $row[3], 
                            'middle_name' => $row[4], 
                            'birthday'    => $formattedDob,
                            'role'        => 'student',
                            'password'    => bcrypt('student123'),
                            'created_at'  => now(),
                        ]);

                        DB::table('students')->insert([
                            'user_id'              => $userId,
                            'lrn'                  => $row[1] ?? null,
                            'extension_name'       => $row[5] ?? null,
                            'sex'                  => $row[9] ?? null,
                            'age' => is_numeric($row[8]) ? (int)$row[8] : null,
                            'place_of_birth'       => $row[7] ?? null,
                            'mother_tongue'        => $row[10] ?? null,
                            
                            'curr_house_number'    => $row[11] ?? null,
                            'curr_street'          => $row[12] ?? null,
                            'curr_barangay'        => $row[13] ?? null,
                            'curr_city'            => $row[14] ?? null,
                            'curr_province'        => $row[15] ?? null,
                            'curr_zip_code'        => $row[17] ?? null,

                            'is_perm_same_as_curr' => (isset($row[19]) && $row[19] == 'Yes') ? 1 : 0,
                            'perm_house_number'    => $row[20] ?? null,
                            'perm_street'          => $row[21] ?? null,
                            'perm_barangay'        => $row[22] ?? null,
                            'perm_city'            => $row[23] ?? null,
                            'perm_province'        => $row[24] ?? null,
                            'perm_zip_code'        => $row[26] ?? null,

                            'father_last_name'     => $row[29] ?? null,
                            'father_first_name'    => $row[30] ?? null,
                            'father_middle_name'   => $row[31] ?? null,
                            'father_contact_number'=> $row[32] ?? null,

                            'mother_last_name'     => $row[33] ?? null,
                            'mother_first_name'    => $row[34] ?? null,
                            'mother_middle_name'   => $row[35] ?? null,
                            'mother_contact_number'=> $row[36] ?? null,

                            'guardian_last_name'   => $row[37] ?? null,
                            'guardian_first_name'  => $row[38] ?? null,
                            'guardian_middle_name' => $row[39] ?? null,
                            'guardian_contact_number' => $row[40] ?? null,
                            
                            'created_at'           => now(),
                            'updated_at'           => now(),
                        ]);
                        
                        $newCount++;
                    });
                }
            }

            // Record history
            DB::table('sync_histories')->insert([
                'records_synced' => $newCount,
                'status' => 'Success',
                'created_at' => now()
            ]);

            return back()->with('success', "Sync Complete! Added $newCount new students.");

        } catch (\Exception $e) {
            Log::error("Sync Error: " . $e->getMessage());
            return back()->with('error', 'Sync Failed: ' . $e->getMessage());
        }
    }

    // This function handles the /admin/sync route (Your mockup page)
    public function showSyncPage()
    {
        // We fetch the history so your table isn't empty
        $history = DB::table('sync_histories')->orderBy('id', 'desc')->get();

        return view('admin.sync', ['syncHistory' => $history]);
    }
}