<?php
/**
 * Author: JokerRoc
 * Date: 2017-11-10
 */
namespace app\commands;

use yii\console\Controller;
use Yii;
use app\models\Patents;
use app\lib\YanCrawler;

class PatentsController extends Controller
{
    /**
     * 通过api获取专利到期时间
     */
    public function actionUpdateDueDate()
    {
        // 建立爬虫
        $crawler = new YanCrawler([
            'concurrency' => 5, // 并发线程数
            'is_init' => 1, // 是否初始化爬取队列
            'log_prefix' => 'patents:update-due-date', // 日志前缀
            'redis_prefix' => 'patents:update-due-date', // redis前缀
            'timeout' => 5.0,   // 爬取网页超时时间
            'log_step' => 5, // 每爬取多少页面记录一次日志
            'base_uri' => Yii::$app->params['api_base_uri'],
            'retry_count' => 0,
            'queue_len' => '',
            'interval' => 0, // 爬取间隔时间
            'requests' => function () { // 需要发送的请求
                // 查询申请号列表
                $application_nos = Patents::find()
                    ->select(['patentApplicationNo'])
                    ->where(['not in', 'patentApplicationNo', ['', 'Not Available Yet']])
                    ->asArray()
                    ->column();
                foreach ($application_nos as $key => $val) {
                    // 获取需要爬取的url
                    $url = "/patents/{$val}/latest-unpaid-fee";
                    $request = [
                        'method' => 'get',
                        'uri' => $url,
                        'callback_data' => [ // 回调参数
                            'application_no' => $val,
                        ],
                    ];
                    yield $request;
                }
            },
            'fulfilled' => function ($result, $request) use (&$total_page) { // 爬取成功的回调函数
                if (!$result) {
                    return;
                }
                $result = json_decode($result, true);
                $application_no = $request['callback_data']['application_no'];
                $patent = Patents::findOne(['patentApplicationNo' => $application_no]);
                $patent->patentFeeDueDate = str_replace('-', '', $result['due_date']);
                $patent->dueDateUpdateAt = time();
                $res = $patent->save();
                echo $application_no. ' - '. $patent->patentFeeDueDate .' - '.$res.PHP_EOL;
            },
            'rejected' => function ($request, $msg) { // 爬取失败的回调函数
            },
        ]);

        $crawler->run();

    }

}
