<?php

$rootParams = require(__DIR__ . '/../params.php');
$db = require(__DIR__ . '/db.php');
$dbEAC = require (__DIR__ . '/dbEAC.php');

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'queue'],
    'controllerNamespace' => 'app\commands',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'exportInterval' => 1,
                    'levels' => ['info'],
                    'categories' => ['crawler'],
                    'logFile' => '@app/runtime/logs/crawler.log',
                    'logVars' => [],
                ],
            ],
        ],
        'db' => $db,
        'dbEAC' => $dbEAC,
        'authManager' => [
            'class' => 'yii\rbac\PhpManager',
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => '127.0.0.1',
            'port' => 6379,
            'database' => 1,
        ],
        'queue' => [
            'class' => yii\queue\redis\Queue::class,
            'redis' => 'redis', // connection ID
            'channel' => 'queue', // queue channel
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => false,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => $rootParams['EmailHost'],
                'username' => $rootParams['EmailUsername'],
                'password' => $rootParams['EmailPassword'],
                'port' => $rootParams['EmailPort'],
                'encryption' => $rootParams['EmailEncryption']
            ]
        ],
    ],
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
    'params' => $rootParams,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
