<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Patents;

/**
 * PatentsSearch represents the model behind the search form about `app\models\Patents`.
 */
class PatentsSearch extends Patents
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['patentID', 'patentUserID', 'patentUserLiaisonID', 'UnixTimestamp'], 'integer'],
            [['patentAjxxbID', 'patentEacCaseNo', 'patentType', 'patentUsername', 'patentUserLiaison', 'patentAgent', 'patentProcessManager', 'patentTitle', 'patentApplicationNo', 'patentPatentNo', 'patentNote'], 'safe'],
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
        $query = Patents::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['UnixTimestamp' => SORT_DESC],
//                'attributes' => ['UnixTimestamp'],
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
            'patentID' => $this->patentID,
            'patentUserID' => $this->patentUserID,
            'patentUserLiaisonID' => $this->patentUserLiaisonID,
        ]);

        $query->andFilterWhere(['like', 'patentAjxxbID', $this->patentAjxxbID])
            ->andFilterWhere(['like', 'patentEacCaseNo', $this->patentEacCaseNo])
            ->andFilterWhere(['like', 'patentType', $this->patentType])
            ->andFilterWhere(['like', 'patentUsername', $this->patentUsername])
            ->andFilterWhere(['like', 'patentUserLiaison', $this->patentUserLiaison])
            ->andFilterWhere(['like', 'patentAgent', $this->patentAgent])
            ->andFilterWhere(['like', 'patentProcessManager', $this->patentProcessManager])
            ->andFilterWhere(['like', 'patentTitle', $this->patentTitle])
            ->andFilterWhere(['like', 'patentApplicationNo', $this->patentApplicationNo])
            ->andFilterWhere(['like', 'patentPatentNo', $this->patentPatentNo])
            ->andFilterWhere(['like', 'patentNote', $this->patentNote])
            ->andFilterWhere(['>', 'UnixTimestamp', $this->UnixTimestamp]);

        return $dataProvider;
    }
}
