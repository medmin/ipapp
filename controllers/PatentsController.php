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
use yii\data\ActiveDataProvider;

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
                        'actions' => ['create', 'delete', 'expiring'],
                        'roles' => ['admin']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['export', 'search', 'update'],
                        'roles' => ['admin', 'secadmin']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'view'],
                        'roles' => ['admin', 'secadmin', 'manager']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['main'],
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
//        return false;
//        $model = $this->findModel($id);
//        $users = new Users();
//
//        $userEmail = $users::findByID($model->patentUserID)->userEmail;
//
//        //删除一条信息，需要警告
//        Yii::$app->queue->push(new SendEmailJob([
//            'mailViewFileNameString' => 'patentDelWarning',
//            'varToViewArray' => ['model' => $model, 'users' => $users],
//            'fromAddressArray' => ['kf@shineip.com' => '阳光惠远客服中心'],
//            'toAddressArray' => [$userEmail,'info@shineip.com'],
//            'emailSubjectString' => '提醒：用户信息被修改'
//        ]));
//
//
//        $model->delete();

        return $this->redirect(['index']);
    }

    /**
     * 查看专利进度
     * @param $id
     * @return string
     */
    public function actionMain($id)
    {
        $where_condition = ['patentAjxxbID' => $id];
        if (Yii::$app->user->identity->userRole == Users::ROLE_CLIENT) {
            $where_condition += ['eventUserID' => Yii::$app->user->id];
        }
        $events = Patentevents::find()->where($where_condition)->andWhere(['in', 'eventContentID', ['05', '07', 'file', 'deleteFile']])->orderBy(['eventCreatUnixTS' => SORT_DESC])->all();

        return $this->render('main', ['models' => $events]);
    }

    /**
     * 导出Excel
     *
     * @param $rows
     */
    public function actionExport($rows)
    {
        if (empty($rows)) return;
        $rows = json_decode($rows);
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()
            ->setCreator(Yii::$app->user->identity->userFullname)
            ->setLastModifiedBy(Yii::$app->user->identity->userFullname)
            ->setTitle(Yii::t('app', 'Patents'))
            ->setSubject(Yii::t('app', 'Patents'));
        $headerArr = ['Patent Ajxxb ID', '我方案卷号', '专利类型', '专利客户', '商务专员', '	专利主办人', '流程管理员', '标题', '专利申请号'];
        $key = ord('A');
        foreach($headerArr as $v){
            $column = chr($key);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($column.'1',$v);
            $key += 1;
        }
        $models = Patents::find()->where(['in', 'patentAjxxbID', $rows])->all();
        foreach ($models as $idx => $model) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . (string)($idx + 2), $model->patentAjxxbID);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B' . (string)($idx + 2), $model->patentEacCaseNo);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C' . (string)($idx + 2), $model->patentType);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D' . (string)($idx + 2), $model->patentUsername);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E' . (string)($idx + 2), $model->patentUserLiaison);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F' . (string)($idx + 2), $model->patentAgent);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G' . (string)($idx + 2), $model->patentProcessManager);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H' . (string)($idx + 2), $model->patentTitle);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I' . (string)($idx + 2), $model->patentApplicationNo == 'Not Available Yet' ? '' : $model->patentApplicationNo)->getStyle('I' . (string)($idx + 2))->getNumberFormat()->setFormatCode('0');

        }
        $objPHPExcel->getActiveSheet()->setTitle('Simple');

// Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="export.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 即将到期的专利
     */
    public function actionExpiring($date_type = 1)
    {
        $query = Patents::find();

        $start_time = date('Ymd');
        switch ($date_type) {
            case 1:
                $end_time = date('Ymd', strtotime('+7 days'));
                break;
            case 2:
                $end_time = date('Ymd', strtotime('+15 days'));
                break;
            case 3:
                $end_time = date('Ymd', strtotime('+30 days'));
                break;
        }
        $query->where(['between', 'patentFeeDueDate', $start_time, $end_time]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['patentFeeDueDate' => SORT_ASC],
                'attributes' => ['patentFeeDueDate'],
            ]
        ]);

        return $this->render('expiring', [
            'dataProvider' => $dataProvider,
            'date_type' => $date_type,
        ]);
    }

    /**
     * 搜索跳转到搜索标题，意义不大
     *
     * @param $q
     * @return \yii\web\Response
     */
    public function actionSearch($q)
    {
        return $this->redirect(['/patents/index','PatentsSearch[patentTitle]' => $q]);
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
