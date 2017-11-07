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

    public function actionIndex()
    {
        $total = 19439;
        $pagerecord = 500; // 最多500

        for ($i=1; $i <= 1; $i++) {
            $client = new Client();
            $options = [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Referer' => 'http://db.hlipo.gov.cn:8080/ipsss/showSearchForm.do?area=cn',
                    'Origin' => 'http://db.hlipo.gov.cn:8080',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
                    'Accept-Language' => 'zh-CN,zh;q=0.8',
                    'Connection' => 'keep-alive',
                    'Cookie' => 'tencentSig=4818557952; JSESSIONID='.$this->getJessionID().'; _qddaz=QD.xuzlxr.ai4dpc.j8fo6daj; _gscu_1547464065=043244422dtr4j90; IESESSION=alive; _qddamta_4001880860=4-0; _qdda=4-1.1d0eof; _qddab=4-1912wz.j9aobqjv', // _qddab 每天
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36'
                ],
                'form_params' => [
                    'area' => 'cn',
                    'strWhere' => '申请（专利权）人=(哈尔滨工业大学)',
                    'strSynonymous' => 'SYNONYM_UTF8',
                    'strSortMethod' => 'RELEVANCE',
                    'strDefautCols' => '主权项, 名称',
                    'iHitPointType' => 115,
                    'strChannels' => '14,15,16',
                    'searchKind' => 'tableSearch',
                    'trsLastWhere' => null,
                    'ABSTBatchCount' => 0,
                    'strSources' => 'fmzl_ft,syxx_ft,wgzl_ab',
                    'iOption' => 2,
                    'pageIndex' => $i,
                    'pagerecord' => $pagerecord
                ],
            ];
            $response = $client->request('POST', 'http://db.hlipo.gov.cn:8080/ipsss/overviewSearch.do?area=cn', $options);
            $html = $response->getBody();
            // echo $html;
            $crawler = new Crawler();
            $crawler->addHtmlContent($html);

            $span = $crawler->filter('#showDetail > .span9 > .row-fluid')
                        ->reduce(function($node,$i){
                            return ($i % 2 == 0);
                        })->each(function($node){
                            return $node->html();
                        });
            foreach ($span as $key => $value) {
                // 专利号
                echo (new Crawler($value))->filter('input')->attr('an');
                // 有效 无效
                $status = new Crawler();
                $status->addHtmlContent($value);
                echo $status->filter('.checkbox > span')->last()->html().PHP_EOL;
            }
        }
    }

    public function getJessionID()
    {
        $ch = curl_init('http://db.hlipo.gov.cn:8080/ipsss/showSearchForm.do?area=cn');
        curl_setopt($ch, CURLOPT_REFERER, "http://db.hlipo.gov.cn:8080/ipsss/");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['host' => 'http://db.hlipo.gov.cn:8080']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // get headers too with this line
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $result = curl_exec($ch);
        // get cookie
        // multi-cookie variant contributed by @Combuster in comments
        preg_match_all('/^Set-Cookie:\s*([^;\r\n]*)/mi', $result, $matches);
        $cookies = array();
        foreach($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
        return $cookies['JSESSIONID'];
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
