<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.6.3
 */

namespace cinghie\userextended\models;

use Yii;
use dektrium\user\clients\ClientInterface;
use dektrium\user\models\Account as BaseAccount;
use yii\authclient\ClientInterface as BaseClientInterface;
use yii\base\InvalidConfigException;
use yii\helpers\Json;

/**
 * Class Account
 */
class Account extends BaseAccount
{
	/**
	 * @param BaseClientInterface $client
	 *
	 * @return Account|BaseAccount
	 * @throws InvalidConfigException
	 */
	public static function create(BaseClientInterface $client)
	{
		/** @var Account $account */
		$account = Yii::createObject([
			'class' => static::class,
			'provider' => $client->getId(),
			'client_id' => $client->getUserAttributes()['id'],
			'data' => Json::encode($client->getUserAttributes()),
		]);

		if ($client instanceof ClientInterface)
        {
			$account->setAttributes([
				'username' => $client->getUsername(),
				'email' => $client->getEmail(),
			], false);
		}

		if (($user = static::fetchUser($account)) instanceof User) {
			$account->user_id = $user->id;
		}

		$account->save(false);

		return $account;
	}
}
