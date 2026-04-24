<?php
$db = new PDO('sqlite:database/database.sqlite');
foreach($db->query('SELECT first_name,last_name,birthday,middle_name,password,role FROM users') as $row) {
    echo implode(' | ', $row) . "\n";
}
