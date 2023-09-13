<?php

include_once(__DIR__ . '/../../../lib/common/common.php');

/**
 * Singleton by service.
 *
 * @param array $data - input data.
 */
function orderutm_get($data = [])
{
	$info = common_setValue($data, 'data');
	include_once __DIR__ . '/fetcher.php';
	orderutm_run($info);
}