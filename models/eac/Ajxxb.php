<?php

namespace app\models\eac;

use Yii;

/**
 * This is the model class for table "aj_ajxxb".
 *
 * @property string $aj_ajxxb_id
 * @property string $anjuanlx
 * @property string $wofangwh
 * @property string $youxiaobj
 * @property string $zhubanr
 * @property integer $modtime
 */
class Ajxxb extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'aj_ajxxb';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('dbEAC');
    }

}
