<?php

namespace app\controllers;

use app\models\Patents;
use Yii;
use app\models\Orders;
use app\models\OrdersSearch;
use yii\filters\AccessControl;
use app\models\UnpaidAnnualFee;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * OrdersController implements the CRUD actions for Orders model.
 */
class OrdersController extends BaseController
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
                    'finish' => ['POST'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'view'],
                        'roles' => ['admin', 'secadmin']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['update', 'finish'],
                        'roles' => ['admin']
                    ],
                    [
                        'allow' => false,
                        'actions' => ['delete']
                    ]
                ],
            ],
        ];
    }

    /**
     * Lists all Orders models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new OrdersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Orders model.
     * @param string $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Orders model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Orders();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->trade_no]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Orders model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->trade_no]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Orders model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * 完成交易
     *
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function actionFinish($id)
    {
        $model = self::findModel($id);
        if ($model && $model->status === Orders::STATUS_PAID) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $ajxxb_ids = json_decode($model->goods_id);
                $days_diff = date_diff(date_create('@'.$model->created_at),date_create())->format('%a'); // 算出订单创建当天和今天的时间差，防止出现意外情况
                foreach ($ajxxb_ids as $ajxxb_id) {
                    $items = Patents::findOne(['patentAjxxbID' => $ajxxb_id])->generateExpiredItems(90-$days_diff, true,true); // 一样要注意参数,主要是天数
                    $count = UnpaidAnnualFee::updateAll(['status' => UnpaidAnnualFee::FINISHED],['in', 'id', array_column($items,'id')]);
                    if (!$count) {
                        throw new \Exception('没有已支付的费用');
                    }
                }
                $model->status = Orders::STATUS_FINISHED;
                $model->updated_at = time();
                if (!$model->save()) {
                    throw new \Exception('订单更新失败');
                }
                $transaction->commit();
                return true;
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::info($e->getMessage(),'orders');
            }
        }
        return false;
    }

    /**
     * Finds the Orders model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Orders the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Orders::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
