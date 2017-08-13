<?php
/**
 * User: Mr-mao
 * Date: 2017/8/13
 * Time: 14:08
 */


namespace app\modules\wechat\controllers;

use Yii;
use EasyWeChat\Foundation\Application;

class WechatController extends \yii\base\Controller
{
    public $options = [];

    public function init()
    {
        parent::init();
        $this->options = [
            'debug'  => YII_DEBUG,
            'app_id' => Yii::$app->params['wechat']['id'],
            'secret' => Yii::$app->params['wechat']['secret'],
            'token'  => Yii::$app->params['wechat']['token'],
            'aes_key' => Yii::$app->params['wechat']['aes_key'],
            'log' => [
                'level' => 'debug',
                'file'  => Yii::$app->params['wechat_log_path'], // XXX: 绝对路径！！！！
            ],
        ];
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
//        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;

        return $behaviors;
    }

    /**
     * 验证
     */
    public function actionValid()
    {
        $app =  new Application($this->options);
        $server = $app->server;

        // 测试发送
        $server->setMessageHandler(function ($message) {
           return '连接成功';
        });

        $response = $server->serve();
        $response->send();

    }

}