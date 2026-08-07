<?php
require_once __DIR__ . '/includes/pesapal_functions.php';
$conn = require_once __DIR__ . '/db/connection.php';
var_dump(getPesapalIpnId($conn));
