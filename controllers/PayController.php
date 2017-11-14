<?php
/**
 * User: Mr-mao
 * Date: 2017/9/7
 * Time: 22:58
 */
namespace app\controllers;

use app\models\Orders;
use app\models\Patents;
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
                'except' => ['wxpay-notify'],
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
     * 生成系统订单
     *
     * @param $application_no
     * @return string
     */
    public function actionSubmit($application_no)
    {
        try {
            $annual_fee_ids = Yii::$app->request->post('ids');
            if (empty($annual_fee_ids)) {
                throw new Exception('请至少选择一个缴费项');
            }
            // 检验费用正确性
            $client = new \GuzzleHttp\Client(['base_uri' => Yii::$app->params['api_base_uri']]);
            $response = $client->request('GET', "/patents/{$application_no}/unpaid-fees");
            $fee_info = json_decode($response->getBody(), true);
            $detail = array_filter($fee_info, function($value) use ($annual_fee_ids) {
                return in_array($value['id'], $annual_fee_ids);
            });
            if (count($detail) !== count($annual_fee_ids)) {
                throw new Exception('缴费项不存在');
            }
            $model = new Orders();
            $model->trade_no = static::generateTradeNumber();
            $model->payment_type = 0; // 0 未支付
            $model->user_id = Yii::$app->user->id;
            $model->goods_id = $application_no;
            $model->detailed_expenses = json_encode($detail,JSON_FORCE_OBJECT);
            $model->amount = array_sum(array_column($detail, 'amount'));
            if ($model->save() == false) {
                throw new Exception('系统订单创建失败');
            }
            $data = $this->isMicroMessage ? $model->trade_no : Url::to(['/pay/wx-qrcode', 'id' => $model->trade_no]);
            return Json::encode(['done' => true, 'data' => $data]);
        } catch (Exception $e) {
            return Json::encode(['done' => false, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 微信浏览器内支付
     *
     * @param $id
     * @return string
     */
    public function actionWxPay($id)
    {
        return $this->wxPay($id);
    }

    /**
     * 微信公众号支付
     *
     * @param $id
     * @return string
     * @throws BadRequestHttpException
     * @throws Exception
     */
    private function wxPay($id)
    {
        $system_order = Orders::findOne($id);
        if (!$system_order) {
            throw new BadRequestHttpException('未找到该订单');
        }

        $wxApp = new Application($this->options());
        $payment = $wxApp->payment;
        // 创建订单
        $attributes = [
            'trade_type'       => 'JSAPI',
            'body'             => '阳光惠远 - 专利续费', // TODO 自定义名称
            'detail'           => '专利号：'. $system_order->goods_id,
            'out_trade_no'     => $system_order->trade_no,
            'total_fee'        => $system_order->amount * 100, // 单位：分
            'notify_url'       => Yii::$app->request->getHostInfo() . Url::to(['/pay/wxpay-notify']), // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'openid'           => Yii::$app->user->identity->wxUser->fakeid, // trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识，
        ];
        $order = new Order($attributes);
        $result = $payment->prepare($order);

        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS'){
            $prepayId = $result->prepay_id;
            $jsConfig = $payment->configForPayment($prepayId);
            $html = $this->renderPartial('/weui/_wxpay',['wx_json' => $jsConfig]);
	        return Json::encode(['done' => true, 'data' => $html]);
        } else {
            Yii::error($result->return_msg . $result->err_code_des);
            echo $result->return_msg . $result->err_code_des;
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
        $this->layout = false;
        $redis = Yii::$app->redis;
        // 先查缓存,如果存在该二维码链接直接返回
        if ($r_url = $redis->get('qrcode:'.$id)) {
            return $this->render('/common/qrcode', ['url' => $r_url, 'id' => $id]);
        }

        $system_order = Orders::findOne($id);
        if (!$system_order) {
            throw new BadRequestHttpException('订单查询失败');
        }

        $wxApp = new Application($this->options());
        $payment = $wxApp->payment;
        $notifyUrl = Yii::$app->request->getHostInfo() . Url::to(['/pay/wxpay-notify']);
        $attributes = [
            'trade_type'       => 'NATIVE',
            'body'             => '阳光惠远 - 专利续费',
            'detail'           => '专利号：' . $system_order->goods_id,
            'out_trade_no'     => $system_order->trade_no,
            'total_fee'        => $system_order->amount * 100, // 单位：分
            'notify_url'       => $notifyUrl, // 支付结果通知网址，如果不设置则会使用配置里的默认地址
        ];
        $o = new Order($attributes);
        $result = $payment->prepare($o);

        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS'){
            // 将支付链接写入缓存(过期时间为零点)
            $ex = strtotime(date('Y-m-d 23:59:59'))-time();
            $redis->setex('qrcode:'.$id, $ex, $result->code_url);

            return $this->render('/common/qrcode', ['url' => $result->code_url, 'id' => $system_order->trade_no]);
        } else {
            Yii::error($result->return_msg . $result->err_code_des);
            echo $result->return_msg . $result->err_code_des;
            exit;
        }
    }

    /**
     * 微信支付回调函数
     */
    public function actionWxpayNotify()
    {
        $wxApp = new Application($this->options());
        $payment = $wxApp->payment;
        $response = $payment->handleNotify(function ($notify, $successful) {
            $system_order = Orders::findOne($notify->out_trade_no);
            if ($system_order == false) {
                return 'Order not exist.';
            }
            if ($system_order->paid_at > 0) {
                return true;
            }
            if ($successful) {
                $this->paySuccess($notify, $system_order);
            } else {
                $this->payFail($notify, $system_order);
            }
            return true;
        });
        $response->send();
    }

    /**
     * 支付成功之后
     *
     * @param $notify
     * @param $system_order Orders
     * @throws Exception
     */
    private function paySuccess($notify, $system_order)
    {
        $isolationLevel = Transaction::SERIALIZABLE;
        $transaction = Yii::$app->db->beginTransaction($isolationLevel);
        try {
            $system_order->out_trade_no = $notify->transaction_id;
            $system_order->payment_type = Orders::TYPE_WXPAY;
            $system_order->user_id = Yii::$app->user->id;
            $system_order->paid_at = time();
            $system_order->status = Orders::STATUS_PAID;
            if (!$system_order->update()) {
                throw new ServerErrorHttpException('系统内部订单更新出错');
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
     * @param $system_order Orders
     * @throws Exception
     */
    private function payFail($notify, $system_order)
    {
        $isolationLevel = Transaction::SERIALIZABLE;
        $transaction = Yii::$app->db->beginTransaction($isolationLevel);
        try {
            $system_order->payment_type = Orders::TYPE_WXPAY;
            $system_order->status = Orders::STATUS_UNPAID;
            $system_order->user_id = Yii::$app->user->id;
            if (!$system_order->update()) {
                throw new ServerErrorHttpException('系统内部订单更新出错');
            }
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * 二维码访问链接
     *
     * @param $content string
     */
    public function actionGetQrCode($content)
    {
        $qrCode = new QrCode($content);
        $qrCode
            ->setSize(200)
            ->setMargin(10)
            ->setLabel('请使用微信扫码支付', 12)
            ->setLabelAlignment('center');

        header('Content-Type: '.$qrCode->getContentType());
        echo $qrCode->writeString();
    }

    /**
     * 检测订单是否已经支付成功
     *
     * @param $id
     * @return string
     */
    public function actionCheckOrder($id){
        try {
            $model = Orders::findOne($id);
            if($model->status !== Orders::STATUS_PAID){
                throw new Exception('订单未支付');
            }
            return Json::encode(['done' => true,'data' => $model->trade_no]);
        } catch (Exception $e) {
            return Json::encode(['done' => false,'msg' => $e->getMessage()]);
        }
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
