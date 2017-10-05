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
            ->where([
                'patentFeeDueDate' => date('Ymd', strtotime($days.' days')),
                'patentCaseStatus' => '有效'
                    ])
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
                    $fakeid = WxUser::findOne(['userid' => $userID])->fakeid;
                    if(isset($fakeid))
                    {
                        $fee_type_sql = '
                                    SELECT fee_type 
                                    FROM unpaid_annual_fee 
                                    WHERE patentAjxxbID=\''.$patent->patentAjxxbID.'\' 
                                    AND status=0 
                                    AND due_date=\''.date('Ymd', strtotime($days.' days')).'\' 
                                    ';
                        $fee_amount_sql = '
                                    SELECT amount 
                                    FROM unpaid_annual_fee 
                                    WHERE patentAjxxbID=\''.$patent->patentAjxxbID.'\' 
                                    AND status=0 
                                    AND due_date=\''.date('Ymd', strtotime($days.' days')).'\' 
                                    ';
                        $fee_type = implode('，', Yii::$app->db->createCommand($fee_type_sql)->queryColumn());
                        $fee_amount_s = Yii::$app->db->createCommand($fee_amount_sql)->queryColumn();
                        $fee_amount = 0;
                        foreach ($fee_amount_s as $amount)
                        {
                            $fee_amount +=$amount;
                        }
                        $deadline = $patent->patentFeeDueDate;
                        $data = [
                            'first' => '您好，您有一项专利需要缴费',
                            'keyword1' => $patent->patentTitle, //数据OK
                            'keyword2' => $fee_type,
                            'keyword3' => $fee_amount,
                            'keyword4' => $deadline, //数据OK
                            'keyword5' => $days, //数据OK
                            'remark' => '如果有任何疑问，请致电0451-88084686',
                        ];
                        $template_id = 'cGvdscYjjF4DZy7xSRTczQuyGCCQZAF0L9KxBnr8V7k';

                        $this->sendWeixinTemplateMessage($fakeid, $data, $template_id);
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
            'url' => 'http://kf.shineip.com',
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

    //必须先执行专利信息爬虫

    //专利信息详情--爬虫入口函数
    public function actionInfoo()
    {
        $start = $_SERVER['REQUEST_TIME'];  // 开始时间

        //所有不为空的专利申请号，包括正在申请中，和已经授权成功的；并且AjxxbID不在unpaid_annual_fee里面，保证每次重启此函数，都是增量爬取
//        $patentApplicationNoS = Yii::$app->db->createCommand(
//            "SELECT patentApplicationNo FROM patents WHERE patentApplicationNo<>'' AND patentAjxxbID not in (SELECT distinct patentAjxxbID from unpaid_annual_fee)"
//        )->queryColumn();

        $patentApplicationNoS = Yii::$app->db->createCommand(
            "SELECT patentApplicationNo FROM patents WHERE patentApplicationNo<>''"
        )->queryColumn();

        //获取5个专利申请号，一次性传递5个到spider，就是5个并发
        do {
            $patentApplicationNoSArrayForSpider = [];
            for ($i = 0; $i < 5 ; $i++) {
                $patentApplicationNoSArrayForSpider[] = array_shift($patentApplicationNoS);
            }
            $this->infoSpider(array_filter($patentApplicationNoSArrayForSpider));

            // 随机暂停1-10秒
            $randomSeconds = mt_rand(1,10);
            sleep($randomSeconds);

        } while (!empty($patentApplicationNoS));

        $this->stdout('Time Consuming:' . (time() - $start) . ' seconds' . PHP_EOL);
    }

    //专利相关信息--具体执行爬取的函数
    public function infoSpider(array $patentApplicationNoSArrayForSpider)
    {
        $base_uri = 'http://cpquery.sipo.gov.cn/txnQueryBibliographicData.do';

        $concurrencyNumber = count($patentApplicationNoSArrayForSpider);

        $requestOptions = [
            'allow_redirects' => false,
            'connect_timeout' => 60,
            'debug' => true,
            'headers' => [
                'User-Agent' => $this->getUa(),
            ],
            'verify' => false,
            'proxy' => $this->getIp(),
            'timeout' => 60,
        ];
        $client = new Client($requestOptions);

        $requests = function ($concurrencyNumber) use ($base_uri, $patentApplicationNoSArrayForSpider, $client) {
            foreach ($patentApplicationNoSArrayForSpider as $patentApplicationNo) {
                yield function () use ($patentApplicationNo, $base_uri, $client) {
                    return $client->getAsync($base_uri . '?select-key:shenqingh=' . $patentApplicationNo);
                };
            }
        };

        $pool = new Pool($client, $requests($concurrencyNumber), [
            'concurrency' => $concurrencyNumber,
            'fulfilled' => function ($response, $index) use ($patentApplicationNoSArrayForSpider) {
                if ($response->getStatusCode() == 200 )
                {
                    $html = $response->getBody()->getContents();

                    if ($html == '')
                    {
                        $this->stdout('Something is wrong about ths patent ' . $patentApplicationNoSArrayForSpider[$index]);
                    }
                    else
                    {
                        $this->parseInfoHtmlAndSaveIntoDB($html, $patentApplicationNoSArrayForSpider[$index]);
                    }

                }
            },
            'rejected' => function ($reason, $index) use ($patentApplicationNoSArrayForSpider) {
                $this->stdout('Error:' . $patentApplicationNoSArrayForSpider[$index] . ' Reason:' . $reason);
                // this is delivered each failed request
            },
        ]);
        $promise = $pool->promise();
        $promise->wait();

    }

    //将专利信息html解析并存入DB
    //0 == '' == '0' == false == null，但是 '0' != null
    public function parseInfoHtmlAndSaveIntoDB($html, $applicationNo)
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);
        $key = $crawler->filter('body > span')->last()->attr('id');
        $useful_id = $this->decrypt($key);
        $idIsKey = array_flip($useful_id);

        //获取申请日
        $crawlerInfo = new Crawler();
        $crawlerInfo->addHtmlContent($html);
        $applicationDateInfoSpan = $crawlerInfo->filter('#zlxid span[name="record_zlx:shenqingr"] span')->each(
            function (Crawler $node) use ($idIsKey){
                if (isset($idIsKey[$node->attr('id')])){
                    return $node->text();
                }
            }
        );

        $applicationDate = str_replace('-','', implode('',$applicationDateInfoSpan));

