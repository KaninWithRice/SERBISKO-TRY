<?php
$ch = curl_init('http://127.0.0.1:8000/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'last_name'=>'User',
        'given_name'=>'Admin',
        'middle_name'=>'',
        'dob'=>'1990-01-01',
        'password'=>'password',
        '_token'=>'',
    ]),
    CURLOPT_HEADER => true,
]);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
echo "HTTP code: {$info['http_code']}\n";
$headerSize = $info['header_size'];
$headers = substr($response,0,$headerSize);
$body = substr($response,$headerSize);
echo "---- HEADERS ----\n$headers\n";
echo "---- BODY ----\n".substr($body,0,500)."\n";
