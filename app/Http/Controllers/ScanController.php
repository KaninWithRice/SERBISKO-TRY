<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ScanController extends Controller
{
    public function processDocument(Request $request)
    {
        try {
            // 🚨 Prevents PHP from killing the process during long AI/LIS scans
            set_time_limit(0); 
            
            $imageData = $request->input('image_data');
            $docType = $request->input('document_type', 'Report Card');
            $userId = session('user_id', 1);

            if (!$imageData) {
                return response()->json(['status' => 'error', 'message' => 'Image data is empty.']);
            }

            if (strpos($imageData, ';base64,') === false) {
                return response()->json(['status' => 'error', 'message' => 'Image data is corrupted.']);
            }

            // Decode and Save Image
            $imageParts = explode(";base64,", $imageData);
            $imageTypeAux = explode("image/", $imageParts[0]);
            $imageType = $imageTypeAux[1] ?? 'jpeg';
            $imageBase64 = base64_decode($imageParts[1]);
            
            $fileName = 'scan_' . $userId . '_' . time() . '.' . $imageType;
            $filePath = 'scans/' . $fileName;

            Storage::disk('public')->put($filePath, $imageBase64);
            $imageFullPath = storage_path('app/public/' . $filePath);

            // Log pending scan in Database
            $scanId = DB::table('scans')->insertGetId([
                'user_id'       => $userId,
                'document_type' => $docType,
                'file_path'     => $filePath,
                'status'        => 'pending',
                'created_at'    => now(),
                'updated_at'    => now()
            ]);

            // --- 1. Dynamic Document Classification ---
            $lowerDoc = strtolower($docType);
            if (str_contains($lowerDoc, 'report') || str_contains($lowerDoc, 'sf9')) {
                $pythonDocType = 'report_card';
            } elseif (str_contains($lowerDoc, 'birth') || str_contains($lowerDoc, 'psa')) {
                $pythonDocType = 'birth_certificate';
            } elseif (str_contains($lowerDoc, 'enrollment') || str_contains($lowerDoc, 'form')) {
                $pythonDocType = 'enrollment_form';
            } elseif (str_contains($lowerDoc, 'als') || str_contains($lowerDoc, 'alternative')) {
                $pythonDocType = 'als_certificate';
            } elseif (str_contains($lowerDoc, 'affidavit') || str_contains($lowerDoc, 'sworn')) {
                $pythonDocType = 'affidavit';
            } elseif (str_contains($lowerDoc, 'moral')) {
                $pythonDocType = 'good_moral';
            } elseif (str_contains($lowerDoc, '137') || str_contains($lowerDoc, 'sf10')) {
                $pythonDocType = 'form_137';
            } else {
                $pythonDocType = 'generic_name_check'; 
            }

            // --- 2. Fetch Logged-In User's Name from Database SAFELY ---
            $user = DB::table('users')->where('id', $userId)->first();
            
            // 🚨 THIS PREVENTS THE CRASH IF THE USER SESSION IS LOST 🚨
            if ($user) {
                $expectedFirstName = $user->first_name ?? $user->firstname ?? 'Unknown';
                $expectedLastName = $user->last_name ?? $user->lastname ?? 'Unknown';
            } else {
                $expectedFirstName = 'Unknown';
                $expectedLastName = 'Unknown';
            }

            // ==========================================
            // SEND TO OCR ENGINE (Port 9001)
            // ==========================================
            try {
                // 60-second timeout to allow the 360-degree Python AI to run
                $ocrResponse = Http::timeout(180)
                    ->attach('image', file_get_contents($imageFullPath), $fileName)
                    ->post('http://127.0.0.1:9001/ocr', [
                        'doc_type'   => $pythonDocType,
                        'scan_id'    => $scanId,
                        'first_name' => $expectedFirstName,
                        'last_name'  => $expectedLastName
                    ]);

                if ($ocrResponse->failed()) {
                    DB::table('scans')->where('id', $scanId)->update(['status' => 'failed']);
                    return response()->json(['status' => 'error', 'message' => 'OCR Server Error']);
                }

                $ocrResult = $ocrResponse->json();
                
                if (isset($ocrResult['success']) && $ocrResult['success'] === false) {
                     DB::table('scans')->where('id', $scanId)->update([
                         'status' => 'failed',
                         'remarks' => $ocrResult['error'] ?? 'Document Rejected.'
                     ]);
                     return response()->json(['status' => 'error', 'message' => $ocrResult['error'] ?? 'Invalid Type']);
                }

                // ==========================================
                // OCR SUCCESS -> ROUTE TO LIS OR INSTANT VERIFY
                // ==========================================
                if (isset($ocrResult['success']) && $ocrResult['success'] === true) {
                    
                    // Route A: Report Cards strictly require DepEd LIS Verification
                    if ($pythonDocType === 'report_card') {
                        $lrn = $ocrResult['lrn'] ?? null;
                        
                        if ($lrn) {
                            // 1. LRN Found! Update DB (Status remains 'pending' while LIS checks it)
                            DB::table('scans')->where('id', $scanId)->update([
                                'lrn' => $lrn,
                                'remarks' => 'Sending to DepEd LIS...'
                            ]);

                            $enrollingGrade = session('grade_level', '11'); 
                            $expectedGrade = ($enrollingGrade == '12') ? 'Grade 11' : 'Grade 10';

                            Log::info("🎯 OCR Success! Extracted LRN: " . $lrn);
                            Log::info("🚀 Sending LRN to LIS Verifier on Port 5001..."); // webhook will use current APP_URL

                            try {
                                // Send to the Selenium Bot
                                $lisResponse = Http::timeout(10)->post('http://127.0.0.1:5001/verify', [
                                    'lrn' => $lrn,
                                    'expected_grade' => $expectedGrade,
                                    'webhook_url' => url('/api/lis-callback'), 
                                    'scan_id' => $scanId
                                ]);
                                Log::info("✅ LIS Server accepted the request: " . $lisResponse->body());
                                
                            } catch (\Exception $e) {
                                Log::error("❌ LIS Verifier Offline or Error: " . $e->getMessage());
                                // Bounce the user back safely if the LIS bot is turned off
                                DB::table('scans')->where('id', $scanId)->update([
                                    'status' => 'failed',
                                    'remarks' => 'LIS Verifier is offline. Please call an administrator.'
                                ]);
                            }
                        } else {
                            // It is a report card, but the LRN was missing or unreadable
                            DB::table('scans')->where('id', $scanId)->update([
                                'status' => 'failed',
                                'remarks' => 'Report Card verified, but no 12-digit LRN was found. Please rescan.'
                            ]);
                        }
                    } 
                    // Route B: All other documents are instantly verified
                    else {
                        DB::table('scans')->where('id', $scanId)->update([
                            'status'  => 'verified', 
                            'remarks' => $ocrResult['message'] ?? 'Document Verified'
                        ]);
                    }
                }

            } catch (\Exception $e) {
                Log::error("Python OCR Error: " . $e->getMessage());
                DB::table('scans')->where('id', $scanId)->update(['status' => 'failed']);
                return response()->json(['status' => 'error', 'message' => 'AI Engine Offline']);
            }

            return response()->json([
                'status' => 'success',
                'redirect' => '/student/verifying'
            ]);

        } catch (\Exception $e) {
            Log::error("System Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'System Error: ' . $e->getMessage()]);
        }
    }

    // ==========================================
    // WEBHOOK LISTENER FOR LIS COMPLETION
    // ==========================================
    public function lisCallback(Request $request)
    {
        $scanId = $request->input('scan_id');
        $status = $request->input('result'); 
        
        if ($scanId && $status) {
            DB::table('scans')->where('id', $scanId)->update([
                'status'     => $status, 
                'updated_at' => now()
            ]);
            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false, 'message' => 'Missing scan_id or result data'], 400);
    }
    
    // ==========================================
    // FRONTEND STATUS CHECKER & TRAFFIC CONTROLLER
    // ==========================================
    public function checkScanStatus()
    {
        $userId = session('user_id', 1);
        $studentStatus = session('student_status', 'Regular'); 
        $currentDoc = session('current_doc', 'Report Card (SF9)');

        // Document requirements dynamically mapped to student type
        $tracks = [
            'Regular'    => ['Report Card (SF9)', 'Birth Certificate', 'Enrollment Form'],
            'ALS'        => ['ALS Certificate', 'Enrollment Form', 'Birth Certificate', 'Affidavit'],
            'Transferee' => ['Report Card (SF9)', 'Birth Certificate', 'Affidavit', 'Enrollment Form'],
            'Balik-Aral' => ['Report Card (SF9)', 'Birth Certificate', 'Affidavit', 'Enrollment Form'],
        ];

        $docList = $tracks[$studentStatus] ?? $tracks['Regular'];
        $currentIndex = array_search($currentDoc, $docList);
        
        $nextUrl = '/student/thankyou'; 

        if ($currentIndex !== false && isset($docList[$currentIndex + 1])) {
            $nextDoc = $docList[$currentIndex + 1];
            $nextUrl = '/student/capture-document?doc=' . urlencode($nextDoc);
        }

        $latestScan = DB::table('scans')
            ->where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->first();

        if (!$latestScan) {
            return response()->json(['status' => 'pending']);
        }

        return response()->json([
            'status' => $latestScan->status,
            'remarks' => $latestScan->remarks,
            'next_url' => $nextUrl,
            'current_doc' => $currentDoc
        ]);
    }
}