<?php
/**
 * User: Mr-mao
 * Date: 2017/7/24
 * Time: 11:10
 */

namespace app\assets;

use yii\web\AssetBundle;
class AdminLtePluginAsset extends AssetBundle
{
    public $sourcePath = '@vendor/almasaeed2010/adminlte/plugins';
    public $js = [
        'iCheck/icheck.min.js'
        // more plugin Js here
    ];
    public $css = [
        'iCheck/all.css'
        // more plugin CSS here
    ];
    public $depends = [
        'dmstr\web\AdminLteAsset',
    ];
}