<?php

$rootParams = require( __DIR__.'./../params.php');

return [
'class' => 'yii\db\Connection',
'dsn' => 'mysql:host='.$rootParams['DBHOST'].';dbname='. $rootParams['DBNAME'],
'username' => $rootParams['DBUSERNAME'],
'password' => $rootParams['DBPASSWORD'],
'charset' => 'utf8mb4',
];
