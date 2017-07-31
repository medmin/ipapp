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
    public function syncPatents(int $days = 30)
    {
        //设置默认同步最近30天的记录，所以把时间戳设置为30天前
        $thresholdTimestamp = strtotime('-' . $days . 'days') * 1000;

        $isolationLevel = Transaction::SERIALIZABLE;

        //所有同步，都必须是transaction，如果同步失败，那么其他所有数据都roll back
        $transaction = Yii::$app->db->beginTransaction($isolationLevel);

        try{
            // 首先找到所有Ajxxb表里所有最近30天的记录
            $ajxxbQueryArray = Ajxxb::find()->where(['modtime' > $thresholdTimestamp])->asArray()->all();
            //这是查patent表里，所有的AjxxbID
            $patentAjxxbIDArray = Yii::$app->db->createCommand('SELECT patentAjxxbID FROM Patents')->queryColumn();

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
                        //youxiaobj为01，是有效，要同步，这是新增，插入操作
                        if ($ajxxbOneSingleRow['youxiaobj'] == '01')
                        {
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

                            //发email给客户，提醒他，立案了
                            //这里还没 写好，所以有错误，但你别管这个，我先把逻辑捋清楚
                            Yii::$app->queue->push(new SendEmailJob(
                                [
                                    'mailViewFileNameString' => 'msgToClientForBuildingACase',
                                    'varToViewArray' => [
                                        'model' => $model,
                                        'patentAgentStringValue' => $patentAgentStringValue
                                    ],
                                    'fromAddressArray' => ['kf@shineip.com' => '阳光惠远客服中心'],
                                    'toAddressArray' => [$patentAgentEmail],
                                    'emailSubjectString' => '阳光惠远客服中心通知邮件'
                                ]
                            ));

                        }
                        else
                        {
                            //这里 是 如果youxiaobj不是01，说明无效记录，就 啥也不做，继续
                            continue;
                        }
                    }
                    else
                    {   //这里是如果待同步的记录，已经存在于patents表里，就看一下其他字段

                        $patent = new Patents();

                        //如果是已经存在的记录，那要看一下youxiaobj是不是变了
                        if ($ajxxbOneSingleRow['youxiaobj'] == '01')
                        {
                            if($ajxxbOneSingleRow['zhubanr'] == '')
                            {
                                //这里的情况是：patents已经存在此记录，youxiaobj是01，zhubanr是空，那就不做任何同步操作
                                continue;
                            }
                            else
                            {
                                //这里是：patents已经存在此记录，youxiaobj是01，zhubanr不是空
                                if($patent::findOne($ajxxbOneSingleRow['aj_ajxxb_id'])->patentAgent !== '')
                                {
                                    //patents表里此记录的agent不是空，
                                    //说明主办人（代理人，agent）已经分配过了，那就啥都不做了
                                    continue;
                                }
                                else
                                {
                                    //patents表里此记录的agent是空 ，现在zhubanr不是空了
                                    //说明专利部分主管张金珠分配了主办人，这个过程，需要大概1-3天
                                    $patent::findOne($ajxxbOneSingleRow['aj_ajxxb_id'])->patentAgent = $ajxxbOneSingleRow['zhubanr'];
                                }
                            }
                        }
                        else
                        {
                            //youxiaobj竟然变成02了，就删了这一条
                            $patent::findOne($ajxxbOneSingleRow['aj_ajxxb_id'])->delete();
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
    public function syncPatentevents(int $days = 30)
    {

        //设置默认同步最近30天的记录，所以把时间戳设置为30天前，
        $thresholdTimestamp = strtotime('-' . $days . 'days') * 1000;

        //所有同步，都必须是transaction，如果同步失败，那么其他所有数据都roll back
        $isolationLevel = Transaction::SERIALIZABLE;
        $transaction = Yii::$app->db->beginTransaction($isolationLevel);

        try{

            // 首先找到所有Rwsl表里所有最近30天的记录
            $rwslQueryArray = Rwsl::find()->where(['modtime' > $thresholdTimestamp])->asArray()->all();

            $eventRwslIDArray = Yii::$app->db->createCommand('SELECT eventRwslID FROM Patentevents')->queryColumn();

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
                               说明任务还没完成，发邮件提醒zhixingr，还有一个任务没完成；*/
                            // TODO 发email提醒zhixingr，还有一个任务没完成
                            Yii::$app->queue->push(new SendEmailJob(
                                [
                                    'mailViewFileNameString' => 'msgToClientForBuildingACase',
                                    'varToViewArray' => [
                                        'model' => $model,
                                        'patentAgentStringValue' => $patentAgentStringValue
                                    ],
                                    'fromAddressArray' => ['kf@shineip.com' => '阳光惠远客服中心'],
                                    'toAddressArray' => [$patentAgentEmail],
                                    'emailSubjectString' => '阳光惠远客服中心通知邮件'
                                ]
                            ));




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