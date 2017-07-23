<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property integer $userID
 * @property string $userUsername
 * @property string $userPassword
 * @property string $userOrganization
 * @property string $userFullname
 * @property string $userFirstname
 * @property string $userGivenname
 * @property string $userNationality
 * @property string $userCitizenID
 * @property string $userEmail
 * @property string $userCellphone
 * @property string $userLandline
 * @property string $userAddress
 * @property string $userLiasion
 * @property integer $userLiasionID
 * @property integer $userRole
 * @property string $userNote
 * @property string $authKey
 * @property integer $UnixTimestamp
 */
class Users extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userUsername', 'userPassword', 'userOrganization', 'userFullname', 'userCitizenID', 'userEmail', 'userCellphone', 'userLandline', 'userAddress', 'userLiasion', 'userLiasionID', 'userRole', 'userNote', 'authKey', 'UnixTimestamp'], 'required'],
            [['userLiasionID', 'userRole', 'UnixTimestamp'], 'integer'],
            [['userNote'], 'string'],
            [['userUsername'], 'string', 'max' => 16],
            [['userPassword'], 'string', 'max' => 100],
            [['userOrganization', 'userEmail'], 'string', 'max' => 40],
            [['userFullname', 'userLiasion'], 'string', 'max' => 24],
            [['userFirstname', 'userGivenname', 'userNationality'], 'string', 'max' => 12],
            [['userCitizenID', 'userCellphone', 'userLandline'], 'string', 'max' => 18],
            [['userAddress'], 'string', 'max' => 255],
            [['authKey'], 'string', 'max' => 32],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'userID' => Yii::t('app', 'User ID'),
            'userUsername' => Yii::t('app', 'User Username'),
            'userPassword' => Yii::t('app', 'User Password'),
            'userOrganization' => Yii::t('app', 'User Organization'),
            'userFullname' => Yii::t('app', 'User Fullname'),
            'userFirstname' => Yii::t('app', 'User Firstname'),
            'userGivenname' => Yii::t('app', 'User Givenname'),
            'userNationality' => Yii::t('app', 'User Nationality'),
            'userCitizenID' => Yii::t('app', 'User Citizen ID'),
            'userEmail' => Yii::t('app', 'User Email'),
            'userCellphone' => Yii::t('app', 'User Cellphone'),
            'userLandline' => Yii::t('app', 'User Landline'),
            'userAddress' => Yii::t('app', 'User Address'),
            'userLiasion' => Yii::t('app', 'User Liasion'),
            'userLiasionID' => Yii::t('app', 'User Liasion ID'),
            'userRole' => Yii::t('app', 'User Role'),
            'userNote' => Yii::t('app', 'User Note'),
            'authKey' => Yii::t('app', 'Auth Key'),
            'UnixTimestamp' => Yii::t('app', 'Unix Timestamp'),
        ];
    }
}
