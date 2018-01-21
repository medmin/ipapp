<?php
/**
 * Author: guiyumin, goes by Eric Gui
 * Date: 2017-09-06
 * Time: 16:01
 * Github: https://www.github.com/medmin/ipapp
 * Email: guiyumin@gmail.com
 */
namespace app\commands;

use app\models\AnnualFeeMonitors;
use app\models\WxUser;
use yii\console\Controller;
use Yii;
use GuzzleHttp\Client;
use EasyWeChat\Foundation\Application;

class FeeController extends Controller
{
    //首先要每天更新一下Patents表里的缴费截止日这个字段，patentFeeDueDate

    /**
     * 查询具体缴费截止日还有 +90, +30, +15, +7, +0, -1天时的专利
     *
     * @param string $days
     */
    public function actionWarning(string $days)
    {
        try {
            // 通过api获取所有到期的专利号
            $due_client = new Client(['base_uri' => Yii::$app->params['api_base_uri']]);
            $due_response = $due_client->request('GET', "/patents/due/" . (int)$days);
            $due_result = json_decode($due_response->getBody(), true);
            /**
             * @var $monitor AnnualFeeMonitors
             *
             * 遍历申请号查看对应用户，然后发送通知
             */
            foreach ($due_result as $application_no) {
                $monitors_model = AnnualFeeMonitors::find()->where(['application_no' => $application_no])->all();
                foreach ($monitors_model as $monitor) {
                    $user = WxUser::findOne(['userID' => $monitor->user_id]);
                    if ($user && isset($user->fakeid)) {
                        try {
                            // 用户存在，那就通过api获取费用以及基本信息(用来查看title)
                            $patent_client = new Client(['base_uri' => Yii::$app->params['api_base_uri']]);
                            $patent_response = $patent_client->request('GET', '/patents/view/'.$application_no);
                            $patent_info = json_decode($patent_response->getBody(), true);
                            $fee_client = new Client(['base_uri' => Yii::$app->params['api_base_uri']]);
                            $fee_response = $response = $fee_client->request('GET', "/patents/".$application_no."/latest-unpaid-fee");
                            $fee_info = json_decode($fee_response->getBody(), true);
                            if (empty($patent_info) || empty($fee_info)) continue; // 如果获取到的专利没有费用信息就跳出循环

                            // 微信提示相关参数
                            $deadline = date('Ymd', strtotime($days.' days'));
                            $data = [
                                'first' => '您好，您有一项专利需要缴费',
                                'keyword1' => $patent_info['title'], //数据OK
                                'keyword2' => $fee_info['type'],
                                'keyword3' => $fee_info['amount'] . '元',
                                'keyword4' => $deadline, //数据OK
                                'keyword5' => (int)$days >= 0 ? (int)$days.'天' : '已逾期'.((int)$days * (-1)).'天', //数据OK
                                'remark' => '如果有任何疑问，请致电0451-88084686',
                            ];
                            $template_id = 'cGvdscYjjF4DZy7xSRTczQuyGCCQZAF0L9KxBnr8V7k';
                            $this->sendWeixinTemplateMessage($user->fakeid, $data, $template_id);
                        }
                        catch (\Exception $e) {
                            Yii::error($e->getMessage());
                            continue;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Yii::error($e->getMessage());
            exit;
        }
    }

    public function sendWeixinTemplateMessage($openid, array $data, string $template_id)
    {
        $options = [
            'debug'  => true,
            'app_id' => Yii::$app->params['wechat']['id'],
            'secret' => Yii::$app->params['wechat']['secret'],
            'token'  => Yii::$app->params['wechat']['token'],
            'aes_key' => Yii::$app->params['wechat']['aes_key'],
            'log' => [
                'level' => 'debug',
                'file'  => Yii::$app->params['wechat_log_path'], // XXX: 绝对路径！！！！
            ]
        ];
        $app = new Application($options);
        $notice = $app->notice;

        $messageID = $notice->send([
            'touser' => $openid,
            'template_id' => $template_id,
            'url' => 'https://kf.shineip.com/users/patents',
            'data' => $data,
        ]);

    }

    public function actionWxtest()
    {
        $fakeid = WxUser::findOne(['userid' => 2])->fakeid;
        $data = [
            'first' => '缴费测试',
            'keyword1' => '缴费测试',
            'keyword2' => '缴费测试',
            'keyword3' => '缴费测试',
            'keyword4' => '缴费测试',
            'keyword5' => '缴费测试',
            'remark' => '缴费测试',
        ];
        $template_id = 'cGvdscYjjF4DZy7xSRTczQuyGCCQZAF0L9KxBnr8V7k';
        $this->sendWeixinTemplateMessage($fakeid, $data, $template_id);
    }

    public function actionTest()
    {
        $monitors_model = AnnualFeeMonitors::find()->where(['application_no' => '2014106327464'])->all();
        foreach ($monitors_model as $monitor) {
            $user = WxUser::findOne(['userid' => $monitor->user_id]);
            if ($user && isset($user->fakeid)) {
                echo $user->fakeid . PHP_EOL;
            }
        }
    }

}