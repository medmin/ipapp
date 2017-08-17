<?php
/**
 * Author: guiyumin, goes by Eric Gui
 * Date: 2017-08-17
 * Time: 9:33
 * Github: https://www.github.com/medmin/ipapp
 * Email: guiyumin@gmail.com
 */

namespace app\modules\wechat\models;


use yii\base\Model;
use Yii;

class Options extends Model
{
    private $options;

    public function getOptions()
    {
        $this->options = [];
        $this->options['debug'] = YII_DEBUG;
        $this->options['app_id'] = Yii::$app->params['wechat']['id'];
        $this->options['secret'] = Yii::$app->params['wechat']['secret'];
        $this->options['token'] = Yii::$app->params['wechat']['token'];
        $this->options['aes_key'] = Yii::$app->params['wechat']['token'];
        $this->options['log'] = [
            'level' => 'debug',
            'file' => Yii::$app->params['wechat_log_path'], // XXX: 绝对路径！！！！
        ];

        return $this->options;
    }

}