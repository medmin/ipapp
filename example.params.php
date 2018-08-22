<?php

$params = [
    // db
    'DBHOST' => '',
    'DBNAME' => '',
    'DBUSERNAME' => '',
    'DBPASSWORD' => '',

    // Email
    'EmailHost' => '',
    'EmailUsername' => '',
    'EmailPassword' => '',
    'EmailPort' => '',
    'EmailEncryption' => '',

    // EAC
    'DBEACHOST' => '',
    'DBEACPORT' => '',
    'DBEACNAME' => '',
    'DBEACUSERNAME' => '',
    'DBEACPASSWORD' => '',

    // wechat
    'wechat' => [
        'token' => '',
        'id' => '',
        'secret' => '',
        'aes_key' => '',
        'mchid' => '',
        'key' => '',
        'cert_path' => '',
        'key_path' => '',
    ],
    'wechat_log_path' => '',
    'wechat_open' => [
        'app_id' =>  '',
        'app_secret' => '',
    ],

    // site
    'company' => '哈尔滨市阳光惠远知识产权代理有限公司',
    'company_link' => 'http://www.shineip.com',

    // file_path
    'filePath' => '',

    // order expired time
    'order_expired_time' => 1800,

    'api_base_uri' => '',

    // 小程序 法务咨询
    'miniprogram_legal' => [
        'appid' => '',
        'url' => '',
        'pagepath' => ''
    ]
];

return $params;
