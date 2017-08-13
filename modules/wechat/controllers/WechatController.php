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

        $response = $server->serve();
        $response->send();

    }

    public function actionEchomsg()
    {
        $app =  new Application($this->options);
        $server = $app->server;

        $server->setMessageHandler(function ($message) {
            switch ($message->MsgType) {
                case 'event':
                    return '收到事件消息';
                    break;
                case 'text':
                    return '收到文字消息';
                    break;
                case 'image':
                    return '收到图片消息';
                    break;
                case 'voice':
                    return '收到语音消息';
                    break;
                case 'video':
                    return '收到视频消息';
                    break;
                case 'location':
                    return '收到坐标消息';
                    break;
                case 'link':
                    return '收到链接消息';
                    break;
                // ... 其它消息
                default:
                    return '收到其它消息';
                    break;
            }
        });

        $response = $server->serve();
        $response->send();
    }
}
