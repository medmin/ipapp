<?php

namespace app\models;

use yii\data\ActiveDataProvider;

/**
 * NotificationSearch represents the model behind the search form about `app\models\Notification`.
 */
class NotificationSearch extends Notification
{
    /**
     * @var string 搜索用户使用
     */
    public $username;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'sender', 'receiver', 'type', 'createdAt', 'status'], 'integer'],
            [['username', 'content'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        $scenarios = parent::scenarios();
        $scenarios['wechat_log'] = ['username', 'content']; // 添加一个场景为了方便查看微信日志
        return $scenarios;
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
        $query = Notification::find();
        if ($this->scenario == 'wechat_log') {
            if (isset($params['NotificationSearch']['username'])) {
                $user = Users::findOne(['userUsername' => $params['NotificationSearch']['username']]);
                $query->where(['receiver' => $user ? $user->userID : -1]);
            }
        } else {
            $query->where(['<>','type',Notification::TYPE_WECHAT_NOTICE]);
        }

        // add conditions that should always apply here

        $sort = [
            'defaultOrder' => ['createdAt' => SORT_DESC],
        ];
        
        if ($this->scenario == 'wechat_log') {
            $sort['attributes'] = ['createdAt'];
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => $sort
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'sender' => $this->sender,
            'receiver' => $this->receiver,
            'type' => $this->scenario == 'wechat_log' ? Notification::TYPE_WECHAT_NOTICE : $this->type,
            'createdAt' => $this->createdAt,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'content', $this->content]);

        return $dataProvider;
    }
}
