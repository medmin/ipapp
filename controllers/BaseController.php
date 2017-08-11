<?php
/**
 * User: Mr-mao
 * Date: 2017/8/11
 * Time: 10:08
 *
 * 这个文件是个傻逼文件。莫名其妙数据库的问题。- -
 */

namespace app\controllers;

use app\models\Users;
use yii\web\Controller;
use Yii;
use yii\web\ForbiddenHttpException;

class BaseController extends Controller
{
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) return false;
        if (Yii::$app->user->isGuest) return true;
        $current_controller = $this->id;
        $current_action = $this->action->id;
        $user_role = Yii::$app->user->identity->userRole;
        switch ($current_controller) {
            case 'users':
                $this->isUsers($current_action, $user_role);
                break;
            case 'patents':
                $this->isPatents($current_action, $user_role);
                break;
            case 'patentevents':
                $this->isEvents($current_action, $user_role);
                break;
            default:
                break;
        }
        return true;
    }

    public function isUsers($action, $user_role)
    {
        if (in_array($action, ['delete']) && !in_array($user_role, [Users::ROLE_ADMIN])) {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        } elseif (in_array($action, ['update', 'create']) && !in_array($user_role, [Users::ROLE_ADMIN])) {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        } elseif (in_array($action, ['index', 'view', 'notify']) && !in_array($user_role, [Users::ROLE_ADMIN, Users::ROLE_SECONDARY_ADMIN, Users::ROLE_EMPLOYEE])) {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        }
        return true;
    }

    public function isPatents($action, $user_role)
    {
        if (in_array($action, ['create', 'update', 'delete']) && !in_array($user_role, [Users::ROLE_ADMIN])) {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        } elseif (in_array($action, ['index', 'view']) && !in_array($user_role, [Users::ROLE_ADMIN, Users::ROLE_SECONDARY_ADMIN])) {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        } else {
            return true;
        }
    }

    public function isEvents($action, $user_role)
    {
        if (in_array($action, ['delete']) && !in_array($user_role, [Users::ROLE_ADMIN])) {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        } elseif (in_array($action, ['index', 'view', 'create', 'update']) && !in_array($user_role, [Users::ROLE_ADMIN, Users::ROLE_SECONDARY_ADMIN])) {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        } else {
            return true;
        }
    }
}