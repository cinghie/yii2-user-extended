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

namespace cinghie\userextended\controllers;

use Throwable;
use cinghie\userextended\helpers\ProfileAvatarService;
use Yii;
use cinghie\userextended\helpers\SecurityAudit;
use cinghie\userextended\models\Assignment;
use cinghie\userextended\models\Profile;
use cinghie\userextended\models\User;
use cinghie\userextended\models\UserSearch;
use dektrium\user\controllers\AdminController as BaseController;
use yii\base\Exception;
use yii\base\ExitException;
use yii\base\InvalidArgumentException;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\filters\AccessRule;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class AdminController
 */
class AdminController extends BaseController
{
	/**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
	        'access' => [
		        'class' => AccessControl::class,
		        'ruleConfig' => [
			        'class' => AccessRule::class,
		        ],
		        'rules' => [
			        [
				        'allow' => false,
				        'actions' => ['switch'],
			        ],
			        [
				        'allow' => true,
				        'roles' => ['admin'],
			        ],
		        ],
	        ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'activemultiple'   => ['POST'],
                    'deactivemultiple' => ['POST'],
                    'delete'           => ['POST'],
                    'deletemultiple'   => ['POST'],
                    'confirm'          => ['POST'],
                    'resend-password'  => ['POST'],
                    'block'            => ['POST'],
                    'switch'           => ['POST'],
                    'assignments'      => ['GET', 'POST'],
                ],
            ],
        ];
    }

	/**
	 * Impersonation is disabled.
	 *
	 * @param int|null $id
	 *
	 * @throws ForbiddenHttpException
	 */
	public function actionSwitch($id = null)
	{
		SecurityAudit::log('switch_denied', (int) $id, [
			'target_user_id' => (int) $id,
		], 'admin', 'User', '/user/admin/switch');

		throw new ForbiddenHttpException(
			Yii::t('userextended', 'User impersonation is disabled.')
		);
	}

	/**
	 * Assign RBAC roles/permissions to a user (centralized POST + CSRF + self-escalation guard).
	 *
	 * @param int $id
	 *
	 * @return string|Response
	 * @throws BadRequestHttpException
	 * @throws InvalidConfigException
	 * @throws NotFoundHttpException
	 */
	public function actionAssignments($id)
	{
		if (!isset(Yii::$app->extensions['dektrium/yii2-rbac'])) {
			throw new NotFoundHttpException();
		}

		Url::remember('', 'actions-redirect');
		$user = $this->findModel($id);

		$model = Yii::createObject([
			'class' => Assignment::class,
			'user_id' => $user->id,
		]);

		if (Yii::$app->request->isPost) {
			if (!Yii::$app->request->validateCsrfToken()) {
				throw new BadRequestHttpException(Yii::t('yii', 'Unable to verify your data submission.'));
			}

			$targetUserId = (int) $user->id;
			if ($model->load(Yii::$app->request->post())) {
				// Defense in depth: never trust posted user_id
				$model->user_id = $targetUserId;
				if ($model->updateAssignments()) {
					Yii::$app->session->setFlash('success', Yii::t('rbac', 'Assignments have been updated'));
					return $this->refresh();
				}
			}
		}

		return $this->render('_assignments', [
			'user' => $user,
			'model' => $model,
		]);
	}

