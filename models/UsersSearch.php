<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Users;

/**
 * UsersSearch represents the model behind the search form about `app\models\Users`.
 */
class UsersSearch extends Users
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userID', 'userLiaisonID', 'userRole', 'UnixTimestamp'], 'integer'],
            [['userUsername', 'userPassword', 'userOrganization', 'userFullname', 'userFirstname', 'userGivenname', 'userNationality', 'userCitizenID', 'userEmail', 'userCellphone', 'userLandline', 'userAddress', 'userLiaison', 'userNote', 'authKey'], 'safe'],
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
        $query = Users::find();

        // add conditions that should always apply here
        $query->with('userPatents');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => false,
        ]);

        if (Yii::$app->user->identity->userRole == Users::ROLE_EMPLOYEE) {
            $query->andWhere(['userLiaisonID' => Yii::$app->user->id]);
        }
        if (Yii::$app->user->identity->userRole == Users::ROLE_SECONDARY_ADMIN) {
            $query->andWhere(['userRole' => Users::ROLE_CLIENT]);
        }
        if (Yii::$app->user->identity->userRole == Users::ROLE_ADMIN) {
            $query->andWhere(['<>', 'userID', Yii::$app->user->id]);
        }

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'userID' => $this->userID,
            'userLiaisonID' => $this->userLiaisonID,
            'userRole' => $this->userRole,
        ]);

        $query->andFilterWhere(['like', 'userUsername', $this->userUsername])
            ->andFilterWhere(['like', 'userPassword', $this->userPassword])
            ->andFilterWhere(['like', 'userOrganization', $this->userOrganization])
            ->andFilterWhere(['like', 'userFullname', $this->userFullname])
            ->andFilterWhere(['like', 'userFirstname', $this->userFirstname])
            ->andFilterWhere(['like', 'userGivenname', $this->userGivenname])
            ->andFilterWhere(['like', 'userNationality', $this->userNationality])
            ->andFilterWhere(['like', 'userCitizenID', $this->userCitizenID])
            ->andFilterWhere(['like', 'userEmail', $this->userEmail])
            ->andFilterWhere(['like', 'userCellphone', $this->userCellphone])
            ->andFilterWhere(['like', 'userLandline', $this->userLandline])
            ->andFilterWhere(['like', 'userAddress', $this->userAddress])
            ->andFilterWhere(['like', 'userLiaison', $this->userLiaison])
            ->andFilterWhere(['like', 'userNote', $this->userNote])
            ->andFilterWhere(['like', 'authKey', $this->authKey])
            ->andFilterWhere(['>', 'UnixTimestamp', $this->UnixTimestamp]);

        return $dataProvider;
    }
}
