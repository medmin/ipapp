<?php
/**
 * User: Mr-mao
 * Date: 2017/9/8
 * Time: 10:53
 */

/* @var $wx_json  */
?>
<script type="text/javascript">
    function jsApiCall(){
        WeixinJSBridge.invoke(
            'getBrandWCPayRequest', <?php echo $wx_json; ?>,
            function(res){
                if(res.err_msg === "get_brand_wcpay_request:ok" ) {
                    alert('支付成功');  // 可以跳转成功页面
                    window.location.href=updateUrl(window.location.href);
                }else if(res.err_msg === "get_brand_wcpay_request:cancel"){
                    alert('支付取消');
                }else if(res.err_msg === "get_brand_wcpay_request:fail"){
                    alert('支付失败');
                }
            }
        );
    }
    function callpay()
    {
        if (typeof WeixinJSBridge == "undefined"){
            if( document.addEventListener ){
                document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
            }else if (document.attachEvent){
                document.attachEvent('WeixinJSBridgeReady', jsApiCall);
                document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
            }
        }else{
            jsApiCall();
        }
    }
    function updateUrl(url,key){
        var key= (key || 't') +'=';  //默认是"t"
        var reg=new RegExp(key+'\\d+');  //正则：t=1472286066028
        var timestamp=+new Date();
        if(url.indexOf(key)>-1){ //有时间戳，直接更新
            return url.replace(reg,key+timestamp);
        }else{  //没有时间戳，加上时间戳
            if(url.indexOf('\?')>-1){
                var urlArr=url.split('\?');
                if(urlArr[1]){
                    return urlArr[0]+'?'+key+timestamp+'&'+urlArr[1];
                }else{
                    return urlArr[0]+'?'+key+timestamp;
                }
            }else{
                if(url.indexOf('#')>-1){
                    return url.split('#')[0]+'?'+key+timestamp+location.hash;
                }else{
                    return url+'?'+key+timestamp;
                }
            }
        }
    }
</script>
