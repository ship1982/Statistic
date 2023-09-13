<?php

/** include common library */

use services\MainService;

/** get MainService specimen */
$service = new MainService();

/** load services libs */
require (__DIR__ . '/models/queries.php');
/** end load services libs */

/** execute service */
if(empty($_GET))
{
	$answer = $service->execute($params);
}
else
{

}