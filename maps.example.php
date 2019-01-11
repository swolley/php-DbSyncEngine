<?php
require_once __DIR__.'/libs/Entity.php';

$map = [
	new Entity(
		'from_tablei',
		'to_table',
		[
			'update' => 'last_update_field',
			'insert' => 'created_field'
		],
		[
			'from_id_field' => 'to_id_field',
			'from_x_field' => 'to_x_field'
		],
		[ 
			'from_id_field' => 'to_id_field',
		],
		[
			'custom where conditions'
		]
	)
];
