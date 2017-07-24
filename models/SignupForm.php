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
            [['username', 'email', 'password', 'repeatPassword'], 'required'],
            ['repeatPassword', 'compare', 'compareAttribute'=>'password', 'message' => '两次密码不一致'],
            ['username', 'unique', 'targetAttribute' => 'clientUsername', 'targetClass' => '\app\models\Clients', 'message' => '用户名已存在'],
            ['username', 'string', 'min' => 2, 'max' => 255],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetAttribute' => 'ClientEmail', 'targetClass' => '\app\models\Clients', 'message' => '邮箱已被占用'],
            ['password', 'string', 'min' => 6],

            [['organization', 'name', 'cellPhone', 'landLine', 'address', 'liaison', 'note'], 'default', 'value' => 'NULL'],
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
        $user->userRole = 1; // 1为普通客户 2为案源人
        $user->userNote = $this->note;
        $user->UnixTimestamp = time() * 1000;
        $user->generateAuthKey();

        return $user->save() ? $user : null;
    }
}