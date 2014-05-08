<?php
return array(
	'_defaultModule' =>'Index',
	'_defaultAction' =>'show',
	'_urlMode' =>1,
	// 1 = pathInfo mode is preferered  , eg. www.domain.com/index.php/module/action/otherparam
	// 2 = parameters mode is for compatible  , eg. www.domain.com/index.php?m=module&a=action&otherparam	
	'_configDir' =>'config',
	'_controllerDir' =>'controllers',
	'_viewDir' =>'views',
	'_modelDir' =>'models',
	'_helperDir' =>'helpers',
	'_dataBase' => array(
			'_driverType' => 'Mysqli',
			'_rwType' => 'masterslave',
			'_master'=>array(
			'_host' => 'localhost',
			'_user' => 'root',
			'_passwd' => '',
			'_dbName' => 'test',
			),
			'_slave'=>array(
			'_host' => 'localhost',
			'_user' => 'root',
			'_passwd' => '',
			'_dbName' => 'test',
			),
		),
);
