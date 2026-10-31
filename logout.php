<?php
require __DIR__ . '/includes/init.php';

$_SESSION = [];
session_regenerate_id(true);
header('Location: ' . url('index.php'));
exit;
