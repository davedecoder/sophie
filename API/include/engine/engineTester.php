<?php
require_once '../conf/Config.php';
require_once '../conf/DbConnect.php';
require_once '../libs/Utilities.php';
require_once '../Patient.php';
require_once 'Motor.php';
// opening db connection
$db = new DbConnect();
$conn = $db->connect();
/*
$engine =  new Motor($conn);

$result = $engine->getReporteSalida(1);

print_r($result);
*/
$cd = new Motor($conn);
$result = $cd->getAlergiesByClinicalData(10);
print_r($result);

?>