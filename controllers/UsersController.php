<?php

namespace app\controllers;

use Yii;
use app\models\Users;
use app\models\UsersSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\User;
use app\queues\SendEmailJob;

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
                        'actions' => ['index', 'view', 'create', 'update'],
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
            if (!Yii::$app->user->can('createEmployee'))
            {
                $model->userRole = 1;
                $model->userLiaison = Yii::$app->user->identity->userFullname;
                $model->userLiaisonID = Yii::$app->user->id;
            }
            else
            {
                $model->userLiaison = $model->userLiaisonID == 0 ? 'N/A' : Users::findOne($model->userLiaisonID)->userFullname;
            }
            $model->generateAuthKey();
            $model->setPassword($model->userPassword);
            $model->UnixTimestamp = time() * 1000;
            if ($model->save())
            {
                //经过这个controller，是案源人或admin手动添加的Users，发的是通知邮件
                $userEmail = $model->userEmail;
//                $liaisonEmail = $model::findByID($model->userLiaisonID)->userEmail;

                Yii::$app->queue->push(new SendEmailJob([
                    'mailViewFileNameString' => 'userAddedByAdminMsg',
                    'varToViewArray' => ['model' => $model],
                    'fromAddressArray' => ['kf@shineip.com' => '阳光惠远客服中心'],
                    'toAddressArray' => [$userEmail,'info@shineip.com'],
                    'emailSubjectString' => '欢迎您注册新用户'
                ]));


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
     * Deletes an existing Users model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        //先获取要删除的Users对象，发警告邮件
        $model = $this->findModel($id);

        $userEmail = $model->userEmail;
        $liaisonEmail = (new Users())::findByID($model->userLiaisonID)->userEmail;

        Yii::$app->queue->push(new SendEmailJob([
            'mailViewFileNameString' => 'userDelWarning',
            'varToViewArray' => ['model' => $model],
            'fromAddressArray' => ['kf@shineip.com' => '阳光惠远客服中心'],
            'toAddressArray' => [$userEmail, $liaisonEmail, 'info@shineip.com'],
            'emailSubjectString' => '警告: 客户信息被删除'
        ]));

        //先发邮件，再删除
        $model->delete();

        return $this->redirect(['index']);
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
