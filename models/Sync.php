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
use yii\db\Transaction;

class Sync extends Model
{
    /**
     * 同步专利
     * (new \app\models\Sync())->syncPatents();
     *
     * @return bool
     * @throws \Exception
     */
    public function syncPatents(int $days = 3)
    {
        //设置默认同步最近3天的记录，所以把时间戳设置为3天前
        $thresholdTimestamp = strtotime('-' . $days . 'days') * 1000;

        $isolationLevel = Transaction::SERIALIZABLE;

        $dbEAC = Yii::$app->get('dbEAC');

        //所有同步，都必须是transaction，如果同步失败，那么其他所有数据都roll back
        $transaction = $dbEAC->beginTransaction($isolationLevel);

        try{
            // 首先找到所有Ajxxb表里所有modtime时间戳大于3天前的，也就是最近3天的记录
            $ajxxbQueryArray = Ajxxb::find()->where(['modtime' > $thresholdTimestamp])->asArray()->all();

            $patentEacCaseNoArray = Yii::$app->get('db')
                ->createCommand('SELECT patentEacCaseNo FROM Patents')
                ->queryColumn();

            if (empty($ajxxbQueryArray))
            {
                return true;//如果是空数组，说明没有什么可更新的
            }
            else
            {
                foreach ($ajxxbQueryArray as $ajxxbOneSingleRow)
                {
                    $patent = new Patents();

                    //这是判断patents表里，是不是已经存在了待同步的记录，使用AjxxbID来查看
                    if (in_array($ajxxbOneSingleRow['aj_ajxxb_id'], $patentEacCaseNoArray))
                    {
                        return true;
                    }
                    else
                    {
                        if ($ajxxbOneSingleRow['youxiaobj'] == '01' ) //youxiaobj为01，是有效，要同步
                        {
                            $patent->patentAjxxbID = $ajxxbOneSingleRow['aj_ajxxb_id'];
                            $patent->patentType = $ajxxbOneSingleRow['anjuanlx'];
                            $patent->patentEACNumber = $ajxxbOneSingleRow['wofangwh'];
                            $patent->patentAgent = $ajxxbOneSingleRow['zhubanr'];
                            $patent->modtime = $ajxxbOneSingleRow['modtime'];
                            $patent->save();
                        }
                        else
                        {
                            //这里其实还有一个隐藏的逻辑：如果一条十天前新建的专利，设置为无效了呢？
                            //这个就肯定查不出来了，所以我本来写了很多，但都删了
                            //解决这个逻辑，要专门写另一个函数，对EAC里ajxxb表进行查询，首先筛出来是02的记录
                            //然后和patents表最对比，配对成功的，就删去
                            return true;
                        }
                    }
                }
            }

            //这里和下面rollback的warning提示，都是IDE的问题
            $transaction->commit();

        }
        catch (\Exception $e)
        {

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