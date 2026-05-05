<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HardwareController extends Controller
{
    public function index()
    {
        $arduinoStatus = 'offline';
        try {
            $response = Http::timeout(2)->get('http://' . env('SERVICE_HOST', '127.0.0.1') . ':51234/status');
            if ($response->successful()) {
                $data = $response->json();
                $arduinoStatus = $data['arduino_connected'] ? 'online' : 'connected_no_arduino';
            }
        } catch (\Exception $e) {
            $arduinoStatus = 'offline';
        }

        return view('admin.hardware.index', compact('arduinoStatus'));
    }

    public function collect()
    {
        try {
            // b5 command for physical retrieval of papers
            $response = Http::timeout(5)->post('http://' . env('SERVICE_HOST', '127.0.0.1') . ':51234/api/strand/HARDWARE');
            
            if ($response->successful()) {
                return response()->json(['success' => true, 'message' => 'Collection command (b5) sent to Arduino.']);
            }
            
            return response()->json(['success' => false, 'message' => 'Failed to communicate with Hardware Controller.'], 500);
        } catch (\Exception $e) {
            Log::error("Hardware Collection Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Hardware Controller is offline.'], 500);
        }
    }
}
