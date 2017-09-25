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
use Codeception\Module\Cli;
use Symfony\Component\CssSelector;
use Symfony\Component\DomCrawler\Crawler;
use yii\console\Controller;
use Yii;
use Facebook\WebDriver\Chrome\ChromeDriver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use yii\db\Exception;
use yii\db\Transaction;
use GuzzleHttp\Client;

class FeeController extends Controller
{

    public function actionHlipo()
    {

    }

    public function getApplicationNoFromMysql()
    {
        $patentApplicationNumbers_in_patents_table =
            Yii::$app->db->createCommand('SELECT patentApplicationNo FROM patents')->queryColumn();
        $existingAjxxbID_in_fee_table =
            Yii::$app->db->createCommand('SELECT patentAjxxbID from unpaid_annual_fee')->queryColumn();
    }



    public function actionFee($applicationNo = '2015210884742')
    {
        $base_uri = 'http://cpquery.sipo.gov.cn/txnQueryFeeData.do';
        $client = new Client();
        $response = $client->request('GET', $base_uri, ['query' => ['select-key:shenqingh' => $applicationNo]]);

        if ($response->getStatusCode() == 200) {
            $html = $response->getBody()->getContents();

            $crawler = new Crawler();
            $crawler->addHtmlContent($html);
            $key = $crawler->filter('body > span')->last()->attr('id');
            $useful_id = $this->decrypt($key);
            $idIsKey = array_flip($useful_id);


            $trHtml = $crawler->filter('table[class="imfor_table_grid"]')->eq(0)->filter('tr')->each(function (Crawler $node) {
                return $node->html();
            });
            $result = [];
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
                        $unpaid_annual_fee_row = new UnpaidAnnualFee();
                        $unpaid_annual_fee_row->patentAjxxbID = "AJ172339_2339";
                        $unpaid_annual_fee_row->amount = (int)implode('',$amount);
                        $unpaid_annual_fee_row->fee_type = implode('',$type);
                        $unpaid_annual_fee_row->due_date = implode('',$date);
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