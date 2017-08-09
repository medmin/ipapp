<?php

namespace app\controllers;

use app\models\Patentevents;
use app\models\Users;
use Yii;
use app\models\Patents;
use app\models\PatentsSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\queues\SendEmailJob;

/**
 * PatentsController implements the CRUD actions for Patents model.
 */
class PatentsController extends Controller
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
                        'actions' => ['create', 'update', 'delete'],
                        'roles' => ['admin']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'view'],
                        'roles' => ['admin', 'controller']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['main'],
                        'roles' => ['@']
                    ]
                ],
            ],
        ];
    }

    /**
     * Lists all Patents models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new PatentsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Patents model.
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
     * Creates a new Patents model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Patents();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            //从EAC同步过来的时候，经过这个controller吗？如果经过，就发

            return $this->redirect(['view', 'id' => $model->patentID]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Patents model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            // 暂时取消发邮件
//            $users = new Users();
//            $userEmail = $users::findByID($model->patentUserID)->userEmail;
//
//            Yii::$app->queue->push(new SendEmailJob([
//                'mailViewFileNameString' => 'patentUpdateMsg',
//                'varToViewArray' => ['model' => $model, 'users' => $users],
//                'fromAddressArray' => ['kf@shineip.com' => '阳光惠远客服中心'],
//                'toAddressArray' => [$userEmail,'info@shineip.com'],
//                'emailSubjectString' => '提醒：专利信息被修改'
//            ]));

            return $this->redirect(['view', 'id' => $model->patentID]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Patents model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        return false;
        $model = $this->findModel($id);
        $users = new Users();

        $userEmail = $users::findByID($model->patentUserID)->userEmail;

        //删除一条信息，需要警告
        Yii::$app->queue->push(new SendEmailJob([
            'mailViewFileNameString' => 'patentDelWarning',
            'varToViewArray' => ['model' => $model, 'users' => $users],
            'fromAddressArray' => ['kf@shineip.com' => '阳光惠远客服中心'],
            'toAddressArray' => [$userEmail,'info@shineip.com'],
            'emailSubjectString' => '提醒：用户信息被修改'
        ]));


        $model->delete();

        return $this->redirect(['index']);
    }

    /**
     * 查看专利进度
     * @param $id
     * @return string
     */
    public function actionMain($id)
    {
        $events = Patentevents::find()->where(['patentAjxxbID' => $id, 'eventUserID' => Yii::$app->user->id])->orderBy(['eventCreatUnixTS' => SORT_DESC])->all();

        return $this->render('main', ['models' => $events]);
    }

    /**
     * Finds the Patents model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Patents the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Patents::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
