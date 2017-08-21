<?php

namespace app\controllers;

use app\models\Patents;
use Yii;
use app\models\Patentevents;
use app\models\PatenteventsSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;

/**
 * PatenteventsController implements the CRUD actions for Patentevents model.
 */
class PatenteventsController extends Controller
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
                        'actions' => ['index', 'view', 'create', 'update', 'todo'],
                        'roles' => ['admin', 'secadmin']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index'],
                        'roles' => ['demo']
                    ]
                ],
            ],
        ];
    }

    /**
     * Lists all Patentevents models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new PatenteventsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Patentevents model.
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
     * Creates a new Patentevents model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @param null $ajxxbID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionCreate($ajxxbID = null)
    {
        $model = new Patentevents();
        $model->eventRwslID = 'ADMIN' . time();
        $model->eventContentID = 'custom';
        $model->patentAjxxbID = $ajxxbID;
        $model->eventCreatUnixTS = time() * 1000;

        if ($model->load(Yii::$app->request->post())) {
            $patent = Patents::findOne(['patentAjxxbID' => $model->patentAjxxbID]);
            if (!$patent) throw new NotFoundHttpException('案卷信息ID为' . $model->patentAjxxbID . '的专利未找到或者未同步,请核实');
            $model->eventUserID = $patent->patentUserID;
            $model->eventUsername = $patent->patentUsername;
            $model->eventUserLiaisonID = $patent->patentUserLiaisonID;
            $model->eventUserLiaison = $patent->patentUserLiaison;
            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->eventID]);
            }
        }
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Patentevents model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->eventID]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Patentevents model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * 待办事项
     *
     * @return string
     */
    public function actionTodo()
    {
        $query = Patentevents::find()->where(['>', 'eventFinishUnixTS', time() * 1000])->orWhere(['<>', 'eventStatus', 'INACTIVE']);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => ['eventCreatUnixTS' => SORT_DESC],
                'attributes' => ['eventStatus', 'eventCreatUnixTS', 'eventFinishUnixTS'],
            ],
        ]);
        $searchModel = new PatenteventsSearch();
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }


    /**
     * Finds the Patentevents model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Patentevents the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Patentevents::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
