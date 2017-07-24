<?php
/**
 * User: Mr-mao
 * Date: 2017/7/24
 * Time: 11:12
 */

namespace app\commands;

use Yii;
use yii\console\Controller;

class RbacController extends Controller
{
    /**
     * initialization
     * 默认生成两个角色manager和admin
     */
    public function actionInit()
    {

        $auth = Yii::$app->authManager;
        if (!is_dir('rbac/')) mkdir('rbac/');
        is_file($auth->assignmentFile)?:touch($auth->assignmentFile);
        is_file($auth->itemFile)?:touch($auth->itemFile);
        is_file($auth->ruleFile)?:touch($auth->ruleFile);
        $auth->removeAll();

        // 1. 权限名和路由没有任何关系
        // 2. 如果只是用ACF来判断，那么只添加角色应该就够用了，否则可以使用 Yii::$app->user->can("permissionName")
        // 3. 可以把permissionName 设置成类似 controller/action 的格式，直接在beforeAction做权限判断
        $createEmployee = $auth->createPermission('createEmployee');
        $createEmployee->description = 'create an employee';
        $auth->add($createEmployee);

        $deleteEmployee = $auth->createPermission('deleteEmployee');
        $deleteEmployee->description = 'delete employee';
        $auth->add($deleteEmployee);

        $manager = $auth->createRole('manager');
        $auth->add($manager);
        $auth->addChild($manager, $createEmployee);

        $admin = $auth->createRole('admin');
        $auth->add($admin);
        $auth->addChild($admin, $deleteEmployee);
        $auth->addChild($admin, $manager);

        // admin可以执行创建和删除操作 manager只能执行创建操作
//        $auth->assign($manager, 2);
        $auth->assign($admin, 1);

        $this->stdout('Complete');
        return Controller::EXIT_CODE_NORMAL;

    }
}