<?php
return array(
	'resources' => array(
		'db' => array(
			'adapter' => DB_ADAPTER,
			'params' => array(
				'host' => DB_HOST,
				'dbname' => DB_DATABASENAME,
				'username' => DB_USERNAME,
				'password' => DB_PASSOWRD,
				'charset' => 'utf8'
			)
		),
		
		'frontController' => array(
			'controllerDirectory' => APPLICATION_PATH . '/controllers',
			'params' => array(
				'displayExceptions' => 0
			)
		),
		
		'layout' => array()
	)
	,
	
	'phpSettings' => array(
		'date' => array(
			'timezone' => 'UTC'
		)
	),
	
	'bootstrap' => array(
		'path' => APPLICATION_PATH . '/Bootstrap.php',
		'class' => 'Bootstrap'
	),
	
	'appnamespace' => 'Application'
)
;
