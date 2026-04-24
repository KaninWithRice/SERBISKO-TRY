<?php
function fetch($url,&$cookies){
    $ch=curl_init($url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true, CURLOPT_HEADER=>true, CURLOPT_HTTPHEADER=>['User-Agent: PHP'], CURLOPT_COOKIEJAR=>"cookies.txt", CURLOPT_COOKIEFILE=>"cookies.txt"]);
    $res=curl_exec($ch);
    $info=curl_getinfo($ch);
    return [$res,$info];
}

$cookies=[];
list($page,$info)=fetch('http://127.0.0.1:8000/',$cookies);
if(preg_match('/name="_token" value="([^"]+)"/',$page,$m)){
    $token=$m[1];
    echo "token=$token\n";
} else { echo "no token\n"; }

$ch=curl_init('http://127.0.0.1:8000/login');
curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_POST=>true, CURLOPT_COOKIEJAR=>"cookies.txt", CURLOPT_COOKIEFILE=>"cookies.txt",
    CURLOPT_POSTFIELDS=>http_build_query(['_token'=>$token,'last_name'=>'User','given_name'=>'Admin','middle_name'=>'','dob'=>'1990-01-01','password'=>'password'])]);
$res=curl_exec($ch);
$info=curl_getinfo($ch);
echo "login HTTP: ".$info['http_code']." redirects: ".$info['redirect_count']."\n";
echo "effective URL: ".$info['url']."\n";
echo substr($res,0,200);
