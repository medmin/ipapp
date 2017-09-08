<?php
/**
 * Author: guiyumin, goes by Eric Gui
 * Date: 2017-09-06
 * Time: 16:01
 * Github: https://www.github.com/medmin/ipapp
 * Email: guiyumin@gmail.com
 */
namespace app\commands;

use GuzzleHttp\Client;
use Symfony\Component\CssSelector;
use Symfony\Component\DomCrawler\Crawler;
use yii\console\Controller;
use Yii;

class FeeController extends Controller
{

    public function actionHlipo()
    {

    }


    public function actionCpquery()
    {
        $qqbrowserUA = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.104 Safari/537.36 Core/1.53.3368.400 QQBrowser/9.6.11860.400';
        $chromeUA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36';

        $infoUrl = 'http://cpquery.sipo.gov.cn/txnQueryBibliographicData.do?select-key:shenqingh=2011103831338&select-key:zhuanlilx=1&select-key:backPage=http%3A%2F%2Fcpquery.sipo.gov.cn%2FtxnQueryOrdinaryPatents.do%3Fselect-key%3Ashenqingh%3D2011103831338%26select-key%3Azhuanlimc%3D%26select-key%3Ashenqingrxm%3D%26select-key%3Azhuanlilx%3D%26select-key%3Ashenqingr_from%3D%26select-key%3Ashenqingr_to%3D%26verycode%3D0%26inner-flag%3Aopen-type%3Dwindow%26inner-flag%3Aflowno%3D1504359929595&inner-flag:open-type=window&inner-flag:flowno=1504686835497';
        $feeUrl = 'http://cpquery.sipo.gov.cn/txnQueryFeeData.do?select-key:shenqingh=2011103831338&select-key:zhuanlilx=1&select-key:gonggaobj=&select-key:backPage=http%3A%2F%2Fcpquery.sipo.gov.cn%2FtxnQueryOrdinaryPatents.do%3Fselect-key%3Ashenqingh%3D2011103831338%26select-key%3Azhuanlimc%3D%26select-key%3Ashenqingrxm%3D%26select-key%3Azhuanlilx%3D%26select-key%3Ashenqingr_from%3D%26select-key%3Ashenqingr_to%3D%26verycode%3D0%26inner-flag%3Aopen-type%3Dwindow%26inner-flag%3Aflowno%3D1504359929595&inner-flag:open-type=window&inner-flag:flowno=1504360062755';
        $feeUrlShort = 'http://cpquery.sipo.gov.cn/txnQueryFeeData.do?select-key:shenqingh=2011103831338';

        $basicFeeURL = 'http://cpquery.sipo.gov.cn/txnQueryFeeData.do?select-key:shenqingh=';
        $basicInfoURL = 'http://cpquery.sipo.gov.cn/txnQueryBibliographicData.do?select-key:shenqingh=';

        $client = new Client();

        $requestOptions = [
            'allow_redirects' => false,
            'connect_timeout' => 60,
            'debug' => true,
            'headers' => [
                'User-Agent' =>  "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; Acoo Browser; SLCC1; .NET CLR 2.0.50727; Media Center PC 5.0; .NET CLR 3.0.04506)",
            ],
            'verify' => false,
            'timeout' => 60,
        ];

        $response = $client->request('GET', $feeUrlShort, $requestOptions);

        if ($response->getStatusCode() == 200)
        {
            $body = $response->getBody()->getContents();

            $crawler = function ($str){
                return new Crawler($str);
            };

            $feeToPayTableHtml = $crawler($body)->filter('#djfid > table')->each(
                function ($node) {
                    return $node->html();
                }
            );

            var_dump($feeToPayTableHtml);
//            echo $feeTr;


            echo PHP_EOL . 'Voila';
        }

    }
}