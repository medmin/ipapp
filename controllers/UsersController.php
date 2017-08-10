<?php

namespace app\controllers;

use app\models\Notification;
use app\models\Patents;
use Symfony\Component\Yaml\Tests\A;
use Yii;
use app\models\Users;
use app\models\UsersSearch;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\queues\SendEmailJob;
use yii\data\ActiveDataProvider;

/**
 * UsersController implements the CRUD actions for Users model.
 */
class UsersController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => ['admin']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['update', 'create'],
                        'roles' => ['admin']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'view', 'notify'],
                        'roles' => ['admin', 'manager', 'secadmin']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['personal-settings', 'reset-password', 'my-patents'],
                        'roles' => ['@']
                    ]
                ],
            ],
        ];
    }

    /**
     * Lists all Users models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UsersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Users model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Users model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Users();

        if ($model->load(Yii::$app->request->post()))
        {
            // 如果是案源人创建了一个客户，那么就默认这个客户的liaison为这个案源人本人
            // 这个if可以取消，暂时先留着(rbac中存在createEmployee)
            if (!Yii::$app->user->can('createEmployee')) {
                $model->userRole = Users::ROLE_CLIENT;
                $model->userLiaison = Yii::$app->user->identity->userFullname;
                $model->userLiaisonID = Yii::$app->user->id;
            } else {
                $model->userLiaison = $model->userLiaisonID == 0 ? 'N/A' : Users::findOne($model->userLiaisonID)->userFullname;
            }
            $model->generateAuthKey();
            $model->setPassword($model->userPassword);
            $model->UnixTimestamp = time() * 1000;
            if ($model->save()) {
                // 根据所创建的角色来分配相应的ROLE
                if ($model->userRole == Users::ROLE_EMPLOYEE) {
                    $auth = Yii::$app->authManager;
                    $authorRole = $auth->getRole('manager');
                    $auth->assign($authorRole, $model->userID);
                } elseif ($model->userRole == Users::ROLE_SECONDARY_ADMIN) {
                    $auth = Yii::$app->authManager;
                    $authorRole = $auth->getRole('secadmin');
                    $auth->assign($authorRole, $model->userID);
                }
                //经过这个controller，是案源人或admin手动添加的Users，发的是通知邮件
//                $liaisonEmail = $model::findByID($model->userLiaisonID)->userEmail;

//                Yii::$app->queue->push(new SendEmailJob([
//                    'mailViewFileNameString' => 'userAddedByAdminMsg',
//                    'varToViewArray' => ['model' => $model],
//                    'fromAddressArray' => ['kf@shineip.com' => '阳光惠远客服中心'],
//                    'toAddressArray' => [$model->userEmail, 'info@shineip.com'],
//                    'emailSubjectString' => '欢迎您注册新用户'
//                ]));


                return $this->redirect(['view', 'id' => $model->userID]);
            }
        }
        return $this->render('create', [
            'model' => $model,
        ]);

    }

    /**
     * Updates an existing Users model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save())
        {
            //经过这个controller，是案源人或admin手动添加的Users，发的是通知邮件
            $userEmail = $model->userEmail;
//                $liaisonEmail = $model::findByID($model->userLiaisonID)->userEmail;

            Yii::$app->queue->push(new SendEmailJob([
                'mailViewFileNameString' => 'userAddedByAdminMsg',
                'varToViewArray' => ['model' => $model],
                'fromAddressArray' => ['kf@shineip.com' => '阳光惠远客服中心'],
                'toAddressArray' => [$userEmail,'info@shineip.com'],
                'emailSubjectString' => '提醒：用户信息被修改'
            ]));


            return $this->redirect(['view', 'id' => $model->userID]);
        }
        else
        {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * 个人资料修改页
     *
     * @return string
     */
    public function actionPersonalSettings()
    {
        $model = $this->findModel(Yii::$app->user->id);
        if ($model->load(Yii::$app->request->post()) && $model->save()){
            Yii::$app->session->setFlash('profile', '基本资料更新成功,如需更多修改请联系管理员');
        }
        return $this->render('profile', ['model' => $model]);
    }

    /**
     * 修改密码
     *
     * @return bool|string
     */
    public function actionResetPassword()
    {
        $id = Yii::$app->request->post('id');
        if ($id && Yii::$app->user->identity->userRole == Users::ROLE_EMPLOYEE) {
            // TODO
            return true;
        }else{
            $client = Users::findOne(Yii::$app->user->id);
            if (!$client->validatePassword(Yii::$app->request->post('oldPassword'))) {
                return Json::encode(['code' => -1, 'message' => Yii::t('app','Old Password is invalid')]);
            }
            $newPassword = Yii::$app->request->post('newPassword');
            if (Yii::$app->request->post('confirmPassword') !== $newPassword) {
                return Json::encode(['code' => -2, 'message' => Yii::t('app','Password doesn\'t match the confirmation')]);
            }
            $client->setPassword($newPassword);
            if ($client->save()) {
                return Json::encode(['code' => 1]);
            }else {
                return JSON::encode(['code' => -3, 'message' => Yii::t('app','Length mismatch')]);
            }
        }
    }

    /**
     * Deletes an existing Users model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        //先获取要删除的Users对象，发警告邮件
        $model = $this->findModel($id);

        if ($model->userRole == Users::ROLE_CLIENT) {
            $userEmail = $model->userEmail;
            $liaisonEmail = (new Users())::findByID($model->userLiaisonID)->userEmail;

            Yii::$app->queue->push(new SendEmailJob([
                'mailViewFileNameString' => 'userDelWarning',
                'varToViewArray' => ['model' => $model],
                'fromAddressArray' => ['kf@shineip.com' => '阳光惠远客服中心'],
                'toAddressArray' => [$userEmail, $liaisonEmail, 'info@shineip.com'],
                'emailSubjectString' => '警告: 客户信息被删除'
            ]));
        }

        //先发邮件，再删除
        $model->delete();

        return $this->redirect(['index']);
    }

    /**
     * 获取未读消息 所有消息
     * @return string
     */
    public function actionNotify()
    {
        $model = Notification::find()->where(['receiver' => Yii::$app->user->id, 'status' => 0])->all();
        Notification::ignore();
        $allNotifies = new ActiveDataProvider([
            'query' => Notification::find()->where(['receiver' => Yii::$app->user->id]),
            'pagination' => [
                'pageSize' => 1000, // 稍后处理分页问题 TODO
            ],
            'sort' => [
                'defaultOrder' => [
                    'createdAt' => SORT_DESC,
                ]
            ]
        ]);
        return $this->render('notify', ['models' => $model, 'allModels' => $allNotifies]);
    }

    /**
     * 获取个人所有专利
     * @return string
     */
    public function actionMyPatents()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Patents::find()->where(['patentUserID' => Yii::$app->user->id]),
            'pagination' => [
                'pageSize' => 10,
            ],
            'sort' => [
                'defaultOrder' => [
                    'UnixTimestamp' => SORT_DESC,
                ]
            ]
        ]);

        return $this->render('my-patents', ['dataProvider' => $dataProvider]);
    }

    /**
     * Finds the Users model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Users the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Users::findOne($id)) !== null)
        {
            return $model;
        }
        else
        {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
