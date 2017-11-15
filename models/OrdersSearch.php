<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Orders;

/**
 * OrdersSearch represents the model behind the search form about `app\models\Orders`.
 */
class OrdersSearch extends Orders
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['trade_no', 'out_trade_no', 'goods_id'], 'safe'],
            [['payment_type', 'user_id', 'goods_type', 'created_at', 'updated_at', 'status'], 'integer'],
            [['amount'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Orders::find()->joinWith('user');

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['status' => SORT_DESC, 'created_at' => SORT_DESC],
                'attributes' => ['payment_type', 'goods_type', 'amount', 'created_at', 'updated_at', 'status', 'paid_at'],
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'payment_type' => $this->payment_type,
            'user_id' => $this->user_id,
            'goods_type' => $this->goods_type,
            'amount' => $this->amount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'trade_no', $this->trade_no])
            ->andFilterWhere(['like', 'out_trade_no', $this->out_trade_no])
            ->andFilterWhere(['like', 'goods_id', $this->goods_id]);

        return $dataProvider;
    }
}
