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
    public function syncPatents(int $days = 1000)
    {
        //设置默认同步最近1000天的记录，所以把时间戳设置为1000天前
        $thresholdTimestamp = strtotime('-' . $days . 'days') * 1000;

        $isolationLevel = Transaction::SERIALIZABLE;

        //所有同步，都必须是transaction，如果同步失败，那么其他所有数据都roll back
        $transaction = Yii::$app->db->beginTransaction($isolationLevel);

        try{
            // 首先找到所有Ajxxb表里所有最近1000天的记录
            $offset = 1000;
            $nums = floor(Ajxxb::find()->count() / $offset);
            //这是查patent表里，所有的AjxxbID
            $patentAjxxbIDArray = Yii::$app->db->createCommand('SELECT patentAjxxbID FROM Patents')->queryColumn();

            for ($i = 0; $i <= $nums; $i++) {
                $ajxxbQueryArray = Ajxxb::find()->where(['modtime' > $thresholdTimestamp])->limit($offset)->offset($i * $offset)->asArray()->all();

                if (empty($ajxxbQueryArray)) {
                    return true;//如果是空数组，说明没有什么可更新的
                } else {
                    foreach ($ajxxbQueryArray as $ajxxbOneSingleRow) {
                        //这是判断patents表里，是不是已经存在了待同步的记录，使用AjxxbID来查看
                        if (!in_array($ajxxbOneSingleRow['aj_ajxxb_id'], $patentAjxxbIDArray)) {
                            //这里是新记录，且youxiaobj为01，是有效，要同步，所以就是插入操作
                            if ($ajxxbOneSingleRow['youxiaobj'] == '01') {
                                $patent = new Patents();
                                $patent->patentAjxxbID = $ajxxbOneSingleRow['aj_ajxxb_id'];
                                switch ($ajxxbOneSingleRow['zhuanlilx']) {
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

                                //这里本应该是发邮件给客户提醒立案了，
                                //但是，这是从EAC同步过来的，如果客户没有认领这个案子，就不知道发邮件给谁

                            } else {
                                //这里 是 如果youxiaobj不是01，说明无效记录，就 啥也不做，继续
                                continue;
                            }
                        } else {   //这里是如果待同步的记录，已经存在于patents表里，就看一下其他字段

                            $patent = new Patents();

                            //如果是已经存在的记录，那要看一下youxiaobj是不是变了
                            if ($ajxxbOneSingleRow['youxiaobj'] == '01') {
                                if ($ajxxbOneSingleRow['zhubanr'] == '') {
                                    //这里的情况是：patents已经存在此记录，youxiaobj是01，zhubanr是空，那就不做任何同步操作
                                    continue;
                                } else {
                                    //这里是：patents已经存在此记录，youxiaobj是01，zhubanr不是空
                                    if ($patent::findOne(['patentAjxxbID' => $ajxxbOneSingleRow['aj_ajxxb_id']])->patentAgent !== '') {
                                        //patents表里此记录的agent不是空，
                                        //说明主办人（代理人，agent）已经分配过了，那就啥都不做了
                                        continue;
                                    } else {
                                        //patents表里此记录的agent是空 ，现在zhubanr不是空了
                                        //说明专利部分主管张金珠分配了主办人，这个过程，需要大概1-3天
                                        $patent::findOne(['patentAjxxbID' => $ajxxbOneSingleRow['aj_ajxxb_id']])->patentAgent = $ajxxbOneSingleRow['zhubanr'];
                                        $patent->save();
                                        //这里有一个需要补充的：分配代理人（agent）之后，代理会要给客户打个电话通知的，所以也不用发邮件了
                                    }
                                }
                            } else {
                                //youxiaobj竟然变成02了，就删了这一条
                                $patent::findOne(['patentAjxxbID' => $ajxxbOneSingleRow['aj_ajxxb_id']])->delete();
                            }
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
    public function syncPatentevents(int $days = 1000)
    {

        //设置默认同步最近1000天的记录，所以把时间戳设置为1000天前，
        $thresholdTimestamp = strtotime('-' . $days . 'days') * 1000; //13位Unix时间戳，因为EAC里是这个格式

        //所有同步，都必须是transaction，如果同步失败，那么其他所有数据都roll back
        $isolationLevel = Transaction::SERIALIZABLE;
        $transaction = Yii::$app->db->beginTransaction($isolationLevel);

        try{
            // 首先找到所有Rwsl表里所有最近1000天的记录
            $offset = 1000;
            $nums = floor(Rwsl::find()->count() / $offset);
            $eventRwslIDArray = Yii::$app->db->createCommand('SELECT eventRwslID FROM Patentevents')->queryColumn();

            for ($i = 0; $i<=$nums; $i++) {
                $rwslQueryArray = Rwsl::find()->where(['modtime' > $thresholdTimestamp])->limit($offset)->offset($i * $offset)->asArray()->all();

                if (!empty($rwslQueryArray))
                {
                    foreach ($rwslQueryArray as $rwslOneSingleRow)
                    {
                        if (in_array($rwslOneSingleRow['rw_rwsl_id'], $eventRwslIDArray))
                        {
                            $event = Patentevents::find()->where(['eventRwslID' => $rwslOneSingleRow['rw_rwsl_id']])->one();
                            //老记录，但原先patentevents表里的zhixingshij是空值，现在$rwslOneSingleRow['zhixingsj']却有值了
                            //说明这个记录不是新建的，但完成了，发邮件，骚扰一下客户。但要考虑，这个专利暂时无人认领。
                            //那还不如不发邮件。客户想看的时候自己看呗，何苦骚扰客户呢？
                            if ($event->eventFinishUnixTS == '' && $rwslOneSingleRow['zhixingsj'] != '')
                            {
                                $event->eventFinishUnixTS = $rwslOneSingleRow['zhixingsj'];
                                $event->save();
                            }
                            else
                            {
                                /* 这个else概括了2个情况
                                 * $event->eventFinishUnixTS 为空，$rwslOneSingleRow['zhixingsj']也空
                                 * 说明这任务已经建立 ，同步过了，但仍然没执行完，本来还说提醒一下zhixingr，
                                 * 但后来考虑觉得没必要，EAC系统里有未完成任务
                                 * $event->eventFinishUnixTS 不空，而且patentevents表里都有了这一条信息，
                                 * 说明这个没必要同步了，这任务执行完了
                                 */
                                continue;
                            }
                        }
                        else
                        {
                            //这里意思是，patentevents表里没有 这个rwslID，新纪录，就是新增，插入操作
                            $event = new Patentevents();
                            $event->patentAjxxbID = $rwslOneSingleRow['aj_ajxxb_id'];
                            $event->eventRwslID = $rwslOneSingleRow['rw_rwsl_id'];
                            $event->eventCreatPerson = $rwslOneSingleRow['chuangjianr'];
                            $event->eventCreatUnixTS = strtotime($rwslOneSingleRow['chuangjiansj']) * 1000; //13位Unix时间戳
                            $event->eventFinishPerson = $rwslOneSingleRow['zhixingr'];

                            //不管这个zhixingsj有没有值，都同步一下
                            //如果有值，说明这是一个尚未同步就已经完成了的任务（暂定24小时同步一次），这种情况还挺多
                            //如果空值，说明这是一个新建任务，尚未完成
                            $event->eventFinishUnixTS = strtotime($rwslOneSingleRow['zhixingsj']) * 1000; //13位Unix时间戳

                            //如果$rwslOneSingleRow['zhixingsj']是空值，那说明是新记录，但这任务还没完成，
                            //本来要发邮件提醒执行人，但后来考虑到这样每次都重复发送，而且EAC里都有相关未完成任务，所以不必提醒

                            $event->eventContentID = $rwslOneSingleRow['rw_rwdy_id'];
                            $event->eventContent = Rwsl::rwdyIdMappingContent()[$rwslOneSingleRow['rw_rwdy_id']];
                            $event->save();


                            /* 值得注意的是，上面两个UnixTimestamp其实同步的值都不是Unix时间戳，
                             * 而是14位时间格式，应该是UTC时间戳，yyyyMMhhssHHmmss，比如20151201163114
                             * 这是2015年12月1日16点31分14秒
                             * 无奈找了半天PHP的函数，不知道怎么转化位Unix时间戳
                             * http://strtotime.co.uk/  这个网站，可以测试转化效果
                             * 似乎 可以用strtotime()函数，把UTC转为Unix
                             */

                        }
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