	/**
	 * Lists all User models.
	 *
	 * @return string
     * @throws InvalidConfigException
	 * @throws InvalidArgumentException
	 */
    public function actionIndex()
    {
        Url::remember('', 'actions-redirect');
        $searchModel  = Yii::createObject(UserSearch::class);
        $dataProvider = $searchModel->search(Yii::$app->request->get());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel'  => $searchModel,
        ]);
    }

	/**
	 * Updates an existing profile.
	 *
	 * @param int $id
	 *
	 * @return Response|string
     * @throws Exception
	 * @throws ExitException
	 * @throws InvalidCallException
	 * @throws InvalidConfigException
	 * @throws InvalidArgumentException
	 * @throws NotFoundHttpException
	 */
    public function actionUpdateProfile($id)
    {
	    /** @var User $user */
        Url::remember('', 'actions-redirect');
	    $user    = $this->findModel($id);
        $profile = $user->profile;

        if ($profile === null) {
            $profile = Yii::createObject(Profile::class);
            $profile->link('user', $user);
        }

        $profile->scenario = 'update';

        // Profile Event
        $event = $this->getProfileEvent($profile);

        // Ajax Validation (before avatar upload — avoids orphan files on AJAX validate)
        $this->performAjaxValidation($profile);

        $avatarUpdate = ProfileAvatarService::begin($profile);

        $this->trigger(self::EVENT_BEFORE_PROFILE_UPDATE, $event);

        if ($profile->load(Yii::$app->request->post())) {
            $avatarUpdate->applyAfterLoad();

            if ($profile->save()) {
                $avatarUpdate->finalizeAfterSave();

                SecurityAudit::log('admin_profile_update', (int) $user->id, [
					'user_id' => (int) $user->id,
					'username' => SecurityAudit::safeLogin($user->username),
				], 'admin', 'User', '/user/admin/update-profile');

                Yii::$app->getSession()->setFlash('success', Yii::t('user', 'Profile details have been updated'));

                $this->trigger(self::EVENT_AFTER_PROFILE_UPDATE, $event);

                return $this->refresh();
            }

            $avatarUpdate->rollbackFailedSave();
        }

        return $this->render('_profile', [
            'user'    => $user,
            'profile' => $profile,
        ]);
    }

	/**
	 * Blocks the user.
	 *
	 * @param int $id
	 *
	 * @return Response
	 * @throws InvalidConfigException
	 * @throws NotFoundHttpException
	 */
    public function actionBlock($id)
    {
        if ( Yii::$app->user->getId() === (int) $id ) {
            Yii::$app->getSession()->setFlash('danger', Yii::t('user', 'You can not block your own account'));
        } else {
	        $user  = $this->findModel($id);
            $event = $this->getUserEvent($user);
            if ($user->getIsBlocked()) {
                $this->trigger(self::EVENT_BEFORE_UNBLOCK, $event);
                $user->unblock();
                $this->trigger(self::EVENT_AFTER_UNBLOCK, $event);
                SecurityAudit::log('user_unblock', (int) $user->id, [
					'username' => SecurityAudit::safeLogin($user->username),
				], 'admin', 'User', '/user/admin/block');
                Yii::$app->getSession()->setFlash('success', Yii::t('user', 'User has been unblocked'));
            } else {
                $this->trigger(self::EVENT_BEFORE_BLOCK, $event);
                $user->block();
                $this->trigger(self::EVENT_AFTER_BLOCK, $event);
                SecurityAudit::log('user_block', (int) $user->id, [
					'username' => SecurityAudit::safeLogin($user->username),
				], 'admin', 'User', '/user/admin/block');
                Yii::$app->getSession()->setFlash('warning', Yii::t('user', 'User has been blocked'));
            }
        }

	    return $this->redirect(Yii::$app->request->referrer);
    }

	/**
	 * Deletes a user (with audit).
	 *
	 * @param int $id
	 *
	 * @return Response
	 * @throws InvalidConfigException
	 * @throws NotFoundHttpException
	 * @throws StaleObjectException
	 * @throws Throwable
	 */
	public function actionDelete($id)
	{
		if ((int) $id === (int) Yii::$app->user->getId()) {
			Yii::$app->getSession()->setFlash('danger', Yii::t('user', 'You can not remove your own account'));
		} else {
			$model = $this->findModel($id);
			$event = $this->getUserEvent($model);
			$username = SecurityAudit::safeLogin($model->username);
			$this->trigger(self::EVENT_BEFORE_DELETE, $event);
			$model->delete();
			$this->trigger(self::EVENT_AFTER_DELETE, $event);
			SecurityAudit::log('user_delete', (int) $id, [
				'username' => $username,
			], 'admin', 'User', '/user/admin/delete');
			Yii::$app->getSession()->setFlash('success', Yii::t('user', 'User has been deleted'));
		}

		return $this->redirect(['index']);
	}

	/**
	 * Active selected User models.
	 *
	 * @throws NotFoundHttpException
	 */
    public function actionActivemultiple()
    {
        foreach ($this->getPostedIds() as $id) {
            $model = $this->findModel($id);

            if ($model->getIsBlocked()) {
                $model->unblock();
                SecurityAudit::log('user_unblock', (int) $model->id, [
					'bulk' => true,
					'username' => SecurityAudit::safeLogin($model->username),
				], 'admin', 'User', '/user/admin/activemultiple');
                Yii::$app->getSession()->setFlash('success', Yii::t('user', 'User has been unblocked'));
            }
        }
    }

	/**
	 * Deactive selected User models.
	 *
	 * @throws NotFoundHttpException
	 */
    public function actionDeactivemultiple()
    {
        $currentId = (int) Yii::$app->user->getId();

        foreach ($this->getPostedIds() as $id) {
            if ($id === $currentId) {
                Yii::$app->getSession()->setFlash('danger', Yii::t('user', 'You can not block your own account'));
                continue;
            }

            $model = $this->findModel($id);

            if (!$model->getIsBlocked()) {
                $model->block();
                SecurityAudit::log('user_block', (int) $model->id, [
					'bulk' => true,
					'username' => SecurityAudit::safeLogin($model->username),
				], 'admin', 'User', '/user/admin/deactivemultiple');
                Yii::$app->getSession()->setFlash('warning', Yii::t('user', 'User has been blocked'));
            }
        }
    }

	/**
	 * Deletes selected User models.
	 *
	 * @throws \Exception
	 * @throws \yii\db\Exception
	 * @throws InvalidConfigException
	 * @throws InvalidArgumentException
	 * @throws NotFoundHttpException
	 * @throws StaleObjectException
	 * @throws Throwable
	 */
	public function actionDeletemultiple()
	{
		$ids = $this->getPostedIds();
		if (!$ids) {
			return false;
		}

		$currentId = (int) Yii::$app->user->getId();
		$deleted = [];

		foreach ($ids as $id) {
			if ($id === $currentId) {
				Yii::$app->session->setFlash('danger', Yii::t('user', 'You can not remove your own account'));
				continue;
			}

			$model = $this->findModel($id);
			$username = SecurityAudit::safeLogin($model->username);
			Yii::$app->db->createCommand()->delete('{{%auth_assignment}}', ['user_id' => $id])->execute();
			$model->delete();
			$deleted[] = $id;
			SecurityAudit::log('user_delete', (int) $id, [
				'bulk' => true,
				'username' => $username,
			], 'admin', 'User', '/user/admin/deletemultiple');
		}

		if ($deleted) {
			SecurityAudit::log('user_delete_bulk', 0, [
				'ids' => $deleted,
				'count' => count($deleted),
			], 'admin', 'User', '/user/admin/deletemultiple');
		}

		Yii::$app->session->setFlash('success', Yii::t('userextended', 'Delete Success!'));

		$searchModel  = Yii::createObject(UserSearch::class);
		$dataProvider = $searchModel->search(Yii::$app->request->get());

		return $this->render('index', [
			'dataProvider' => $dataProvider,
			'searchModel'  => $searchModel,
		]);
	}

	/**
	 * Reset password securely: recovery link by default (no plaintext email).
	 *
	 * @param int $id
	 *
	 * @return Response
	 * @throws ForbiddenHttpException
	 * @throws NotFoundHttpException
	 */
	public function actionResendPassword($id)
	{
		$user = $this->findModel($id);
		if ($user->isAdmin) {
			throw new ForbiddenHttpException(Yii::t('user', 'Password generation is not possible for admin users'));
		}

		if ($user->resendPassword()) {
			Yii::$app->session->setFlash(
				'success',
				Yii::t('userextended', 'A password reset link has been sent to the user.')
			);
		} else {
			Yii::$app->session->setFlash(
				'danger',
				Yii::t('userextended', 'Error while trying to send a password reset link.')
			);
		}

		return $this->redirect(Url::previous('actions-redirect'));
	}

	/**
	 * Sanitize posted bulk-action ids (POST only; VerbFilter enforced).
	 *
	 * @return int[]
	 */
	protected function getPostedIds()
	{
		$ids = Yii::$app->request->post('ids', []);
		if (!is_array($ids)) {
			return [];
		}

		$normalized = [];
		foreach ($ids as $id) {
			$id = (int) $id;
			if ($id > 0) {
				$normalized[$id] = $id;
			}
		}

		return array_values($normalized);
	}
}
