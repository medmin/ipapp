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

    const CUSTOMER_ALERTS_NOTIFICATION = 'WXrxhUrFslEmmVlnQqwCKI1kVbF6FsoIYoSg6aX4Cug';

    public function rules()
    {
        return [
            [['first', 'remark', 'keyword1', 'keyword2'], 'required'],
            [['keyword3', 'keyword4'], 'safe'],
        ];
    }
}

