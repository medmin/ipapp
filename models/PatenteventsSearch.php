<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Patentevents;

/**
 * PatenteventsSearch represents the model behind the search form about `app\models\Patentevents`.
 */
class PatenteventsSearch extends Patentevents
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['eventID', 'eventUserID', 'eventUserLiaisonID', 'eventCreatUnixTS', 'eventFinishUnixTS'], 'integer'],
            [['eventRwslID', 'eventContentID', 'eventContent', 'eventNote', 'patentAjxxbID', 'eventUsername', 'eventUserLiaison', 'eventCreatPerson', 'eventFinishPerson', 'eventStatus'], 'safe'],
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
        $query = Patentevents::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'eventID' => $this->eventID,
            'eventUserID' => $this->eventUserID,
            'eventUserLiaisonID' => $this->eventUserLiaisonID,
            'eventCreatUnixTS' => $this->eventCreatUnixTS,
            'eventFinishUnixTS' => $this->eventFinishUnixTS,
        ]);

        $query->andFilterWhere(['like', 'eventRwslID', $this->eventRwslID])
            ->andFilterWhere(['like', 'eventContentID', $this->eventContentID])
            ->andFilterWhere(['like', 'eventContent', $this->eventContent])
            ->andFilterWhere(['like', 'eventNote', $this->eventNote])
            ->andFilterWhere(['like', 'patentAjxxbID', $this->patentAjxxbID])
            ->andFilterWhere(['like', 'eventUsername', $this->eventUsername])
            ->andFilterWhere(['like', 'eventUserLiaison', $this->eventUserLiaison])
            ->andFilterWhere(['like', 'eventCreatPerson', $this->eventCreatPerson])
            ->andFilterWhere(['like', 'eventFinishPerson', $this->eventFinishPerson])
            ->andFilterWhere(['like', 'eventStatus', $this->eventStatus]);

        return $dataProvider;
    }
}
