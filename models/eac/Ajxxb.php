<?php

namespace app\models\eac;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "aj_ajxxb".
 *
 * @property string $aj_ajxxb_id
 * @property string $anjuanlx
 * @property string $wofangwh
 * @property string $youxiaobj
 * @property string $zhubanr
 * @property string $zhuanlilx
 * @property string $zhuanlizwmc
 * @property string $shenqingh
 * @property string $shenqingr
 * @property integer $modtime
 */
class Ajxxb extends ActiveRecord
{
    //这里返回的是object，而不是\yii\db\Connection
    //见app\models\Sync.php文件
    public static function getDb()
    {
        return Yii::$app->get('dbEAC');
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'aj_ajxxb';
    }

}
