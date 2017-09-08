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
                if(res.err_msg == "get_brand_wcpay_request:ok" ) {
                    alert('支付成功');  // 可以跳转成功页面
                }else if(res.err_msg == "get_brand_wcpay_request:cancel"){
                    alert('支付取消');
                }else if(res.err_msg == "get_brand_wcpay_request:fail"){
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
</script>
