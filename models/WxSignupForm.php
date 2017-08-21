<?php
/**
 * User: Mr-mao
 * Date: 2017/8/16
 * Time: 21:35
 */

namespace app\models;

use yii\base\Model;
use Yii;
use yii\base\Exception;

class WxSignupForm extends Model
{
    const SCENARIO_REGISTER = 'register';
    const SCENARIO_BIND = 'bind';

    public $unionid;
    public $openid;
    public $username;
    public $password;
    public $repeatPassword;
    public $email;
    public $fullname;

    /**
     * 场景区分
     * 
     * @return array
     */
    public function scenarios(){
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_REGISTER] = ['unionid', 'email', 'password', 'repeatPassword', 'fullname'];
        $scenarios[self::SCENARIO_BIND] = ['unionid', 'username', 'password'];
        return $scenarios;
    }

    /**
     * 验证规则
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['unionid', 'email', 'password', 'repeatPassword', 'fullname'], 'required', 'on' => self::SCENARIO_REGISTER],
            [['unionid', 'username', 'password'], 'required', 'on' => self::SCENARIO_BIND],
            [['unionid', 'openid'], 'string', 'max'=>50],
            ['password', 'string', 'min' => 6],
            ['password', 'validatePassword', 'on' => self::SCENARIO_BIND],
            ['repeatPassword', 'compare', 'compareAttribute'=>'password', 'message' => Yii::t('app','The two passwords differ'), 'on' => self::SCENARIO_REGISTER],
            ['username', 'string', 'min' => 2, 'max' => 255],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetAttribute' => 'userEmail', 'targetClass' => '\app\models\Users', 'message' => Yii::t('app','This email has already been taken'), 'on' => self::SCENARIO_REGISTER],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'username' => Yii::t('app','Username'),
            'password' => Yii::t('app', 'Password'),
            'fullname' => Yii::t('app', 'Fullname'),
            'repeatPassword' => Yii::t('app', 'Repeat Password'),
        ];
    }

    public function signup()
    {
        if (!$this->validate()) {
            return null;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            /**
             * 创建用户
             */
            $users_count = Users::find()->count() + 1234;
            $user = new Users();
            $user->userUsername = '阳光惠远_' . $users_count;
            $user->setPassword($this->password);
            $user->userEmail = $this->email;
            $user->userFullname = $this->fullname;
            // 以下信息可空
            $user->userCitizenID = 'N/A';
            $user->userOrganization = 'N/A';
            $user->userCellphone = 'N/A';
            $user->userLandline = 'N/A';
            $user->userAddress = 'N/A'; // 可写微信地址
            $user->userLiaison = 'N/A';
            $user->userRole = Users::ROLE_CLIENT;
            $user->userLiaisonID = 0;
            $user->userNote = 'N/A';
            $user->UnixTimestamp = time() * 1000;
            $user->generateAuthKey();
            if (!$user->save()) {
                throw new Exception();
            }

            /**
             * 创建 WxUser
             */
            $wxUser = new WxUser();
            $wxUser->userID = $user->userID;
            $wxUser->unionid = $this->unionid;
            $wxUser->fakeid = $this->isMicroMessage() ? $this->openid : '';
            $wxUser->createdAt = time();
            if (!$wxUser->save()) {
                throw new Exception();
            }

            $transaction->commit();
            return $user;
        } catch (Exception $e) {
            Yii::error($e->getMessage());
            $transaction->rollBack();
        }
        return null;
    }

    public function bind()
    {
        if (!$this->validate()) {
            return null;
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {

            /**
             * 检查用户,如果demo用户禁止绑定
             */
            $user = Users::findByUsernameOrEmail($this->username);
            if($user == null || $user->userUsername == 'demo'){
                throw new Exception();
            }

            /**
             * 删除已有绑定
             */
            $wxUser = WxUser::findOne(['userID' => $user->userID]);
            if($wxUser != null && !$wxUser->delete()){
                throw new Exception();
            }

            /**
             * 创建绑定
             */
            $wxUser = new WxUser();
            $wxUser->userID = $user->userID;
            $wxUser->unionid = $this->unionid;
            $wxUser->fakeid = $this->isMicroMessage() ? $this->openid : '';
            $wxUser->createdAt = time();
            if(!$wxUser->save()){
                throw new Exception();
            }

            $transaction->commit();
            return $user;

        } catch (Exception $e) {
            Yii::error($e->getMessage());
            $transaction->rollBack();
        }
        return null;
    }

    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = Users::findByUsernameOrEmail($this->username);
            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, Yii::t('app','Incorrect username or password.'));
            }
        }
    }

    /**
     * 是否通过微信访问
     * return bool
     */
    protected function isMicroMessage()
    {
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
            return true;
        }
        return false;
    }
}
