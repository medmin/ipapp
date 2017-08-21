<?php
/**
 * User: Mr-mao
 * Date: 2017/8/13
 * Time: 14:08
 */


namespace app\modules\wechat\controllers;

use app\modules\wechat\models\DefaultMenu;
use app\modules\wechat\models\TemplateForm;
use EasyWeChat\Js\Js;
use Yii;
use EasyWeChat\Foundation\Application;
use yii\helpers\Json;

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
            ]
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

    public function actionProcessMessage()
    {

        $app =  new Application($this->options);
        $server = $app->server;

        $server->setMessageHandler(function ($message) {
            switch ($message->MsgType) {
                case 'event':
                    return $this->returnWelcomeMsg($message);
                    break;
                case 'text':
                    return $this->getText($message);
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

    public function getText($msg)
    {
        if ($msg->Content == 'ok') {
            return 'ok';
        }
        return '收到文本消息';
    }

    public function returnWelcomeMsg($message)
    {
        if ($message->Event == 'subscribe')
        {
            $msg = "您好，欢迎关注阳光惠远客服中心；" . PHP_EOL .
                "请前往kf.shineip.com注册一个新用户，或者绑定一个已经存在的用户；" . PHP_EOL .
                "如有其他疑问，请联系0451-88084686。";
            return $msg;
        }

        return '收到事件信息';
    }

    public function actionTest()
    {
        $model = new TemplateForm(['scenarios' => 'keywords_4']);
        if ($model->load(Yii::$app->request->post())) {
            $app = new Application($this->options);
            $notice = $app->notice;
            $userId = 'oSEZTs3PbPyyJPyHastxW8s3JcUo';
            $templateId = TemplateForm::CUSTOMER_ALERTS_NOTIFICATION;
            $url = 'http://kf.shineip.com';
            $data = [
                'first' => $model->first,
                'keyword1' => $model->keyword1,
                'keyword2' => $model->keyword2,
                'keyword3' => $model->keyword3,
                'keyword4' => $model->keyword4,
                'remark' => $model->remark,
            ];
            $result = $notice->to($userId)->uses($templateId)->andUrl($url)->data($data)->send();

            if ($result) {
                // messageID 是个对象
                // object(EasyWeChat\Support\Collection)#254 (1) { ["items":protected]=> array(3) { ["errcode"]=> int(0) ["errmsg"]=> string(2) "ok" ["msgid"]=> int(421397396) } }
                return $result;
            }
        }

        return $this->render('template', ['model' => $model]);

    }


    public function actionGetDefaultMenu()
    {
        $menu = new DefaultMenu();

        return $menu->getDefaultMenu();

    }


    public function actionSendTemplate()
    {
        $template = Yii::$app->request->post('template');
        switch ($template['name']) {
            case TemplateForm::CUSTOMER_ALERTS_NOTIFICATION:
                $model = new TemplateForm(['scenario' => 'keywords_4']);
                break;
            case TemplateForm::SCHEDULE:
                $model = new TemplateForm(['scenario' => 'keywords_2']);
                break;
            case TemplateForm::PROJECT_PROGRESS_NOTIFICATION:
                $model = new TemplateForm(['scenario' => 'keywords_2']);
                break;
            default:
                return Json::encode(['code' => -1, 'msg' => '模板类型错误']);
        }
        if ($model->load($template, '') && $model->validate()) {
            $data = $template;
            array_splice($data,0, 2);
            $app = new Application($this->options);
            $notice = $app->notice;
            $messageID = $notice->send([
                'touser' => $template['openid'],
                'template_id' => $template['name'],
                'url' => 'http://kf.shineip.com',
                'data' => $data,
            ]);
            if ($messageID) {
                // messageID 是个对象
                // object(EasyWeChat\Support\Collection)#254 (1) { ["items":protected]=> array(3) { ["errcode"]=> int(0) ["errmsg"]=> string(2) "ok" ["msgid"]=> int(421397396) } }
                return Json::encode(['code' => 0, 'msg' => '发送成功']);

            } else {
                return Json::encode(['code' => -1, 'msg' => '未知错误']);
            }
        } else {
            return Json::encode(['code' => 1, 'msg' => json_encode($model->errors)]);
        }
    }

}
