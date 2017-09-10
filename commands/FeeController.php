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
use yii\db\Transaction;

class FeeController extends Controller
{

    public function actionHlipo()
    {

    }


    public function actionFee()
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
                                $unpaid_annual_fee_row->fee_type = '专利的第' . $matches[1] . '年年费';
                            }
                            if ($i == 1){
                                $unpaid_annual_fee_row->amount = $titles[$i];
                            }

                        }

                        $unpaid_annual_fee_row->due_date = ''; //TODO for Mr. Mao

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

                        $unpaid_annual_fee_obj->due_date = ''; //TODO for Mr. Mao

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