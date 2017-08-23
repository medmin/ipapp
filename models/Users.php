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
    /* 普通用户 */
    const ROLE_CLIENT = 1;

    /* 员工/Liaison/商务 */
    const ROLE_EMPLOYEE = 2;

    /* 二级管理员 */
    const ROLE_SECONDARY_ADMIN = 3;

    /* 超级管理员 */
    const ROLE_ADMIN = 4;

    /* demo 测试使用 */
    const DEMO = 99;

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
            [['userUsername', 'userEmail'], 'unique'],
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
        if ($this->$attribute !== 'N/A' && (!preg_match('/^\d{17}[0-9xX]$/', $this->$attribute) || !$checkIDCode($this->$attribute))) {
            $this->addError($attribute, '请填写正确的身份证号码');
        }
    }

    /**
     * userRole 和 RBAC 中的对应关系
     *
     * @return array
     */
    public static function RoleCorrespond()
    {
        return [
            self::ROLE_EMPLOYEE => 'manager',
            self::ROLE_SECONDARY_ADMIN => 'secadmin',
            self::ROLE_ADMIN => 'admin',
        ];
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
     * Finds user by email
     *
     * @param  string $email
     * @return static|null
     */
    public static function findByEmail($email)
    {
        return static::findOne(['userEmail' => $email]);
    }

    /**
     * 通过用户名或者邮箱来登录
     *
     * @param $usernameOrEmail
     * @return Users|null
     */
    public static function findByUsernameOrEmail($usernameOrEmail)
    {
        if (filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL)) {
            return self::findByEmail($usernameOrEmail);
        }
        return self::findByUsername($usernameOrEmail);
    }

    /**
     * @param $id
     * @return static
     */
    public static function findByID($id)
    {
        return static::findOne(['userID' => $id]);
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
        if ($this->userRole == self::ROLE_CLIENT) return null;
        return $this->hasMany(Patents::className(), ['patentUserLiaisonID' => 'userID']);
    }

    /**
     * 获取微信unionid 和 fakeid
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWxUser()
    {
        return $this->hasOne(WxUser::className(), ['userID' => 'userID']);
    }

    /**
     * 获取关联微信,拿到WxUserinfo信息
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWxUserinfo()
    {
        return $this->hasOne(WxUserinfo::className(), ['unionid' => 'unionid'])->viaTable(WxUser::tableName(), ['userID' => 'userID']);
    }

    /**
     * 修改用户之后更改权限以及更改相关专利
     * 
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        // 如果是更新并且userRole设置了
        if (!$insert && isset($changedAttributes['userRole'])) {
            $auth = Yii::$app->authManager;
            if ($changedAttributes['userRole'] != Users::ROLE_CLIENT) {
                // 先移除,踩坑：revoke() 和 assign() 第一参数要求是yii\rbac\Role对象，不是role的字符串
                $auth->revoke($auth->getRole(self::RoleCorrespond()[$changedAttributes['userRole']]), $this->userID);
            }
            if ($this->userRole != Users::ROLE_CLIENT) {
                // 再添加
                $auth->assign($auth->getRole(self::RoleCorrespond()[$this->userRole]), $this->userID);
            }
        }
        // 如果更新了所属商务专员 需要同时更新专利以及专利事件 - -!
        if (!$insert && isset($changedAttributes['userLiaisonID'])) {
            if ($this->userLiaisonID != $changedAttributes['userLiaisonID']) {
                Patents::updateAll(['patentUserLiaisonID' => $this->userLiaisonID, 'patentUserLiaison' => $this->userLiaisonID ? Users::findOne($this->userLiaisonID)->userFullname : ''], ['patentUserID' => $this->userID]);
                Patentevents::updateAll(['eventUserLiaisonID' => $this->userLiaisonID, 'eventUserLiaison' => $this->userLiaisonID ? Users::findOne($this->userLiaisonID)->userFullname : ''], ['eventUserID' => $this->userID]);
            }
        }
    }
}
