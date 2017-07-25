<?php
/**
 * Author: guiyumin, goes by Eric Gui
 * Date: 2017-07-25
 * Time: 16:03
 * Github: https://www.github.com/medmin/ipapp
 * Email: guiyumin@gmail.com
 *
 * @var $model app\models\Patents
 * @var $users app\models\Users
 */
?>
<h2>警告：专利信息被修改</h2>
<h3>尊敬的<?=$users->userFullname; ?>老师，您好！</h3>
<div>
    您的一条专利信息被删除，以下是您的专利信息：<br>

    <p class = 'info'>专利名称：<?= $model->patentTitle; ?>  <br></p>
    <p class = 'info'>EAC系统案卷号：<?= $model->patentEacCaseNo; ?>  <br></p>

    本网站竭诚为您服务，有任何问题，您可以拨打0451-88084686或联系您的商务专员
    <?php
    if ( $model->patentUserLiaisonID == 0)
    {
        echo '';
    }
    else
    {
        echo $users::findByID($model->patentUserLiaisonID)->userFullname;
    }
    ?>。<br>

    <p class = 'hello'>此致，<br></p>
    <p class = 'hello'>敬礼！<br></p>
    <p class = 'clientcenter'>阳光惠远客户服务中心<br></p>
    <p class = 'clientcenter'><?= date('Y-m-d H:i',time()); ?><br></p>
</div>
