<?php

$rootParams = require(__DIR__ . '/../params.php');
$db = require(__DIR__ . '/db.php');
$dbEAC = require (__DIR__ . '/dbEAC.php');

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'queue'],
    'language' => 'zh-CN',
    'name' => '阳光惠远客服中心',
    'modules' => [
        'wechat' => [
            'class' => 'app\modules\wechat\Module',
        ]
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'mWFFPxJEjyP1_2Qlu_OSErmDA_8Oh5n_',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
//            'class' => 'yii\redis\Cache'
        ],
        'user' => [
            'identityClass' => 'app\models\Users',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
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
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'dbEAC' => $dbEAC,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'GET wechat/valid' => 'wechat/wechat/valid'
            ],
        ],
        'assetManager' => [
            'bundles' => [
                'dmstr\web\AdminLteAsset' => [
                    'skin' => 'skin-blue',
                ],
            ],
        ],
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
    ],
    'params' => $rootParams,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
