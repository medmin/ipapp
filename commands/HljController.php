<?php
/**
 * Author: guiyumin, goes by Eric Gui
 * Date: 2017-10-07
 * Time: 13:46
 * Github: https://www.github.com/medmin/ipapp
 * Email: guiyumin@gmail.com
 */
namespace app\commands;


use app\models\Patents;
use GuzzleHttp\Client;
use SebastianBergmann\CodeCoverage\Report\PHP;
use Symfony\Component\DomCrawler\Crawler;
use yii\console\Controller;

class HljController extends Controller
{
    public function actionIpo()
    {
        $start = $_SERVER['REQUEST_TIME'];  // 开始时间

        $client = new Client();

        $hlj_ipo_url = 'http://db.hlipo.gov.cn:8080/ipsss/showSearchForm.do?area=cn';

        $requestOptions = [
            'allow_redirects' => false,
            'connect_timeout' => 60,
            'debug' => true,
            'headers' => [
                'User-Agent' => $this->getUa(),
            ],
            'verify' => false,
//            'proxy' => $this->getIp(),
            'timeout' => 60,
            'form_params' => [
                'area' => 'cn',
                'synonymous' => 'SYNONYM_UTF8',
                'strWhere' => '',
                'strSynonymous' => '1',
                'strSortMethod' => 'RELEVANCE',
                'strDefautCols' => '主权项, 名称, 摘要',
                'iHitPointType' => 115,
                'strChannels'   => '14,15,16,17',
                'searchKind'    => 'tableSearch',
                'txt_I'         => '哈尔滨工业大学'
            ],
        ];

        $response = $client->request('POST', $hlj_ipo_url, $requestOptions);

        echo $response->getStatusCode() . PHP_EOL;

        $crawler = new Crawler();
        $crawler->addHtmlContent($response->getBody());

        $patent_numbers = $crawler->filter('body > div:nth-child(51) > div > div.span3 > ul:nth-child(1) > li > a');
        print_r($patent_numbers);
//        preg_match('/\d+/', $patent_numbers, $matches);
//        print_r($matches);

        $this->stdout('Time Consuming:' . (time() - $start) . ' seconds' . PHP_EOL);
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

    public function getIp(): string
    {
        // 代理服务器
        $proxyServer = "http-dyn.abuyun.com:9020";

        // 隧道身份信息
        $proxyUser   = "H18X85J4I7X5727D";
        $proxyPass   = "35C23C0BC635ADD0";

        return 'http://' . $proxyUser . ':' . $proxyPass . '@' . $proxyServer;
    }


    /**
     * 导入Excel
     * 导入之前先执行以下sql
     * ALTER TABLE `patents` CHANGE `patentTitle` `patentTitle` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';
     */
    public function actionImport()
    {
        $successCount = 0;
        echo 'Start time: ' . date('y/m/d H:i:s') . PHP_EOL;
        $path_1 = './runtime/list.xlsx'; // 附件1：哈工大2016年授权专利待核实列表.xls
        $path_2 = './runtime/one.xls'; //专利列表.xlsx
        $objReader = \PHPExcel_IOFactory::load($path_1);
        $sheetData = $objReader->getActiveSheet()->toArray(null,true,true,true);
        /*
         * 在.xlsx这个数据表中，F列的时间格式不一样，有的用/有的用-来分割，第122行的F列还多了一个中划线，手动更改
         */
        foreach ($sheetData as $idx => $data) {
            if (strlen($data['C']) == 14) {
                $applicationNo = str_replace('.','',$data['C']);
                if (!Patents::findOne(['patentApplicationNo' => $applicationNo])) {
                    $model = new Patents();
                    $model->patentAjxxbID = 'AJ000001_' . str_pad((string)$idx,4,'0',STR_PAD_LEFT); //结果类似 AJ000001_0000
                    $model->patentEacCaseNo = 'AAA'; // 没有唯一，就统一设为AAA
                    $model->patentType = ''; // 没有类型
                    $model->patentUserID = 0;
                    $model->patentAgent = '';
                    $model->patentProcessManager = '';
                    $model->patentTitle = $data['D'];
                    $model->patentApplicationNo = $applicationNo;
                    $model->patentPatentNo = '';
                    $model->patentNote = (string)$data['G']; // 将学院信息写到Note中,因为有的可能null，需要讲null转为字符串否则报错

                    //这个日期很恶心，好多格式  2017/5/23 => 05-23-17 有的是string格式 有的是float
                    if (is_string($data['F'])) {
                        if (strlen($data['F']) == 8) {
                            // 如果长度是8 说明是 05-23-14的格式  否则就是 2009-02-16的格式
                            $model->patentApplicationDate = '20' . substr($data['F'],-2) . substr($data['F'],0,2) . substr($data['F'],3,2);
                        } else {
                            $model->patentApplicationDate = str_replace('-','',$data['F']);
                        }
                    } else {
                        // 如果不是字符串格式，那就是float格式，从147行开始就是float
                        $model->patentApplicationDate = (string)$data['F'];
                    }

                    $model->patentCaseStatus = '有效'; // 案件状态写为 有效
                    $model->patentInventors = str_replace(';','、',$data['E']); // 从国知局爬过来的数据统一是顿号分割
                    $model->patentApplicationInstitution = '哈尔滨工业大学'; // 设置专利权人为 哈尔滨工业大学(这个表数据较少，默认给成这个)
                    $model->UnixTimestamp = round(microtime(true) * 1000);
                    if (!$model->save()) {
                        echo 'Error: ' . $applicationNo . PHP_EOL;
                        print_r($model->errors);
                        echo PHP_EOL;
                    } else {
//                        echo $model->patentApplicationNo . ' OK'.PHP_EOL;
                        $successCount ++;
                    }
                } else {
                    echo $applicationNo . ' already exists!' . PHP_EOL;
                }
            }
        }
        echo 'End ...' . PHP_EOL;
        $objReader = \PHPExcel_IOFactory::load($path_2);
        $sheetData = $objReader->getActiveSheet()->toArray(null,true,true,true);
        foreach ($sheetData as $idx => $data) {
            if ($idx > 1) {
                $applicationNo = str_replace('.','',$data['B']);
                if (!Patents::findOne(['patentApplicationNo' => $applicationNo])) {
                    $model = new Patents();
                    $model->patentAjxxbID = 'AJ000002_' . str_pad((string)$idx,4,'0',STR_PAD_LEFT);
                    $model->patentEacCaseNo = 'AAAA';
                    $model->patentType = $data['I'];
                    $model->patentUserID = 0;
                    $model->patentAgent = '';
                    $model->patentProcessManager = '';
                    $model->patentTitle = $data['A'];
                    $model->patentApplicationNo = $applicationNo;
                    $model->patentPatentNo = '';
                    $model->patentNote = $data['H']; // 所属单位写入 Note
                    $model->patentApplicationDate = str_replace('-','',$data['C']);
                    $model->patentCaseStatus = '有效'; // 案件状态写为 有效
                    $model->patentInventors = str_replace(',','、',$data['G']);
                    $model->patentApplicationInstitution = $data['J'];
                    $model->UnixTimestamp = round(microtime(true) * 1000);
                    if (!$model->save()) {
                        echo 'Error: ' . $applicationNo . PHP_EOL;
                        print_r($model->errors);
                        echo PHP_EOL;
                    } else {
//                        echo $model->patentApplicationNo . ' OK'.PHP_EOL;
                        $successCount ++;
                    }
                } else {
                    echo $applicationNo . ' already exists!' . PHP_EOL;
                }
            }
        }

        echo 'Successfully written: '.$successCount.PHP_EOL;
        echo 'End time: ' . date('y/m/d H:i:s');

    }
}
