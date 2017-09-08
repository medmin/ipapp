<?php
/**
 * User: Mr-mao
 * Date: 2017/8/30
 * Time: 20:31
 */

use yii\helpers\Html;
use yii\helpers\Url;
use rmrevin\yii\fontawesome\FA;

/* @var $this \yii\web\View */
/* @var $content string */

rmrevin\yii\fontawesome\AssetBundle::register($this);
$this->registerCssFile('/css/wx.css');

$this->title = Yii::$app->name;
?>
<?php $this->beginPage() ?>
<!doctype html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <title><?= Html::encode($this->title) ?></title>
    <link rel="stylesheet" href="https://res.wx.qq.com/open/libs/weui/1.1.2/weui.min.css">
    <script type="text/javascript" src="https://res.wx.qq.com/open/libs/weuijs/1.1.2/weui.min.js"></script>
    <?php $this->head() ?>
</head>
<body ontouchstart>
<?php $this->beginBody() ?>
<div class="container" id="container">
    <div style="height: 100%;">
        <div class="weui-tab">
            <div class="weui-tab__panel">
                <?= $content ?>
            </div>
            <div class="weui-tabbar">
                <a href="/" class="weui-tabbar__item weui-bar__item_on">
                    <?= FA::icon('home', ['class' => 'weui-tabbar__icon']) ?>
                    <p class="weui-tabbar__label">进度</p>
                </a>
                <a href="<?= Url::to(['/users/my-patents']) ?>" class="weui-tabbar__item">
                    <?= FA::icon('files-o', ['class' => 'weui-tabbar__icon']) ?>
                    <p class="weui-tabbar__label">案件</p>
                </a>
                <a href="<?= Url::to(['/users/personal-settings']) ?>" class="weui-tabbar__item">
                    <?= FA::icon('user', ['class' => 'weui-tabbar__icon']) ?>
                    <p class="weui-tabbar__label">个人中心</p>
                </a>
            </div>
        </div>
    </div>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
