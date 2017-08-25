<?php
/**
 * User: Mr-mao
 * Date: 2017/8/8
 * Time: 20:02
 */

/* @var $models */
/* @var $link bool */

/**
 * 日期分组
 * @param $models
 * @return array|null
 */
function group($models) {
    if (empty($models)) return null;
    $current_year = date('Y');
    $result = [];
    foreach ($models as $model) {
        $time = $model->eventCreatUnixTS / 1000;
        $date = ($current_year == date('Y', $time) ? date('m月d日', $time) : date('Y年m月d日', $time));
        $result[$date][] = $model;
    }
    return $result;
}

/**
 * 颜色
 * @param $i
 * @return mixed
 */
function color($i) {
    $colors = [
        'bg-maroon',
        'bg-red',
        'bg-green',
        'bg-orange',
        'bg-navy'
    ];
    return $colors[$i % count($colors)];
}
$i = 0;
$link = $link ?? false;

$this->registerCss('
.file-download {
    margin-left: 5px;
    cursor: pointer;
//    color: #3c8dbc;
}
');
$this->registerJs('
var download = function(id) {
    var u = navigator.userAgent;
    var isMicromessager = u.toLowerCase().match(/MicroMessenger/i) == "micromessenger";
    var isAndroid = u.indexOf(\'Android\') > -1 || u.indexOf(\'Adr\') > -1;
    if (isMicromessager && isAndroid) {
        iziToast.show({
                message: "安卓微信暂不支持下载文件，请点击右上角在手机浏览器中打开并下载",
                position: "topCenter",
                progressBar: false,
                transitionInMobile: "fadeDown",
                transitionOutMobile: "flipOutX",
                theme: "dark",
                timeout: 6000,
                backgroundColor: "yellow",
                messageColor: "red",
            });
    } else {
        window.location.href = "'. \yii\helpers\Url::to(['patentfiles/download']) .'?id=" + id;
    }
}
',\yii\web\View::POS_END);
?>
<ul class="timeline timeline-inverse">
    <? foreach (group($models) as $idx => $group_model): ?>
        <li class="time-label">
            <span class="<?= color($i) ?>"><?= $idx ?></span>
            <?php
            foreach ($group_model as $model) {
                echo $this->render('detail', ['model' => $model, 'link' => $link]);
            }
            ?>
        </li>
        <? $i++; ?>
    <? endforeach; ?>
    <li>
        <i class="fa fa-clock-o bg-gray"></i>
    </li>
</ul>