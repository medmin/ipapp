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
//    public function actionUpdate($id)
//    {
//        $model = $this->findModel($id);
//
//        if ($model->load(Yii::$app->request->post()) && $model->save())
//        {
//            return $this->redirect(['view', 'id' => $model->fileID]);
//        }
//        else
//        {
//            return $this->render('update', [
//                'model' => $model,
//            ]);
//        }
//    }

    /**
     * Deletes an existing Patentfiles model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $theSingleOneModel = $this->findModel($id);
        $filePath = $theSingleOneModel->filePath;

        if( unlink($filePath) && $theSingleOneModel->delete() )
        {
            return $this->redirect(['index']);
        }
        else
        {
            throw new \Exception;
        }

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


    public function actionDownload($id)
    {
        ignore_user_abort(true);
        set_time_limit(0); // disable the time limit for this script

        $model = $this->findModel($id);
        $filePath = $model->filePath;

        if ($fd = fopen ($filePath, "r")) {
            $fsize = filesize($filePath);
            $path_parts = pathinfo($filePath);
            $ext = strtolower($path_parts["extension"]);
            switch ($ext) {
                case "tiff":
                    header("Content-type: application/tiff");
                    header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"");//不懂啥意思
                    break;
                case "png":
                    header("Content-type: application/png");
                    header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"");//不懂啥意思
                    break;
                case "jpg":
                    header("Content-type: application/jpg");
                    header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"");//不懂啥意思
                    break;
                case "doc":
                    header("Content-type: application/doc");
                    header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"");//不懂啥意思
                    break;
                case "docx":
                    header("Content-type: application/docx");
                    header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"");//不懂啥意思
                    break;
                case "xls":
                    header("Content-type: application/xls");
                    header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"");//不懂啥意思
                    break;
                case "xlsx":
                    header("Content-type: application/xlsx");
                    header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"");//不懂啥意思
                    break;
                case "ppt":
                    header("Content-type: application/ppt");
                    header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"");//不懂啥意思
                    break;
                case "pptx":
                    header("Content-type: application/pptx");
                    header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"");//不懂啥意思
                    break;
                case "pdf":
                    header("Content-type: application/pdf");
                    header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\""); // use 'attachment' to force a file download
                    break;
                case "zip":
                    header("Content-type: application/zip");
                    header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"");//不懂啥意思
                    break;
                case "rar":
                    header("Content-type: application/rar");
                    header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"");//不懂啥意思
                    break;
                case "7z":
                    header("Content-type: application/7z");
                    header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"");//不懂啥意思
                    break;
                case "txt":
                    header("Content-type: application/txt");
                    header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"");//不懂啥意思
                    break;
                default;
                    header("Content-type: application/octet-stream");
                    header("Content-Disposition: filename=\"".$path_parts["basename"]."\"");
                    break;
            }
            header("Content-length: $fsize");
            header("Cache-control: private"); //use this to open files directly
            while(!feof($fd)) {
                $buffer = fread($fd, 2048);
                echo $buffer;
            }
        }
        fclose ($fd);
        exit;
    }



}
