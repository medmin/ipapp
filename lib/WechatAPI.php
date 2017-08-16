<?php
/**
 * User: Mr-mao
 * Date: 2017/8/16
 * Time: 20:19
 */

namespace app\lib;

/**
 * Class WechatAPI
 * @package app\lib
 */
class WechatAPI
{
    CONST SCOPE_SNSAPI_USERINFO = 'snsapi_userinfo';
    CONST SCOPE_SNSAPI_BASE = 'snsapi_base';

    private $appId;
    private $appSecret;

    public function __construct($appId, $appSecret) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }
    /**
     * #############
     * ## 网页授权 ##
     * #############
     */

    /**
     * 获取授权Code
     * @param $redirect_uri
     * @param $scope
     */
    public function authCode($redirect_uri,$scope){
        $appid = $this->appId;
        $redirect_uri = urlencode($redirect_uri);
        $response_type = 'code';

        $state = "STATE"."#wechat_redirect";
        $authUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appid&redirect_uri=$redirect_uri&response_type=$response_type&scope=$scope&state=$state";
        Header("Location: $authUrl");
        exit();
    }

    /**
     * 获取授权AccessToken
     * @param $code
     * @return mixed
     */
    public function authToken($code){
        $urlParams["appid"] = $this->appId;
        $urlParams["secret"] =$this->appSecret;
        $urlParams["code"] = $code;
        $urlParams["grant_type"] = "authorization_code";
        $bizString = http_build_query($urlParams);
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
        $res = $this->httpGet($url);
        $data = json_decode($res,true);
        return $data;
    }

    /**
     * 拉取用户信息 authCode(scope=SCOPE_SNSAPI_USERINFO)
     * @param $code
     * @return mixed
     */
    public function authUserInfo($code){
        $baseInfo = $this->authToken($code);
        $accessToken = $baseInfo['access_token'];
        $openid = $baseInfo['openid'];

        $urlParams['access_token'] = $accessToken;
        $urlParams['openid'] =$openid;
        $urlParams['lang'] = 'zh_CN';
        $urlParamStr = http_build_query($urlParams);
        $url = "https://api.weixin.qq.com/sns/userinfo?".$urlParamStr;
        $res = $this->httpGet($url);
        $data = json_decode($res,true);
        return $data;
    }

    /**
     * #############
     * ## 接口方法 ##
     * #############
     */

    /**
     * 发送模板消息
     * @param $toUser
     * @param $templateID
     * @param array $data
     * @param null $url
     * @return bool|mixed
     */
    public function sendTemplateMessage($toUser,$templateID,$data=array(),$url=null){
        $accessToken = $this->getAccessToken();

        $post = [
            'touser' => $toUser,
            'template_id' => $templateID,
            'data'   => $data,
        ];
        if(!empty($url)){
            $post['url']=$url;
        }

        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=$accessToken";
        $result = $this->httpPost($url,json_encode($post));
        return $result;
    }

    /**
     * 获取公众号AccessToken
     * @return mixed
     */
    private function getAccessToken() {
        // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
        $data = json_decode(file_get_contents(__DIR__."/access_token.json"));
        if ($data->expire_time < time()) {
            // 如果是企业号用以下URL获取access_token
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = json_decode($this->httpGet($url));
            $access_token = $res->access_token;
            if ($access_token) {
                $data->expire_time = time() + 7000;
                $data->access_token = $access_token;
                $fp = fopen(__DIR__."/access_token.json", "w");
                fwrite($fp, json_encode($data));
                fclose($fp);
            }
        } else {
            $access_token = $data->access_token;
        }
        return $access_token;
    }

    private function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }
    /**
     * 发送post请求
     * @param string $url
     * @param string $param
     * @return bool|mixed
     */
    function httpPost($url = '', $param = '')
    {
        if (empty($url) || empty($param)) {
            return false;
        }
        $postUrl = $url;
        $curlPost = $param;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
