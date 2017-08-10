<?php
/**
 * User: Mr-mao
 * Date: 2017/7/24
 * Time: 12:06
 */

namespace app\models;

use yii\base\Model;
use Yii;

class SignupForm extends Model
{
    public $username;
    public $email;
    public $password;
    public $citizenID;
    public $repeatPassword;
    public $organization;
    public $name;
    public $cellPhone;
    public $landLine;
    public $address;
    public $liaison;
    public $note;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'email'], 'trim'],
            [['username', 'email', 'password', 'repeatPassword', 'citizenID'], 'required'],
            ['repeatPassword', 'compare', 'compareAttribute'=>'password', 'message' => Yii::t('app','The two passwords differ')],
            ['username', 'unique', 'targetAttribute' => 'userUsername', 'targetClass' => '\app\models\Users', 'message' => Yii::t('app','This username has already been taken')],
            ['username', 'string', 'min' => 2, 'max' => 255],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetAttribute' => 'userEmail', 'targetClass' => '\app\models\Users', 'message' => Yii::t('app','This email has already been taken')],
            ['password', 'string', 'min' => 6],
            ['citizenID', 'unique', 'targetAttribute' => 'userCitizenID', 'targetClass' => '\app\models\Users', 'message' => Yii::t('app','This citizenID number has already been taken')],
            [['organization', 'name', 'cellPhone', 'landLine', 'address', 'liaison', 'note'], 'default', 'value' => 'N/A'],
//            [['organization', 'name', 'cellPhone', 'landLine', 'address', 'liaison', 'note'], 'required'],
            [['organization', 'name', 'cellPhone', 'landLine', 'address', 'liaison', 'note'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => Yii::t('app', 'Username'),
            'email' => Yii::t('app', 'Email'),
            'password' => Yii::t('app', 'Password'),
            'repeatPassword' => Yii::t('app', 'Repeat Password'),
            'citizenID' => Yii::t('app', 'Citizen Id'),
            'organization' => Yii::t('app', 'Organization'),
            'name' => Yii::t('app', 'Name'),
            'landLine' => Yii::t('app', 'Land Line'),
            'cellPhone' => Yii::t('app', 'Cell Phone'),
            'address' => Yii::t('app', 'Address')
        ];
    }

    /**
     * Signs user up.
     *
     * @return Users|null the saved model or null if saving fails
     */
    public function signup()
    {
        if (!$this->validate()) {
            return null;
        }

        $user = new Users();
        $user->userUsername = $this->username;
        $user->setPassword($this->password);
        $user->userCitizenID = $this->citizenID;
        $user->userOrganization = $this->organization;
        $user->userFullname = $this->name;
        $user->userEmail = $this->email;
        $user->userCellphone = $this->cellPhone;
        $user->userLandline = $this->landLine;
        $user->userAddress = $this->address;
        $user->userLiaison = $this->liaison;
        $user->userRole = Users::ROLE_CLIENT;
        $user->userNote = $this->note;
        if ($this->liaison !== 'N/A') {
            $liaison = Users::findByFullName($this->liaison);
            if ($liaison) {
                $user->userLiaisonID = $liaison->userID;
            }
        }else{
            $user->userLiaisonID = 0;
        }
        $user->UnixTimestamp = time() * 1000;
        $user->generateAuthKey();

        return $user->save() ? $user : null;
    }
}