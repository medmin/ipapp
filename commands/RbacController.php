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
     * 生成三个角色
     * admin --> 超级管理员
     * secadmin --> 二级管理员
     * manager --> 商务人员
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

        $createClient = $auth->createPermission('createClient');
        $createClient->description = 'create a client';
        $auth->add($createClient);

        // 这个权限其实可有可无，当他拥有createClient的时候 其实他也拥有delete的权益，这个是可以不写的
        $deleteClient = $auth->createPermission('deleteClient');
        $deleteClient->description = 'delete client';
        $auth->add($deleteClient);

        // 商务：只读所属用户以及所属用户的专利及事件
        $manager = $auth->createRole('manager');
        $auth->add($manager);
        // manager 这个角色设为员工，可以管理客户
        $auth->addChild($manager, $createClient);
        $auth->addChild($manager, $deleteClient);

        // 超级管理员：所有功能
        $admin = $auth->createRole('admin');
        $auth->add($admin);
        // admin 可以创建员工
        $auth->addChild($admin, $createEmployee);
        $auth->addChild($admin, $deleteEmployee);
        // 将manager的权限也赋给admin，admin不仅可以创建员工，可以创建客户
        $auth->addChild($admin, $manager);

        // 二级管理员 ：只读所有user 只读所有 Patent  读写Paten Event，权限控制放在ACF中
        $secAdmin = $auth->createRole('secadmin');
        $auth->add($secAdmin);

//        $auth->assign($manager, 2);
        $auth->assign($admin, 1);

        $this->stdout('Complete' . PHP_EOL);
        return Controller::EXIT_CODE_NORMAL;

    }
}