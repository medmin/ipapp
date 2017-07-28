<?php
/**
 * User: Mr-mao
 * Date: 2017/7/24
 * Time: 17:43
 */

namespace app\models;

use phpDocumentor\Reflection\Types\Null_;
use yii\base\Model;
use Yii;
use app\models\eac\Ajxxb;
use app\models\eac\Rwsl;
use yii\db\Transaction;
use app\queues\SendEmailJob;

class Sync extends Model
{
    /**
     * 同步专利
     * (new \app\models\Sync())->syncPatents();
     *
     * @param $days
     * @return bool
     * @throws \Exception
     */
    public function syncPatents(int $days = 3)
    {
        //设置默认同步最近3天的记录，所以把时间戳设置为3天前
        $thresholdTimestamp = strtotime('-' . $days . 'days') * 1000;

        $isolationLevel = Transaction::SERIALIZABLE;

        $db = Yii::$app->db;

        //所有同步，都必须是transaction，如果同步失败，那么其他所有数据都roll back
        $transaction = $db->beginTransaction($isolationLevel);

        try{
            // 首先找到所有Ajxxb表里所有modtime时间戳大于3天前的，也就是最近3天的记录
            $ajxxbQueryArray = Ajxxb::find()->where(['modtime' > $thresholdTimestamp])->asArray()->all();

            $patentAjxxbIDArray = Yii::$app->get('db')
                ->createCommand('SELECT patentAjxxbID FROM Patents')
                ->queryColumn();

            if (empty($ajxxbQueryArray))
            {
                return true;//如果是空数组，说明没有什么可更新的
            }
            else
            {
                foreach ($ajxxbQueryArray as $ajxxbOneSingleRow)
                {
                    //这是判断patents表里，是不是已经存在了待同步的记录，使用AjxxbID来查看
                    if (!in_array($ajxxbOneSingleRow['aj_ajxxb_id'], $patentAjxxbIDArray)) {
                        //youxiaobj为01，是有效，要同步
                        if ($ajxxbOneSingleRow['youxiaobj'] == '01') {
                            $patent = new Patents();
                            $patent->patentAjxxbID = $ajxxbOneSingleRow['aj_ajxxb_id'];
                            switch ($ajxxbOneSingleRow['zhuanlilx'])
                            {
                                case '01':
                                    $patent->patentType = '发明专利';
                                    break;
                                case '02':
                                    $patent->patentType = '实用新型';
                                    break;
                                case '03':
                                    $patent->patentType = '外观设计';
                                    break;
                                default:
                                    $patent->patentType = '请管理员添加案件类型';
                            }
                            $patent->patentEacCaseNo = $ajxxbOneSingleRow['wofangwh'];//这里是我方卷号，AAA或BAA开头
                            $patent->patentAgent = $ajxxbOneSingleRow['zhubanr'];
                            $patent->UnixTimestamp = $ajxxbOneSingleRow['modtime'];
                            $patent->save();

                            //TODO 发email给客户，提醒他，立案了
                        } else {
                            //这里其实还有一个隐藏的逻辑：如果一条十天前新建的专利，设置为无效了呢？
                            //这个就肯定查不出来了，因为这里只查了最近3天的所有记录
                            //TODO 解决这个逻辑，要专门写另一个函数，对EAC里ajxxb表进行查询，首先筛出来是02的记录
                            //然后和patents表最对比，配对成功的，就删去

                            //此处呢，就单纯的不做任何动作，如果是02，就啥也不做，不同步，也不删，因为会有另一个函数专门删
                            continue;
                        }
                    }
                }
            }

            //如果用多个db，这里和下面rollback会有warning提示，但那都是IDE的问题
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
     * @param $days
     * @return bool
     * @throws \Exception
     */
    public function syncPatentevents(int $days = 3)
    {

        //设置默认同步最近3天的记录，所以把时间戳设置为3天前，
        //TODO 但这个设置的问题是，如果有一个10天前的任务被完成，那就查不出来了，需要写另一个函数，查询所有zhishisj是空的值
        $thresholdTimestamp = strtotime('-' . $days . 'days') * 1000;

        //所有同步，都必须是transaction，如果同步失败，那么其他所有数据都roll back
        $isolationLevel = Transaction::SERIALIZABLE;
        $transaction = Yii::$app->db->beginTransaction($isolationLevel);

        try{

            // 首先找到所有Rwsl表里所有modtime时间戳大于3天前的，也就是最近3天的记录
            $rwslQueryArray = Rwsl::find()->where(['modtime' > $thresholdTimestamp])->asArray()->all();

            $eventRwslIDArray = Yii::$app->get('db')
                ->createCommand('SELECT eventRwslID FROM Patentevents')
                ->queryColumn();

            if (!empty($rwslQueryArray)) {
                foreach ($rwslQueryArray as $rwslOneSingleRow) {
                    if (in_array($rwslOneSingleRow['rw_rwsl_id'], $eventRwslIDArray)) {
                        $event = Patentevents::find()->where(['eventRwslID' => $rwslOneSingleRow['rw_rwsl_id']])->one();
                        //老记录，但原先patentevents表里的zhixingshij是空值，现在$rwslOneSingleRow['zhixingsj']却有值了
                        //说明这个记录不是新建的，但完成了，发邮件，骚扰一下客户。但要考虑，这个专利暂时无人认领。
                        //那还不如不发邮件。客户想看的时候自己看呗，何苦骚扰客户呢？
                        if ($event->eventFinishUnixTS == '' && $rwslOneSingleRow['zhixingsj'] != ''){
                            $event->eventFinishUnixTS = $rwslOneSingleRow['zhixingsj'];
                            $event->save();
                        } elseif ($event->eventFinishUnixTS == '' && $rwslOneSingleRow['zhixingsj'] == '') {
                            /* 老记录，原先patentevents表里的zhixingshij是空值，现在$rwslOneSingleRow['zhixingsj']还是空值,
                               说明任务还没完成，发邮件提醒zhixingr，还有一个任务没完成；
                               TODO 发email提醒zhixingr，还有一个任务没完成 */
                        } else {
                            continue;
                        }
                    } else {
                        $event = new Patentevents();
                        $event->patentAjxxbID = $rwslOneSingleRow['aj_ajxxb_id'];
                        $event->eventRwslID = $rwslOneSingleRow['rw_rwsl_id'];
                        $event->eventCreatPerson = $rwslOneSingleRow['chuangjianr'];
                        $event->eventCreatUnixTS = $rwslOneSingleRow['chuangjiansj']; //这里格式不对，不是UNIX TIMESTAMP
                        $event->eventFinishPerson = $rwslOneSingleRow['zhixingr'];

                        //不管这个zhixingsj有没有值，都同步一下
                        $event->eventFinishUnixTS = $rwslOneSingleRow['zhixingsj'];//这里格式也不不是UNIX TIMESTAMP

                        //如果$rwslOneSingleRow['zhixingsj']是空值，那说明是新记录，但这任务还没完成，就要发邮件提醒执行人
                        if($rwslOneSingleRow['zhixingsj'] =='' || $rwslOneSingleRow['zhixingsj'] == null){

                            Yii::$app->queue->push(new SendEmailJob(

                            ));
                        }
                        //else 这里的else，说明是虽然是新记录 ，但任务已经完成了，单纯同步而已，不发邮件，不骚扰客户和zhixingr

                        $event->eventContentID = $rwslOneSingleRow['rw_rwdy_id'];
                        $event->eventContent = Rwsl::rwdyIdMappingContent()[$rwslOneSingleRow['rw_rwdy_id']];
                        $event->save();
                    }
                }
            }
            $transaction->commit();
        }catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return true;
    }

    //patents表里 ，youxiaobj不是01的，就删了
    public function deleteYouxiaobjNot01(){

    }

    //所有rwid是06的，属于新案质检，是提交到CPC前最后一步，通常当天就搞定了
    //也就是说，还没来得及同步，这个任务从创建到完成，一个工作日之内就完事了
    public function rwdyidIs06(){

    }

}