<?php
/**
 * User: Mr-mao
 * Date: 2017/8/16
 * Time: 8:55
 */
namespace app\modules\wechat\models;

use yii\base\Model;

/**
 * 模板消息测试
 * Class TemplateForm
 * @package app\modules\wechat\models
 */
class TemplateForm extends Model
{
    public $first;
    public $keyword1;
    public $keyword2;
    public $keyword3;
    public $keyword4;
    public $remark;

    const CUSTOMER_ALERTS_NOTIFICATION = 'WXrxhUrFslEmmVlnQqwCKI1kVbF6FsoIYoSg6aX4Cug'; // keywords_4
    const SCHEDULE = 'j0VDfgYFGY9BJSjdyI8PjwuNMYHwgHpvKOIOMlX732w'; // keywords_2
    const PROJECT_PROGRESS_NOTIFICATION = 'EVYTcHVuQsxE6BJQ1Plp-5N-E7Sk1vAbP_z2_LwPDp4'; // keywords_2

    /**
     * 暂时想不到合适的解决方案
     *
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['keywords_2'] = ['first', 'remark', 'keyword1', 'keyword2'];
        $scenarios['keywords_4'] = ['first', 'remark', 'keyword1', 'keyword2', 'keyword3', 'keyword4'];
        return $scenarios;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['first', 'remark'], 'required'],
            [['keyword1', 'keyword2'], 'required', 'on' => ['keywords_2', 'keywords_4']],
            [['keyword3', 'keyword4'], 'required', 'on' => 'keywords_4'],
        ];
    }

    /**
     * @return array
     */
    public static function status()
    {
        return [
            self::CUSTOMER_ALERTS_NOTIFICATION => '客服通知提醒',
            self::SCHEDULE => '待办任务提醒',
            self::PROJECT_PROGRESS_NOTIFICATION => '项目进展通知'
        ];
    }
}

