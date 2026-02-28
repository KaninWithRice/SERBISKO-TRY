<?php
$s=socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($s && socket_bind($s, '127.0.0.1', 8002)) {
    echo 'ok';
    socket_close($s);
} else {
    echo 'fail '.socket_strerror(socket_last_error());
}
