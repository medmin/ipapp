<?php
/**
 * User: Mr-mao
 * Date: 2017/8/11
 * Time: 10:08
 */

namespace app\controllers;

use yii\web\Controller;

class BaseController extends Controller
{
    public $isMicroMessage = false;

    /**
     * 是否通过微信访问
     * return bool
     */
    private function isMicroMessage() {
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return true;
        }
        return false;
    }
}