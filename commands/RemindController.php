<?php
/**
 * User: Mr-mao
 * Date: 2017/9/19
 * Time: 16:16
 */

namespace app\commands;

use app\models\AnnualFeeMonitors;
use app\models\Patents;
use yii\console\Controller;
use Yii;

class RemindController extends Controller
{
    public function actionIndex(int $days = 30)
    {
//        $users = AnnualFeeMonitors::find()->select('user_id')->where(['patent_id' => 3002])->asArray()->all();
//        $users = array_column($users, 'user_id');
//        $redis = Yii::$app->redis;
//        print_r($redis->keys("*"));
//        foreach($users as $id) {
//
//        }
//        exit;
        $patentModels = Patents::find()->where(['patentFeeDueDate' => date('Ymd', strtotime('+'.$days.' days')), 'patentCaseStatus' => '有效'])->all();

        /* @var $patent Patents */
        foreach ($patentModels as $patent) {
            $unpaidAnnualFee = json_decode($patent->generateUnpaidAnnualFee(), true);
            if ($unpaidAnnualFee['status'] == true) {
                $users = AnnualFeeMonitors::find()->select('user_id')->where(['patent_id' => $patent->patentID])->asArray()->all();
                $users = array_column($users, 'user_id');
                // TODO 先留着，明天搞
                $redis = Yii::$app->redis;

            }
        }
    }
}