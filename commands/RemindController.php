<?php
/**
 * User: Mr-mao
 * Date: 2017/9/19
 * Time: 16:16
 */

namespace app\commands;

use app\models\AnnualFeeMonitors;
use app\models\Patents;
use app\models\UnpaidAnnualFee;
use app\models\Users;
use yii\console\Controller;
use Yii;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use Symfony\Component\DomCrawler\Crawler;

class RemindController extends Controller
{
    public function actionIndex(int $days = 30)
    {
        $patentModels = Patents::find()->where([
            'patentFeeDueDate' => date('Ymd', strtotime('+'.$days.' days')),
            'patentCaseStatus' => '有效'
        ])->all();

        $redis = Yii::$app->redis;

        $redis->del('remind');

        /* @var $patent Patents */
        foreach ($patentModels as $patent)
        {
            $unpaidAnnualFee = json_decode($patent->generateUnpaidAnnualFee(), true);

            if ($unpaidAnnualFee['status'] == true)
            {
                $users = AnnualFeeMonitors::find()->select('user_id')
                    ->where(['patent_id' => $patent->patentID])->asArray()->all();

                $users = array_column($users, 'user_id');

                foreach($users as $id)
                {
                    $redis->hset('remind', $id, $redis->hget('remind', $id) . $patent->patentID . ',');
                }
            }
        }

        // 提醒
        $users = $redis->hkeys('remind');

        foreach ($users as $user_id)
        {
            // 到期专利总数
            $sum = substr_count($redis->hget('remind', $user_id), ',');
            // 提醒用户
            $username = Users::findOne($user_id)->userFullname;
            $openid = Users::findOne($user_id)->wxUser->fakeid;
            // TODO 发送模板消息
        }

        $redis->del('remind'); // 删除redis
    }

    /**
     * 爬取数据库所有专利的缴费信息
     */
    public function actionClaw()
    {
        $start = $_SERVER['REQUEST_TIME'];  // 开始时间
        $this->stdout('Start time:' . date('H:i:s',$start) . PHP_EOL);

        $redis = Yii::$app->redis;
        if ($redis->exists('patent_l') == 0) {
            $this->queue();
            UnpaidAnnualFee::deleteAll();
        }
        $patents_queue = $redis->lrange('patent_l',0,-1);

        $patents_list = Patents::find()->select(['patentAjxxbID','patentApplicationNo','patentApplicationDate'])->where(['in', 'patentApplicationNo', $patents_queue])->asArray()->all();
        do {
            $patentsArray = [];
            $tmp_i = mt_rand(2,5);  // 随机2到5条
            for ($i = 0; $i < $tmp_i ; $i++) {
                $patentsArray[] = array_shift($patents_list);
            }
            $this->spider(array_filter($patentsArray));
            $tmp_time = mt_rand(1,8);  // 随机1-8秒
            sleep($tmp_time);

        } while (!empty($patents_list));

        $this->stdout('Time Consuming:' . (time() - $start) . ' seconds' . PHP_EOL);
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
     *
     * @param array $patents
     * @param string $base_uri
     */
    public function spider(array $patents, $base_uri = 'http://cpquery.sipo.gov.cn/txnQueryFeeData.do')
    {
        $concurrency = count($patents);
        $client = new Client([
            'headers' => [
                'User-Agent' => $this->getUa(),
                'Proxy-Authorization' => 'Basic ' . base64_encode('H18X85J4I7X5727D:35C23C0BC635ADD0')
            ],
            'proxy' => 'http-dyn.abuyun.com:9020',
            'cookies' => true,
        ]);
        $requests = function ($total) use ($base_uri, $patents, $client) {
            foreach ($patents as $patent) {
                yield function() use ($patent, $base_uri, $client) {
                    return $client->getAsync($base_uri . '?select-key:shenqingh=' . $patent['patentApplicationNo']);
                };
            }
        };
        $patent_list = array_values($patents);
        $pool = new Pool($client, $requests($concurrency), [
            'concurrency' => $concurrency,
            'fulfilled' => function ($response, $index) use ($patent_list) {
                if ($response->getStatusCode() == 200) {
                    $html = $response->getBody()->getContents();
                    if ($html === '') {
                        $this->stdout($patent_list[$index]['patentApplicationNo'] . ' is null' .PHP_EOL);
                    } else {
                        $result = $this->parseUnpaidInfo($html);
                        $this->saveUnpaidFee($result, $patent_list[$index]['patentAjxxbID'], $patent_list[$index]['patentApplicationDate']);
                        $this->stdout($patent_list[$index]['patentApplicationNo'] . ' OK'.PHP_EOL);
                        Yii::$app->redis->lrem('patent_l',1,$patent_list[$index]['patentApplicationNo']); // 删除redis中的值
                    }
                }
            },
            'rejected' => function ($reason, $index) use ($patent_list) {

                $this->stdout('Error occurred time:' . date('H:i:s',time()) . PHP_EOL);
                $this->stdout('Error No:' . $patent_list[$index]['patentApplicationNo'] . ' Reason:' . $reason . PHP_EOL);
                // this is delivered each failed request
            },
        ]);
        $promise = $pool->promise();
        $promise->wait();
    }

    public function clawUnpaid($applicationNo)
    {
        $base_uri = 'http://cpquery.sipo.gov.cn/txnQueryFeeData.do';

        $client = new Client(['base_uri' => $base_uri]);
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

                    $result[] = [implode('',$type), implode('',$amount), implode('',$date)];
                }
            }

