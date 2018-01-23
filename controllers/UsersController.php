<?php

namespace app\controllers;

use app\models\AnnualFeeMonitors;
use app\models\Notification;
use app\models\Orders;
use app\models\Patents;
use app\models\UserLevel;
use Yii;
use app\models\Users;
use app\models\UsersSearch;
use yii\db\Query;
use yii\helpers\Json;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use GuzzleHttp\Client;

/**
 * UsersController implements the CRUD actions for Users model.
 */
class UsersController extends BaseController
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
                    'check-exist' => ['POST'],
                    'unfollow-patent' => ['POST'],
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
                        'actions' => ['index', 'view', 'notify', 'events-schedule', 'client-monitor-patents', 'patents-search', 'assignment', 'delete-assignment'],
                        'roles' => ['admin', 'manager', 'secadmin']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['personal-settings', 'reset-password', 'my-patents', 'monitor-patents', 'monitor-unpaid-list', 'follow-patents', 'unfollow-patent', 'records', 'show-unpaid-fee', 'search'],
                        'roles' => ['@']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['check-exist'],
                        'roles' => ['?']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'notify'],
                        'roles' => ['demo']
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
//            $userEmail = $model->userEmail;
//                $liaisonEmail = $model::findByID($model->userLiaisonID)->userEmail;

