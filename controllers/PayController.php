<?php
/**
 * User: Mr-mao
 * Date: 2017/9/7
 * Time: 22:58
 */
namespace app\controllers;

use app\models\Orders;
use app\models\Patents;
use app\models\UnpaidAnnualFee;
use yii\base\Exception;
use yii\filters\AccessControl;
use Yii;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use EasyWeChat\Foundation\Application;
use EasyWeChat\Payment\Order;
use yii\web\NotFoundHttpException;
use yii\helpers\Url;
use Endroid\QrCode\QrCode;
use yii\web\ServerErrorHttpException;
use yii\db\Transaction;

class PayController extends BaseController
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'except' => ['wxpay-notify', 'wxpay-notify-qrcode'],
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
                'cert_path' => Yii::$app->params['wechat']['cert_path'],
                'key_path' => Yii::$app->params['wechat']['key_path'],
                'notify_url' => 'https://kf.shineip.com/pay/wxpay-notify/',
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
            return $this->wxPay(Yii::$app->request->post('id'));
        } else {
            throw new BadRequestHttpException('支付方式有误');
        }
    }

    /**
     * 微信公众号支付
     *
     * @param $id
     * @return string
     * @throws BadRequestHttpException
     * @throws Exception
     * @throws NotFoundHttpException
     */
    private function wxPay($id)
    {
        // 暂时先一个支付
        $patent = Patents::findOne(['patentAjxxbID' => $id]);
        if ($patent == null) {
            throw new NotFoundHttpException('专利不存在');
        }
        $fee = $patent->generateExpiredItems(90,false); // 天数跟前端展示的查询天数一样,这儿只查未支付的
        if ($fee == null) {
            throw new BadRequestHttpException('该专利暂时没有待缴年费');
        }

        $wxApp = new Application($this->options());
        $payment = $wxApp->payment;
        // 创建订单
        $attributes = [
            'trade_type'       => 'JSAPI',
            'body'             => '阳光惠远 - 专利续费', // TODO 自定义名称
            'detail'           => '专利号：'.$patent->patentApplicationNo.PHP_EOL.'专利名称：'.$patent->patentTitle.PHP_EOL.'费用描述：'.implode(',',array_column($fee,'fee_type')),
            'out_trade_no'     => static::generateTradeNumber(),
            'total_fee'        => array_sum(array_column($fee,'amount')) * 100, // 单位：分
            'notify_url'       => Yii::$app->request->getHostInfo() . Url::to(['/pay/wxpay-notify']), // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'openid'           => Yii::$app->user->identity->wxUser->fakeid, // trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识，
        ];
        $order = new Order($attributes);
        $result = $payment->prepare($order);

        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS'){
            // 生成订单
            $isolationLevel = Transaction::SERIALIZABLE;
            $transaction = Yii::$app->db->beginTransaction($isolationLevel);
            try {
                $system_order = new Orders();
                $system_order->trade_no = $attributes['out_trade_no'];
                $system_order->payment_type = Orders::TYPE_WXPAY;
                $system_order->user_id = Yii::$app->user->id;
                $system_order->goods_id = json_encode([$id]); // json_encode
                $system_order->goods_type = Orders::USE_PATENT;
                $system_order->amount = $attributes['total_fee'] / 100;
                $system_order->created_at = time();
                $system_order->updated_at = time();
                $system_order->status = Orders::STATUS_PENDING;
                if (!$system_order->save()) {
                    throw new Exception('系统内部订单出错');
                }
                $transaction->commit();
            } catch (Exception $e) {
                $transaction->rollBack();
                throw $e;
            }

            $prepayId = $result->prepay_id;
            $jsConfig = $payment->configForPayment($prepayId);
            $html = $this->renderPartial('/weui/_wxpay',['wx_json' => $jsConfig]);
	        return Json::encode(['done' => true, 'data' => $html]);
        } else {
            Yii::info($result);
            print_r($result);
            exit;
        }
    }

    /**
     * 微信支付二维码生成
     *
     * @param $id
     * @return string
     * @throws BadRequestHttpException
     * @throws Exception
     * @throws NotFoundHttpException
     */
    public function actionWxQrcode($id)
    {
        $patent = Patents::findOne(['patentAjxxbID' => $id]);
        if ($patent == null) {
            throw new NotFoundHttpException('专利不存在');
        }
        $fee = $patent->generateExpiredItems(90,false); // 天数跟前端展示的查询天数一样,这儿只查未支付的
        if ($fee == null) {
            throw new BadRequestHttpException('该专利暂时没有待缴年费');
        }

        $wxApp = new Application($this->options());
        $payment = $wxApp->payment;
        $notifyUrl = Yii::$app->request->getHostInfo() . Url::to(['/pay/wxpay-notify-qrcode']);
        $attributes = [
            'trade_type'       => 'NATIVE',
            'body'             => '阳光惠远 - 专利续费', // TODO 自定义名称
            'detail'           => '专利号：'.$patent->patentApplicationNo.PHP_EOL.'专利名称：'.$patent->patentTitle.PHP_EOL.'费用描述：'.implode(',',array_column($fee,'fee_type')),
            'out_trade_no'     => static::generateTradeNumber(),
            'total_fee'        => array_sum(array_column($fee,'amount')) * 100, // 单位：分
            'notify_url'       => $notifyUrl, // 支付结果通知网址，如果不设置则会使用配置里的默认地址
        ];
        $o = new Order($attributes);
        $result = $payment->prepare($o);

        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS'){
            // 生成订单
            $isolationLevel = Transaction::SERIALIZABLE;
            $transaction = Yii::$app->db->beginTransaction($isolationLevel);
            try {
                $system_order = new Orders();
                $system_order->trade_no = $attributes['out_trade_no'];
                $system_order->payment_type = Orders::TYPE_WXPAY;
                $system_order->user_id = Yii::$app->user->id;
                $system_order->goods_id = json_encode([$id]); // json_encode
                $system_order->goods_type = Orders::USE_PATENT;
                $system_order->amount = $attributes['total_fee'] / 100;
                $system_order->created_at = time();
                $system_order->updated_at = time();
                $system_order->status = Orders::STATUS_PENDING;
                if (!$system_order->save()) {
                    throw new Exception('系统内部订单出错');
                }
                $transaction->commit();
            } catch (Exception $e) {
                $transaction->rollBack();
                throw $e;
            }

            $qrCode = new QrCode($result->code_url);
            $qrCode->setSize(200);
            header('Content-Type: '.$qrCode->getContentType());
            return $qrCode->writeString();
        } else {
            Yii::info($result);
            print_r($result);
            exit;
        }
    }

    /**
     * 微信JS支付回调函数
     */
    public function actionWxpayNotify()
    {
        $wxApp = new Application($this->options());
        $payment = $wxApp->payment;
        $response = $payment->handleNotify(function ($notify, $successful) {
            if ($successful) {
                $this->paySuccess($notify);
            } else {
                $this->payFail($notify);
            }
        });
        $response->send();
    }

    /**
     * 微信二维码支付回调函数
     */
    public function actionWxpayNotifyQrcode()
    {
        $wxApp = new Application($this->options());
        $payment = $wxApp->payment;
        $response = $payment->handleNotify(function ($notify, $successful) {
            if ($successful) {
                Yii::info('SUCCESS');
                $this->paySuccess($notify);
            } else {
                Yii::info('FAIL');
                $this->payFail($notify);
            }
        });
        $response->send();
    }

    /**
     * 支付成功之后
     *
     * @param $notify
     * @throws Exception
     */
    private function paySuccess($notify)
    {
        $isolationLevel = Transaction::SERIALIZABLE;
        $transaction = Yii::$app->db->beginTransaction($isolationLevel);
        try {
            $system_order = Orders::findOne(['trade_no' => $notify->out_trade_no]);
//            if ($system_order->created_at + Yii::$app->params['order_expired_time'] < time()) {
//                throw new Exception('订单已过期');
//            }
//            if ($system_order->status == Orders::STATUS_PAID || $system_order->status == Orders::STATUS_FINISHED) {
//                throw new Exception('请勿重复支付');
//            }
            $system_order->out_trade_no = $notify->transaction_id;
            // $system_order->amount = $notify->total_fee; //TODO 有没有必要重新记录实际付款金额
            $system_order->updated_at = time();
            $system_order->status = Orders::STATUS_PAID;
            if (!$system_order->save()) {
                throw new ServerErrorHttpException('系统内部订单更新出错');
            }
            if (!$system_order->successProcess()) {
                throw new ServerErrorHttpException('专利状态更新出错');
            }
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * 支付失败之后
     *
     * @param $notify
     * @throws Exception
     */
    private function payFail($notify)
    {
        $isolationLevel = Transaction::SERIALIZABLE;
        $transaction = Yii::$app->db->beginTransaction($isolationLevel);
        try {
            $system_order = Orders::findOne(['trade_no' => $notify->out_trade_no]);
//            if ($system_order->created_at + Yii::$app->params['order_expired_time'] > time()) {
//                throw new Exception('订单已过期');
//            }
            $system_order->out_trade_no = $notify->transaction_id;
            $system_order->updated_at = time();
            $system_order->status = Orders::STATUS_UNPAID;
            if (!$system_order->save()) {
                throw new ServerErrorHttpException('系统内部订单更新出错');
            }
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * 支付宝二维码生成(暂不可用)
     *
     * @param $id
     * @return string
     */
    public function actionAliQrcode($id)
    {
        $qrCode = new QrCode("https://kf.shineip.com");
        $qrCode->setSize(200);
        header('Content-Type: '.$qrCode->getContentType());
        return $qrCode->writeString();
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