//        echo $applicationDate;
//        echo PHP_EOL;

        //获取案件状态
        $crawlerStatus = new Crawler();
        $crawlerStatus->addHtmlContent($html);
        $statusInfoSpan = $crawlerStatus->filter('#zlxid span[name="record_zlx:anjianywzt"] span')->each(
            function (Crawler $node) use ($idIsKey){
                if (isset($idIsKey[$node->attr('id')])){
                    return $node->text();
                }
            }
        );

        $caseStatus = implode('',$statusInfoSpan);
//            echo $caseStatus;
//            echo PHP_EOL;

        //获取申请人
        $crawlerInstitution = new Crawler();
        $crawlerInstitution->addHtmlContent($html);
        $patentApplicationInstitutionInfoSpan = $crawlerInstitution->filter('#sqrid span[name="record_sqr:shenqingrxm"] span')->each(
            function (Crawler $node) use ($idIsKey){
                if (isset($idIsKey[$node->attr('id')])){
                    return $node->text();
                }
            }
        );

        $patentApplicationInstitution = implode('',$patentApplicationInstitutionInfoSpan);
//            echo $patentApplicationInstitution;
//            echo PHP_EOL;

        //获取发明人
        $crawlerInventors = new Crawler();
        $crawlerInventors->addHtmlContent($html);
        $patentApplicationInventorsInfoSpan = $crawlerInventors->filter('#fmrid span[name="record_fmr:famingrxm"] span')->each(
            function (Crawler $node) use ($idIsKey){
                if (isset($idIsKey[$node->attr('id')])){
                    return $node->text();
                }
            }
        );

        $patentApplicationInventors = implode('',$patentApplicationInventorsInfoSpan);
//            echo $patentApplicationInventors;
//            echo PHP_EOL;

        //获取代理机构
        $crawlerAgency = new Crawler();
        $crawlerAgency->addHtmlContent($html);
        $patentApplicationAgencyInfoSpan = $crawlerAgency->filter('#zldlid span[name="record_zldl:dailijgmc"] span')->each(
            function (Crawler $node) use ($idIsKey){
                if (isset($idIsKey[$node->attr('id')])){
                    return $node->text();
                }
            }
        );

        $patentApplicationAgency = implode('', $patentApplicationAgencyInfoSpan);
