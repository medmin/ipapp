<?php
/**
 * User: Mr-mao
 * Date: 2017/7/24
 * Time: 17:43
 */

namespace app\models;

use yii\base\Model;
use Yii;
use app\models\eac\Ajxxb;

class Sync extends Model
{
    /**
     * 同步专利
     * (new \app\models\Sync())->syncPatents();
     *
     * @return bool
     * @throws \Exception
     */
    public function syncPatents($days = 0)
    {
        $thresholdTimestamp = strtotime('-' . $days . 'day 0:0') * 1000;
        $transaction = Yii::$app->dbEAC->beginTransaction();
        try{
            // TODO
            // 下边这个同步过程可能不准，算是一个参考的demo，未测试
            $ajxxbQueryArray = Ajxxb::find()->where(['modtime' > $thresholdTimestamp])->asArray()->all();
            if (empty($ajxxbQueryArray)) return true;
            foreach ($ajxxbQueryArray as $model) {
                // 可以做条件判断 在new之前，什么情况下需要new什么情况需要update
                $patent = new Patents();
                $patent->patentAjxxbID = $model['aj_ajxxb_id'];
                $patent->patentType = $model['anjuanlx'];
                $patent->patentEACNumber = $model['wofangwh'];
                $patent->patentAgent = $model['zhubanr'];
                $patent->modtime = $model['modtime'];
                $patent->save();
            }
            $transaction->commit();
        }catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return true;
    }

    /**
     * 同步专利事件
     *
     * @return bool
     * @throws \Exception
     */
    public function syncPatentevents()
    {
        $transaction = Yii::$app->dbEAC->beginTransaction();
        try{
            // TODO
            $transaction->commit();
        }catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return true;
    }
}