<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * ContactForm is the model behind the contact form.
 */
class ContactForm extends Model
{
    public $name;
    public $email;
    public $subject;
    public $body;
    public $verifyCode;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // body is required
            [['body'], 'required'],
            // verifyCode needs to be entered correctly
            ['verifyCode', 'captcha'],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'verifyCode' => Yii::t('app', 'Verification Code'),
            'body' => Yii::t('app', 'Content'),
        ];
    }


    public function contact()
    {
        if ($this->validate()) {
            $notice = new Notification();
            $notice->sender = Yii::$app->user->id;
            $notice->receiver = Yii::$app->user->identity->userLiaisonID ?: 1;
            $notice->content = $this->body;
            $notice->type = Notification::TYPE_NOTICE;
            $notice->save();
            return true;
        }
        return false;
    }
}
