<?php

namespace app\controllers;

use app\models\Patentevents;
use app\models\Patents;
use app\models\Users;
use Yii;
use app\models\Patentfiles;
use app\models\PatentfilesSearch;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\UploadForm;
use yii\web\UploadedFile;
use yii\filters\AccessControl;

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
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['delete', 'index', 'view', 'upload', 'download-group'],
                        'roles' => ['admin', 'secadmin']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['download'],
                        'roles' => ['@']
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
     * 删除文件
     *
     * @param $id
     * @return \yii\web\Response
     * @throws \Exception
     */
    public function actionDelete($id)
    {
        $theSingleOneModel = $this->findModel($id);
        $filePath = $theSingleOneModel->filePath;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 增加event
            $event = new Patentevents();
            $event->eventRwslID = uniqid() . '_' . $theSingleOneModel->fileID;
            $event->patentAjxxbID = $theSingleOneModel->patentAjxxbID;
            $event->eventContentID = 'deleteFile';
            $event->eventContent = \app\models\eac\Rwsl::rwdyIdMappingContent()[$event->eventContentID]
                . $theSingleOneModel->fileName;
            $event->eventCreatUnixTS = time() *1000;
            $event->eventCreatPerson = Yii::$app->user->identity->userFullname;
            $event->eventStatus = 'INACTIVE';
            $event->eventFinishUnixTS = time() *1000;
            $event->eventFinishPerson = Yii::$app->user->identity->userFullname;
            $event->eventUserID = 0;
            if (!$event->save() || !unlink($filePath) || !$theSingleOneModel->delete()) {
                throw new \Exception();
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return $this->redirect(['index']);
    }

    /**
     * 上传文件
     *
     * @param $ajxxb_id
     * @return string|\yii\web\Response
     */
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
            throw new NotFoundHttpException('文件不存在');
        }
    }

    /**
     * 下载单个文件
     *
     * @param $id
     * @throws NotFoundHttpException
     */
    public function actionDownload($id)
    {
        ignore_user_abort(true);
        set_time_limit(600); // disable the time limit for this script

        $model = $this->findModel($id);

        // 如果客户看的专利文件不是自己的，返回false
        if (Yii::$app->user->identity->userRole == Users::ROLE_CLIENT
            && Yii::$app->user->id !== Patents::findOne(['patentAjxxbID' => $model->patentAjxxbID])->patentUserID
        ) {
            throw new NotFoundHttpException('文件不存在');
        }
        $filePath = $model->filePath;

        if ($fd = fopen ($filePath, "r")) {
            $fsize = filesize($filePath);
            $path_parts = pathinfo($filePath);
            $ext = strtolower($path_parts["extension"]);
            header('Content-type: application/' . $ext);
            header('Content-Disposition: attachment; filename="' . $model->fileName . '.' . $ext . '"');
            header("Content-length: $fsize");
            header('Cache-control: private'); //use this to open files directly
            while(!feof($fd)) {
                $buffer = fread($fd, 2048);
                echo $buffer;
            }
        }
        fclose ($fd);
        exit;
    }

    /**
     * 根据ajxxb_id打包下载相关文件
     *
     * @param $ajxxb_id
     * @return bool
     */
    public function actionDownloadGroup($ajxxb_id)
    {
        $files = Patentfiles::find()->where(['patentAjxxbID' => $ajxxb_id])->asArray()->all();
        if (count($files)) {
            if (Yii::$app->request->isAjax) {
                return true;
            }
            $zip_path = Yii::$app->params['filePath'];
            $zip_name = Patents::findOne(['patentAjxxbID' => $ajxxb_id])->patentTitle . '.zip';
            $zip = new \ZipArchive();
            // OVERWRITE 不能创建不存在的zip（5.6.16版本就开始，是一个bug）  CREATE  不存在就创建
            if ($zip->open($zip_path . $zip_name, \ZIPARCHIVE::CREATE | \ZIPARCHIVE::OVERWRITE) !== true) {
                die ('An error occurred creating ZIP file.');
            }
//            $zip->open($zip_path, \ZipArchive::OVERWRITE);
            foreach ($files as $file) {
                $zip->addFile($file['filePath'], $file['fileName'] . '.' . pathinfo($file['filePath'], PATHINFO_EXTENSION));
            }
            $zip->close();
            header('Content-Type: application/zip'); // 标注文件类型
            header('Content-disposition: attachment; filename=' . $zip_name); // 强制弹出文件下载框
            header('Content-Length: ' . filesize($zip_path . $zip_name)); // 个别浏览器在不指定文件大小时无法下载
            header('Cache-control: private');
            readfile($zip_path . $zip_name);
            unlink($zip_path . $zip_name);
            exit;
        } else {
            return false;
        }
    }
}
