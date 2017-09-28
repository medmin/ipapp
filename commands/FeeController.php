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

class FeeController extends Controller
{

    public function actionPatentinfo($applicationNo = '2015210884742')
    {
        $base_uri = 'http://cpquery.sipo.gov.cn/txnQueryBibliographicData.do';
        $client = new Client();
        $requestOptions = [
            'allow_redirects' => false,
            'connect_timeout' => 60,
            'debug' => true,
            'headers' => [
                'User-Agent' => "Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 1.0.3705; .NET CLR 1.1.4322)",
            ],
            'verify' => false,
            'proxy' => $this->getIp(),
            'query' => ['select-key:shenqingh' => $applicationNo],
            'timeout' => 60,
        ];
        $response = $client->request('GET', $base_uri, $requestOptions);

        if ($response->getStatusCode() == 200) {
            $html = $response->getBody()->getContents();

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

            $applicationDateInfoSpan = array_filter($applicationDateInfoSpan);
            $applicationDate = '';
            foreach ($applicationDateInfoSpan as $info)
            {
                $applicationDate .= $info;
            }
//            echo $applicationDate;
//            echo PHP_EOL;

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

            $statusInfoSpan = array_filter($statusInfoSpan);
            $caseStatus = '';
            foreach ($statusInfoSpan as $info)
            {
                $caseStatus .= $info;
            }
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

            $patentApplicationInstitutionInfoSpan = array_filter($patentApplicationInstitutionInfoSpan);
            $patentApplicationInstitution = '';
            foreach ($patentApplicationInstitutionInfoSpan as $info)
            {
                $patentApplicationInstitution .= $info;
            }
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

            $patentApplicationInventorsInfoSpan = array_filter($patentApplicationInventorsInfoSpan);
            $patentApplicationInventors = '';
            foreach ($patentApplicationInventorsInfoSpan as $info)
            {
                $patentApplicationInventors .= $info;
            }
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

            $patentApplicationAgencyInfoSpan = array_filter($patentApplicationAgencyInfoSpan);
            $patentApplicationAgency = '';
            foreach ($patentApplicationAgencyInfoSpan as $info)
            {
                $patentApplicationAgency .= $info;
            }
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

            $patentApplicationAgencyAgentInfoSpan = array_filter($patentApplicationAgencyAgentInfoSpan);
            $patentApplicationAgencyAgent = '';
            foreach ($patentApplicationAgencyAgentInfoSpan as $info)
            {
                $patentApplicationAgencyAgent .= $info;
            }
//            echo $patentApplicationAgencyAgent;
//            echo PHP_EOL;

            $isolationLevel = Transaction::SERIALIZABLE;
            $transaction = Yii::$app->db->beginTransaction($isolationLevel);
            try
            {
                $thisOnePatent = Patents::findOne(['patentApplicationNo' => '$applicationNo']);

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

    }

    //费用信息--爬虫入口函数
    public function actionFeee()
    {
        $start = $_SERVER['REQUEST_TIME'];  // 开始时间

        //所有不为空的专利申请号，包括正在申请中，和已经授权成功的
        $all_patentApplicationNo_array = Patents::find()->select(['patentApplicationNo'])->where(['<>', 'patentApplicationNo', ''])->asArray()->all();
        $patentApplicationNoS = [];
        foreach ($all_patentApplicationNo_array as $patentAppNo)
        {
            $patentApplicationNoS[] = $patentAppNo['patentApplicationNo'];
        }

        //获取5个专利申请号，一次性传递5个spider，就是5个并发
        do {
            $patentApplicationNoSArrayForSpider = [];
            for ($i = 0; $i < 5 ; $i++) {
                $patentsArray[] = array_shift($patentApplicationNoS);
            }
            $this->feeSpider(array_filter($patentApplicationNoSArrayForSpider));
        } while (!empty($patentApplicationNoS));

        $this->stdout('Time Consuming:' . (time() - $start) . ' seconds' . PHP_EOL);
    }

    //费用信息--具体执行爬虫的函数
    public function feeSpider(array $patentApplicationNoSArrayForSpider)
    {
        $base_uri = 'http://cpquery.sipo.gov.cn/txnQueryFeeData.do';
//        $applicationNo = '2015210884742';

        $concurrencyNumber = count($patentApplicationNoSArrayForSpider);

        $requestOptions = [
            'allow_redirects' => false,
            'connect_timeout' => 60,
            'debug' => true,
            'headers' => [
                'User-Agent' => "Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 1.0.3705; .NET CLR 1.1.4322)",
            ],
            'verify' => false,
            'proxy' => $this->getIp(),
//            'query' => ['select-key:shenqingh' => $applicationNo],
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
            'fulfilled' => function ($response) use ($patentApplicationNoSArrayForSpider) {
                if ($response->getStatusCode() == 200 && ($html = $response->getBody()->getContents()) !== '')
                {
                    $this->parseFeeHtmlAndSaveIntoDB($html);
                }
            },
            'rejected' => function ($reason) use ($patentApplicationNoSArrayForSpider) {
                $this->stdout('Error:' . $patentApplicationNoSArrayForSpider . ' Reason:' . $reason);
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
    public function parseFeeHtmlAndSaveIntoDB($html)
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
                //不需要date这个字段，不准确，需要用info里的字段来计算
//                    $trCrawler = new Crawler();
//                    $trCrawler->addHtmlContent($tr);
//                    $date = $trCrawler->filter('span[name="record_yingjiaof:jiaofeijzr"] span')->each(function (Crawler $node) use ($idIsKey) {
//                        if (isset($idIsKey[$node->attr("id")])) {
//                            return $node->text();
//                        }
//                    });

                $isolationLevel = Transaction::SERIALIZABLE;
                $transaction = Yii::$app->db->beginTransaction($isolationLevel);
                try
                {
                    $thisOnePatent = Patents::findOne(['patentApplicationNo' => '$applicationNo']);

                    $unpaid_annual_fee_row = new UnpaidAnnualFee();

                    $unpaid_annual_fee_row->patentAjxxbID = $thisOnePatent->patentAjxxbID;
                    $unpaid_annual_fee_row->amount = (int)implode('',$amount);
                    $unpaid_annual_fee_row->fee_type = implode('',$type);
                    preg_match('/\d{1,}/', $unpaid_annual_fee_row->fee_type, $matches);
                    $year = $matches[1];
                    $application_date = $thisOnePatent->patentApplicationDate;
                    $unpaid_annual_fee_row->due_date = ((int)substr($application_date,0,4) + (int)$year - 1) . substr($application_date,4,4);

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