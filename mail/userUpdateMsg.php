<?php
/**
 * Author: guiyumin, goes by Eric Gui
 * Date: 2017-07-25
 * Time: 15:21
 * Github: https://www.github.com/medmin/ipapp
 * Email: guiyumin@gmail.com
 *
 * @var $model app\models\Users
 */
?>
<style type="text/css">
    div{ text-indent:2em;}
    .info{ text-indent:4em;}
    .hello{ text-indent:10em;}
    .clientcenter{ text-indent:7em;}
</style>
<h2>提醒：用户信息被修改</h2>
<h3>尊敬的<?=$model->userFullname ?>老师，您好！</h3>
<div>
    您在本网站注册的信息被修改，以下是您的最新信息：<br>

    <p class = 'info'>用户名：<?= $model->userUsername; ?>  <br></p>
    <p class = 'info'>电子邮箱：<?= $model->userEmail ?>  <br></p>
    <p class = 'info'>手机：<?= $model->userCellphone ?>  <br></p>
    <p class = 'info'>固定电话：<?= $model->userLandline ?>  <br></p>
    <p class = 'info'>地址：<?= $model->userAddress ?>  <br></p>

    如果您忘记密码 ，可以通过以上信息找回您的密码，也可以直接使用微信扫描登陆。<br>
    本网站竭诚为您服务，有任何问题，您可以拨打0451-88084686或者联系您的商务专员。<br>

    <p class = 'hello'>此致，<br></p>
    <p class = 'hello'>敬礼！<br></p>
    <p class = 'clientcenter'>阳光惠远客户服务中心<br></p>
    <p class = 'clientcenter'><?= date('Y-m-d H:i',time()); ?><br></p>
</div>

