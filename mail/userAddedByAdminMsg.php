<?php
/**
 * User: guiyumin, goes by Eric Gui
 * Date: 2017-07-24
 * Time: 20:27
 * Github: https://www.github.com/medmin/ipapp
 * Email: guiyumin@gmail.com
 */
/**
 * @var $model app\models\Users
 */
?>
<style type="text/css">
    div{ text-indent:2em;}
    .info{ text-indent:4em;}
    .hello{ text-indent:10em;}
    .clientcenter{ text-indent:7em;}
</style>
<h2>欢迎注册新用户</h2>
<h3>尊敬的<?=$model->userFullname ?>老师，您好！</h3>
<div>
欢迎您注册本网站会员，以下是您的信息：<br>

    <p class = 'info'>用户名：<?= $model->userUsername; ?>  <br></p>
    <p class = 'info'>电子邮箱：<?= $model->userEmail ?>  <br></p>

    如果您忘记密码 ，可以通过以上信息找回您的密码，也可以直接使用微信扫描登陆。<br>
    本网站竭诚为您服务，有任何问题，您可以拨打0451-88084686或者联系您的商务专员。<br>

    <p class = 'hello'>此致，<br></p>
    <p class = 'hello'>敬礼！<br></p>
    <p class = 'clientcenter'>阳光惠远客户服务中心<br></p>
    <p class = 'clientcenter'><?= date('Y-m-d H:i',time()); ?><br></p>
</div>

