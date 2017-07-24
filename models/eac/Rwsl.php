<?php

namespace app\models\eac;

use Yii;

/**
 * This is the model class for table "rw_rwsl".
 *
 * @property string $rw_rwsl_id
 * @property string $aj_ajxxb_id
 * @property string $chuangjianr
 * @property string $chuangjiansj
 * @property string $zhixingr
 * @property string $zhixingsj
 * @property string $rw_rwdy_id
 * @property integer $modtime
 */
class Rwsl extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'rw_rwsl';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('dbEAC');
    }

}