//            Yii::$app->queue->push(new SendEmailJob([
//                'mailViewFileNameString' => 'userAddedByAdminMsg',
//                'varToViewArray' => ['model' => $model],
//                'fromAddressArray' => ['kf@shineip.com' => '阳光惠远客服中心'],
//                'toAddressArray' => [$userEmail,'info@shineip.com'],
//                'emailSubjectString' => '提醒：用户信息被修改'
//            ]));


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
     * 搜索用户，根据是否为邮箱或者数字来判断查找方式，返回用户简单数据
     *
     * @param $username
     * @return string
     */
    public function actionSearch($username)
    {
        if ((filter_var($username, FILTER_VALIDATE_EMAIL) && ($user = Users::findByEmail($username)))
            || (filter_var($username, FILTER_VALIDATE_INT) && ($user = Users::findByID($username)))) {
            return Json::encode(['error' => false, 'id' => $user->userID, 'username' => $user->userUsername, 'fullname' => $user->userFullname, 'email' => $user->userEmail]);
        }
        return Json::encode(['error' => true]);
    }

    /**
     * 给用户分配上下级
     *
     * @return string
     */
    public function actionAssignment()
    {
        $post = Yii::$app->request->post();
        if ($post['type'] == 'sub') {
            if (UserLevel::findOne(['user_id' => $post['user_id'], 'parent_id' => $post['id']])) {
                return Json::encode(['error' => true, 'message' => '不能将上级设为下级']);
            }
            if (UserLevel::findOne(['user_id' => $post['id'], 'parent_id' => $post['user_id']])) {
                return Json::encode(['error' => true, 'message' => '重复添加下级']);
            }
            $r = new UserLevel();
            $r->user_id = $post['id'];
            $r->parent_id = $post['user_id'];
            if ($r->save()) {
                return Json::encode(['error' => false]);
            }
        } elseif ($post['type'] == 'superior') {
            if (UserLevel::findOne(['user_id' => $post['id'], 'parent_id' => $post['user_id']])) {
                return Json::encode(['error' => true, 'message' => '不能将下级设为上级']);
            }
            if (UserLevel::findOne(['user_id' => $post['user_id'], 'parent_id' => $post['id']])) {
                return Json::encode(['error' => true, 'message' => '重复添加上级']);
            }
            $r = new UserLevel();
            $r->user_id = $post['user_id'];
            $r->parent_id = $post['id'];
            if ($r->save()) {
                return Json::encode(['error' => false]);
            }
        } else {
            return Json::encode(['error' => true, 'message' => '未知类型']);
        }
        return Json::encode(['error' => true, 'message' => '']);
    }

    /**
     * 取消用户上下级关系
     *
     * @return string
     */
    public function actionDeleteAssignment()
    {
        $post = Yii::$app->request->post();
        if ($post['type'] == 'sub') {
            $r = UserLevel::findOne(['parent_id' => $post['user_id'], 'user_id' => $post['id']]);
            if ($r) {
                $r->delete();
                return Json::encode(['error' => false, 'message' => 'ok']);
            } else {
                return Json::encode(['error' => true, 'message' => '用户不存在']);
            }
        } elseif ($post['type'] == 'superior') {
            $r = UserLevel::findOne(['parent_id' => $post['id'], 'user_id' => $post['user_id']]);
            if ($r) {
                $r->delete();
                return Json::encode(['error' => false, 'message' => 'ok']);
            } else {
                return Json::encode(['error' => true, 'message' => '用户不存在']);
            }
        } else {
            return Json::encode(['error' => true, 'message' => '未知类型']);
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
            /* demo 不能修改 */
            if (Yii::$app->user->identity->userRole == Users::DEMO) {
                return Json::encode(['code' => -1, 'message' => 'demo用户禁止修改']);
            }
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
     * 查看客户专利进度(所有的进度)
     *
     * @param $user_id
     * @return string
     * @throws ForbiddenHttpException
     */
    public function actionEventsSchedule($user_id)
    {
        if (Yii::$app->user->identity->userRole == Users::ROLE_EMPLOYEE
            && self::findModel($user_id)->userLiaisonID !== Yii::$app->user->id) {
            throw new ForbiddenHttpException('无权访问该用户');
        }
        $events = \app\models\Patentevents::find()->where(['eventUserID' => $user_id])->orderBy(['eventCreatUnixTS' => SORT_DESC])->all();
        return $this->render('events-schedule', ['events' => $events, 'username' => self::findModel($user_id)->userUsername]);
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
//            $userEmail = $model->userEmail;
//            $liaisonEmail = (new Users())::findByID($model->userLiaisonID)->userEmail;
//
//            Yii::$app->queue->push(new SendEmailJob([
//                'mailViewFileNameString' => 'userDelWarning',
//                'varToViewArray' => ['model' => $model],
//                'fromAddressArray' => ['kf@shineip.com' => '阳光惠远客服中心'],
//                'toAddressArray' => [$userEmail, $liaisonEmail, 'info@shineip.com'],
//                'emailSubjectString' => '警告: 客户信息被删除'
//            ]));
            $wxUser = \app\models\WxUser::findOne(['userID' => $model->userID]);
            if ($wxUser) {
                $wxUser->delete();
            }
            $model->delete();
        }

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
                'pageSize' => 20,
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
        // 查询绑定的专利
        $patents = Patents::find()
            ->where(['patentUserID' => Yii::$app->user->id])
            ->asArray()
            ->all();
        $children_patents = Users::childrenPatents(Yii::$app->user->id);
        return $this->render('my-patents', ['patents' => array_merge($patents, $children_patents)]);
    }

    /**
     * 年费监管页
     *
     * @param integer $id userID
     * @return string
     */
    public function actionMonitorPatents($id = null)
    {
        if ($id == null) {
            $id = Yii::$app->user->id;
        }
        if (Yii::$app->user->identity->userRole === Users::ROLE_CLIENT) {
            $id = Yii::$app->user->id;
        }

        // 实例化guzzle
        $client = new Client(['base_uri' => Yii::$app->params['api_base_uri']]);
        // 查询所有监管的申请号
        $application_nos = AnnualFeeMonitors::find()
            ->select(['application_no'])
            ->where(['user_id' => Yii::$app->user->id])
            ->asArray()
            ->column();

        $patents = [];
        // 通过api获取数据
        foreach ($application_nos as $application_no) {
            // 获取专利信息
            try {
                $response = $client->request('GET', '/patents/view/'.$application_no);
                $patent = json_decode($response->getBody(), true);
                switch ($patent['general_status']) {
                    case '无效':
                        $patent['general_status_color'] = '#999';
                        break;
                    case '在审':
                        $patent['general_status_color'] = '#f39c12';
                        break;
                    default:
                        $patent['general_status_color'] = '#17a46d';
                        break;
                }
                $patents[$application_no] = $patent;
            } catch (\Exception $e) {
                continue;
            }
            // 获取费用信息
            try {
                $response = $client->request('GET', "/patents/{$application_no}/unpaid-fees");
                $patents[$application_no]['fee_info'] = json_decode($response->getBody(), true);
            }
            catch (\Exception $e) {

            }
        }

        return $this->render('monitor-patents', ['patents' => $patents]);

        // $dataProvider = new ActiveDataProvider([
        //     'query' => Patents::find()->where(['in', 'patentApplicationNo', (new Query())->select('application_no')->from('annual_fee_monitors')->where(['user_id' => $id])]),
        //     'pagination' => [
        //         'pageSize' => 10,
        //     ],
        //     'sort' => [
        //         'defaultOrder' => [
        //             'patentFeeDueDate' => SORT_ASC,
        //         ]
        //     ]
        // ]);

        // return $this->render('monitor-patents', ['dataProvider' => $dataProvider]);
    }


    /**
     * 效果同上，只是在view层展示只有支付的专利
     *
     * @param null $id
     * @return string
     */
    public function actionMonitorUnpaidList($id = null)
    {
        if ($id == null) {
            $id = Yii::$app->user->id;
        }
        if (Yii::$app->user->identity->userRole === Users::ROLE_CLIENT) {
            $id = Yii::$app->user->id;
        }
        $dataProvider = new ActiveDataProvider([
            'query' => Patents::find()
                    ->where(['in', 'patentApplicationNo', (new Query())->select('application_no')->from('annual_fee_monitors')
                    ->where(['user_id' => $id])]),
            'pagination' => [
                'pageSize' => 10,
            ],
            'sort' => [
                'defaultOrder' => [
                    'patentFeeDueDate' => SORT_ASC,
                ]
            ]
        ]);
        return $this->render('monitor-unpaid-list', ['dataProvider' => $dataProvider]);
    }

    /**
     * 添加监管
     *
     * @param integer $user_id
     * @return bool|string
     */
    public function actionFollowPatents($user_id = null)
    {
        if (Yii::$app->user->identity->userRole === Users::ROLE_CLIENT || !$user_id) {
            $user_id = Yii::$app->user->id;
        }
        if (Yii::$app->request->isPost) {
            $application_no = Yii::$app->request->post('application_no');
            if (AnnualFeeMonitors::findOne(['user_id' => $user_id, 'application_no' => $application_no])) {
                return false;
            } else {
                $model = new AnnualFeeMonitors();
                $model->application_no = $application_no;
                $model->user_id = $user_id;
                return $model->save();
            }
        } else {
            $application_no = trim(Yii::$app->request->getQueryParam('application_no'));
            // 通过api获取数据
            $client = new Client(['base_uri' => Yii::$app->params['api_base_uri']]);
            try {
                $response = $client->request('GET', '/patents/view/'.$application_no);
                $patents[] = json_decode($response->getBody(), true);
            } catch (\Exception $e) {
                $patents = [];
            }
            return $this->render('follow-patents', ['patents' => $patents]);
        }
    }

    /**
     * 取消监管
     *
     * @param string $application_no
     * @param string|null $user_id
     * @return false|int
     */
    public function actionUnfollowPatent($application_no, $user_id = null)
    {
        if (Yii::$app->user->identity->userRole === Users::ROLE_CLIENT || !$user_id) {
            $user_id = Yii::$app->user->id;
        }
        if ($model = AnnualFeeMonitors::findOne(['user_id' => $user_id, 'application_no' => $application_no])) {
            return $model->delete();
        }
        return false;
    }

    /**
     * 获取用户缴费记录
     *
     * @return string
     */
    public function actionRecords()
    {
       $dataProvider = new ActiveDataProvider([
           'query' => Orders::find()->where(['user_id' => Yii::$app->user->id, 'status' => Orders::STATUS_PAID]),
           'pagination' => [
               'pageSize' => 20
           ],
           'sort' => false,
       ]);
        return $this->render('records', ['dataProvider' => $dataProvider]);
    }

    /**
     * 管理员查看用户监管页
     *
     * @param $user_id
     * @return string
     */
    public function actionClientMonitorPatents($user_id)
    {
        $client = new Client(['base_uri' => Yii::$app->params['api_base_uri']]);
        $monitors = AnnualFeeMonitors::find()
            ->select(['application_no', 'created_at'])
            ->where(['user_id' => $user_id])
            ->orderBy('created_at DESC')
            ->asArray()
            ->all();
        $resultData = [];
        foreach ($monitors as $value) {
            try {
                $response = $client->request('GET', '/patents/view/'.$value['application_no']);
                $patent = json_decode($response->getBody(), true);
                $patent['monitor_date'] = $value['created_at'];
                $resultData[] = $patent;
            } catch (\Exception $e) {
                Yii::error($e->getMessage());
                continue;
            }
        }
        $dataProvider = new ArrayDataProvider([
            'allModels' => $resultData,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
        return $this->render('/common/client-monitor-patents', ['dataProvider' => $dataProvider ]);
    }

    /**
     * 管理员搜索专利给用户添加
     *
     * @return string
     */
    public function actionPatentsSearch()
    {
        $No = trim(Yii::$app->request->post('No'));
        $institution = trim(Yii::$app->request->post('institution'));
        if (!$No && !$institution) {
            return '';
        }
        $client = new Client(['base_uri' => Yii::$app->params['api_base_uri']]);
        $resultData = [];
        try {
            $response = $client->request('GET', '/patents/view/'.$No);
            $resultData[] = json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Yii::error($e->getMessage());
        }
        $dataProvider = new ArrayDataProvider([
            'allModels' => $resultData,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
        return $this->renderPartial('/common/client-search-patents', ['patents' => $dataProvider]); // 目前支持支精确查找,只返回一条结果
    }

    /**
     * 获取未缴年费
     *
     * @param $application_no
     * @return string
     */
    public function actionShowUnpaidFee($application_no)
    {
        // 实例化guzzle
        $client = new \GuzzleHttp\Client(['base_uri' => Yii::$app->params['api_base_uri']]);
        // 获取费用信息
        try {
            $response = $client->request('GET', "/patents/{$application_no}/unpaid-fees");
            $fee_info = json_decode($response->getBody(), true);
        }
        catch (\Exception $e) {
            $fee_info = [];
        }
        $dataProvider = new ArrayDataProvider([
            'allModels' => $fee_info,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'attributes' => ['id', 'name'],
            ],
        ]);

        return $this->renderPartial('/common/unpaid-fee-list', ['models' => $dataProvider]);

        // $dataProvider = new ActiveDataProvider([
        //     'query' => UnpaidAnnualFee::find()->where(['patentAjxxbID' => 'AJ151100_1100', 'status' => UnpaidAnnualFee::UNPAID])->orderBy(['due_date' => SORT_ASC]),
        //     'sort' => false,
        // ]);
        // return $this->renderPartial('/common/unpaid-fee-list', ['models' => $dataProvider]);
    }

    /**
     * ajax检测用户是否存在
     *
     * @return string
     */
    public function actionCheckExist()
    {
        $username = Yii::$app->request->post('username');
        if (Users::findByUsernameOrEmail($username)) {
            return Json::encode(['code' => true]);
        }
        return Json::encode(['code' => false]);
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
            throw new NotFoundHttpException('该用户不存在');
        }
    }
}