            return $result;

        } else {
            echo $response->getStatusCode();
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

    /**
     * 测试使用的HTML
     *
     * @return string
     */
    public function html()
    {
        $html = <<<HTML
<html>
<body>
<input id="usertype" type="hidden"
			value="1">
<div class="hd">
	<div class="head" id="header1">
		<div class="logo_box">

		</div>
		<div class="nav_box">
			<ul class="header_menu">
				<li id="header_query"
					class="_over" ><div
						 class="nav_over" >
						中国专利审查信息查询
					</div></li>
				<li id="header-family" ><div
						>
						多国发明专利审查信息查询
					</div></li>
			</ul>

		</div>
		<div class="hr">
			<ul>
				<!-- 公众用户 -->

					<li id="regpublic"><a href="javascript:;">注册</a></li>
					<li id="loginpublic"><a href="javascript:;">登录</a></li>

				<!-- 公众注册用户 -->

				<!-- 电子申请注册用户 -->

				<!-- 公用部分  -->

				<li title="选择语言">
					<div class="selectlang">
						<a href="javascript:;"> <i class="lang"></i>
						</a>
						<div class="topmenulist hidden">
							<ul>
								<li id="zh"><span  title="中文">中文</span></li>
								<li id="en"><span  title="English">English</span></li>
								<li id="de"><span  title="Deutsch">Deutsch</span></li>
								<li id="es"><span  title="Espa&ntilde;ol">Espa&ntilde;ol</span></li>
								<li id="fr"><span  title="Fran&ccedil;ais">Fran&ccedil;ais</span></li>
								<li id="ja"><span  title="&#26085;&#26412;&#35486;">&#26085;&#26412;&#35486;</span></li>
								<li id="ko"><span  title="&#54620;&#44397;&#50612;">&#54620;&#44397;&#50612;</span></li>
								<li id="ru"><span  title="&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;">&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;</span></li>
							</ul>
						</div>
					</div>
				</li>
				<li id="navLogoutBtn" class="mouse_cross" title="退出">
					<a href="javascript:;"><i class="out"></i></a>
			 	</li>
			</ul>
		</div>

		<ul class="float_botton">
		<li id="backToTopBtn" title="返回顶部" style="display: none;"><i
				class="top"></i></li>
			<li id="backToPage" class="hidden"><a href="javascript:;"><i
					class="back" title="返回"></i></a></li>
			<li id="faqBtn" ><a href="javascript:;"><i
					class="faq_icon" title="FAQ"></i></a></li>
		</ul>
	</div>
</div>
<script src='http://cpquery.sipo.gov.cn:80/appjs/header.js'></script>
<!-- header.jsp对应js -->

	<input type='hidden' name='select-key:shenqingh' id='select-key:shenqingh' value="2015210884742">
	<input type='hidden' name='select-key:backPage' id='select-key:backPage' value="">
	<input type='hidden' name='show:isdjfshow' id='show:isdjfshow' value="yes">
	<input type='hidden' name='show:isyjfshow' id='show:isyjfshow' value="yes">
	<input type='hidden' name='show:istfshow' id='show:istfshow' value="no">
	<input type='hidden' name='show:isznjshow' id='show:isznjshow' value="no">
	<input type='hidden' name='select-key:zhuanlilx' id='select-key:zhuanlilx' value="">
	<input type='hidden' name='select-key:gonggaobj' id='select-key:gonggaobj' value="">
	<div class="bd">
		<div class="tab_body">
			<div class="tab_list">
				<ul>


				   <li id="jbxx" class="tab_first"><div class="tab_top"></div>
						<p>
							申请信息
						</p></li>
					<li id='wjxx'><div class="tab_top"></div>
						<p>
							审查信息
						</p></li>
					<li id='fyxx' class="on"><div class="tab_top_on"></div>
						<p>
							费用信息
						</p></li>
					<li id='fwxx'><div class="tab_top"></div>
						<p>
							发文信息
						</p></li>
					<li id='gbgg'><div class="tab_top"></div>
						<p>
							公布公告
						</p></li>

					<li id='djbxx'><div class="tab_top"></div>
						<p>专利登记簿</p></li>


				</ul>
			</div>
			<div class="tab_box">
				<div class="imfor_part1">
					<h2>
						应缴费信息
						<i id="djftitle" class="draw_up"></i>
					</h2>
					<div id="djfid" class="imfor_table">
						<table class="imfor_table_grid">
							<tr>
								<th width="40%">费用种类</th>
								<th width="30%">应缴金额</th>
								<th width="30%">缴费截止日</th>
							</tr>

								<tr>
									<td><span name="record_yingjiaof:yingjiaofydm" title="pos||"><span id="05f83ec308c745958ce3e22310c19041" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利第3年年费</span><span id="0d1346beb4154f149ef8748e6f5df94e" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利第3年年费</span><span id="81182d92a97f4b208e1677022231b759" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利第3年年费</span><span id="4d7bc20be21c4ca68b5bd85ee75b77eb" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利第3年年费</span><span id="fb784f1562aa4b3eba43dd6d954abb27" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利第3年年费</span><span id="e09062e236ff407396de3ab5b696aed6" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利第3年年费</span><span id="57918b68f44b4749893386672e8973b9" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利第3年年费</span><span id="c0d80b4e9ffd4896964834a3468d1b94" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利第3年年费</span><span id="0ee6c94cfd244aa18cb4cfd23dffb3d3" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利第3年年费</span><span id="19dbe733dfdc4d32a10704a025dea1f0" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利第3年年费</span></span></td>
									<td><span name="record_yingjiaof:shijiyjje" title="pos||"><span id="2b3271ff72ce41048559d75b20937a68" class="nlkfqirnlfjerldfgzxcyiuro">180</span><span id="d002a4966397426591360e02cfcd0098" class="nlkfqirnlfjerldfgzxcyiuro">180</span><span id="fa892685acc64825b6884653125690db" class="nlkfqirnlfjerldfgzxcyiuro">180</span><span id="17d471bb5d1e4f78b45da074c73c1ba0" class="nlkfqirnlfjerldfgzxcyiuro">180</span><span id="122ba9becc184777bb4ea52c14423b14" class="nlkfqirnlfjerldfgzxcyiuro">180</span><span id="12e8376add164bc9b97bbd66ade66ad6" class="nlkfqirnlfjerldfgzxcyiuro">180</span><span id="d3a45b5a47b6418d84fd72063e34f8cb" class="nlkfqirnlfjerldfgzxcyiuro">180</span><span id="eb4f6dbc02cd402abd5229bd35d0074d" class="nlkfqirnlfjerldfgzxcyiuro">180</span><span id="99a1b5ae1a6b46dc84bdc25352715c08" class="nlkfqirnlfjerldfgzxcyiuro">180</span><span id="6e48c74457c9449fbed7d6f4c3307683" class="nlkfqirnlfjerldfgzxcyiuro">180</span></span></td>
									<td><span name="record_yingjiaof:jiaofeijzr" title="pos||"><span id="70c64c2270754d9c8407f5684f4af17d" class="nlkfqirnlfjerldfgzxcyiuro">01-24</span><span id="2a9c1d5ecd6e4fe9824d78471ea01ec0" class="nlkfqirnlfjerldfgzxcyiuro">2018-</span><span id="c22835bb869848dfac294e4b1fbcd4e4" class="nlkfqirnlfjerldfgzxcyiuro">01-24</span><span id="8e2a35f70697493394c53ef0fc23935c" class="nlkfqirnlfjerldfgzxcyiuro">01-24</span><span id="4d56841e0ef546a6b47614ac5c02d37a" class="nlkfqirnlfjerldfgzxcyiuro">2018-</span><span id="60cfa3925d9044a09549fe358b106a0b" class="nlkfqirnlfjerldfgzxcyiuro">01-24</span><span id="b9c895bf4a8840eb802a06a72c3c9826" class="nlkfqirnlfjerldfgzxcyiuro">2018-</span><span id="1d936686b19249a786fe0659eec8e8ca" class="nlkfqirnlfjerldfgzxcyiuro">01-24</span><span id="0e440338e7c64478975c442eb65a14f6" class="nlkfqirnlfjerldfgzxcyiuro">01-24</span><span id="73fc9951b0f249628391e17bea37af6f" class="nlkfqirnlfjerldfgzxcyiuro">2018-</span></span></td>
								</tr>

								<tr>
									<td><span name="record_yingjiaof:yingjiaofydm" title="pos||"><span id="28f82fb51073401eb34d99fe1e4babae" class="nlkfqirnlfjerldfgzxcyiuro">型</span><span id="d169c08ab3194d26858500cd490c857b" class="nlkfqirnlfjerldfgzxcyiuro">实</span><span id="8cbdd23286e5454485e2778dcb875741" class="nlkfqirnlfjerldfgzxcyiuro">型</span><span id="b336d97c228b4856ab7a32728aa233b7" class="nlkfqirnlfjerldfgzxcyiuro">用</span><span id="2e6fcef054774aeca54fd9c9a9117e2a" class="nlkfqirnlfjerldfgzxcyiuro">用</span><span id="c046727dcb064f44864a48bf4d5686b7" class="nlkfqirnlfjerldfgzxcyiuro">新</span><span id="c8ce3e454ca24db883005b85d784ff78" class="nlkfqirnlfjerldfgzxcyiuro">型</span><span id="2da2a5df6887498f8f389c9403b6a411" class="nlkfqirnlfjerldfgzxcyiuro">专</span><span id="323c99ebbf5f4718811c05cd65a6c7a4" class="nlkfqirnlfjerldfgzxcyiuro">利</span><span id="448bb1a82d6d4444a1068b2dde66c22d" class="nlkfqirnlfjerldfgzxcyiuro">第4年年费</span></span></td>
									<td><span name="record_yingjiaof:shijiyjje" title="pos||"><span id="8c346581197046db9e3adb9eec3b7ab8" class="nlkfqirnlfjerldfgzxcyiuro">270</span><span id="7e0f401a4b3f4505adf9b27ac1fe3033" class="nlkfqirnlfjerldfgzxcyiuro">270</span><span id="7b52e8ce5e544f7386350857d703a2c2" class="nlkfqirnlfjerldfgzxcyiuro">270</span><span id="5a9c6ff65c3640c4856964d4b9ac2118" class="nlkfqirnlfjerldfgzxcyiuro">270</span><span id="7a95155f1e8949d1b6a02eaae96b3484" class="nlkfqirnlfjerldfgzxcyiuro">270</span><span id="78dc5122f23a4cb09ecf6c17d440beab" class="nlkfqirnlfjerldfgzxcyiuro">270</span><span id="9d3912311b574279b6ccae19484fd844" class="nlkfqirnlfjerldfgzxcyiuro">270</span><span id="2cf183e9863a4d98ba0e7d9028e9a50f" class="nlkfqirnlfjerldfgzxcyiuro">270</span><span id="0882ae550b9f42d9aed4b77d49ad7853" class="nlkfqirnlfjerldfgzxcyiuro">270</span><span id="96ca1e63bc3640cf8e95c04a70500a87" class="nlkfqirnlfjerldfgzxcyiuro">270</span></span></td>
									<td><span name="record_yingjiaof:jiaofeijzr" title="pos||"><span id="df73145228364a189a489679f50d059d" class="nlkfqirnlfjerldfgzxcyiuro">1-</span><span id="a4fbf0dee5fd4916ac4ffb9643dc4e91" class="nlkfqirnlfjerldfgzxcyiuro">20</span><span id="761b6035534d4f04b55b8572c49586f0" class="nlkfqirnlfjerldfgzxcyiuro">-0</span><span id="66f09cc1f4f2484a9182947fea82b880" class="nlkfqirnlfjerldfgzxcyiuro">-0</span><span id="1a49d63e293f4421a6b1a0d9a8eeec90" class="nlkfqirnlfjerldfgzxcyiuro">-0</span><span id="f04f8e9c2ad74816a18db6d4b2bbbb92" class="nlkfqirnlfjerldfgzxcyiuro">19</span><span id="161b9a300e29478db7facd70030d6a91" class="nlkfqirnlfjerldfgzxcyiuro">20</span><span id="82f3f30fb2f14ca892ecd983a3ac501d" class="nlkfqirnlfjerldfgzxcyiuro">-0</span><span id="aef27d888c4849d49e231430444c7a06" class="nlkfqirnlfjerldfgzxcyiuro">1-</span><span id="cf9a9bf256754d4ab2bc9010a36bdca8" class="nlkfqirnlfjerldfgzxcyiuro">24</span></span></td>
								</tr>

								<tr>
									<td><span name="record_yingjiaof:yingjiaofydm" title="pos||"><span id="e9b3be3af76a445ca9c0afd3aa8544e0" class="nlkfqirnlfjerldfgzxcyiuro">型专利</span><span id="d80589ab838e41e9ba7ba5a934f100f9" class="nlkfqirnlfjerldfgzxcyiuro">第5年年费</span><span id="282b6adc224340598d293a29ab7ff56d" class="nlkfqirnlfjerldfgzxcyiuro">实用新</span><span id="f388310a74ce43e9b07362013adce5f1" class="nlkfqirnlfjerldfgzxcyiuro">实用新</span><span id="3ac554e2647e46409c0f687688f904df" class="nlkfqirnlfjerldfgzxcyiuro">型专利</span><span id="8ef805480d44480992bccad5038d1a4e" class="nlkfqirnlfjerldfgzxcyiuro">型专利</span><span id="3911301a95b3420188220d2e40c9bd6c" class="nlkfqirnlfjerldfgzxcyiuro">第5年年费</span><span id="fc867cbbd7ef4f608e555093df3ddd3d" class="nlkfqirnlfjerldfgzxcyiuro">实用新</span><span id="a13bb62844814e35963e468da6af4470" class="nlkfqirnlfjerldfgzxcyiuro">实用新</span><span id="6606390c870540799d0c40c4f2018c6a" class="nlkfqirnlfjerldfgzxcyiuro">实用新</span></span></td>
									<td><span name="record_yingjiaof:shijiyjje" title="pos||"><span id="081631be6a8f4c5a9823f0a59bf630fb" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="aa73abbbbfec427cb8329cc6c2c30b53" class="nlkfqirnlfjerldfgzxcyiuro">70</span><span id="54962195eac042fd811b197d0af2b76d" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="0c4b1901688c4f9f86ca79cf5129d94e" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="7cfd31455f2149dd9626df40b937b3f0" class="nlkfqirnlfjerldfgzxcyiuro">70</span><span id="715493e402e649f18567f057cca3354d" class="nlkfqirnlfjerldfgzxcyiuro">70</span><span id="71890f51ea3d444c9130f5f1283609b7" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="3a19c584175c4b7b944432398a9a2957" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="a76ccd5bfc394830ba587e5d501d3171" class="nlkfqirnlfjerldfgzxcyiuro">70</span><span id="0ca33b21d37c423ba2023c266e52e74c" class="nlkfqirnlfjerldfgzxcyiuro">2</span></span></td>
									<td><span name="record_yingjiaof:jiaofeijzr" title="pos||"><span id="ab12d36179f7405696a463deedc49805" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="41e2f43ce221424fb4fa610cc59be673" class="nlkfqirnlfjerldfgzxcyiuro">-</span><span id="dbf5c4f1c0b04a959af69ea745d0c401" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="fb52742a30b54f2b9bee1dcb3ea49061" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="6be9ae9968cc42e5bea0574288c0c906" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="210b7a237e194ecf9bb6251ad3fc88b7" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="09a25c01f866426eb926bed9fc8bcac4" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="5f681c757ba54e21aa162f9bd393bac2" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="5d124c7e94b94072a167d392ea058df0" class="nlkfqirnlfjerldfgzxcyiuro">-</span><span id="dd2ebcaf71ee49d9b4ba7f3622ac3d05" class="nlkfqirnlfjerldfgzxcyiuro">01-24</span></span></td>
								</tr>

								<tr>
									<td><span name="record_yingjiaof:yingjiaofydm" title="pos||"><span id="213f3b525cd04b33b246459d55c44863" class="nlkfqirnlfjerldfgzxcyiuro">新</span><span id="b406a6e4f2b14c5fb0cdf60b27fa5141" class="nlkfqirnlfjerldfgzxcyiuro">新</span><span id="5131e1a099ee4b8fa5f65cf25e8d680c" class="nlkfqirnlfjerldfgzxcyiuro">实</span><span id="a4d84b792f5942779ce30241b36163a9" class="nlkfqirnlfjerldfgzxcyiuro">利第6年年费</span><span id="c0cbb67d4d254ee0a2ec21945a535f6f" class="nlkfqirnlfjerldfgzxcyiuro">专</span><span id="7d0fa10fdbe74338b57e5a88d3e13a48" class="nlkfqirnlfjerldfgzxcyiuro">用</span><span id="ad5c13eb7c5b4b4daa7cd6e8d689252b" class="nlkfqirnlfjerldfgzxcyiuro">新</span><span id="f13b428afdb34bbd8e5a611b9a04dfda" class="nlkfqirnlfjerldfgzxcyiuro">型</span><span id="810da155e6bc458ba15fd12d68a904d3" class="nlkfqirnlfjerldfgzxcyiuro">专</span><span id="e4f252c8e173459f878985ffc8b8022a" class="nlkfqirnlfjerldfgzxcyiuro">利第6年年费</span></span></td>
									<td><span name="record_yingjiaof:shijiyjje" title="pos||"><span id="9d3e66444c864449a271b4c0c82fa296" class="nlkfqirnlfjerldfgzxcyiuro">360</span><span id="cc2dda3b76e14ae8ba4ed4d871afe905" class="nlkfqirnlfjerldfgzxcyiuro">360</span><span id="41d5c2447e234bd194a39da0a9743e36" class="nlkfqirnlfjerldfgzxcyiuro">360</span><span id="b94bffd019934f13b369773cdf7ee1ad" class="nlkfqirnlfjerldfgzxcyiuro">360</span><span id="246e065cbd0e43d089a7e025fe4f7b3b" class="nlkfqirnlfjerldfgzxcyiuro">360</span><span id="0ed6bfeb987a4dff962d7d2074af3a9a" class="nlkfqirnlfjerldfgzxcyiuro">360</span><span id="9d0e4e58e4984cf9a4e36a2993b25305" class="nlkfqirnlfjerldfgzxcyiuro">360</span><span id="0ee8d6d35e2641da854ed6301e44cd66" class="nlkfqirnlfjerldfgzxcyiuro">360</span><span id="f5622bc8b29b4498b6ed2b09e0bb71f6" class="nlkfqirnlfjerldfgzxcyiuro">360</span><span id="5937b26903e94787ab99664c3d099eed" class="nlkfqirnlfjerldfgzxcyiuro">360</span></span></td>
									<td><span name="record_yingjiaof:jiaofeijzr" title="pos||"><span id="26edf279fac346b2b83693991ca1095a" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="cf90ff887ca14a09b287f3583b5e2016" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="25ff702eb98b46b7ae86eec3b2cd82b2" class="nlkfqirnlfjerldfgzxcyiuro">-</span><span id="857069beda9f409d9ad99cc08e4f36d4" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="1de93a1c2a7e4f8e85067473ce24b945" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="8d9dab6704644ed19034056f3fb650b6" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="915a5aa4c1b745d78760a9feba9b6e8c" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="8605f7e488fe4f909f73c335a108b293" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="09524587f71c4903ad10c3049f2ede16" class="nlkfqirnlfjerldfgzxcyiuro">-</span><span id="feb9cf61ca5b4de9bb11dd19122f2062" class="nlkfqirnlfjerldfgzxcyiuro">01-25</span></span></td>
								</tr>

								<tr>
									<td><span name="record_yingjiaof:yingjiaofydm" title="pos||"><span id="da2062c64e8f431083a4fc7458c5b4e6" class="nlkfqirnlfjerldfgzxcyiuro">实</span><span id="7a80b54fd5fd4315aa718d67c1f79790" class="nlkfqirnlfjerldfgzxcyiuro">用</span><span id="bec9dd0f60ef4d958fe9a5d24b8e6ee7" class="nlkfqirnlfjerldfgzxcyiuro">第</span><span id="8bcc2451b719441c9536af37047fbf63" class="nlkfqirnlfjerldfgzxcyiuro">新</span><span id="0d9f900c2e82407f941d2a801637f000" class="nlkfqirnlfjerldfgzxcyiuro">利</span><span id="912ff07e73a3420caddf2bd652aa4fd0" class="nlkfqirnlfjerldfgzxcyiuro">型</span><span id="71b0fff6b473472aa20080cd6686e8ca" class="nlkfqirnlfjerldfgzxcyiuro">专</span><span id="b56aa68a04ca40ba890837d3c626240f" class="nlkfqirnlfjerldfgzxcyiuro">利</span><span id="d4364788b26448c599879494633d1217" class="nlkfqirnlfjerldfgzxcyiuro">第</span><span id="2d7c2126caeb4a209c8d01125a81b376" class="nlkfqirnlfjerldfgzxcyiuro">7年年费</span></span></td>
									<td><span name="record_yingjiaof:shijiyjje" title="pos||"><span id="4797ef0b2ec84721ac17c6edb7ac9242" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="72573192c6f64a3c91c51deec6e71497" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="7bd6d4db44e14369b5627221e2e9dd4b" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="03de8f901c0a4f95942d66736345a904" class="nlkfqirnlfjerldfgzxcyiuro">00</span><span id="8139da6b5c5e4c448200bdf509199e8a" class="nlkfqirnlfjerldfgzxcyiuro">00</span><span id="39b918119d1a4b969c8bc266606b65fd" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="d38f10d45900449d8837a5191d27abbd" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="637c916977144b99ba3d63f5756308fa" class="nlkfqirnlfjerldfgzxcyiuro">00</span><span id="0944731f5da54bd1b14ce59d3a20d27a" class="nlkfqirnlfjerldfgzxcyiuro">00</span><span id="5c7d3152283940f09e115ae4f2b73f29" class="nlkfqirnlfjerldfgzxcyiuro">2</span></span></td>
									<td><span name="record_yingjiaof:jiaofeijzr" title="pos||"><span id="7bb212e4409747c8b5ddfd8e1c07214b" class="nlkfqirnlfjerldfgzxcyiuro">2022-01-24</span><span id="191041c169c741ecbb5c5dff401ce4c6" class="nlkfqirnlfjerldfgzxcyiuro">2022-01-24</span><span id="0051508235a64c16bde8f206c1dd4ffd" class="nlkfqirnlfjerldfgzxcyiuro">2022-01-24</span><span id="821d9074a82f498ba327b4e24cd0be51" class="nlkfqirnlfjerldfgzxcyiuro">2022-01-24</span><span id="6752c8822c4347109473142af64e9538" class="nlkfqirnlfjerldfgzxcyiuro">2022-01-24</span><span id="ef025d042f6946c0b198219f0eddfae1" class="nlkfqirnlfjerldfgzxcyiuro">2022-01-24</span><span id="a6dc99435b54475999d92317cd145e6c" class="nlkfqirnlfjerldfgzxcyiuro">2022-01-24</span><span id="a6a75d5ef0c34921ac53cc7cb30c3ad6" class="nlkfqirnlfjerldfgzxcyiuro">2022-01-24</span><span id="ab5c457d5bfa49ebbc60176b4c3cccbb" class="nlkfqirnlfjerldfgzxcyiuro">2022-01-24</span><span id="066e5607cecc4961aa49481c29c4fddf" class="nlkfqirnlfjerldfgzxcyiuro">2022-01-24</span></span></td>
								</tr>

								<tr>
									<td><span name="record_yingjiaof:yingjiaofydm" title="pos||"><span id="18af78f62df349a48fbe6d768b5921ae" class="nlkfqirnlfjerldfgzxcyiuro">实用</span><span id="091109952ed145f99f324c4b7aed1d59" class="nlkfqirnlfjerldfgzxcyiuro">新型</span><span id="b4f415bff50c4c0fb492af390c148ce2" class="nlkfqirnlfjerldfgzxcyiuro">专利</span><span id="da86ee4bcc7242818a3952a5b72835b4" class="nlkfqirnlfjerldfgzxcyiuro">专利</span><span id="ea330ae438e1458b99eebf1cb235e4bf" class="nlkfqirnlfjerldfgzxcyiuro">第8</span><span id="d734895db5404d4ca453ae1dc656810a" class="nlkfqirnlfjerldfgzxcyiuro">年年费</span><span id="c69ebeb6c7de41e3b0d6cc326b70f755" class="nlkfqirnlfjerldfgzxcyiuro">新型</span><span id="fe73dfc853a944248cf6b143e9ed6fd4" class="nlkfqirnlfjerldfgzxcyiuro">第8</span><span id="8eb43ac81e4d45d9bc0b56458769c27b" class="nlkfqirnlfjerldfgzxcyiuro">新型</span><span id="6a931becd09c434faff0d4de6a4a0a0f" class="nlkfqirnlfjerldfgzxcyiuro">年年费</span></span></td>
									<td><span name="record_yingjiaof:shijiyjje" title="pos||"><span id="238d6fec169547b8a0b13e12bb17273a" class="nlkfqirnlfjerldfgzxcyiuro">00</span><span id="4edc7668d28e423aad80fb21b58d58df" class="nlkfqirnlfjerldfgzxcyiuro">12</span><span id="b16627f301b34582905de310d77f5cc8" class="nlkfqirnlfjerldfgzxcyiuro">00</span><span id="b07daf69078b42f097fdec4918e92f4d" class="nlkfqirnlfjerldfgzxcyiuro">00</span><span id="a535941fb1aa44cc817cc05a2b103e5c" class="nlkfqirnlfjerldfgzxcyiuro">12</span><span id="85486f9cdbcb4478a0fa9070fd6525c8" class="nlkfqirnlfjerldfgzxcyiuro">00</span><span id="98cb15b5f0924f47a102632ee1c1990c" class="nlkfqirnlfjerldfgzxcyiuro">12</span><span id="cc6d9bc3c5024f64943ff67835f32a11" class="nlkfqirnlfjerldfgzxcyiuro">00</span><span id="06d77c9c1935492fb4ec997fc26c6ea4" class="nlkfqirnlfjerldfgzxcyiuro">00</span><span id="8080b682b70e4a2a85d57c89f79096e4" class="nlkfqirnlfjerldfgzxcyiuro">00</span></span></td>
									<td><span name="record_yingjiaof:jiaofeijzr" title="pos||"><span id="1e5b9d93edf54da69b557cec03a214af" class="nlkfqirnlfjerldfgzxcyiuro">01-24</span><span id="d89b9ac4968a402693058ca09e4d27a1" class="nlkfqirnlfjerldfgzxcyiuro">2023-</span><span id="895e6ff6fdc04a899b1746627631c623" class="nlkfqirnlfjerldfgzxcyiuro">01-24</span><span id="6bb4504fe5134066a0871676d9e20b8c" class="nlkfqirnlfjerldfgzxcyiuro">2023-</span><span id="343cca07190648a188b135ef84c544e8" class="nlkfqirnlfjerldfgzxcyiuro">2023-</span><span id="221ff502a33f44d78e079c914eb30856" class="nlkfqirnlfjerldfgzxcyiuro">2023-</span><span id="ae8b76220332464cb48daeb44213e531" class="nlkfqirnlfjerldfgzxcyiuro">2023-</span><span id="50c180e13fb24783959cb8106951a01a" class="nlkfqirnlfjerldfgzxcyiuro">01-24</span><span id="d96baa7159334e2aa0a39d050a2c56b2" class="nlkfqirnlfjerldfgzxcyiuro">01-24</span><span id="c9785f727aea40b79292d29525fd4079" class="nlkfqirnlfjerldfgzxcyiuro">01-24</span></span></td>
								</tr>

								<tr>
									<td><span name="record_yingjiaof:yingjiaofydm" title="pos||"><span id="71bbad75621a4071bc3cfb974c3f92d7" class="nlkfqirnlfjerldfgzxcyiuro">实</span><span id="68d2e2d7553846dd8009374844c20b6f" class="nlkfqirnlfjerldfgzxcyiuro">用</span><span id="421c37f8ae79452bb84036c4a2f0771a" class="nlkfqirnlfjerldfgzxcyiuro">新</span><span id="0e21c18b1cbd464f8aa74ab062b5b269" class="nlkfqirnlfjerldfgzxcyiuro">实</span><span id="f428f7ae4dbc494bbdae93e422cd91a4" class="nlkfqirnlfjerldfgzxcyiuro">型</span><span id="523b4802455146d4ada16fc871b2d726" class="nlkfqirnlfjerldfgzxcyiuro">专</span><span id="3867242604b24f69bac31cece03f2d08" class="nlkfqirnlfjerldfgzxcyiuro">新</span><span id="d42dcbd8d27340499d0f646d5acd191a" class="nlkfqirnlfjerldfgzxcyiuro">用</span><span id="cf75a504991f4604981a11860fe576f7" class="nlkfqirnlfjerldfgzxcyiuro">利</span><span id="9233fbf022e34b1dbd04728e0cc0f664" class="nlkfqirnlfjerldfgzxcyiuro">第9年年费</span></span></td>
									<td><span name="record_yingjiaof:shijiyjje" title="pos||"><span id="49fb3e0ea584473a97d0536a765d0115" class="nlkfqirnlfjerldfgzxcyiuro">00</span><span id="fbab3231adbc46be81ac6aa0f71e8015" class="nlkfqirnlfjerldfgzxcyiuro">00</span><span id="66f6f696344e4322b7629f82fc935e90" class="nlkfqirnlfjerldfgzxcyiuro">00</span><span id="816ea56c528342b2bf73e412eb32dabc" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="9855697f0abb41d294515c83a6f1770f" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="5450cb4589c64e69837af78ca7cee586" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="99ec544ec7574943bed981ab43213e6e" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="835864dc31524f37910eb58cc1a63ec7" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="287a0476e2fe404d8839d00430c6c19e" class="nlkfqirnlfjerldfgzxcyiuro">00</span><span id="290bf3eba3b64d40bca179fde7a32595" class="nlkfqirnlfjerldfgzxcyiuro">2</span></span></td>
									<td><span name="record_yingjiaof:jiaofeijzr" title="pos||"><span id="1c3f03995e2840b9b8b97d5eaa04523b" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="568d34eb1b12412daa398cf348b534fd" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="7b75f3024d9e4ab298ce0d11ba6debac" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="24f790da8de941739bc66807908d69e1" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="e0301bde02ae4b81b835d75bfc4c8138" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="468b2975ce3641908a6fcd12a21814fa" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="f6ae518ccbac4d53b2a5bdc4a81a34ea" class="nlkfqirnlfjerldfgzxcyiuro">4</span><span id="c611cfc01add48e1a73e2ed7916cedec" class="nlkfqirnlfjerldfgzxcyiuro">-</span><span id="872c194e5af24ceb9ae78730a39c9b2f" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="42f2920779f24e37b77a1da619c5ad8c" class="nlkfqirnlfjerldfgzxcyiuro">1-24</span></span></td>
								</tr>

								<tr>
									<td><span name="record_yingjiaof:yingjiaofydm" title="pos||"><span id="4c392a3d9ca04ac0b0f0deac4bb8acb5" class="nlkfqirnlfjerldfgzxcyiuro">实</span><span id="51c1bb0ecb634b94b4c969d767ad0675" class="nlkfqirnlfjerldfgzxcyiuro">用</span><span id="162cc2a1c3714473b515a06af055ab54" class="nlkfqirnlfjerldfgzxcyiuro">实</span><span id="70c537cbe899431ea9cb575498c467d8" class="nlkfqirnlfjerldfgzxcyiuro">新</span><span id="248db544bb3d4150bbfc808af5c2e742" class="nlkfqirnlfjerldfgzxcyiuro">型</span><span id="e6bfd5abda9e43b388f729937a3e79da" class="nlkfqirnlfjerldfgzxcyiuro">用</span><span id="ccfdb68de6bb4dbb9a3efd1915642638" class="nlkfqirnlfjerldfgzxcyiuro">专</span><span id="c4a0e0866e1443ccb0e5cafd74482cf7" class="nlkfqirnlfjerldfgzxcyiuro">利</span><span id="91b54c6db3aa4c5da6e4628150ee10d9" class="nlkfqirnlfjerldfgzxcyiuro">用</span><span id="c7256d0a92784427b3c97a66086f57e3" class="nlkfqirnlfjerldfgzxcyiuro">第10年年费</span></span></td>
									<td><span name="record_yingjiaof:shijiyjje" title="pos||"><span id="05cea56383fc436983e4a1c24e7f2093" class="nlkfqirnlfjerldfgzxcyiuro">2000</span><span id="ba78cb4a13c8456683027fdb0c1c2243" class="nlkfqirnlfjerldfgzxcyiuro">2000</span><span id="6176e1b36bc34b28b1a8196e0474bedc" class="nlkfqirnlfjerldfgzxcyiuro">2000</span><span id="6f03c8f65a584323995c8a9e0e7f20c7" class="nlkfqirnlfjerldfgzxcyiuro">2000</span><span id="6fd137bb5d314cdabd7513c27a2324fe" class="nlkfqirnlfjerldfgzxcyiuro">2000</span><span id="16b1bcf65a0448d884ac62825abd29b8" class="nlkfqirnlfjerldfgzxcyiuro">2000</span><span id="df28b2d892044c5abba180064dc6b673" class="nlkfqirnlfjerldfgzxcyiuro">2000</span><span id="7d520611397e44bab7092f2b68240354" class="nlkfqirnlfjerldfgzxcyiuro">2000</span><span id="459221ee1890401dad9df8af482ea6b3" class="nlkfqirnlfjerldfgzxcyiuro">2000</span><span id="5f6a2a33c2a3446b99dc9c37aae8506d" class="nlkfqirnlfjerldfgzxcyiuro">2000</span></span></td>
									<td><span name="record_yingjiaof:jiaofeijzr" title="pos||"><span id="02b9f8a035174ef881eeb89a52901b8a" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="3275c2d120754d349c5af85469cf8dd3" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="7c528421a12c43e48d8f42571323aa6b" class="nlkfqirnlfjerldfgzxcyiuro">1-24</span><span id="3f7033463a9147168183b396987fbf83" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="12fb5f81dc234721ab191c42f3088d14" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="6548b2cd55ea4d8582af0d7fd2192e77" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="1185b141999b42718d50c3505610954e" class="nlkfqirnlfjerldfgzxcyiuro">5</span><span id="c6800983477a43d5aa0f9b7738dd8ab8" class="nlkfqirnlfjerldfgzxcyiuro">-</span><span id="e94ae2d1d2cb40ecbe68eda756fffce8" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="de6041e35d3f4ced9fd4392b96ee8652" class="nlkfqirnlfjerldfgzxcyiuro">1-24</span></span></td>
								</tr>

						</table>
					</div>
				</div>
				<div class="imfor_part1">
					<h2>
						已缴费信息
						<i id="yjftitle" class="draw_up"></i>
					</h2>
					<div id="yjfid" class="imfor_table">
						<table class="imfor_table_grid">
							<tr>
								<th width="25%">缴费种类</th>
								<th width="15%">缴费金额</th>
								<th width="20%">缴费日期</th>
								<th width="25%">缴费人姓名</th>
								<th width="15%">收据号</th>
							</tr>

								<tr>
									<td><span name="record_yijiaof:feiyongzldm" title="pos||"><span id="bd57aac0aab348909b5272acc8d32c52" class="nlkfqirnlfjerldfgzxcyiuro">用</span><span id="ad4b8086be4f4337bb966b93a06fae44" class="nlkfqirnlfjerldfgzxcyiuro">实</span><span id="305b6ff993c5494282556a01a942e862" class="nlkfqirnlfjerldfgzxcyiuro">第2年年费</span><span id="00742314ac844b6b86129b6ea8a1e70d" class="nlkfqirnlfjerldfgzxcyiuro">用</span><span id="ecab3b620b2943afa65ebfdc4a2e7a6e" class="nlkfqirnlfjerldfgzxcyiuro">新</span><span id="ddd7f02c98cc4baea675d688e085ffe7" class="nlkfqirnlfjerldfgzxcyiuro">第2年年费</span><span id="be5cbff24b7147868bf779c585846288" class="nlkfqirnlfjerldfgzxcyiuro">型</span><span id="df4c6c707e5147af97a3a289e70460df" class="nlkfqirnlfjerldfgzxcyiuro">专</span><span id="dfb78423fb42494fa85c621173906267" class="nlkfqirnlfjerldfgzxcyiuro">利</span><span id="bc45c8c8af8f4593967006a18adf85d6" class="nlkfqirnlfjerldfgzxcyiuro">第2年年费</span></span></td>
									<td><span name="record_yijiaof:jiaofeije" title="pos||"><span id="50a8b96a4a2a40138c7289f3b14308ce" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="7ba222ccdc184ca4a00dcd6863273498" class="nlkfqirnlfjerldfgzxcyiuro">80</span><span id="7de4fca72fff43ce847f85f356ad4b47" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="e8b09e61df624e349e41483a29965b54" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="f3cb6879b60d4b5c89da10b3d3263ba5" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="07f32103702a4efc9352b74fecb6b326" class="nlkfqirnlfjerldfgzxcyiuro">80</span><span id="8e4b0375ffda425e9f01c90fdd0db56d" class="nlkfqirnlfjerldfgzxcyiuro">80</span><span id="8db269e5dc9a4298acdd24c55634a556" class="nlkfqirnlfjerldfgzxcyiuro">80</span><span id="007fb6a614de440ba51e8484f2684a62" class="nlkfqirnlfjerldfgzxcyiuro">80</span><span id="2e08b50df47447a9a25903d88910ad14" class="nlkfqirnlfjerldfgzxcyiuro">1</span></span></td>
									<td><span name="record_yijiaof:jiaofeisj" title="pos||"><span id="a1ba235491214532bbbe22b1214a3591" class="nlkfqirnlfjerldfgzxcyiuro">201</span><span id="e8ab3f61a2214716a1e2ea1662627e96" class="nlkfqirnlfjerldfgzxcyiuro">2-05</span><span id="1684a0e9520544ef9571b7d795c48999" class="nlkfqirnlfjerldfgzxcyiuro">201</span><span id="091a953453b447e388bed372b7dec69c" class="nlkfqirnlfjerldfgzxcyiuro">201</span><span id="ee44ea3eff7449048678e093f4d6addd" class="nlkfqirnlfjerldfgzxcyiuro">2-05</span><span id="504bc271a66e426397f4cab56caa9a69" class="nlkfqirnlfjerldfgzxcyiuro">2-05</span><span id="98620985760c4809b75f00ad778c196a" class="nlkfqirnlfjerldfgzxcyiuro">6-1</span><span id="1f5543667a6548f1879e2bc0d8228d87" class="nlkfqirnlfjerldfgzxcyiuro">2-05</span><span id="a06f9ffde7384db78e9e0ba6a71bcc35" class="nlkfqirnlfjerldfgzxcyiuro">201</span><span id="87fe5c2cc1fa430eb7abdfe56abd080f" class="nlkfqirnlfjerldfgzxcyiuro">201</span></span></td>
									<td><span name="record_yijiaof:jiaofeirxm" title="pos||"><span id="61b263f47d024ead89acb68d21a19409" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨盈江科技有限公司</span><span id="a2e6dbb10a444e618a82c00777e7549f" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨盈江科技有限公司</span><span id="6aa218f25e87447a96085bd08b15bd5c" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨盈江科技有限公司</span><span id="12be0b3eb4344904b7320c053cb3cdf4" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨盈江科技有限公司</span><span id="0835e320ffaf49e49514e15d850a7d94" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨盈江科技有限公司</span><span id="b3a6fa9d55f241a8ab02c2e498f26f96" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨盈江科技有限公司</span><span id="93c682d8fad84d5b84807237f4697f89" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨盈江科技有限公司</span><span id="6b1758551af84e6dae000969ebf507c3" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨盈江科技有限公司</span><span id="d80b85421cb04899b54c2e7433673207" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨盈江科技有限公司</span><span id="53c5fad372d44533ab643922e53bf6be" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨盈江科技有限公司</span></span></td>
									<td><span name="record_yijiaof:shoujuh" title="pos||"><span id="73ca669a8dbd4c55a40fa30776fb515c" class="nlkfqirnlfjerldfgzxcyiuro">53266349</span><span id="d981b72530184a05b06535b76930f9ac" class="nlkfqirnlfjerldfgzxcyiuro">53266349</span><span id="8b0fd1e61ee34be297c9b59b051ecc10" class="nlkfqirnlfjerldfgzxcyiuro">53266349</span><span id="46869fd5126445ef8b792775e4043e9f" class="nlkfqirnlfjerldfgzxcyiuro">53266349</span><span id="f5c27e866abc46aa844ef92ac951887a" class="nlkfqirnlfjerldfgzxcyiuro">53266349</span><span id="81a355ec3c924880a401daab672eec6f" class="nlkfqirnlfjerldfgzxcyiuro">53266349</span><span id="83b7d43484da420bb958da7306810a5a" class="nlkfqirnlfjerldfgzxcyiuro">53266349</span><span id="dbd03bcb435a489bb8887d01c88712fe" class="nlkfqirnlfjerldfgzxcyiuro">53266349</span><span id="44019e5afe0647d599c03b0507c114f9" class="nlkfqirnlfjerldfgzxcyiuro">53266349</span><span id="3c99c02a12bb46b9a2046a80dd2f2490" class="nlkfqirnlfjerldfgzxcyiuro">53266349</span></span></td>
								</tr>

								<tr>
									<td><span name="record_yijiaof:feiyongzldm" title="pos||"><span id="e16cc9b6476c40caa83ebb04ad534910" class="nlkfqirnlfjerldfgzxcyiuro">实用新</span><span id="bc9900bbae9a4eb49e8f77158b20b844" class="nlkfqirnlfjerldfgzxcyiuro">实用新</span><span id="b8d2fefd42514f8eb9ced0d0910cb80c" class="nlkfqirnlfjerldfgzxcyiuro">型专利</span><span id="7621b944db2a40be8b8e501a398e02d1" class="nlkfqirnlfjerldfgzxcyiuro">第1年年费</span><span id="68ba8d20c8f141e49c8aef39cfb3fd49" class="nlkfqirnlfjerldfgzxcyiuro">实用新</span><span id="02044313df2f42aeb32c3d0b027117fc" class="nlkfqirnlfjerldfgzxcyiuro">第1年年费</span><span id="5f41146c18364256866f63c4254434d0" class="nlkfqirnlfjerldfgzxcyiuro">第1年年费</span><span id="5ea00d9833fb44ddbc17bf968bd4e828" class="nlkfqirnlfjerldfgzxcyiuro">型专利</span><span id="0f5aaa38383f405982edf3e238a5ffc2" class="nlkfqirnlfjerldfgzxcyiuro">型专利</span><span id="2a53bbdc27b544f2915f886e309c931e" class="nlkfqirnlfjerldfgzxcyiuro">第1年年费</span></span></td>
									<td><span name="record_yijiaof:jiaofeije" title="pos||"><span id="33e8ded2170e4098919c0cb1e2476563" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="8a5100f32db348d28350a8ae42cf9c12" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="c655a873930f4d0ba887d0451898d6cc" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="562635500f6c419fabe90ed5d0bd87ea" class="nlkfqirnlfjerldfgzxcyiuro">80</span><span id="652ccc0caa9d4c488c60dfc038f7354c" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="7bcc0e599aca481eb9e8f2dce7bbaad4" class="nlkfqirnlfjerldfgzxcyiuro">80</span><span id="ea965f01ec6b4c1bbbaa90f17566e8fa" class="nlkfqirnlfjerldfgzxcyiuro">80</span><span id="378b72415811491ab5623da8bfa2aeaf" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="7408aaa19b5246cda82899246cf21b15" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="4df612abefbe4859b185dea69baa8034" class="nlkfqirnlfjerldfgzxcyiuro">80</span></span></td>
									<td><span name="record_yijiaof:jiaofeisj" title="pos||"><span id="cb36b982b4d549119a12f0d1acff0634" class="nlkfqirnlfjerldfgzxcyiuro">2016-04-18</span><span id="315ba78246464f58815965e17f1b11db" class="nlkfqirnlfjerldfgzxcyiuro">2016-04-18</span><span id="59fd247e4dd04ddaacba91a1d8308a98" class="nlkfqirnlfjerldfgzxcyiuro">2016-04-18</span><span id="14ae088227e54aca894c9c886b52a11f" class="nlkfqirnlfjerldfgzxcyiuro">2016-04-18</span><span id="b59f5437735546b0b55f7fa6124a624c" class="nlkfqirnlfjerldfgzxcyiuro">2016-04-18</span><span id="0adafa8586f04769b5d554b602c24ccf" class="nlkfqirnlfjerldfgzxcyiuro">2016-04-18</span><span id="c9d6dfa2cf7a4c50a7aa2e20fa0d9a90" class="nlkfqirnlfjerldfgzxcyiuro">2016-04-18</span><span id="ea6efd56ddf241f3a9640006e414f735" class="nlkfqirnlfjerldfgzxcyiuro">2016-04-18</span><span id="b8e0c1722431442eb2124f823454028a" class="nlkfqirnlfjerldfgzxcyiuro">2016-04-18</span><span id="d73f927f636b4350b6045bf9806beab2" class="nlkfqirnlfjerldfgzxcyiuro">2016-04-18</span></span></td>
									<td><span name="record_yijiaof:jiaofeirxm" title="pos||"><span id="4b89900cef5f4a4a939eca6eb99c61d1" class="nlkfqirnlfjerldfgzxcyiuro">哈尔</span><span id="a01a4885eb7c4aa7a33f47de86d128b2" class="nlkfqirnlfjerldfgzxcyiuro">技有限公司</span><span id="cab56006966a4fd295410c94de31bb63" class="nlkfqirnlfjerldfgzxcyiuro">江科</span><span id="2b70166399d44dbc80211dabde32de47" class="nlkfqirnlfjerldfgzxcyiuro">滨盈</span><span id="643c178109044b5590ac7ce583d3c9cb" class="nlkfqirnlfjerldfgzxcyiuro">哈尔</span><span id="fdbd06169a4148f1b32d7d61945e7f63" class="nlkfqirnlfjerldfgzxcyiuro">江科</span><span id="317bc7360cb140e69bab52f4dffa6f1f" class="nlkfqirnlfjerldfgzxcyiuro">滨盈</span><span id="a1cce73475294a3eacbd649b946f6022" class="nlkfqirnlfjerldfgzxcyiuro">江科</span><span id="71a4842ad1ae4dac947694479203d987" class="nlkfqirnlfjerldfgzxcyiuro">哈尔</span><span id="6f47736ccbd84bcab652c7efa7aaf3ab" class="nlkfqirnlfjerldfgzxcyiuro">技有限公司</span></span></td>
									<td><span name="record_yijiaof:shoujuh" title="pos||"><span id="f7f70b99ab0749978c9cffbd50a2f3a4" class="nlkfqirnlfjerldfgzxcyiuro">4</span><span id="3e3061f26a2041769d4b3e95367a5c29" class="nlkfqirnlfjerldfgzxcyiuro">4</span><span id="674966c81dda4899ab9d19a9d1ed1ef8" class="nlkfqirnlfjerldfgzxcyiuro">4</span><span id="05dee121023e4746958c55946f56e909" class="nlkfqirnlfjerldfgzxcyiuro">4</span><span id="faef0093143b45fe857843c5ad5dbbf8" class="nlkfqirnlfjerldfgzxcyiuro">4</span><span id="3ea207056c9b4c6597907851f8371a7c" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="c2bf70983dcd407ba7427b6af45f3a09" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="b332cf1821d04d0ea56657c4ee4e1533" class="nlkfqirnlfjerldfgzxcyiuro">9</span><span id="22be6f4a922341158a2d13908164592e" class="nlkfqirnlfjerldfgzxcyiuro">9</span><span id="2ee9f94ab4ad415890c9bba4c53b6165" class="nlkfqirnlfjerldfgzxcyiuro">67</span></span></td>
								</tr>

								<tr>
									<td><span name="record_yijiaof:feiyongzldm" title="pos||"><span id="c7c1739f31c246b886e90fc5301f1010" class="nlkfqirnlfjerldfgzxcyiuro">实用</span><span id="e728995bc5e04561a55a80fd7769a02f" class="nlkfqirnlfjerldfgzxcyiuro">登记</span><span id="c9710f6f6051452a809bb9a3077648b3" class="nlkfqirnlfjerldfgzxcyiuro">新型</span><span id="51e013a32bf64a45bdc9366eca56b67e" class="nlkfqirnlfjerldfgzxcyiuro">专利</span><span id="1228bc0f2354446fb205fa1defb10b9d" class="nlkfqirnlfjerldfgzxcyiuro">实用</span><span id="d1268592808d458dab603178a9f7823a" class="nlkfqirnlfjerldfgzxcyiuro">登记</span><span id="f5943c1267f343bc936133c74dc0ee57" class="nlkfqirnlfjerldfgzxcyiuro">登记</span><span id="e1639f4d130b4aee87b5a0b4e1bc8856" class="nlkfqirnlfjerldfgzxcyiuro">登记</span><span id="cb36f246c35d446e9cb557089d6c30bf" class="nlkfqirnlfjerldfgzxcyiuro">印刷费</span><span id="a195f4f0bdce4431b9ab192c5af6191a" class="nlkfqirnlfjerldfgzxcyiuro">实用</span></span></td>
									<td><span name="record_yijiaof:jiaofeije" title="pos||"><span id="88fcd9bbd59d4f238eafe6b7d542803f" class="nlkfqirnlfjerldfgzxcyiuro">200</span><span id="05a6808612f4438191d7e0c6cec9aee1" class="nlkfqirnlfjerldfgzxcyiuro">200</span><span id="d2475c3706ac4bc0b82d958ef90ba9aa" class="nlkfqirnlfjerldfgzxcyiuro">200</span><span id="2a91b68d23514acc860c4de1420c9d0b" class="nlkfqirnlfjerldfgzxcyiuro">200</span><span id="afdc86cdcd814833885e71a1d45d9b25" class="nlkfqirnlfjerldfgzxcyiuro">200</span><span id="915a9b231ae646c2b9e77946628be036" class="nlkfqirnlfjerldfgzxcyiuro">200</span><span id="c4c4fd808d0641c09d9bf8d50015280e" class="nlkfqirnlfjerldfgzxcyiuro">200</span><span id="675c39656722493eba86a737cc364700" class="nlkfqirnlfjerldfgzxcyiuro">200</span><span id="c4bc62cb551f4a49a6c841df41557297" class="nlkfqirnlfjerldfgzxcyiuro">200</span><span id="329c7d8593344598bdcbde4aa64b7011" class="nlkfqirnlfjerldfgzxcyiuro">200</span></span></td>
									<td><span name="record_yijiaof:jiaofeisj" title="pos||"><span id="9abfcc4e5018404d995d46b919006481" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="db082854deb14330ad71390eb9e336dd" class="nlkfqirnlfjerldfgzxcyiuro">04-18</span><span id="254aeae5dc894fb3b95d5b257d611817" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="f8538bc7471547a78ac6fa48680f742f" class="nlkfqirnlfjerldfgzxcyiuro">6</span><span id="34dd478f82fc4a0290a1d426d2f62597" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="69530394690f4ccdbdd7109efb1aa9aa" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="719ba0c654364bb097bce5206d69e0c7" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="89d3d28c413d427191f4b9cc465d813b" class="nlkfqirnlfjerldfgzxcyiuro">6</span><span id="001383a7db844536bd75b4fa1c23e2d5" class="nlkfqirnlfjerldfgzxcyiuro">-</span><span id="cc98e14b960a4501bb7f8988ac6f823a" class="nlkfqirnlfjerldfgzxcyiuro">04-18</span></span></td>
									<td><span name="record_yijiaof:jiaofeirxm" title="pos||"><span id="777dacd3964a418bb059a08904626b06" class="nlkfqirnlfjerldfgzxcyiuro">技有限公司</span><span id="46af3ac9c4d94270bc02255982ae5198" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨</span><span id="ef885025485d49fcb84fd5c41a9b01af" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨</span><span id="c8fe468fd3ce40baa411e00a27d2a94d" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨</span><span id="f85a9ff509f04fb4a2c3cf9d7e70970d" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨</span><span id="157833d90bb64541a4a840137271ec4a" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨</span><span id="9fa0543066354c9caadc2d9b9134026d" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨</span><span id="b60d3282b82b4b849afa82dd31f77b64" class="nlkfqirnlfjerldfgzxcyiuro">技有限公司</span><span id="8cc7900701fb4c5d843c77b8dc844f56" class="nlkfqirnlfjerldfgzxcyiuro">盈江科</span><span id="79f525e2348c4ee2b87f7158c5cd58b0" class="nlkfqirnlfjerldfgzxcyiuro">技有限公司</span></span></td>
									<td><span name="record_yijiaof:shoujuh" title="pos||"><span id="11e918aada154f1084fe440e2e2fbe56" class="nlkfqirnlfjerldfgzxcyiuro">4440</span><span id="0aaa829805e049989d97ef5a45b27835" class="nlkfqirnlfjerldfgzxcyiuro">4440</span><span id="fee0189fb47c4895bb323224f717481b" class="nlkfqirnlfjerldfgzxcyiuro">4440</span><span id="be7830b729fa467192f6fee83def4c7c" class="nlkfqirnlfjerldfgzxcyiuro">9967</span><span id="ab0ba14688c24a8fa578ea636f8f92bb" class="nlkfqirnlfjerldfgzxcyiuro">9967</span><span id="1c70ccd2a18649459ca561a67431ed5a" class="nlkfqirnlfjerldfgzxcyiuro">9967</span><span id="22ca1bc79dea4df4b596e16847fb3f04" class="nlkfqirnlfjerldfgzxcyiuro">9967</span><span id="5ed1577fc44a484fbe9c2f3aa23f01f1" class="nlkfqirnlfjerldfgzxcyiuro">9967</span><span id="2f3b0f910fdb4b5d95db75d964073fa2" class="nlkfqirnlfjerldfgzxcyiuro">9967</span><span id="b9b2e04ea8a04a4b855bfabe53f4a181" class="nlkfqirnlfjerldfgzxcyiuro">4440</span></span></td>
								</tr>

								<tr>
									<td><span name="record_yijiaof:feiyongzldm" title="pos||"><span id="fb58837996784229bc15e0cc0587aa05" class="nlkfqirnlfjerldfgzxcyiuro">花税</span><span id="709c95ff19974b0fa7ab7ee861f331ec" class="nlkfqirnlfjerldfgzxcyiuro">印</span><span id="5d878cccf3694c019a85d7b835b7c2a6" class="nlkfqirnlfjerldfgzxcyiuro">印</span><span id="3a523c05d6104d45a4c00efa0a8e5ce9" class="nlkfqirnlfjerldfgzxcyiuro">花税</span><span id="3d397390654b4e71803bb7bfc07b1b2e" class="nlkfqirnlfjerldfgzxcyiuro">花税</span><span id="d20a49f6119e40298fd5e9804042e7e1" class="nlkfqirnlfjerldfgzxcyiuro">花税</span><span id="61a082250ff34e6eb1b54459aed41489" class="nlkfqirnlfjerldfgzxcyiuro">印</span><span id="77cbac565a894b54b57559fab7e89bff" class="nlkfqirnlfjerldfgzxcyiuro">花税</span><span id="df76cc9a60a041579b9e470971cb89da" class="nlkfqirnlfjerldfgzxcyiuro">花税</span><span id="8c2d96151ff44b5297aa58db50e40db0" class="nlkfqirnlfjerldfgzxcyiuro">花税</span></span></td>
									<td><span name="record_yijiaof:jiaofeije" title="pos||"><span id="604387c48d81463f94eeb7ed891ebfb6" class="nlkfqirnlfjerldfgzxcyiuro">5</span><span id="95e590573f5348ee8a7befa18c904db0" class="nlkfqirnlfjerldfgzxcyiuro">5</span><span id="ab2f0e038df240a4b00813e3de9d71cf" class="nlkfqirnlfjerldfgzxcyiuro">5</span><span id="d247ea8298074be1ac4d3e61cf5aeb8b" class="nlkfqirnlfjerldfgzxcyiuro">5</span><span id="a0a2b35756f942a8b4ccd8fcb3d4de9b" class="nlkfqirnlfjerldfgzxcyiuro">5</span><span id="9f7a93aa30104c37803e1712809d4f26" class="nlkfqirnlfjerldfgzxcyiuro">5</span><span id="b26d8e80eb9f40ce9c14e4953d3d46cd" class="nlkfqirnlfjerldfgzxcyiuro">5</span><span id="46161eb4a4324120bff36515af6cf5f1" class="nlkfqirnlfjerldfgzxcyiuro">5</span><span id="9985e98aa9ba4d3296c785e26f0d5b88" class="nlkfqirnlfjerldfgzxcyiuro">5</span><span id="fba13c3f1bd04140a5c4aa1b1e366548" class="nlkfqirnlfjerldfgzxcyiuro">5</span></span></td>
									<td><span name="record_yijiaof:jiaofeisj" title="pos||"><span id="fd64109476fc4a998a3251114500e430" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="702ddfbdc263447381b798a73b709de0" class="nlkfqirnlfjerldfgzxcyiuro">2</span><span id="fa69013e39644ec898e29fdfff42eee2" class="nlkfqirnlfjerldfgzxcyiuro">-18</span><span id="a50f20c5a3d744c4af1e8828f10be488" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="eae733306c7344a6a05859cbdacba040" class="nlkfqirnlfjerldfgzxcyiuro">1</span><span id="501c1cd220b741b3b3b6d18e41fc3ee4" class="nlkfqirnlfjerldfgzxcyiuro">6</span><span id="d705f52f2dbd4ebaab7a3250819394a5" class="nlkfqirnlfjerldfgzxcyiuro">-</span><span id="facafec05de84dc0b86be0b2164bef53" class="nlkfqirnlfjerldfgzxcyiuro">0</span><span id="9a605fc67d794d978c627d0d35863d94" class="nlkfqirnlfjerldfgzxcyiuro">4</span><span id="1d3b033a2c694f87a4cf0e7061a8bbd8" class="nlkfqirnlfjerldfgzxcyiuro">-18</span></span></td>
									<td><span name="record_yijiaof:jiaofeirxm" title="pos||"><span id="be1cd921a13e45c7b12662e0252823a3" class="nlkfqirnlfjerldfgzxcyiuro">哈</span><span id="c5b60b048878413486985f6dc5510567" class="nlkfqirnlfjerldfgzxcyiuro">尔</span><span id="51500dc67b714eebb1d02bb582bcca17" class="nlkfqirnlfjerldfgzxcyiuro">盈</span><span id="e71b7eaa897a44b482cef202cb72d022" class="nlkfqirnlfjerldfgzxcyiuro">科技有限公司</span><span id="de25bc9e2ad048bb8355700fb2469fc4" class="nlkfqirnlfjerldfgzxcyiuro">科技有限公司</span><span id="7f1bcd9380f7441e8cb69675215f74d7" class="nlkfqirnlfjerldfgzxcyiuro">江</span><span id="c357e91c6f714bbab9f29cd8a633dd3c" class="nlkfqirnlfjerldfgzxcyiuro">滨</span><span id="0b29d7884b6f4181a75b44ffe8be4ff0" class="nlkfqirnlfjerldfgzxcyiuro">盈</span><span id="226442f6e2d149c8916a862803ff556c" class="nlkfqirnlfjerldfgzxcyiuro">江</span><span id="069ec838950b4849ae8a868397e187c0" class="nlkfqirnlfjerldfgzxcyiuro">科技有限公司</span></span></td>
									<td><span name="record_yijiaof:shoujuh" title="pos||"><span id="a1b7279a9c28470ab238037986a5516e" class="nlkfqirnlfjerldfgzxcyiuro">44</span><span id="d38ee04fe9f943c0bf202811bb1e7796" class="nlkfqirnlfjerldfgzxcyiuro">40</span><span id="d9c0a0a6ded64209bb06dbe824efb8e0" class="nlkfqirnlfjerldfgzxcyiuro">99</span><span id="91e767c8f7664f61a3e53f945135748e" class="nlkfqirnlfjerldfgzxcyiuro">67</span><span id="af322eef4c9c43d68d73623a83f02243" class="nlkfqirnlfjerldfgzxcyiuro">99</span><span id="6da94c69ca7e4dcabeb29eafb21f0e68" class="nlkfqirnlfjerldfgzxcyiuro">67</span><span id="9e6e4a89d0f4401fbc634d213d79e7db" class="nlkfqirnlfjerldfgzxcyiuro">99</span><span id="d3893fe64667454da19588e0c4dce7f3" class="nlkfqirnlfjerldfgzxcyiuro">67</span><span id="94dd25967c43428a87252ee32350351d" class="nlkfqirnlfjerldfgzxcyiuro">40</span><span id="6d48598004df4defbf0d15051d4bf8d6" class="nlkfqirnlfjerldfgzxcyiuro">44</span></span></td>
								</tr>

								<tr>
									<td><span name="record_yijiaof:feiyongzldm" title="pos||"><span id="32cb74729e314a5ab1a7cff575cff76e" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利申请费</span><span id="9a71c8dbb6414f18bee0b71fa070f8c7" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利申请费</span><span id="50a9ee4e37c641538ec257074075d85f" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利申请费</span><span id="195e2002600e41bfb21c33e097dcf81c" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利申请费</span><span id="3096ac241664419382206b37ba7412f8" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利申请费</span><span id="1bb651a62aea49c48369cb7328b7552b" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利申请费</span><span id="dba0d89e97a044b799e63aa545453c6d" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利申请费</span><span id="902dd546353e4fe49f956516093239f0" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利申请费</span><span id="e4a8fb2a7f35447bb8525e0f248f2afe" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利申请费</span><span id="a8c3755139bd41f1808e1e0d09763290" class="nlkfqirnlfjerldfgzxcyiuro">实用新型专利申请费</span></span></td>
									<td><span name="record_yijiaof:jiaofeije" title="pos||"><span id="e15b9e2a758144eb82731a277e5d8f7e" class="nlkfqirnlfjerldfgzxcyiuro">150</span><span id="0a58962fca554823b224049ca2db448b" class="nlkfqirnlfjerldfgzxcyiuro">150</span><span id="e29d730475554fbaaa8de1bad24729ec" class="nlkfqirnlfjerldfgzxcyiuro">150</span><span id="c343f1239f6c4200885a6398505f8b13" class="nlkfqirnlfjerldfgzxcyiuro">150</span><span id="5fa56d164b9f4b70803820faa842c3ef" class="nlkfqirnlfjerldfgzxcyiuro">150</span><span id="54e9331ad303415a987ada36afc1390e" class="nlkfqirnlfjerldfgzxcyiuro">150</span><span id="2c136cccfe6748adb2029a0186153c67" class="nlkfqirnlfjerldfgzxcyiuro">150</span><span id="e9b99faf8113480980d78c24fdd43fc9" class="nlkfqirnlfjerldfgzxcyiuro">150</span><span id="daff17e265954496acb6e4ce8c2b6e0a" class="nlkfqirnlfjerldfgzxcyiuro">150</span><span id="98d7078bc4284497ab719ae4fec3f6a1" class="nlkfqirnlfjerldfgzxcyiuro">150</span></span></td>
									<td><span name="record_yijiaof:jiaofeisj" title="pos||"><span id="19e7d17acf3c415ca2053bfc63f7c505" class="nlkfqirnlfjerldfgzxcyiuro">1-07</span><span id="fc50d5f2056d4dbf81df7547924d072a" class="nlkfqirnlfjerldfgzxcyiuro">1-07</span><span id="8b53a2341c8f4721b06c9560339db648" class="nlkfqirnlfjerldfgzxcyiuro">201</span><span id="aaea0ccf3dfd44169fc422c9dbe7a456" class="nlkfqirnlfjerldfgzxcyiuro">201</span><span id="4a369a16ccc2480a8dcfbce5d8524915" class="nlkfqirnlfjerldfgzxcyiuro">6-0</span><span id="ee54173eba534ba9abfa3156f3f7a4c0" class="nlkfqirnlfjerldfgzxcyiuro">6-0</span><span id="4c1d5e59cbf34fe4b8502f9ad44de1a1" class="nlkfqirnlfjerldfgzxcyiuro">201</span><span id="5128995d89a24ba88349c68b9fcc9b23" class="nlkfqirnlfjerldfgzxcyiuro">6-0</span><span id="9db2e99fe3f343dbb583760813bd5984" class="nlkfqirnlfjerldfgzxcyiuro">6-0</span><span id="44db36be20424f6598c3b5d3d0b258d3" class="nlkfqirnlfjerldfgzxcyiuro">1-07</span></span></td>
									<td><span name="record_yijiaof:jiaofeirxm" title="pos||"><span id="0c1164f59ef94862b5453c4f1da3ce40" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨</span><span id="ab92bd56b1604b0fa9752e863ab3264a" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨</span><span id="1b272124b5ff48f183fa07ecf27c70e4" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨</span><span id="6bd14711191a49e89034cd7c4a4d65bd" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨</span><span id="b7e55781573b460f839065822ec9eeec" class="nlkfqirnlfjerldfgzxcyiuro">盈江科</span><span id="29f4fe340a764d2e8996e38f03bf74b4" class="nlkfqirnlfjerldfgzxcyiuro">盈江科</span><span id="729ad238e91e4a10babb00ccc8789702" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨</span><span id="b38bc7f515954cc98891c486069e06e9" class="nlkfqirnlfjerldfgzxcyiuro">哈尔滨</span><span id="7d7c4d5a92bf43ca8fa1282879e6037e" class="nlkfqirnlfjerldfgzxcyiuro">盈江科</span><span id="7fec72d0cd834edca78acdacdd89a8cb" class="nlkfqirnlfjerldfgzxcyiuro">技有限公司</span></span></td>
									<td><span name="record_yijiaof:shoujuh" title="pos||"><span id="9e2b09b480274741b9c2694c11d8e72c" class="nlkfqirnlfjerldfgzxcyiuro">53</span><span id="fbaa2d9bef604e7b9bf402d3e4f4c0af" class="nlkfqirnlfjerldfgzxcyiuro">7016</span><span id="f778de2fe8b74a66a7ffdc94e41c0bee" class="nlkfqirnlfjerldfgzxcyiuro">23</span><span id="12c48847b6b9403e8cdaf7257542fd1f" class="nlkfqirnlfjerldfgzxcyiuro">7016</span><span id="210b8c1220194f73abec8208242348a1" class="nlkfqirnlfjerldfgzxcyiuro">7016</span><span id="15c38bfa97ca4ce0bf33f620ed586ce1" class="nlkfqirnlfjerldfgzxcyiuro">7016</span><span id="828a4438479b4dc5bc81494b135acc8e" class="nlkfqirnlfjerldfgzxcyiuro">53</span><span id="6a45540c66c0496d9c188e1ad1b98bb6" class="nlkfqirnlfjerldfgzxcyiuro">23</span><span id="94567ae8a7a8433084db620c87e10400" class="nlkfqirnlfjerldfgzxcyiuro">23</span><span id="332a579796854dd4a3dc2016f15afb46" class="nlkfqirnlfjerldfgzxcyiuro">7016</span></span></td>
								</tr>

						</table>
					</div>
				</div>

				<div class="imfor_part1">
					<h2>
						退费信息
						<i id="tftitle" class="draw_up"></i>
					</h2>
					<div id="tfid" class="imfor_table">
						<table class="imfor_table_grid">
							<tr>
								<th width="25%">退费种类</th>
								<th width="15%">退费金额</th>
								<th width="20%">退费日期</th>
								<th width="25%">收款人姓名</th>
								<th width="15%">收据号</th>
							</tr>

						</table>
					</div>
				</div>

				<div class="imfor_part1">
					<h2>
						滞纳金信息
						<i id="znjtitle" class="draw_up"></i>
					</h2>
					<div id="znjid" class="imfor_table">
						<table class="imfor_table_grid">
							<tr>
								<th width="25%">缴费时间</th>
								<th width="25%">当前年费金额</th>
								<th width="25%">应交滞纳金额</th>
								<th width="25%">总计</th>
							</tr>

						</table>
					</div>
				</div>

				<div class="imfor_part1">
					<h2>
						收据发文信息
						<i class="draw_up"></i>
					</h2>
				</div>
			</div>
		</div>
	</div>
	<div class="ft"></div>
<span style="display: none" id="3830333b36613f3569303d6d386f3c3f28742325232226252a2b292a7e2a2b260c47431b1d17101f1d4849481a19161d0553040b0c0100020b08080e0a040e5b226d70227d2677237d2c292f7a287a2935686a616031616f6c6e6b3e3d6d6f3a03514e5b015707545d0f5d5b5a54595b4942414a401643441d1f4a1d1f4f4d46b3b4e1afe0b4b0beebb9b2eaeebebfb6a4f5a0a5aca0aea2a8a9f9ffa8a4aefc989495c188c795949ecd939ccf9f9c97d2858a8682d4d480d98a888c8e85dfdef2f2f1a1f3e9a5f7fcfffdf9fba9adade0e7e6b5e0e1eee1ecb8eee3bebbeabbd5d7dad586d2ca84d08a8fd889d9dbdb9390c0c79097cecfcbc9cace9ec5cb9b3739366562323e2b3a6d6b396d386a6926292a24202c2e71207f2923257e272b1012401545111716041a18184f14174a525354065201010600010b0a5f0d0b5c24777722722671267c657e7f742f2c7e31696037623162636c6d3b6a6c6b663d52050606525305555a0d465c095d085b40401347164610434d494f1a181b471db2b6e3e0b5e3e3b4b8bab9a7edb9e8edf6a1f6f6f1a0f0f3aca0abadfdfeaaf9c6c39b959096c2c49ccc939a80cb9e9bd689d78ad787d7d38f8d828a8adc8f87a4a3f4a7f0a7f4a5aaaba8f2fee1f6fdb6e2b4e0e4b3b4e5bee8eeb8bde5e7ed858286dadcd687d4898adfdbdd89c28e9597c0c490cdcecf9bcdc2cfc599cac665333132303636333c3d693c6d3d382373772b722d7770252d2f2d2e28792a7e421340401d151717491a1c49484e4f171c030a01560357535b0b080f0f090e0a797926717d7627757128287c2a2b7b79347d6132376063633d6b6c6f6b386a6954515b005403505f5f5f52530a545e5b14175e404d44474448481b42491f4d4bb2b1b3bbbcb7b4b7ecbbefbfbceeb7edf4a7f1bfa1a1afa1aaa8a3aef9fcfdaf9493c4c79c9497c599909dcf9cccc89dd28684d79882d5d1dc8a8b8f8988d88df1f5fba7a0fcf0f5feadacfffcaff7fce7b3e1b5e4f9b7b5e9ebbee8eaece9e686d6d6d3d1d3dfd189dddcd888888b8b93c5cbcbc4c0da919accc8ccc8cf9fcc306337376237643e6a6c6f3a686e6c3c7570262a2423273b2e7b7f227d78272616194140101743124a4c4b1b191a1a1d08095103570c0601140b0b0b5e0a5f0d737627727d7123242e7028297a7f7b7e31356135376d6e356f756f3f6d6f6a3c57045b57065c52575f5b0b5a5a5a0a5c4943171244404e131e49561f184f1b1de3e0e4b4b5e0e3b3b1edb3e9b8efefb8f6a2a4a1a6f4f5a4fca9afb7a9acadaec590c3939d9cc3c29ccb92cdcd98c89985d2d48181d08ed38e818ad8908ada8fa6a0f3f3a2a1a4a2fffdf9f8f4affbf8b5e4b3ebecb1e5b2e9eabbefe4f1bfbbd582d3d08187d184dd8bde89d8898f8ec79296c591cd92c1c0c0c8cece9fd29931326037363d67616c6b393f6e6f6a37752473252524742e79292e7f7a797f3318101247451413124d1f48481818164d51000755500404530e015b020c095a0c6c24762576707424702c7b7c7f797b763669656b6d6d63313e3a6239646d6c6d014d575a575204555e505a5809545a58484613114d4c40414c1a491f4c44471ae5e5aeb2e0e0bfb4e9b8e9b9edbaebbbf6a9f7aba1a5a0a0acaea9f8f9afaafd9995978f9cc19fc3c9cb9c9c9c99989b84d4d6828d858583888c8cdd8fdbdc89f5f1a0f5e8fcf7f2a9fcabaaf8aeffade7e5e7b7e3ede1e1e8b8e3bdb9bfbfe682d787db87c9ded1d8dc8cdc89d9d6d79694c695cdc5cf91cfca99c8cfc89fce303960313d362a37313c383f39353969272071272d2525767c282a782f2d2a26461347474114100b4e4c48124f4b181e535007510051530e5a5b0b0a58590f067173702576757075642d2b797c7b7c2c6665376b326165666861693a683b3d6854545a00510752025e455d0a545d0c5a4417164612114244494c1b1a4b4c461bb6b6e1b2e2b2bfb0b1b9a6b3eeeeedbda4a4a3f1a3a4afa3aca8f9a2a9aea8fec69295939092c0c5ce9f9987959c9cc9d68185d68386d7848c8b8ad8ddd9dad9f2a3a6f5f1f7a7a6fcafaefbe0faffade0b7b4b5e2b7e2e0ebedede9bdbcecefd0d9d28080d3d0dfde8cd2888dc18cdac69093c5cc94c6c39b98cecb9e9cc6c630393134603665313a3f383f3c6b226b24222427232d2e752a2f2e2f247e2b261919151a101c12111b1a4e1a1e1c190302550550060404015b585f59085c0c0f79227a27747477757d28727a2e7e79797c6660666366676e6a3a6c3d6a693f6c03585300515402020d0a5c0e5b5c5a56475d4511104312431c1b4e4f194c4a4cb6b8e0b6b2b7b1b5bab8efb9e9b4eaeba4f3beaba5a6aff3f9aff8aeffa8fbabc395969b969596c5cccf9f9b959c9796d589d39f858c87878c88d98a8a84dd88f4f0a7a0a6a7f3a4fdadacadf8fdffacb5e5b1e5f8e4eeb6beeee2bdeaefbab9d3d5db82d0dd80858ddf8edcdad58cdac9c3c39291d9c6cec9c8cac2c5c8cc9a64303636623c3f613b3b3e68386f396e75752377212c3a7379212c7e79297c7c43161017161d171f491a131e1e4c1b4d07030a000157021b5d5809080c5c5b0b7379277270707e2571702f2e2e2b7f2c3263616631613431743d6d686865676a04035757545102530b085e5e5f0c0b5e14124446424d474719554e1e181e4949b6b9e6b1bce0b2b5bbe8ebefb4bde8eda2a0f0a6acf1a3affcffb6abaaf9a9a8c398c1929d969393919bccc998c8cd968986d4d08683d581ddd88e97d88587ddf9a0a1f7fdf3fea6fcf9f8fdf5fefefae8b2b3e3edb0e2b3eaeebbeaf0e5e7ea85d78485d2838284d8dd8bd3d5d48cdec7c5c4c5c6c2c0c4c99accc9cfd1c9ce626363673330303539683e3b3b3c6c6c237274712d2222742b7f2329782a322918451046164111121d1a121f1a494a1700010b0003010e030c5a080b5e0b5813747373207772207f292c7d7278787c2d3269666367633563396b3c6b6b6a6f3e4c0756515c0351060d5d0e090f59575b12131612114c45124c4b481818444f1eb4adb7b1b7e7b2bfb8bbbebeb9bcbab9f4a5f3f7f5a4a0f1fba1adaafeaffaa892978ec0c29293c69d999e92959cc89b8681868a8c84d78689818c8bdad88b88f6a7f5effdf7f5f4aeabacfbfeffabfce4b3e3b7b6b1e6e3efebe2beecbebdef86d7d4d7c8dcded2dddfd3dc8add8f8d92c5c397c6ccc2c2c9cc99c3cf9cc8993136353362293f3e6d6a3f3f38686d382526262a202674727c20222a7d7f2a2c1210114612400a15101e4b1b181a184a025757070401520f000a035f0c0d0a0c70227420757c236b7d7f722f7f792b2d613363616064643339386962643e386c5459005657510003445e085c590b5d5f4245164a114117154a404218194d1a4eb1e3e3b5e0e0e4e6eba5b8bfeabab7bff4f0aaf7f1aca2a6afaaa3f9ffaba8a790969b939cc1909ecd9886cd9acccb9a8189d1d0d6d4d583dc8c89d98edc8bdda4a2f6a2fcf4a7f4fcacabe7affbfffeb3b7b1e3e5b4b2b3ece1bfeabdeaedbad28486d4ddd4d0848d8d8f88c0d5d9dd93c0cbc791c09791cacd999e9ec49f9a373935303464353e6b3068396a213a3d76232b212422212e7e2b2e7e2f2a7c28174013474513171e4b1c4b4f144e021b53020b015506520e5b580a0f5d5e0e5d70277227212425732a2b722a2f2f7b6365603162363766323b3b6c68683f676b0255015a525c02505e5e0b0f5c5b595a5c464210414641141a1c424245494d4ee5e0bbe0e6b0b1b2bcb0b2e8b8bbb9eba8bda0a7acf1f4a2acadf8f9aff9aaae9591c0c1c2c69e9790c8cc9ecf9fcb9884839ed0d7d3d2d58e81dede8adfdc8ba4a3a0faa5f6a3a1acf8f3faf9fbfafde6e2eaffb7e1b7e7bde9e2edeab8efebd4d2818086d583d28b888c8fdbd9dad7c29294c4d896c1c5cdcf9ecb9dc4ccc83835363133673564313e6b3d3a3d363976242576273920262f2f7f2a7e2e287d43121641161d441649111b121a481e1b0705505650561a045e0e0a080f09080c217873777374707f797179297f74787668663431326d657b696b3c39693b666e0402505050525456090b5b525d0e5a5d1642424b4c114743544f4f4f441f4c1ce4b4b7e6e5b1e2bfbdb1b8eaeabdeab8f6f5a0a2ada7f3a0afb5abaaa4a8fcae94909b9a9dc792959f9892cf999dcd9c8581878585858f828cdc96d88a858e8ff9f9f1f7f3f2a7f3fbadffaaadfda8f6b2e6e5e0ecb1b2efb9bbe2f7b9e4eabe85d386d280d78585dcd98f888e88d8d7959593c4c1c390919e9a9fc3d0999bc930353366373062346e3d696e6834686b24222b21762c20727d212c2e2e317f7b14431a131c1344421c4f1e181f1a4c4d090704510d0657070e5f5b5e0809120f70767671777472262b717e7f2e7b2c776660606a3663333660386b3e6b6d3a730502030157075055580b5852585e0f0911474716161312144c18481e4b1c481aace3e7b6e7e7e0e1babde8bcbdb9b9b7a6a9f0f5a3a2aff4ada1afa3a8abaca7988dc6c590c690c49f999dce999c9a98d1d78b84d586d7858080df8c8c89888fa4a7eea7a2a7f1fffcfbf9adaef9fcfbe9e5b4b2ece0b5e1eae8ebecefe4eee9d2d7d5cf8686d2d28bd189d38d8bd689c4c4cbc0cdc3c1c7c8cf9bcac49c9a993834663528303666306b333d6d396f3d71252222272d75202a21237d2f7f2f2b13111a4041091e421c4b1a181b1848495450060101500f51080859020c5b5a5b7025207672216a7771782b72797e7a7a633366676330656f603b3f3f6f6a6c3d57050700525c054b51515c595c54565a47474210404d464e1a4e4f1d4c4d1f1bb7b6bae0b5bcb0e6a4b8ecbeb9b9bdb9a6a6f3a5a1a1aef1a9a1ada2f9affcfc90c59a91969dc29f9f859c9ace9f989cd68585d7848782d2d9dd8282dddedc89f8a5f0f2a5f4fff3f8f0e6aff5f5ffade7e3e7e0e4e4eee3b9e9efb9ecebebecd583d5d5ddd6d681d18889c7dad58c8ec895c0c397cd90c6ccc89fcfc59ec69e6567313a676364346e6d3e32203d683a717073202c262e247e2d2a2e25252c7a4447114616161e461d4f4c481e011c4e05025051505604005a0c0e0f5a0f070e75277a7b72207577712a73787d28627731646363643365653c3b696f64396c67535452025c0403535a0a0c520f5c5c4315104b45411346461d1a4c19481e4f1de2e3e3e2bdb5e0b6bfbcbcbde9b5e8eebcf3aaf6a4f6a7a0aaabaea8ada9aaadc5c390929691c09f9a9a9e9e989d9c97d19d86d18c8c8f8788dadfdd89db8adef4a0fbf0fda0a5a6feaca8f2f5aef8feb4e0fee1b6e2e6e6eeefe9e2e5b9eaeb848381dbd4d7d7d68c88888f89dedc8b95c5c5df95c495949dcec9cfcbc8ccc634603166656664633e3d33693539386926212021382370232f2e292d7f7e7c7b18154040454710121a4a1d4e4a4c194e51570152561950005e0e0a5905045f5d7076767a7d727e24712a2c2d2e297b7f3163346035617a616f6d636d6a3e666e040503575c5c5f060a500e5a550c570b4114164211134e5b1e181f1d4c4d474cb1b5b1e1b0b0e0e2b0bcbdb3b8beedbaf1f5a7f7f6f7f0afb4faa8f9faaaaea69892c6c0c0919690cac89d9f9e9acc99d1d78686d286d7878195d8888f8fddd9f1f9f0f2a0f5f2a3f8acabfefafbfbf8b3e5b7b6e0b0e7e2ebeaf6e9eebfbbe986d583dad6d7d5d3d9d8dfd38ddf8adec3c8c2cbc5c3c2c2c1cb9fd7ce989bc666383662663167633c383f33353d6d3672737327772025752e282c2e307e297c1116111a421617441a1d1c491415184a09015450010606065e080a0a0c115d0677707225722370777d787e7e7e2c767f6933306a356666606f6f6e633e6e726a51045252570455550a0f5c5f0d595b0d14124b4042431314194c4c194a4a1b53e5b0b4b0bde3b2e3b9babae9b8ecebeaa8a6f0a6f5a5f4a3fda8f8f8a4a5aba98cc2c09092c394939eca999ec8999a99d588d1d1818081878080de8ddf8e8edda6edfafba2a6a2feaaabaefef5a9faa9e2e2eab6b5b3b3e1baeebeeee8efe6efd387ced0d08182d3dfd18cd3de8b8ddb91c1c0cac494c793cccbcc9fce9bc8cd3538352f323c3334383a333f3a343e6924727177767172202929237e7a7f2f7e411843420812171e4a481a481a181a1c06055051040c01555b5c0f090c0b5a097924722073697e7e2c7a2e79742e7a7e6335666163646f663e6d38623f3e6a6955055a5257074a57585859535f0c590b12494647414640151c4e4f19481b1f4ee3b3b1e6b6e1b3abebeab3b3e9bcbaeda9a7a2f2a0a0a6a6fafbadfda4a4a6a7c1c294c59c9795c684ca92cdc9999897d6d581d0d18186d5d9d88e8a8dd88e8fa1f3f5a7f6a4fff3ace5f2a8affaf7ffe0e6e2e2b2b7e2b4edbde2efefbee9e882d98680dcd1d281dddfc6dcd58bdbddc594c0c0c0cd95c39d9cc899c4ca98c831343a6031666232306b3a273c6c6f6e28232b2b242073272c2023232579272845471742101044151f11191e004f4b180802025103070f51590d0c0c0d040c59762727267c7622222e7d297c2f617b2b68666a30373630646e606e386c6c673e58540654065d55520a5e09590d5b425c14424b44474c46414d4d184f194a4f47b0b2e0e1b3e7e0e4b8bee8baeebfeba3a9a4f7a6ada5a3a0abffafa8a8a5fbfa98c095c1c1c3c79690ca939b98c9cc9f9c868281d0d1d0d5dcda888d8f898a88f3f9f3a1f3fcfea6fffaa8fcfcf4aaaae0fdb3e6e4b3e4e7bbecbbe8b8eaeaeb83d58385d580dedfdad18cdadc8f8bdbc8c9de969590c1c4cbcacacd9fcacdcb3460346234303e32316a686f6d6e6c6e2025223f21252774297a7e292e2d7c2814104010461644114c18124e181c484c03545707185101070d5f0f095a0f5a5d2475272125242470297a787e7c757f7663686632617930363b383c3e3f6d6b3b055956070755045f5e0b0f5b0e5f5f594413171541465a4e194f4a4e1a1e4848e4b6bbb7e0bcb1bfebbfb8bce8bdeabca5a9a4a0f0aca2bba9fda9f9acaeadfe92c2949a90c39e90c99dc9cd9cc8999f8680d38bd6d7d28f94dbdf8adfd9878df1a0f3f0a1f1f3a4ffabfbf9fafbfcaae0e3e7e1ece7e5b6ebf5b9eebeebeebdd0d5dadbd3ddd2d6dbddd2ddd5d5db89c69591c6c1c4c6c2ceced698cfc8c99a39306135623237336a6b6b69356b3c3673752a72222625737c2a79372c7f2c2644161a1b104710411c18121a4d1a1b4d04055455510d54520c5f5c0b100f0c097475702572207423797d732874747f79316964616c6565313e6c6f6d3f716e695904015b575d5f52580b5e5358540f0a48104a454c464f401d48424c1f4d521eb1e3b5b1b3bce7beebbbb2bfbbbdefeda2a2aaa3a7a2afafaef8afaeadabfbb3c4929ac6c19592c1cd90cc92989ecd9fd2d78083868d8786dadb8bde8b8a8789eca5fba0f4a4f6a6feadafaffaf9fcffe9b3b0e3e2b1b4b2e0ebeebebabfe6bad0cddbd281d2d0d08bd18cdcdadbda89c6c093c091c0c591c1cdcfcacfc8c9cb38642e30366664303c3e3832693e3f3b71247371257421747e7f2f2c297e78791717470f40444041191e4f191a18171a04050b05555654015d0d595e045e0c5d76247222687d24727b287878787c2d7736656561653766613b606f6d6c6e6d66040354575c495f030a5b0f52550b0b5c1642464010171442404a4d4d4c454f4ce2e5b7babcb1aab3bcede8b8baefebbda0a5a0a7f2a3a3aea0faa9f9a9f9adfb90c390969cc1958b99cb989c9e9c9c9bd284d4d5808dd086808adcda8c8adbdca6f3f5a0f3f5a3f3e4feaefcaff9aafab1e8e0b1b2e1e5b4b9e1bcbaedefe6edd8d6db86d2d5d5d08dc5dd8d898ed9dd94c19197ccc6c2929c9a9bccc49c9d9b616266673c3c673f6b6b2632693f6c3f2973262b242721232f2d2b79257e2c291915411215411e421f1b49074a1a191754540055510d54000c580c0d5d0a585924227b7721717724782b2f2e607f7f7f3269316266676666616d3c6c6f3c3c3a035950535c5752555b5d520a5d41">版权信息：国家知识产权局所有</span></body>

</html>
HTML;
        return $html;
    }

    public function queue()
    {
        $redis = Yii::$app->redis;
        $redis->del('patent_l');
        $patents_list = Patents::find()->select(['patentApplicationNo'])->where(['<>', 'patentApplicationNo', ''])->asArray()->all();
        foreach ($patents_list as $patent) {
            $redis->rpush('patent_l',$patent['patentApplicationNo']);
        }
//        print_r($redis->lrange('patent_l',0,-1));
    }

    public function actionQueue()
    {
//        $patents_list = Patents::find()->select(['patentAjxxbID','patentApplicationNo','patentApplicationDate'])->where(['<>', 'patentApplicationNo', ''])->indexBy('patentApplicationNo')->asArray()->all();
//        var_dump($patents_list);exit;

//        $this->queue();
//        print_r(Yii::$app->redis->lrange('patent_l',0,-1));
        echo Yii::$app->redis->llen('patent_l').PHP_EOL;

//        $a = Patents::find()->where(['not in', 'patentAjxxbID', (Yii::$app->db->createCommand('SELECT distinct patentAjxxbID from unpaid_annual_fee')->queryAll())])->asArray()->all();
//        print_r($a);

//        Yii::$app->redis->del('patent_l');
        $this->stdout('OK');
    }

    /**
     * 解析页面,获取未付款信息
     *
     * @param string $html
     * @return array
     */
    public function parseUnpaidInfo(string $html)
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);
        $last_span = $crawler->filter('body > span')->last();
        if (!$last_span) {
            $this->stdout('Error: empty node'.PHP_EOL.'Source code: '.$html);
            return [];
        } else {
            $key = $last_span->attr('id');
        }
        $useful_id = array_flip($this->decrypt($key));

        $trHtml = $crawler->filter('table[class="imfor_table_grid"]')->eq(0)->filter('tr')->each(function (Crawler $node) {
            return $node->html();
        });
        $result = [];
        foreach ($trHtml as $idx => $tr) {
            if ($idx !== 0) {
                $trCrawler = new Crawler();
                $trCrawler->addHtmlContent($tr);
                $type = $trCrawler->filter('span[name="record_yingjiaof:yingjiaofydm"] span')->each(function (Crawler $node) use ($useful_id) {
                    if (isset($useful_id[$node->attr("id")])) {
                        return $node->text();
                    }
                });

                $trCrawler = new Crawler();
                $trCrawler->addHtmlContent($tr);
                $amount = $trCrawler->filter('span[name="record_yingjiaof:shijiyjje"] span')->each(function (Crawler $node) use ($useful_id) {
                    if (isset($useful_id[$node->attr("id")])) {
                        return $node->text(); // 默认的else{return NULL}
                    }
                });

                $trCrawler = new Crawler();
                $trCrawler->addHtmlContent($tr);
                $date = $trCrawler->filter('span[name="record_yingjiaof:jiaofeijzr"] span')->each(function (Crawler $node) use ($useful_id) {
                    if (isset($useful_id[$node->attr("id")])) {
                        return $node->text();
                    }
                });

                $result[] = [implode('',$type), implode('',$amount), implode('',$date)];
            }
        }

        return $result;
    }

    /**
     * 保存未缴费用信息
     *
     * @param array $result
     * @param $ajxxb_id
     * @param $application_date
     */
    public function saveUnpaidFee(array $result, $ajxxb_id, $application_date)
    {
        if (!empty($result)) {
            foreach ($result as $item) {
                preg_match('/\d+/',$item[0],$matches);
                $fee = new UnpaidAnnualFee();
                $fee->patentAjxxbID = $ajxxb_id;
                $fee->amount = $item[1];
                $fee->fee_type = $item[0];
                if (!isset($matches[0])) {
                    $fee->due_date = str_replace('-','',$item[2]);
                } else {
                    $fee->due_date = ($matches[0] + (int)substr(trim($application_date),0,4) - 1) . substr(trim($application_date),-4);
                    $fee->fee_category = UnpaidAnnualFee::ANNUAL_FEE;  // 设置分类为年费
                }
                if (!$fee->save()) {
                    print_r($fee->errors);
                    exit;
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
        ];
        return $ua[mt_rand(0, count($ua) - 1)];
    }

    /**
     * 各种测试使用
     */
    public function actionTest()
    {
//        $patent = Patents::findOne(['patentAjxxbID' => 'AJ161361_1361']);
//        $unpaid = $patent->generateExpiredItems(90,false);
//        print_r($unpaid);
//        $count = UnpaidAnnualFee::updateAll(['status' => UnpaidAnnualFee::PAID, 'paid_at' => $_SERVER['REQUEST_TIME']],['in', 'id', array_column($unpaid,'id')]);
//        echo $count;
    }
}
