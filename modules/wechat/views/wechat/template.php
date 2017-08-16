<?php
/**
 * User: Mr-mao
 * Date: 2017/8/16
 * Time: 9:09
 */

/* @var $model \app\modules\wechat\models\TemplateForm */

$form = \yii\bootstrap\ActiveForm::begin();

echo $form->field($model, 'first')->textInput(['value' => '尊敬的客户您好，您订购的产品订单已经成功，具体信息如下:']);

echo $form->field($model, 'keyword1')->textInput(['value' => '客户名']);
echo $form->field($model, 'keyword2')->textInput(['value' => '产品订单']);
echo $form->field($model, 'keyword3')->textInput(['value' => '您购买的高强棉型涤纶短纤维40吨，金额250000元，账户余额2000元，提单号：9000123456']);
echo $form->field($model, 'keyword4')->textInput(['value' => '2017.8.16']);

echo $form->field($model, 'remark')->textInput(['value' => '仅供参考，如有疑问致电0517-12345678']);

echo \yii\helpers\Html::submitInput('提交', ['class' => 'btn btn-primary btn-block btn-flat']);

\yii\bootstrap\ActiveForm::end();

