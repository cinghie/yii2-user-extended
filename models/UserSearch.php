<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.5.8
 */

namespace cinghie\yii2userextended\models;

use dektrium\user\models\UserSearch as BaseUserSearch;
use yii\data\ActiveDataProvider;

class UserSearch extends BaseUserSearch
{
    /** @var int */
    public $id;

    /** @var string */
    public $firstname;

    /** @var string */
    public $lastname;

    /** @var string */
    public $birthday;

    /** @inheritdoc */
    public function rules()
    {
        return [
            'fieldsSafe' => [['username', 'firstname', 'lastname', 'birthday','email', 'registration_ip', 'created_at'], 'safe'],
            'createdDefault' => ['created_at', 'default', 'value' => null],
        ];
    }

    /** @inheritdoc */
    public function attributeLabels()
    {
        return [
            'id'              => Yii::t('user', 'ID'),
            'username'        => Yii::t('user', 'Username'),
            'firstname'       => Yii::t('userextended', 'Firstname'),
            'lastname'        => Yii::t('userextended', 'Lastname'),
            'birthday'        => Yii::t('userextended', 'Birthday'),
            'email'           => Yii::t('user', 'Email'),
            'created_at'      => Yii::t('user', 'Registration time'),
            'registration_ip' => Yii::t('user', 'Registration ip'),
        ];
    }

    /**
     * @param $params
     *
     * @return ActiveDataProvider
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
                'username',
                'firstname',
                'lastname',
                'birthday',
                'email',
                'created_at'
            ],
            'defaultOrder' => [
                'created_at' => SORT_DESC
            ],
        ]);

        // Print SQL query
        //var_dump($query->createCommand()->sql); exit();

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        if ($this->created_at !== null) {
            $date = strtotime($this->created_at);
            $query->andFilterWhere(['between', 'created_at', $date, $date + 3600 * 24]);
        }

        $query->andFilterWhere(['like', 'username', $this->username])
              ->andFilterWhere(['like', 'profile.firstname', $this->firstname])
              ->andFilterWhere(['like', 'profile.lastname', $this->lastname])
              ->andFilterWhere(['like', 'profile.birthday', $this->birthday])
              ->andFilterWhere(['like', 'email', $this->email])
              ->andFilterWhere(['registration_ip' => $this->registration_ip]);

        return $dataProvider;
    }

}
