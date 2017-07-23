<?php
/**
 * User: guiyumin, goes by Eric Gui
 * Date: 2017-07-23
 * Time: 15:14
 * Github: https://www.github.com/medmin/ipapp
 * Email: guiyumin@gmail.com
 */
$rootParams = require( __DIR__.'./../params.php');

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host='.$rootParams['DBEACHOST'].';dbname='. $rootParams['DBEACNAME'],
    'username' => $rootParams['DBEACUSERNAME'],
    'password' => $rootParams['DBEACPASSWORD'],
    'charset' => 'utf8',
];
