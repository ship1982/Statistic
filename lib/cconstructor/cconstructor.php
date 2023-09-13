<?php

function cconstructor_getListofCondition()
{
	common_inc('_database');
	$o = select_db(
		1,
		'list_sequence_conditions', [
			'id', 'json_cond'
		], [
			'state' => 0
		]
	);

	if(!empty($o))
		return $o;
	else
		return [];
}