<?php
/**
 * Author: guiyumin, goes by Eric Gui
 * Date: 2017-08-17
 * Time: 9:18
 * Github: https://www.github.com/medmin/ipapp
 * Email: guiyumin@gmail.com
 */

namespace app\modules\wechat\models;

use yii\base\Model;
use EasyWeChat\Foundation\Application;

class DefaultMenu extends Model
{

    public $options;
    public $app;
    public $buttons;
    public $menu;

    public function getDefaultMenu()
    {
        $this->options = (new Options())->getOptions();
        $this->app = new Application($this->options);


        $this->buttons = [
            [
                "type" => "view",
                "name" => "我的进度",
                "key"  => "SHINEIP_USER_VIEW_MYEVENTS", //key是自定义的
                'url'  => 'http://kf.shineip.com/'
            ],
            [
                "type" => "view",
                "name" => "我的专利",
                "key"  => "SHINEIP_USER_VIEW_MYPATENTS", //key是自定义的
                'url'  => 'http://kf.shineip.com/users/my-patents'
            ],
            [
                "type" => "view",
                "name" => "我要反馈",
                "key"  => "SHINEIP_USER_VIEW_CONTACT", //key是自定义的
                'url'  => 'http://kf.shineip.com/site/contact'
            ]

        ];

        $this->menu = $this->app->menu;

        return $this->menu->add($this->buttons);

    }
}