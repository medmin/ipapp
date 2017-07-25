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
 * @property string $userLiaison
 * @property integer $userLiaisonID
 * @property integer $userRole
 * @property string $userNote
 * @property string $authKey
 * @property integer $UnixTimestamp
 */
class Users extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    const ROLE_EMPLOYEE = 2;
    const ROLE_CLIENT = 1;

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
            [['userUsername', 'userPassword', 'userOrganization', 'userFullname', 'userCitizenID', 'userEmail', 'userCellphone', 'userLandline', 'userAddress', 'userLiaison', 'userLiaisonID', 'userRole', 'userNote', 'authKey', 'UnixTimestamp'], 'required'],
            [['userUsername', 'userCitizenID', 'userEmail'], 'unique'],
            [['userLiaisonID', 'userRole', 'UnixTimestamp'], 'integer'],
            ['userEmail', 'email'],
            ['userCitizenID', 'validateCitizenID'],
            [['userNote'], 'string'],
            [['userUsername'], 'string', 'max' => 16],
            [['userPassword'], 'string', 'max' => 100],
            [['userOrganization', 'userEmail'], 'string', 'max' => 40],
            [['userFullname', 'userLiaison'], 'string', 'max' => 24],
            [['userFirstname', 'userGivenname', 'userNationality'], 'string', 'max' => 12],
            [['userCitizenID', 'userCellphone', 'userLandline'], 'string', 'max' => 18],
            [['userAddress'], 'string', 'max' => 255],
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
            'userLiaison' => Yii::t('app', 'User Liaison'),
            'userLiaisonID' => Yii::t('app', 'User Liaison ID'),
            'userRole' => Yii::t('app', 'User Role'),
            'userNote' => Yii::t('app', 'User Note'),
            'authKey' => Yii::t('app', 'Auth Key'),
            'UnixTimestamp' => Yii::t('app', 'Unix Timestamp'),
        ];
    }

    /**
     * LiaisonValidator
     * 如果liaison有重名这个就不好处理了，再考虑
     * @param $attribute
     */
    public function validateLiaison($attribute)
    {
        if (!static::findByUserName($this->$attribute)) {
            $this->addError($attribute, '该用户不存在');
        }
    }

    /**
     * ChinaCitizenID validator
     * @param $attribute
     */
    public function validateCitizenID($attribute)
    {
        $checkIDCode = function($ID) {
            $parsed = date_parse(substr($ID, 6, 8));

            if (!(isset($parsed['warning_count']) && $parsed['warning_count'] == 0))
            { //年月日位校验
                return false;
            }

            $base = substr($ID, 0, 17);

            $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];

            $tokens = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];

            $checkSum = 0;
            for ($i=0; $i<17; $i++) {
                $checkSum += intval(substr($base, $i, 1)) * $factor[$i];
            }

            $mod = $checkSum % 11;
            $token = $tokens[$mod];

            $lastChar = strtoupper(substr($ID, 17, 1));

            return $lastChar === $token;
        };

        if (!preg_match('/^\d{17}[0-9xX]$/', $this->$attribute) || !$checkIDCode($this->$attribute)) {
            $this->addError($attribute, '请填写正确的身份证号码');
        }
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return false;
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['userUsername' => $username]);
    }

    /**
     * Finds user by fullName
     *
     * 还是无法解决重名问题
     * @param $fullName
     * @return static|null
     */
    public static function findByFullName($fullName)
    {
        return static::findOne(['userFullname' => $fullName]);
    }

    /**
     * Finds user by citizenID
     *
     * @param $citizenID
     * @return static
     */
    public function findByCitizenID($citizenID)
    {
        return static::findOne(['userCitizenID' => $citizenID]);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->userID;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->userPassword = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->authKey = Yii::$app->security->generateRandomString();
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->userPassword);
    }

    /**
     * 获取普通客户的专利
     * 如果普通客户只有一个专利的话，那就更改getUserPatents()为getUserPatent(),hasMany()改为hasOne()
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserPatents()
    {
        return $this->hasMany(Patents::className(), ['patentUserID' => 'userID']);
    }

    /**
     * 获取案源人的专利
     * 这里做了个判断，如果是普通客户,直接返回null，虽然他的查询结果也是null
     *
     * @return null|\yii\db\ActiveQuery
     */
    public function getLiaisonPatents()
    {
        if ($this->userRole == 1) return null;
        return $this->hasMany(Patents::className(), ['patentUserLiaisonID' => 'userID']);
    }
}