//            echo $patentApplicationAgency;
//            echo PHP_EOL;

        //获取第一代理人
        $crawlerAgencyAgent = new Crawler();
        $crawlerAgencyAgent->addHtmlContent($html);
        $patentApplicationAgencyAgentInfoSpan = $crawlerAgencyAgent->filter('#zldlid span[name="record_zldl:diyidlrxm"] span')->each(
            function (Crawler $node) use ($idIsKey){
                if (isset($idIsKey[$node->attr('id')])){
                    return $node->text();
                }
            }
        );

        $patentApplicationAgencyAgent = implode('', $patentApplicationAgencyAgentInfoSpan);
//            echo $patentApplicationAgencyAgent;
//            echo PHP_EOL;

        $isolationLevel = Transaction::SERIALIZABLE;
        $transaction = Yii::$app->db->beginTransaction($isolationLevel);
        try
        {
            $thisOnePatent = Patents::findOne(['patentApplicationNo' => $applicationNo]);

            $thisOnePatent->patentApplicationDate = $applicationDate;
            $thisOnePatent->patentCaseStatus = $caseStatus;
            $thisOnePatent->patentApplicationInstitution = $patentApplicationInstitution;
            $thisOnePatent->patentInventors = $patentApplicationInventors;
            $thisOnePatent->patentAgency = $patentApplicationAgency;
            $thisOnePatent->patentAgencyAgent = $patentApplicationAgencyAgent;

            $thisOnePatent->save();

            $transaction->commit();
        }
        catch (\Exception $e)
        {
            $transaction->rollBack();
            throw $e;
        }

    }

    //费用信息--爬虫入口函数
    public function actionFeee()
    {
        $start = $_SERVER['REQUEST_TIME'];  // 开始时间

        //所有不为空的专利申请号，包括正在申请中，和已经授权成功的
        $patentApplicationNoS = Yii::$app->db->createCommand(
            "SELECT patentApplicationNo FROM patents WHERE patentApplicationNo<>'' AND patentAjxxbID not in (SELECT distinct patentAjxxbID from unpaid_annual_fee)"
        )->queryColumn();

        //获取5个专利申请号，一次性传递5个spider，就是5个并发
        do {
            $patentApplicationNoSArrayForSpider = [];
            for ($i = 0; $i < 5 ; $i++) {
                $patentApplicationNoSArrayForSpider[] = array_shift($patentApplicationNoS);
            }
            $this->feeSpider(array_filter($patentApplicationNoSArrayForSpider));

            // 随机暂停1-10秒
            $randomSeconds = mt_rand(1,10);
            sleep($randomSeconds);

        } while (!empty($patentApplicationNoS));

        $this->stdout('Time Consuming:' . (time() - $start) . ' seconds' . PHP_EOL);
    }

    //费用信息--具体执行爬虫的函数
    public function feeSpider(array $patentApplicationNoSArrayForSpider)
    {
        $base_uri = 'http://cpquery.sipo.gov.cn/txnQueryFeeData.do';

        $concurrencyNumber = count($patentApplicationNoSArrayForSpider);

        $requestOptions = [
            'allow_redirects' => false,
            'connect_timeout' => 60,
            'debug' => true,
            'headers' => [
                'User-Agent' => $this->getUa(),
            ],
            'verify' => false,
            'proxy' => $this->getIp(),
            'timeout' => 60,
        ];
        $client = new Client($requestOptions);

        $requests = function ($concurrencyNumber) use ($base_uri, $patentApplicationNoSArrayForSpider, $client) {
            foreach ($patentApplicationNoSArrayForSpider as $patentApplicationNo)
            {
                yield function() use ($patentApplicationNo, $base_uri, $client) {
                    return $client->getAsync($base_uri . '?select-key:shenqingh=' . $patentApplicationNo);
                };
            }
        };

        $pool = new Pool($client, $requests($concurrencyNumber), [
            'concurrency' => $concurrencyNumber,
            'fulfilled' => function ($response, $index) use ($patentApplicationNoSArrayForSpider) {

                if ($response->getStatusCode() == 200 )
                {
                    $html = $response->getBody()->getContents();

                    if ($html == '')
                    {
                        $this->stdout('Something is wrong about ths patent ' . $patentApplicationNoSArrayForSpider[$index]);
                    }
                    else
                    {
                        $this->parseFeeHtmlAndSaveIntoDB($html, $patentApplicationNoSArrayForSpider[$index]);
                    }

                }
            },
            'rejected' => function ($reason, $index) use ($patentApplicationNoSArrayForSpider) {
                $this->stdout('Error:' . $patentApplicationNoSArrayForSpider[$index] . ' Reason:' . $reason);
                // this is delivered each failed request
            },
        ]);
        $promise = $pool->promise();
        $promise->wait();
    }

    public function decrypt($key)
    {
        $b2 = '';
        $b4 = 0;
        for ($b3 = 0; $b3 < strlen($key); $b3 += 2) {
            if ($b4 > 255) {
                $b4 = 0;
            }
            $b1 = (int)(hexdec(substr($key, $b3, 2)) ^ $b4++);
            $b2 .= chr($b1);
        }
        if ($b2) {
            return array_filter(explode(',', $b2));
        } else {
            return [];
        }
    }

    public function getIp(): string
    {
        // 代理服务器
        $proxyServer = "http-dyn.abuyun.com:9020";

        // 隧道身份信息
        $proxyUser   = "H18X85J4I7X5727D";
        $proxyPass   = "35C23C0BC635ADD0";

        return 'http://' . $proxyUser . ':' . $proxyPass . '@' . $proxyServer;
    }

    //解析HTML页面并且存到数据库里
    public function parseFeeHtmlAndSaveIntoDB($html, $applicationNo)
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);
        $key = $crawler->filter('body > span')->last()->attr('id');
        $useful_id = $this->decrypt($key);
        $idIsKey = array_flip($useful_id);


        $trHtml = $crawler->filter('table[class="imfor_table_grid"]')->eq(0)->filter('tr')->each(function (Crawler $node) {
            return $node->html();
        });

        foreach ($trHtml as $idx => $tr) {
            if ($idx !== 0) {
                $trCrawler = new Crawler();
                $trCrawler->addHtmlContent($tr);
                $type = $trCrawler->filter('span[name="record_yingjiaof:yingjiaofydm"] span')->each(function (Crawler $node) use ($idIsKey) {
                    if (isset($idIsKey[$node->attr("id")])){
                        return $node->text();
                    }
                });

                $trCrawler = new Crawler();
                $trCrawler->addHtmlContent($tr);
                $amount = $trCrawler->filter('span[name="record_yingjiaof:shijiyjje"] span')->each(function (Crawler $node) use ($idIsKey) {
                    if (isset($idIsKey[$node->attr("id")])) {
                        return $node->text();
                    }
                });
                //如果是刚申请授权，就是“印花税”，直接保存这个日期即可
                $trCrawler = new Crawler();
                $trCrawler->addHtmlContent($tr);
                $date = $trCrawler->filter('span[name="record_yingjiaof:jiaofeijzr"] span')->each(function (Crawler $node) use ($idIsKey) {
                    if (isset($idIsKey[$node->attr("id")])) {
                        return $node->text();
                    }
                });

                $isolationLevel = Transaction::SERIALIZABLE;
                $transaction = Yii::$app->db->beginTransaction($isolationLevel);
                try
                {
                    $thisOnePatent = Patents::findOne(['patentApplicationNo' => $applicationNo]);

                    $unpaid_annual_fee_row = new UnpaidAnnualFee();

                    $unpaid_annual_fee_row->patentAjxxbID = $thisOnePatent->patentAjxxbID;
                    $unpaid_annual_fee_row->amount = (int)implode('',$amount);
                    $unpaid_annual_fee_row->fee_type = implode('',$type);
                    preg_match('/\d{1,}/', $unpaid_annual_fee_row->fee_type, $matches);
                    if(isset($matches[0]))
                    {
                        $year = $matches[0];
                        $application_date = str_replace('-', '', $thisOnePatent->patentApplicationDate);
                        $unpaid_annual_fee_row->due_date = ((int)substr($application_date,0,4) + (int)$year - 1) . substr($application_date,4,4);
                    }
                    else
                    {
                        $unpaid_annual_fee_row->due_date = str_replace('-', '',implode('',$date));
                    }

                    $unpaid_annual_fee_row->save();


                    $transaction->commit();
                }
                catch (\Exception $e)
                {
                    $transaction->rollBack();
                    throw $e;
                }
            }
        }
    }

    public function getUa()
    {
        $ua = [
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:23.0) Gecko/20100101 Firefox/23.0',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.62 Safari/537.36',
            'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; WOW64; Trident/6.0)',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64; rv:24.0) Gecko/20140205 Firefox/24.0 Iceweasel/24.3.0',
            'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:28.0) Gecko/20100101 Firefox/28.0',
            'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:28.0) AppleWebKit/534.57.2 (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2',
            "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; AcooBrowser; .NET CLR 1.1.4322; .NET CLR 2.0.50727)",
            "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; Acoo Browser; SLCC1; .NET CLR 2.0.50727; Media Center PC 5.0; .NET CLR 3.0.04506)",
            "Mozilla/4.0 (compatible; MSIE 7.0; AOL 9.5; AOLBuild 4337.35; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)",
            "Mozilla/5.0 (Windows; U; MSIE 9.0; Windows NT 9.0; en-US)",
            "Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.2; .NET CLR 1.1.4322; .NET CLR 2.0.50727; InfoPath.2; .NET CLR 3.0.04506.30)",
            "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN) AppleWebKit/523.15 (KHTML, like Gecko, Safari/419.3) Arora/0.3 (Change: 287 c9dfb30)",
            "Mozilla/5.0 (X11; U; Linux; en-US) AppleWebKit/527+ (KHTML, like Gecko, Safari/419.3) Arora/0.6",
            "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2pre) Gecko/20070215 K-Ninja/2.1.1",
            "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9) Gecko/20080705 Firefox/3.0 Kapiko/3.0",
            "Mozilla/5.0 (X11; Linux i686; U;) Gecko/20070322 Kazehakase/0.4.5",
            "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.8) Gecko Fedora/1.9.0.8-1.fc10 Kazehakase/0.5.6",
            "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_3) AppleWebKit/535.20 (KHTML, like Gecko) Chrome/19.0.1036.7 Safari/535.20",
            "Opera/9.80 (Macintosh; Intel Mac OS X 10.6.8; U; fr) Presto/2.9.168 Version/11.52",
            "Mozilla/5.0 (Linux; U; Android 2.3.6; en-us; Nexus S Build/GRK39F) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1",
            "Avant Browser/1.2.789rel1 (http://www.avantbrowser.com)",
            "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/532.5 (KHTML, like Gecko) Chrome/4.0.249.0 Safari/532.5",
            "Mozilla/5.0 (Windows; U; Windows NT 5.2; en-US) AppleWebKit/532.9 (KHTML, like Gecko) Chrome/5.0.310.0 Safari/532.9",
            "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/534.7 (KHTML, like Gecko) Chrome/7.0.514.0 Safari/534.7",
            "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US) AppleWebKit/534.14 (KHTML, like Gecko) Chrome/9.0.601.0 Safari/534.14",
            "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.14 (KHTML, like Gecko) Chrome/10.0.601.0 Safari/534.14",
            "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.20 (KHTML, like Gecko) Chrome/11.0.672.2 Safari/534.20",
            "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534.27 (KHTML, like Gecko) Chrome/12.0.712.0 Safari/534.27",
            "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/13.0.782.24 Safari/535.1",
            "Mozilla/5.0 (Windows NT 6.0) AppleWebKit/535.2 (KHTML, like Gecko) Chrome/15.0.874.120 Safari/535.2",
            "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.7 (KHTML, like Gecko) Chrome/16.0.912.36 Safari/535.7",
            "Mozilla/5.0 (Windows; U; Windows NT 6.0 x64; en-US; rv:1.9pre) Gecko/2008072421 Minefield/3.0.2pre",
            "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10",
            "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.9.0.11) Gecko/2009060215 Firefox/3.0.11 (.NET CLR 3.5.30729)",
            "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 GTB5",
            "Mozilla/5.0 (Windows; U; Windows NT 5.1; tr; rv:1.9.2.8) Gecko/20100722 Firefox/3.6.8 ( .NET CLR 3.5.30729; .NET4.0E)",
            "Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1",
            "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:2.0.1) Gecko/20100101 Firefox/4.0.1",
            "Mozilla/5.0 (Windows NT 5.1; rv:5.0) Gecko/20100101 Firefox/5.0",
            "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:6.0a2) Gecko/20110622 Firefox/6.0a2",
            "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:7.0.1) Gecko/20100101 Firefox/7.0.1",
            "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:2.0b4pre) Gecko/20100815 Minefield/4.0b4pre",
            "Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.0 )",
            "Mozilla/4.0 (compatible; MSIE 5.5; Windows 98; Win 9x 4.90)",
            "Mozilla/5.0 (Windows; U; Windows XP) Gecko MultiZilla/1.6.1.0a",
            "Mozilla/2.02E (Win95; U)",
            "Mozilla/3.01Gold (Win95; I)",
            "Mozilla/4.8 [en] (Windows NT 5.1; U)",
            "Mozilla/5.0 (Windows; U; Win98; en-US; rv:1.4) Gecko Netscape/7.1 (ax)",
            "HTC_Dream Mozilla/5.0 (Linux; U; Android 1.5; en-ca; Build/CUPCAKE) AppleWebKit/528.5  (KHTML, like Gecko) Version/3.1.2 Mobile Safari/525.20.1",
            "Mozilla/5.0 (hp-tablet; Linux; hpwOS/3.0.2; U; de-DE) AppleWebKit/534.6 (KHTML, like Gecko) wOSBrowser/234.40.1 Safari/534.6 TouchPad/1.0",
            "Mozilla/5.0 (Linux; U; Android 1.5; en-us; sdk Build/CUPCAKE) AppleWebkit/528.5  (KHTML, like Gecko) Version/3.1.2 Mobile Safari/525.20.1",
            "Mozilla/5.0 (Linux; U; Android 2.1; en-us; Nexus One Build/ERD62) AppleWebKit/530.17 (KHTML, like Gecko) Version/4.0 Mobile Safari/530.17",
            "Mozilla/5.0 (Linux; U; Android 2.2; en-us; Nexus One Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1",
            "Mozilla/5.0 (Linux; U; Android 1.5; en-us; htc_bahamas Build/CRB17) AppleWebKit/528.5  (KHTML, like Gecko) Version/3.1.2 Mobile Safari/525.20.1",
            "Mozilla/5.0 (Linux; U; Android 2.1-update1; de-de; HTC Desire 1.19.161.5 Build/ERE27) AppleWebKit/530.17 (KHTML, like Gecko) Version/4.0 Mobile Safari/530.17",
            "Mozilla/5.0 (Linux; U; Android 2.2; en-us; Sprint APA9292KT Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1",
            "Mozilla/5.0 (Linux; U; Android 1.5; de-ch; HTC Hero Build/CUPCAKE) AppleWebKit/528.5  (KHTML, like Gecko) Version/3.1.2 Mobile Safari/525.20.1",
            "Mozilla/5.0 (Linux; U; Android 2.2; en-us; ADR6300 Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1",
            "Mozilla/5.0 (Linux; U; Android 2.1; en-us; HTC Legend Build/cupcake) AppleWebKit/530.17 (KHTML, like Gecko) Version/4.0 Mobile Safari/530.17",
            "Mozilla/5.0 (Linux; U; Android 1.5; de-de; HTC Magic Build/PLAT-RC33) AppleWebKit/528.5  (KHTML, like Gecko) Version/3.1.2 Mobile Safari/525.20.1 FirePHP/0.3",
            "Mozilla/5.0 (Linux; U; Android 1.6; en-us; HTC_TATTOO_A3288 Build/DRC79) AppleWebKit/528.5  (KHTML, like Gecko) Version/3.1.2 Mobile Safari/525.20.1",
            "Mozilla/5.0 (Linux; U; Android 1.0; en-us; dream) AppleWebKit/525.10  (KHTML, like Gecko) Version/3.0.4 Mobile Safari/523.12.2",
            "Mozilla/5.0 (Linux; U; Android 1.5; en-us; T-Mobile G1 Build/CRB43) AppleWebKit/528.5  (KHTML, like Gecko) Version/3.1.2 Mobile Safari 525.20.1",
            "Mozilla/5.0 (Linux; U; Android 1.5; en-gb; T-Mobile_G2_Touch Build/CUPCAKE) AppleWebKit/528.5  (KHTML, like Gecko) Version/3.1.2 Mobile Safari/525.20.1",
            "Mozilla/5.0 (Linux; U; Android 2.0; en-us; Droid Build/ESD20) AppleWebKit/530.17 (KHTML, like Gecko) Version/4.0 Mobile Safari/530.17",
            "Mozilla/5.0 (Linux; U; Android 2.2; en-us; Droid Build/FRG22D) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1",
            "Mozilla/5.0 (Linux; U; Android 2.0; en-us; Milestone Build/ SHOLS_U2_01.03.1) AppleWebKit/530.17 (KHTML, like Gecko) Version/4.0 Mobile Safari/530.17",
            "Mozilla/5.0 (Linux; U; Android 2.0.1; de-de; Milestone Build/SHOLS_U2_01.14.0) AppleWebKit/530.17 (KHTML, like Gecko) Version/4.0 Mobile Safari/530.17",
            "Mozilla/5.0 (Linux; U; Android 3.0; en-us; Xoom Build/HRI39) AppleWebKit/525.10  (KHTML, like Gecko) Version/3.0.4 Mobile Safari/523.12.2",
            "Mozilla/5.0 (Linux; U; Android 0.5; en-us) AppleWebKit/522  (KHTML, like Gecko) Safari/419.3",
            "Mozilla/5.0 (Linux; U; Android 1.1; en-gb; dream) AppleWebKit/525.10  (KHTML, like Gecko) Version/3.0.4 Mobile Safari/523.12.2",
            "Mozilla/5.0 (Linux; U; Android 2.0; en-us; Droid Build/ESD20) AppleWebKit/530.17 (KHTML, like Gecko) Version/4.0 Mobile Safari/530.17",
            "Mozilla/5.0 (Linux; U; Android 2.1; en-us; Nexus One Build/ERD62) AppleWebKit/530.17 (KHTML, like Gecko) Version/4.0 Mobile Safari/530.17",
            "Mozilla/5.0 (Linux; U; Android 2.2; en-us; Sprint APA9292KT Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1",
            "Mozilla/5.0 (Linux; U; Android 2.2; en-us; ADR6300 Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1",
            "Mozilla/5.0 (Linux; U; Android 2.2; en-ca; GT-P1000M Build/FROYO) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1",
            "Mozilla/5.0 (Linux; U; Android 3.0.1; fr-fr; A500 Build/HRI66) AppleWebKit/534.13 (KHTML, like Gecko) Version/4.0 Safari/534.13",
            "Mozilla/5.0 (Linux; U; Android 3.0; en-us; Xoom Build/HRI39) AppleWebKit/525.10  (KHTML, like Gecko) Version/3.0.4 Mobile Safari/523.12.2",
            "Mozilla/5.0 (Linux; U; Android 1.6; es-es; SonyEricssonX10i Build/R1FA016) AppleWebKit/528.5  (KHTML, like Gecko) Version/3.1.2 Mobile Safari/525.20.1",
            "Mozilla/5.0 (Linux; U; Android 1.6; en-us; SonyEricssonX10i Build/R1AA056) AppleWebKit/528.5  (KHTML, like Gecko) Version/3.1.2 Mobile Safari/525.20.1",
        ];
        return $ua[mt_rand(0, count($ua) - 1)];
    }

    public function actionFee2()
    {
//        $basicFeeURL = 'http://cpquery.sipo.gov.cn/txnQueryFeeData.do?select-key:shenqingh=';
//        $basicInfoURL = 'http://cpquery.sipo.gov.cn/txnQueryBibliographicData.do?select-key:shenqingh=';

        $isolationLevel = Transaction::SERIALIZABLE;
        $transaction = Yii::$app->db->beginTransaction($isolationLevel);

        try
        {
            $patentApplicationNumbers_in_patents_table = Yii::$app->db->createCommand('SELECT patentApplicationNo FROM patents')->queryColumn();
            $existingAjxxbID_in_fee_table = Yii::$app->db->createCommand('SELECT patentAjxxbID from unpaid_annual_fee')->queryColumn();

            $driver_extension = strtoupper(substr(PHP_OS,0,3)) == 'WIN' ? '.exe' : '';

            putenv("webdriver.chrome.driver=". __DIR__ . "/../archives/chromedriver" . $driver_extension);

            $chromeOptions = new ChromeOptions();
            $chromeOptions->addArguments(['headless', 'start-maximized']);
            $capabilities = $chromeOptions->toCapabilities();
            $driver = ChromeDriver::start($capabilities);

            foreach ($patentApplicationNumbers_in_patents_table as $patentApplicationNumber)
            {
                //先把这个申请号对应的ajxxbID查出来
                $thisOnePatentAjxxbID = Patents::findOne(['patentApplicationNo' => $patentApplicationNumber])->patentAjxxbID;

                //不管是不是已经存在年费记录，每周都更新一次，如果不存在，就是insert
                $driver->get('http://cpquery.sipo.gov.cn/txnQueryFeeData.do?select-key:shenqingh=' . $patentApplicationNumber);

                $xOffset = mt_rand(1,80);
                $yOffset = mt_rand(1,80);
                $driver->getMouse()->mouseMove(null, $xOffset, $yOffset);

                $html = $driver->getPageSource();

                $crawler = function ($str){
                    return new Crawler($str);
                };

                $trTagHtml = $crawler($html)->filter('#djfid > table > tbody > tr')->each(
                    function ($node) {
                        return $node->html();
                    }
                );

                foreach ($trTagHtml as $span)
                {
                    $titles = $crawler($span)->filter('td > span')->each(
                        function ($node){
                            return $node->attr('title');
                        }
                    );
//                echo $titles[0]; 报错：Undefined offset: 0

//                    echo $titles[$i];
                    //每一次循环，都是遍历一个数组，结构是：['发明专利第6年年费', '2000', '2017-12-15']
                    //判断一下ajxxbID是否已经存在于unpaid_annual_fee，如果已经存在，就是update，如果不存在，就是insert

                    if (in_array($thisOnePatentAjxxbID, $existingAjxxbID_in_fee_table))
                    {
                        $unpaid_annual_fee_row = UnpaidAnnualFee::findOne(['patentAjxxbID' => $thisOnePatentAjxxbID ]);

                        //这里就不用处理ajxxbID了，因为已经存在了

                        foreach ($titles as $i => $title)
                        {
                            if($i == 0) {
                                preg_match('/\d{1,}/', $titles[$i], $matches);
                                $year = $matches[1]; // TODO error?
                                $unpaid_annual_fee_row->fee_type = '专利的第' . $matches[1] . '年年费';
                            }
                            if ($i == 1){
                                $unpaid_annual_fee_row->amount = $titles[$i];
                            }

                        }

                        $application_date = Patents::findOne(['patentApplicationNo' => $patentApplicationNumber])->patentApplicationDate;
                        if (!$application_date) {
                            $unpaid_annual_fee_row->due_date = '';
                        } else {
                            $unpaid_annual_fee_row->due_date = ((int)substr($application_date,0,4) + (int)$year - 1) . substr($application_date,4,4);
                        }
                        $unpaid_annual_fee_row->save();
                    }
                    else
                    {

                        $unpaid_annual_fee_obj = new UnpaidAnnualFee();

                        $unpaid_annual_fee_obj->patentAjxxbID = $thisOnePatentAjxxbID;

                        foreach ($titles as $i => $title)
                        {
                            if($i == 0) {
                                //注意：这里存的是乱码，将来取出来的时候，取出来后，要做正则表达式处理
                                //preg_match('/\d{1,}/', $string, $matches);
                                $unpaid_annual_fee_obj->fee_type = $titles[$i];
                            }
                            if ($i == 1){
                                $unpaid_annual_fee_obj->amount = $titles[$i];
                            }

                        }

                        $unpaid_annual_fee_obj->due_date = ''; // TODO 同上

                        $unpaid_annual_fee_obj->save();

                    }

                }

                $driver->quit();
            }

            $transaction->commit();
        }
        catch (\Exception $e)
        {
            $transaction->rollBack();
            throw $e;
        }

        echo PHP_EOL . 'Voila' . PHP_EOL;
    }



}