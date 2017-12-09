<?php
/**
 * Author: guiyumin, goes by Eric Gui
 * Date: 2017-09-06
 * Time: 16:01
 * Github: https://www.github.com/medmin/ipapp
 * Email: guiyumin@gmail.com
 */
namespace app\commands;

use app\models\Patents;
use app\models\UnpaidAnnualFee;
use app\models\WxUser;
use Symfony\Component\CssSelector;
use Symfony\Component\DomCrawler\Crawler;
use yii\console\Controller;
use Yii;
use Facebook\WebDriver\Chrome\ChromeDriver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use yii\db\Exception;
use yii\db\Transaction;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use EasyWeChat\Foundation\Application;

class FeeController extends Controller
{
    //首先要每天更新一下Patents表里的缴费截止日这个字段，patentFeeDueDate

    public function actionWarning(string $days)
    {
        /**
         * @var $patent Patents
         *
         * 查询具体缴费截止日还有 +90, +30, +15, +7, +0, -1天时的专利
         */
        $patentModels = Patents::find()
            ->where(['patentFeeDueDate' => date('Ymd', strtotime($days.' days'))])
            ->andWhere(['<>', 'patentApplicationNo', ''])
            ->all();

        foreach ($patentModels as $patent)
        {
            $patentUserID = Yii::$app->db
                ->createCommand(
                    'SELECT DISTINCT patentUserID From patents WHERE patentAjxxbID=\''.$patent->patentAjxxbID.'\''
                )
                ->queryColumn();

            if (isset($patentUserID))
            {
                foreach ($patentUserID as $userID)
                {
                    $user = WxUser::findOne(['userid' => $userID]);
                    if($user && isset($user->fakeid))
                    {
                        // 通过api获取费用信息
                        $client = new \GuzzleHttp\Client(['base_uri' => Yii::$app->params['api_base_uri']]);
                        try {
                            $response = $client->request('GET', "/patents/".$patent->patentApplicationNo."/latest-unpaid-fee");
                            $fee_info = json_decode($response->getBody(), true);
                            if (empty($fee_info)) continue; // 如果获取到的专利没有费用信息就跳出循环
                        }
                        catch (\Exception $e) {
                            Yii::error($e->getMessage());
                            continue; // 如果请求失败(404或者其他)也跳出循环
                        }
                        $deadline = $patent->patentFeeDueDate;
                        $data = [
                            'first' => '您好，您有一项专利需要缴费',
                            'keyword1' => $patent->patentTitle, //数据OK
                            'keyword2' => $fee_info['type'],
                            'keyword3' => $fee_info['amount'] . '元',
                            'keyword4' => $deadline, //数据OK
                            'keyword5' => (int)$days >= 0 ? (int)$days.'天' : '已逾期'.((int)$days * (-1)).'天', //数据OK
                            'remark' => '如果有任何疑问，请致电0451-88084686',
                        ];
                        $template_id = 'cGvdscYjjF4DZy7xSRTczQuyGCCQZAF0L9KxBnr8V7k';
                        $this->sendWeixinTemplateMessage($user->fakeid, $data, $template_id);
                    }
                }
            }
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

}