<?php
/**
 * User: Mr-mao
 * Date: 2017/7/24
 * Time: 11:10
 */

namespace app\assets;

use yii\web\AssetBundle;
class IziToastAsset extends AssetBundle
{
    public $sourcePath = '@bower/izitoast/dist';
    public $js = [
        'js/iziToast.min.js'
        // more plugin Js here
    ];
    public $css = [
        'css/iziToast.min.css'
        // more plugin CSS here
    ];
}