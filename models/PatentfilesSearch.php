<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Patentfiles;

/**
 * PatentfilesSearch represents the model behind the search form about `app\models\Patentfiles`.
 */
class PatentfilesSearch extends Patentfiles
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fileID', 'fileUploadedAt', 'fileUpdatedAt'], 'integer'],
            [['patentAjxxbID', 'fileName', 'filePath', 'fileUploadUserID', 'fileUpdateUserID', 'fileNote'], 'safe'],
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
        $query = Patentfiles::find();

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
            'fileID' => $this->fileID,
            'fileUploadedAt' => $this->fileUploadedAt,
            'fileUpdatedAt' => $this->fileUpdatedAt,
        ]);

        $query->andFilterWhere(['like', 'patentAjxxbID', $this->patentAjxxbID])
            ->andFilterWhere(['like', 'fileName', $this->fileName])
            ->andFilterWhere(['like', 'filePath', $this->filePath])
            ->andFilterWhere(['like', 'fileUploadUserID', $this->fileUploadUserID])
            ->andFilterWhere(['like', 'fileUpdateUserID', $this->fileUpdateUserID])
            ->andFilterWhere(['like', 'fileNote', $this->fileNote]);

        return $dataProvider;
    }
}
