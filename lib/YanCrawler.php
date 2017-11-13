<?php
/**
 * Guzzle封装类
 * 
 * Guzzle文档: http://guzzle-cn.readthedocs.io/zh_CN/latest/quickstart.html#id6
 */
namespace app\lib;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Yii;

class YanCrawler
{
    // 爬虫错误日志key
    const error_log_key = 'crawler:error_log';
    // 爬虫配置
    private $config;

    public function __construct(array $config = array())
    {
        $defaults = array(
            'concurrency' => 1, // 并发线程数
            'is_init' => 0, // 是否初始化爬取队列
            'log_prefix' => 'crawler', // 日志前缀
            'redis_prefix' => 'crawler', // redis前缀
            'timeout' => 10.0,    // 爬取网页超时时间
            'log_step' => 50, // 每爬取多少页面记录一次日志
            'base_uri' => '', // 爬取根域名
            'interval' => 0, // 每次爬取间隔时间
            'queue_len' => '', // 队列长度，用于记录队列进度日志
            'queue_log_step' => 5, // 间隔多少百分比记录一次入队列进度日志
            'retry_count' => 5, // 失败重试次数
            'requests' => function () { // 需要发送的请求
                // 示例代码:
                /*
                $base_url = 'http://www.example.com/p/';
                for ($i=0; $i < 100; $i++) {
                    $request = [
                        'method' => 'get',
                        'uri' => $base_url.$i,
                        'callback_data' => [ // 回调参数
                            'page' => $i,
                        ],
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
                        ],
                    ];
                    yield $request;
                }
                */
            },
            'fulfilled' => function ($result, $request) { // 爬取成功的回调函数
            },
            'rejected' => function ($url, $msg) { // 爬取失败的回调函数
            },
        );

        // 合并数组
        $this->config = $config + $defaults;
        $this->redis_prefix = $config['redis_prefix'];
    }

