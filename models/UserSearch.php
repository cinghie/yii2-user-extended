<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.6.4
 */

namespace cinghie\userextended\models;

use Yii;
use cinghie\traits\ViewsHelpersTrait;
use cinghie\userextended\helpers\RbacRoleCache;
use dektrium\user\models\UserSearch as BaseUserSearch;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * @property array $nameList
 */
class UserSearch extends BaseUserSearch
{
	use ViewsHelpersTrait;

	/**
     * @var string
     */
	public $blocked_at;

    /**
     * @var string
     */
    public $firstname;

    /**
     * @var string
     */
    public $lastname;

    /**
     * @var string
     */
    public $birthday;

    /**
     * @var string
     */
    public $rule;

	/**
	 * @inheritdoc
	 */
    public function rules()
    {
        return [
            'fieldsSafe' => [['id', 'username', 'firstname', 'lastname', 'birthday', 'email', 'rule', 'registration_ip', 'created_at', 'blocked_at', 'last_login_at'], 'safe'],
            'ruleInList' => ['rule', 'in', 'range' => array_keys($this->getNameList()), 'skipOnEmpty' => true],
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
            'id' => Yii::t('userextended', 'ID'),
            'username' => Yii::t('user', 'Username'),
            'firstname' => Yii::t('userextended', 'Firstname'),
            'lastname' => Yii::t('userextended', 'Lastname'),
            'birthday' => Yii::t('userextended', 'Birthday'),
            'email' => Yii::t('user', 'Email'),
            'rule' => Yii::t('traits', 'Rule'),
            'blocked_at' => Yii::t('userextended', 'Enabled'),
            'created_at' => Yii::t('user', 'Registration time'),
            'registration_ip' => Yii::t('user', 'Registration ip'),
            'last_login_at' => Yii::t('userextended', 'Last login')
        ];
    }

	/**
	 * @param $params
	 *
	 * @return ActiveDataProvider
	 * @throws InvalidArgumentException
	 */
    public function search($params)
    {
        $query = $this->finder->getUserQuery();
        $query->joinWith(['profile']);
        $query->with(['roles']);

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
                'blocked_at',
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

        /** @var Model $modelClass */
	    $modelClass = $query->modelClass;
	    $table_name = $modelClass::tableName();

        if ($this->blocked_at !== '' && $this->created_at !== null) {
            $date = strtotime($this->created_at);
            $query->andFilterWhere(['between', $table_name . '.created_at', $date, $date + 3600 * 24]);
        }

        if($this->blocked_at !== '' && $this->blocked_at !== NULL && (int)$this->blocked_at === 0) {
	        $query->andWhere(['>', $table_name . '.blocked_at', 0]);
        }

        $query->andFilterWhere(['like', $table_name . '.username', $this->username])
              ->andFilterWhere(['like', 'profile.firstname', $this->firstname])
              ->andFilterWhere(['like', 'profile.lastname', $this->lastname])
              ->andFilterWhere(['like', 'profile.birthday', $this->birthday])
              ->andFilterWhere(['like', $table_name . '.email', $this->email])
              ->andFilterWhere([$table_name . '.id' => $this->id])
              ->andFilterWhere([$table_name . '.registration_ip' => $this->registration_ip]);

        if ($this->rule !== null && $this->rule !== '') {
            $assignmentTable = Yii::$app->authManager->assignmentTable;
            $query->innerJoin(
                ['aa' => $assignmentTable],
                $table_name . '.id = aa.user_id'
            )->andWhere(['aa.item_name' => $this->rule]);
        }

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
	 * @throws InvalidArgumentException
	 */
	public function last($limit, $orderby = 'id', $order = SORT_DESC)
	{
		$query = User::find()->with(['profile', 'roles'])->limit($limit);

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
        return RbacRoleCache::getRoleNames();
    }

	/**
	 * Bulk delete with explicit CSRF field (defense in depth vs relying only on yii.js ajaxSend).
	 *
	 * @param string $w
	 *
	 * @return string
	 */
	public function getDeleteButtonJavascript($w)
	{
		return $this->buildBulkActionJavascript($w, 'btn-delete', 'deletemultiple', true);
	}

	/**
	 * @param string $w
	 *
	 * @return string
	 */
	public function getActiveButtonJavascript($w)
	{
		return $this->buildBulkActionJavascript($w, 'btn-active', 'activemultiple', false);
	}

	/**
	 * @param string $w
	 *
	 * @return string
	 */
	public function getDeactiveButtonJavascript($w)
	{
		return $this->buildBulkActionJavascript($w, 'btn-deactive', 'deactivemultiple', false);
	}

	/**
	 * @param string $w grid selector
	 * @param string $btnClass
	 * @param string $action
	 * @param bool $confirm
	 *
	 * @return string
	 */
	protected function buildBulkActionJavascript($w, $btnClass, $action, $confirm)
	{
		$route = '/' . Yii::$app->controller->getRoute();
		$controllerRoute = substr($route, 0, strrpos($route, '/'));
		$url = Url::to([$controllerRoute . '/' . $action]);
		$indexUrl = Url::to([$route]);
		$csrf = Json::htmlEncode([
			Yii::$app->request->csrfParam => Yii::$app->request->getCsrfToken(),
		]);
		$confirmMsg = Json::htmlEncode(Yii::t('traits', 'Do you want delete selected items?'));
		$selectMsg = Json::htmlEncode(Yii::t('traits', 'Select at least one item'));

		$confirmBlock = $confirm
			? 'var choose = confirm(' . $confirmMsg . '); if (!choose) { return; }'
			: '';

		return '$("a.' . $btnClass . '").on("click", function(e) {
            e.preventDefault();
            var selectedId = $("' . $w . '").find("input[name=\"selection[]\"]:checked").map(function() {
                return $(this).val();
            }).get();
            if (selectedId.length == 0) {
                alert(' . $selectMsg . ');
                return;
            }
            ' . $confirmBlock . '
            var payload = $.extend({ids: selectedId}, ' . $csrf . ');
            $.ajax({
                type: "POST",
                url: ' . Json::htmlEncode($url) . ' + "?id=" + selectedId[0],
                data: payload,
                success: function() {
                    $.pjax.reload({url: ' . Json::htmlEncode($indexUrl) . ', container: "' . $w . '-container", push: false, replace: false, timeout: 8000});
                }
            });
        });';
	}
}
