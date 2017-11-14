<?php
/**
 * User: Mr-mao
 * Date: 2017/11/13
 * Time: 22:00
 */

/* @var $url string 二维码链接 */
/* @var $id string 订单ID */

$this->registerJs('
 var url = "' . \yii\helpers\Url::to(["/pay/check-order", "id" => $id]) . '";
 var timesRun = 0;
 var t = setInterval(function(){
   timesRun += 1;
   if (timesRun === 600) {
     clearInterval(interval); 
   }
   $.getJSON(url, function(d){
     if (d.done == true) {
       clearTimeout(t);
       window.location.href = "/users/monitor-patents";
     }
   })
 }, 3000)
');
$this->registerCss('
body {
  background: #f5f5f1;
}
');
?>
<?php $this->beginPage() ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>阳光惠远 | 订单支付</title>
    <style type="text/css">
        body {
            background: #f5f5f1;
        }
    </style>
</head>
<body>
<?php $this->beginBody() ?>
<div class="container">
    <div class="order-pay" id="order-pay" style="width:350px; margin:100px auto;">
        <img src="<?= \yii\helpers\Url::to(['/pay/get-qr-code', 'content' => $url]) ?>" alt="">
    </div>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
