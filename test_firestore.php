<?php
require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Mocking minimal Laravel environment for standalone test
function base64UrlEncode($data) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
}

$keyFilePath = __DIR__.'/storage/app/serviceAccountKey.json';
if (!file_exists($keyFilePath)) {
    die("❌ Error: serviceAccountKey.json not found at $keyFilePath\n");
}

$keyFile = json_decode(file_get_contents($keyFilePath), true);
$projectId = $keyFile['project_id'];

echo "🚀 Testing Firestore Connection for Project: $projectId\n";

try {
    // 1. Generate JWT
    $now = time();
    $header = base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $payload = base64UrlEncode(json_encode([
        'iss' => $keyFile['client_email'],
        'scope' => 'https://www.googleapis.com/auth/datastore',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
    ]));

    $sigInput = "$header.$payload";
    openssl_sign($sigInput, $sig, $keyFile['private_key'], 'SHA256');
    $jwt = "$sigInput." . base64UrlEncode($sig);

    echo "🔑 JWT Generated. Requesting Access Token...\n";

    // 2. Get Access Token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt,
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $tokenData = json_decode($response, true);
    if ($status !== 200) {
        die("❌ Auth Failed (HTTP $status): " . $response . "\n");
    }

    $accessToken = $tokenData['access_token'];
    echo "✅ Access Token Obtained.\n";

    // 3. Test Firestore Read (List form_schemas)
    echo "📡 Testing Firestore Read (form_schemas)...\n";
    $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/form_schemas?pageSize=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status === 200) {
        echo "🔥 [SUCCESS] Connected to Firestore successfully!\n";
        echo "Response snippet: " . substr($response, 0, 100) . "...\n";
    } else {
        echo "❌ [FAILED] Firestore connection error (HTTP $status):\n";
        echo $response . "\n";
    }

} catch (Exception $e) {
    echo "❌ [EXCEPTION] " . $e->getMessage() . "\n";
}
