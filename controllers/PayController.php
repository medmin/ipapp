<?php
/**
 * User: Mr-mao
 * Date: 2017/9/7
 * Time: 22:58
 */
namespace app\controllers;

use app\models\Patents;
use app\models\UnpaidAnnualFee;
use yii\filters\AccessControl;
use Yii;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use EasyWeChat\Foundation\Application;
use EasyWeChat\Payment\Order;
use yii\web\NotFoundHttpException;

class PayController extends BaseController
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ]
            ]
        ];
    }

    /**
     * easywecaht 配置
     * @return array
     */
    protected function options()
    {
        return [
            'app_id' => Yii::$app->params['wechat']['id'],
            'secret' => Yii::$app->params['wechat']['secret'],
            'payment' => [
                'merchant_id' => Yii::$app->params['wechat']['mchid'],
                'key' => Yii::$app->params['wechat']['key'],
//                'cert_path' => Yii::$app->params['wechat']['cert_path'],
//                'key_path' => Yii::$app->params['wechat']['key_path'],
                'notify_url' => 'http://kf.shineip.com/pay/wxpay-notify',
            ]
        ];
    }

    /**
     * 发起支付
     *
     * @throws BadRequestHttpException
     */
    public function actionPayment()
    {
        $pay_type = Yii::$app->request->post('pay_type'); // 留坑

        if ($pay_type == 'WXPAY') {
            $this->wxPay(Yii::$app->request->post('id'));
        } else {
            throw new BadRequestHttpException('支付方式有误');
        }
    }

    private function wxPay($id)
    {
        // 暂时先一个支付
        $patent = Patents::findOne(['patentAjxxbID' => $id]);
        if ($patent == null) {
            throw new NotFoundHttpException('专利不存在');
        }
        $fee = UnpaidAnnualFee::findOne(['patentAjxxbID' => $id, 'due_date' => $patent->patentFeeDueDate]);
        if ($fee == null) {
            throw new BadRequestHttpException('该专利暂时没有待缴年费');
        }

        $wxApp = new Application($this->options());
        $payment = $wxApp->payment;
        // 创建订单
        $attributes = [
            'trade_type'       => 'JSAPI',
            'body'             => '阳光惠远 - 专利续费', // TODO 自定义名称
            'detail'           => '专利号：'.$patent->patentApplicationNo.PHP_EOL.'专利名称：'.$patent->patentTitle.PHP_EOL.'费用描述：'.$fee->fee_type,
            'out_trade_no'     => static::generateTradeNumber(),
            'total_fee'        => $fee->amount * 100, // 单位：分
            //'notify_url'       => '', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'openid'           => Yii::$app->user->identity->wxUser->fakeid, // trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识，
            // ...
        ];
        $order = new Order($attributes);
        $result = $payment->prepare($order);
        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS'){
            $prepayId = $result->prepay_id;
            $jsConfig = $payment->configForPayment($prepayId);
            $html = $this->renderPartial('/weui/_wxpay',['wx_json' => $jsConfig]);
            return Json::encode(['done' => true, 'data' => $html]);
        } else {
            echo '<pre>';
            print_r($result);
            echo '</pre>';
            exit;
        }
    }

    /**
     * 微信回调函数
     */
    public function actionWxpayNotify()
    {
        $wxApp = new Application($this->options());
        $payment = $wxApp->payment;
        $response = $payment->handleNotify(function ($notify, $successful) {
            if ($successful) {
                // TODO 成功之后生成一个日志
            }
        });
        $response->send();
    }

    /**
     * 生成订单号
     * @return string
     */
    private static function generateTradeNumber()
    {
        $mircotime = round(microtime(true)*1000);
        $str0 = date("ymdHis",substr($mircotime,0,10)).substr($mircotime,-3,2);
        $str1 = sprintf("%02d",mt_rand(1,99));
        $number = $str0.$str1;
        return $number;
    }
}