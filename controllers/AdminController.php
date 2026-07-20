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
use Yii;
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
	 * @throws \yii\web\ForbiddenHttpException
	 */
	public function actionSwitch($id = null)
	{
		throw new \yii\web\ForbiddenHttpException(
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

        // Load Old Image
        $oldImage = $profile->avatar;

        // Load avatarPath from Module Params
        $avatarPath = Yii::getAlias(Yii::$app->getModule('userextended')->avatarPath);

        // Create uploadAvatar Instance (only when avatar feature is enabled)
        $image = false;
        if (Yii::$app->getModule('userextended')->avatar) {
            $image = $profile->uploadAvatar($avatarPath);
        }

        // Profile Event
        $event = $this->getProfileEvent($profile);

        // Ajax Validation
        $this->performAjaxValidation($profile);

        $this->trigger(self::EVENT_BEFORE_PROFILE_UPDATE, $event);

        if ($profile->load(Yii::$app->request->post())) {
            // File inputs clear avatar on load(); restore correct value before save
            if ($image === false) {
                $profile->avatar = $oldImage;
            } else {
                $profile->avatar = $image->name;
            }

            if ($profile->save()) {
                if ($image !== false && $oldImage && $oldImage !== $image->name) {
                    $profile->deleteImage($oldImage);
                    // deleteImage() nulls current avatar; persist the new one again
                    $profile->updateAttributes(['avatar' => $image->name]);
                }

                Yii::$app->getSession()->setFlash('success', Yii::t('user', 'Profile details have been updated'));

                $this->trigger(self::EVENT_AFTER_PROFILE_UPDATE, $event);

                return $this->refresh();
            }
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
                Yii::$app->getSession()->setFlash('success', Yii::t('user', 'User has been unblocked'));
            } else {
                $this->trigger(self::EVENT_BEFORE_BLOCK, $event);
                $user->block();
                $this->trigger(self::EVENT_AFTER_BLOCK, $event);
                Yii::$app->getSession()->setFlash('warning', Yii::t('user', 'User has been blocked'));
            }
        }

	    return $this->redirect(Yii::$app->request->referrer);
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

		foreach ($ids as $id) {
			if ($id === $currentId) {
				Yii::$app->session->setFlash('danger', Yii::t('user', 'You can not remove your own account'));
				continue;
			}

			Yii::$app->db->createCommand()->delete('{{%auth_assignment}}', ['user_id' => $id])->execute();
			$this->findModel($id)->delete();
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
