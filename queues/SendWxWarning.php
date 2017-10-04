<?php

namespace app\queues;

use Yii;
use yii\base\Object;
use \yii\queue\Job;

class SendWxWarning extends Object implements Job
{

    public $mailViewFileNameString;
    public $varToViewArray;
    public $fromAddressArray; // Set the From address with an associative array
    public $toAddressArray;
    public $emailSubjectString;

    public function execute($queue)
    {

        Yii::$app->mailer->compose($this->mailViewFileNameString, $this->varToViewArray)
            ->setFrom($this->fromAddressArray)
            ->setTo($this->toAddressArray)
            ->setSubject($this->emailSubjectString)
            ->send();
    }

}
