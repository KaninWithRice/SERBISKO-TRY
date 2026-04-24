<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$container = $app->make(Illuminate\Contracts\Console\Kernel::class);
$container->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$last='User'; $first='Admin'; $dob='1990-01-01'; $middle=''; $password='password';

$user = DB::table('users')->where('last_name',$last)
           ->where('first_name',$first)
           ->where('birthday',$dob)
           ->when(!empty($middle), function($q) use($middle){return $q->where('middle_name',$middle);}, function($q){return $q->where(function($q){$q->whereNull('middle_name')->orWhere('middle_name','');});})
           ->first();

var_dump($user);
if($user){
    echo 'password check: '.(Hash::check($password,$user->password) ? 'OK' : 'FAIL')."\n";
}
