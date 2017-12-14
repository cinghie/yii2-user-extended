<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.6.1
 */

namespace cinghie\userextended\models;

use cinghie\traits\ViewsHelpersTrait;
use dektrium\user\models\UserSearch as BaseUserSearch;
use yii\base\InvalidParamException;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class UserSearch extends BaseUserSearch
{

	use ViewsHelpersTrait;

    /** @var string */
    public $firstname;

    /** @var string */
    public $lastname;

    /** @var string */
    public $birthday;

    /** @var string */
    public $rule;

	/**
	 * @inheritdoc
	 */
    public function rules()
    {
        return [
            'fieldsSafe' => [['id', 'username', 'firstname', 'lastname', 'birthday','email', 'rule', 'registration_ip', 'created_at', 'last_login_at'], 'safe'],
            'createdDefault' => ['created_at', 'default', 'value' => null],
            'lastloginDefault' => ['last_login_at', 'default', 'value' => null],
        ];
    }

	/**
	 * @inheritdoc
	 */
    public function attributeLabels()
    {
        return [
            'id'              => \Yii::t('userextended', 'ID'),
            'username'        => \Yii::t('user', 'Username'),
            'firstname'       => \Yii::t('userextended', 'Firstname'),
            'lastname'        => \Yii::t('userextended', 'Lastname'),
            'birthday'        => \Yii::t('userextended', 'Birthday'),
            'email'           => \Yii::t('user', 'Email'),
            'rule'            => \Yii::t('rbac', 'Rule'),
            'created_at'      => \Yii::t('user', 'Registration time'),
            'registration_ip' => \Yii::t('user', 'Registration ip'),
            'last_login_at'   => \Yii::t('userextended', 'Last login')
        ];
    }

	/**
	 * @param $params
	 *
	 * @return ActiveDataProvider
	 * @throws \yii\base\InvalidParamException
	 */
    public function search($params)
    {
        $query = $this->finder->getUserQuery();
        $query->select('*');
        $query->joinWith('profile');

        // Add default Order
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        // Override Sort Attributes
        $dataProvider->setSort([
            'attributes' => [
                'id',
                'username',
                'firstname',
                'lastname',
                'birthday',
                'email',
                'rule',
                'created_at',
                'last_login_at'
            ],
            'defaultOrder' => [
                'created_at' => SORT_DESC
            ],
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

	    $table_name = $query->modelClass;
	    $table_name = $table_name::tableName();

        if ($this->created_at !== null) {
            $date = strtotime($this->created_at);
            $query->andFilterWhere(['between', $table_name . '.created_at', $date, $date + 3600 * 24]);
        }

        $query->andFilterWhere([
            'id' => $this->id
        ]);

        $query->andFilterWhere(['like', 'username', $this->username])
              ->andFilterWhere(['like', 'profile.firstname', $this->firstname])
              ->andFilterWhere(['like', 'profile.lastname', $this->lastname])
              ->andFilterWhere(['like', 'profile.birthday', $this->birthday])
              ->andFilterWhere(['like', 'email', $this->email])
              ->andFilterWhere(['registration_ip' => $this->registration_ip]);

        if ($this->rule !== '') {
            $query->andWhere('`id` IN (
                SELECT {{%auth_assignment}}.user_id FROM {{%auth_assignment}} 
                WHERE {{%auth_assignment}}.`item_name` = "'.$this->rule.'")'
            );
        }

        // Print SQL query
        //Svar_dump($query->createCommand()->sql); exit();

        return $dataProvider;
    }

	/**
	 * Creates data provider instance with last categories
	 *
	 * @param int $limit
	 * @param string $orderby
	 * @param int $order
	 *
	 * @return ActiveDataProvider
	 * @throws InvalidParamException
	 */
	public function last($limit, $orderby = 'id', $order = SORT_DESC)
	{
		$query = User::find()->limit($limit);

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'pagination' => [
				'pageSize' => $limit,
			],
			'sort' => [
				'defaultOrder' => [
					$orderby => $order
				],
			],
			'totalCount' => $limit
		]);

		if (!$this->validate()) {
			// uncomment the following line if you do not want to return any records when validation fails
			// $query->where('0=1');
			return $dataProvider;
		}

		return $dataProvider;
	}

    /**
     * Returns list of item names.
     *
     * @return array
     */
    public function getNameList()
    {
        $rows = (new Query)
            ->select(['name'])
            ->andWhere(['type' => 1])
            ->andWhere('name != "public"')
            ->from(\Yii::$app->authManager->itemTable)
            ->all();

        return ArrayHelper::map($rows, 'name', 'name');
    }

}
