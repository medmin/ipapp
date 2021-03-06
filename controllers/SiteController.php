<?php

namespace app\controllers;

use app\models\Users;
use app\models\WxSignupForm;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\SignupForm;
use app\queues\SendEmailJob;
use app\models\WxUser;
use app\models\WxUserinfo;

class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'signup', 'wx-login', 'wx-signup', 'wx-signup-bind'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['about','error'],
                        'allow' => true,
                        'roles' => ['?', '@']
                    ],
                    [
                        'actions' => [],
                        'allow' => true,
                        'roles' => ['@'],
                    ]
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                'minLength' => 4,
                'maxLength' => 4,
                'offset' => 1,
                'height' => 35
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        if (!Yii::$app->user->isGuest && Yii::$app->user->identity->userRole == Users::ROLE_EMPLOYEE) {
            return $this->redirect('/users/index');
        }
        return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        if ($this->isMicroMessage()) {
            return $this->redirect('wx-login');
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            if (Yii::$app->user->identity->userRole == Users::ROLE_CLIENT && Yii::$app->getUser()->getReturnUrl() == Yii::$app->getHomeUrl()) {
                return $this->redirect('/users/my-patents');
            } else {
                return $this->goBack();
            }
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $this->layout = 'main-login';
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post()) && $user = $model->signup()) {
            if (Yii::$app->getUser()->login($user)) {
                return $this->redirect('/users/my-patents');
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    public function actionWxLogin()
    {
        $app_id = $this->isMicroMessage() ? Yii::$app->params['wechat']['id'] : Yii::$app->params['wechat_open']['app_id'];
        $app_secret = $this->isMicroMessage() ? Yii::$app->params['wechat']['secret'] : Yii::$app->params['wechat_open']['app_secret'];
        if (isset($_REQUEST['code'])) {
            try{
                $weiAPI = new \app\lib\WechatAPI($app_id, $app_secret);
                $userinfo = $weiAPI->authUserInfo(Yii::$app->request->getQueryParam('code'));
            } catch (\Exception $e) {
                Yii::$app->session->setFlash('error','授权失败');
                return $this->redirect(['login']);
            }
            $wxUser = WxUser::findOne(['unionid'=>$userinfo['unionid']]);
            if($wxUser){
                if ($this->isMicroMessage() && $wxUser->fakeid == '') {
                    $wxUser->fakeid = $userinfo['openid'];
                    $wxUser->save();
                }
                $userIdentity = Users::findIdentity($wxUser->userID);
                if(Yii::$app->user->login($userIdentity, 3600 * 24 * 30)){
                    if (Yii::$app->user->identity->userRole == Users::ROLE_CLIENT) {
                        return $this->redirect('/users/my-patents');
                    } else {
                        return $this->goBack();
                    }
                } else {
                    // 这个else 我自己都觉得不会出现，去掉末尾会一直提示缺少return - -
                    Yii::$app->getSession()->set('error','未知错误');
                    return $this->redirect(['login']);
                }
            }else {
                if(WxUserinfo::findOne(['unionid'=> $userinfo['unionid']])===null){
                    $wxUserinfo = new WxUserinfo();
                    $wxUserinfo->openid = $userinfo['openid'];
                    $wxUserinfo->unionid = $userinfo['unionid'];
                    $wxUserinfo->nickname = $userinfo['nickname'];
                    $wxUserinfo->sex = $userinfo['sex'];
                    $wxUserinfo->province = $userinfo['province'];
                    $wxUserinfo->city = $userinfo['city'];
                    $wxUserinfo->country = $userinfo['country'];
                    $wxUserinfo->headimgurl = $userinfo['headimgurl'];
                    $wxUserinfo->createdAt = time();
                    $wxUserinfo->save();
                }

                Yii::$app->getSession()->set('wx_unionid',$userinfo['unionid']);
                Yii::$app->getSession()->set('wx_openid',$userinfo['openid']);
                return $this->redirect(['wx-signup']);
            }
        } else {
            $redirect_url = urlencode(Url::to(['site/wx-login'], true));
//            $redirect_url = urlencode('http://kf.shineip.com/site/wx-login');
            $state = md5(time());
            if ($this->isMicroMessage()) {
                $wxUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$app_id&redirect_uri=$redirect_url&response_type=code&scope=snsapi_userinfo&state=$state#wechat_redirect";
            } else {
                $wxUrl = "https://open.weixin.qq.com/connect/qrconnect?appid=$app_id&redirect_uri=$redirect_url&response_type=code&scope=snsapi_login&state=$state#wechat_redirect";
            }
            return $this->redirect($wxUrl);
        }
    }

    /**
     * web 微信注册
     *
     * @return string|Response
     */
    public function actionWxSignup()
    {
        $model = new WxSignupForm(['scenario' => WxSignupForm::SCENARIO_REGISTER]);
        if (!Yii::$app->getSession()->get('wx_unionid')) {
            return $this->redirect(['wx-login']);
        }
        $model->unionid = Yii::$app->getSession()->get('wx_unionid');
        $model->openid = Yii::$app->getSession()->get('wx_openid', '');
        if ($model->load(Yii::$app->request->post()) && ($user = $model->signup()) !== null){
            if (Yii::$app->getUser()->login($user)) {
                return $this->redirect('/users/my-patents');
            }
        }
        $this->layout = 'main-login';
        return $this->render('wx-signup', [
            'model' => $model,
        ]);
    }

    /**
     * 微信绑定
     *
     * @return string|Response
     */
    public function actionWxSignupBind()
    {
        $model = new WxSignupForm(['scenario' => WxSignupForm::SCENARIO_BIND]);
        if(!Yii::$app->getSession()->get('wx_unionid')) {
            return $this->redirect(['wx-login']);
        }
        $model->unionid = Yii::$app->getSession()->get('wx_unionid');
        $model->openid = Yii::$app->getSession()->get('wx_openid', '');

        if ($model->load(Yii::$app->request->post()) && ($user = $model->bind())) {
            if (Yii::$app->getUser()->login($user)) {
                return $this->goHome();
            }
        }
        $this->layout = 'main-login';
        return $this->render('wx-signup', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact()) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * 是否通过微信访问
     * return bool
     */
    protected function isMicroMessage()
    {
        if (YII_DEBUG) return false;
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
            return true;
        }
        return false;
    }
}