    /**
     * 执行并发爬取
     */
    public function run()
    {
        // 线程数
        $concurrency = $this->config['concurrency'];
        // 判断是否需要初始化队列
        if ($this->config['is_init'] == 1) {
            $this->log('初始化队列 start');
            // 清除旧的redis数据
            $this->redis_del($this->redis_prefix.'*');
            $last_log_process = 0;
            foreach ($this->config['requests']() as $key => $val) {
                $request = $this->get_request($val);
                $request = json_encode($request);
                // 利用sets数据结构来避免添加重复请求到队列
                if (Yii::$app->redis->sadd($this->redis_prefix.':sets', $request))
                    Yii::$app->redis->lpush($this->redis_prefix.':queue', $request);
                // 记录队列进度日志
                if ($this->config['queue_len']) {
                    $cur_process = round(($key+1) / $this->config['queue_len'], 2)*100;
                    if ($cur_process-$last_log_process >= 5) {
                        $this->log('初始化队列:'.$cur_process.'%, 队列长度:'.Yii::$app->redis->llen($this->redis_prefix.':queue'));
                        $last_log_process = $cur_process;
                    }
                }
            }
            // 初始化剩余爬取的页面总数(以此来判断是否爬取完成)
            $overplus = Yii::$app->redis->llen($this->redis_prefix.':queue');
            Yii::$app->redis->set($this->redis_prefix.':overplus', $overplus);
            // 记录爬取页面总数(以此来判断爬取进度)
            Yii::$app->redis->set($this->redis_prefix.':total', $overplus);
            // 清除sets数据
            Yii::$app->redis->del($this->redis_prefix.':sets');
            $this->log('初始化队列 done');
        }
        // 断点续爬逻辑
        else {
            // 把上次请求中(requesting)的请求入队列
            $requesting = Yii::$app->redis->hgetall($this->redis_prefix.':requesting');
            foreach ($requesting as $key => $val) {
                Yii::$app->redis->rpush($this->redis_prefix.':queue', $val);
            }
            // 初始化剩余爬取的页面总数(以此来判断是否爬取完成)
            $overplus = Yii::$app->redis->llen($this->redis_prefix.':queue');
            Yii::$app->redis->set($this->redis_prefix.':overplus', $overplus);
            // 清除请求中的数据
            Yii::$app->redis->del($this->redis_prefix.':requesting');
        }
        $this->log('爬取 start');
        $begin_time = microtime(TRUE);
        // 统计数据
        $this->stat_data = [
            'success_count' => 0,
            'request_error_pages' => 0,
            'save_error_pages' => 0,
        ];
        // 实例化guzzle
        $client = new \GuzzleHttp\Client([
            'timeout' => $this->config['timeout'],
        ]);
        // 判断如果没有爬取完，则重试
        while (Yii::$app->redis->get($this->redis_prefix.':overplus')) {
            // 获取请求闭包函数
            $requests = function () use ($client) {
                // 记录请求下标。对应回调函数里的$index
                $i = 0;
                while (($request = Yii::$app->redis->rpop($this->redis_prefix.':queue:error')) || ($request = Yii::$app->redis->rpop($this->redis_prefix.':queue'))) {
                    // 记录正在进行的请求(用于成功回调函数内可获取该请求，和断点续爬)
                    Yii::$app->redis->hset($this->redis_prefix.':requesting', $i, $request);
                    // 返回请求
                    $request = json_decode($request, true);
                    yield function () use ($client, $request) {
                        $options = $request;
                        return $client->requestAsync($request['method'], $request['uri'], $options);
                    };
                    $i++;
                }
            };
            $config = $this->config;
            // 爬取网站数据
            $pool = new Pool($client, $requests(), [
                'concurrency' => $concurrency,
                'fulfilled' => function ($response, $index) {
                    $this->stat_data['success_count']++;
                    // 获取请求数据
                    $request = Yii::$app->redis->hget($this->redis_prefix.':requesting', $index);
                    // 获取请求结果
                    $result = $response->getBody()->getContents();
                    // 调用爬取成功回调函数
                    $request = json_decode($request, true);
                    try {
                        $callback_res = $this->config['fulfilled']($result, $request, $response);
                        // 判断回调函数状态
                        if (isset($callback_res['status']) && $callback_res['status'] <= 0) {
                            /* 记录爬取错误日志 */
                            sort($callback_res['error_resaons']);
                            $error_log = [
                                'prefix' => $this->redis_prefix,
                                'request' => $request,
                                'error_type' => 'save_validate',
                                'reason' => $callback_res['error_resaons'],
                                'error_time' => time(),
                            ];
                            Yii::$app->redis->lpush(self::error_log_key, json_encode($error_log));
                            Yii::$app->redis->ltrim(self::error_log_key, 0, 9999);
                            /* /记录爬取错误日志 */
                        }
                    } catch (\Exception $e) {
                        /* 记录爬取错误日志 */
                        $error_log = [
                            'prefix' => $this->redis_prefix,
                            'request' => $request,
                            'error_type' => 'crawler_exception',
                            'reason' => $e->getMessage(),
                            'error_time' => time(),
                        ];
                        Yii::$app->redis->lpush(self::error_log_key, json_encode($error_log));
                        Yii::$app->redis->ltrim(self::error_log_key, 0, 9999);
                        /* /记录爬取错误日志 */
                        $this->stat_data['save_error_pages']++;
                    }
                    /* 获取总成功爬取页面数 */
                    $total = Yii::$app->redis->get($this->redis_prefix.':total');
                    $overplus = Yii::$app->redis->get($this->redis_prefix.':overplus');
                    $success_count = $overplus == 0 ? $total : $total - $overplus;
                    /* /获取总成功爬取页面数 */
                    if ($success_count % $this->config['log_step'] == 0) {
                        $process = round(($success_count / $total), 2)*100;
                        $this->log('爬取进度:'.$process.'%, 已爬取:'.$success_count.'个页面, 剩余页面:'.$overplus);
                    }
                    // 减少剩余爬取页面数
                    Yii::$app->redis->decr($this->redis_prefix.':overplus');
                    // 在请求中hash中删除该请求
                    Yii::$app->redis->hdel($this->redis_prefix.':requesting', $index);
                    // 删除该请求失败重试次数
                    Yii::$app->redis->hdel($this->redis_prefix.':retry_count', json_encode($request));
                    // 爬取时间间隔
                    sleep($this->config['interval']);
                },
                'rejected' => function ($reason, $index) {
                    // 获取请求数据
                    $request = Yii::$app->redis->hget($this->redis_prefix.':requesting', $index);
                    // 在请求中hash中删除该请求
                    Yii::$app->redis->hdel($this->redis_prefix.':requesting', $index);
                    $this->stat_data['request_error_pages']++;
                    $error_log = "失败请求:{$request}".PHP_EOL;
                    $error_log .= "失败原因:{$reason->getMessage()}".PHP_EOL;
                    $this->log($error_log, 'ERR');
                    // 获取请求重试次数
                    $retry_count = Yii::$app->redis->hget($this->redis_prefix.':retry_count', $request);
                    // 判断失败次数超过限制，则跳过该请求
                    if ($retry_count >= $this->config['retry_count'])
                    {
                        // 调用爬取失败回调函数
                        $this->config['rejected'](json_decode($request, true), $reason->getMessage());
                        /* 记录请求错误日志 */
                        $error_log = [
                            'prefix' => $this->redis_prefix,
                            'request' => json_decode($request, true),
                            'error_type' => 'request_fail',
                            'reason' => $reason->getMessage(),
                            'error_time' => time(),
                        ];
                        Yii::$app->redis->lpush(self::error_log_key, json_encode($error_log));
                        Yii::$app->redis->ltrim(self::error_log_key, 0, 9999);
                        /* /记录请求错误日志 */

                        // 减少剩余请求页面数
                        Yii::$app->redis->decr($this->redis_prefix.':overplus');
                        // 删除该请求失败重试次数
                        Yii::$app->redis->hdel($this->redis_prefix.':retry_count', $request);
                    }
                    // 失败次数没超过限制，则重试
                    else
                    {
                        // 爬取时间间隔
                        sleep($this->config['interval']);
                        // 把请求重新重新放入队列
                        Yii::$app->redis->lpush($this->redis_prefix.':queue:error', $request);
                        // 记录重试次数
                        Yii::$app->redis->hincrby($this->redis_prefix.':retry_count', $request, 1);
                    }
                },
            ]);
            // 等待爬取完成
            $promise = $pool->promise();
            $promise->wait();
        }
        $take_time = number_format((microtime(TRUE)-$begin_time), 6);
        $end_log = "爬取 done".PHP_EOL;
        $end_log .= "花费时间:".$take_time.'s'.PHP_EOL;
        $end_log .= "线程数:".$concurrency.PHP_EOL;
        $end_log .= "本次爬取页数:".Yii::$app->redis->get($this->redis_prefix.':total').PHP_EOL;
        $end_log .= "请求失败次数:".$this->stat_data['request_error_pages'].PHP_EOL;
        $end_log .= "解析失败次数:".$this->stat_data['save_error_pages'].PHP_EOL;
        $this->log($end_log);
        // 清除redis数据
        $this->redis_del($this->redis_prefix.':*');

        return ['status'=>1];
    }

    /**
     * 获取请求
     * @param  string|array $request 请求内容
     * @return array          格式化后的请求
     */
    protected function get_request($request)
    {
        // 如果请求内容为字符串/数字，则把字符串/数字当作url转为get数组请求。
        if (is_string($request) || is_numeric($request))
        {
            return [
                'method' => 'get',
                'uri' => $this->config['base_uri'].$request,
            ];
        }
        elseif (is_array($request))
        {
            $request['uri'] = $this->config['base_uri'].$request['uri'];
            return $request;
        }
        return false;
    }

    /**
     * 记录爬取日志
     */
    protected function log($message,$level='INFO',$type='')
    {
        $message = $this->config['log_prefix'].$message;
        Yii::info($message, 'crawler');
    }

    /**
     * 删除redis数据(支持通配符删除)
     */
    function redis_del($keys)
    {
        $del_num = 0;
        foreach (Yii::$app->redis->keys($keys) as $key => $val) {
            $del_num += Yii::$app->redis->del($val);
        }
        return $del_num;
    }
}