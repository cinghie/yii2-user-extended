<?php

/**
 * @copyright Copyright &copy; Gogodigital Srls
 * @company Gogodigital Srls - Wide ICT Solutions
 * @website http://www.gogodigital.it
 * @github https://github.com/cinghie/yii2-user-extended
 * @license GNU GENERAL PUBLIC LICENSE VERSION 3
 * @package yii2-user-extended
 * @version 0.5.5
 */

namespace cinghie\yii2userextended\filters;

use yii\base\ActionFilter;
use yii\web\ForbiddenHttpException;

class BackendFilter extends ActionFilter
{
    /**
     * @var array
     */
    public $controllers = ['recovery', 'registration'];

    /**
     * @param \yii\base\Action $action
     *
     * @return bool
     * @throws \yii\web\NotFoundHttpException
     */
    public function beforeAction($action)
    {
        if (in_array($action->controller->id, $this->controllers)) {
            throw new ForbiddenHttpException();
        }
        return true;
    }
}
