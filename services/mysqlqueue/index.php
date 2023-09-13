<?php

use services\MainService;

$service = new MainService();
/** load service file */
require (__DIR__ . '/models/queries.php');
/** end load service file */

$answer = $service->execute($params);