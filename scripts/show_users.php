<?php
$db = new PDO('sqlite:database/database.sqlite');
foreach($db->query('SELECT id,first_name,last_name,birthday,role FROM users') as $row){
    echo implode(' | ',$row) . "\n";
}
