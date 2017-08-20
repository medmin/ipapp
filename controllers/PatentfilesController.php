<?php

namespace app\controllers;

use app\models\Patentevents;
use Yii;
use app\models\Patentfiles;
use app\models\PatentfilesSearch;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\UploadForm;
use yii\web\UploadedFile;

/**
 * PatentfilesController implements the CRUD actions for Patentfiles model.
 */
class PatentfilesController extends Controller
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
        ];
    }

    /**
     * Lists all Patentfiles models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new PatentfilesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Patentfiles model.
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
     * Creates a new Patentfiles model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
//    public function actionCreate()
//    {
//        $model = new Patentfiles();
//
//        if ($model->load(Yii::$app->request->post()) && $model->save()) {
//            return $this->redirect(['view', 'id' => $model->fileID]);
//        } else {
//            return $this->render('create', [
//                'model' => $model,
//            ]);
//        }
//    }

    /**
     * Updates an existing Patentfiles model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->fileID]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Patentfiles model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    public function actionUpload($ajxxb_id)
    {
        $this->layout = false;
        $model = new UploadForm();
        $model->ajxxb_id = $ajxxb_id;

        if (Yii::$app->request->isPost) {
            $model->patentFiles = UploadedFile::getInstances($model, 'patentFiles');
            if ($model->upload()) {
                // file is uploaded successfully
//                return Json::encode(['code' => 0, 'msg' => 'success']);
                return $this->redirect(\yii\helpers\Url::to(['patents/index']));
            } else {
//                return Json::encode(['code' => 1, 'msg' => json_encode($model->errors)]);
            }
        }

        return $this->render('upload', ['model' => $model]);
    }

    /**
     * Finds the Patentfiles model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Patentfiles the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Patentfiles::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }




}
