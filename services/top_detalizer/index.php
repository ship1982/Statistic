<?php

use services\MainService;

$service = new MainService();

/** load service file */
require (__DIR__ . '/models/queries.php');
/** end load service file */

//TODO:спросить зачем
$data = (empty($_GET) ? $params : $_GET);
//$data = $params;

$answer = $service->execute($data);