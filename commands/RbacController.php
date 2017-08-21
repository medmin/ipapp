<?php
/**
 * User: Mr-mao
 * Date: 2017/7/24
 * Time: 11:12
 */

namespace app\commands;

use app\models\Users;
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

    /**
     * 生成一个demo用户,修改其他controller只让demo访问index
     *
     * @return int
     */
    public function actionDemo()
    {
        if (Users::findOne(['userUsername' => 'demo'])) {
            $this->stdout('The demo already exists' . PHP_EOL);
            return Controller::EXIT_CODE_ERROR;
        }
        $auth = Yii::$app->authManager;
        $demo = $auth->createRole('demo');
        $auth->add($demo);

        $demo_user = new Users();
        $demo_user->userUsername = 'demo';
        $demo_user->setPassword('123456');
        $demo_user->userOrganization = 'doze';
        $demo_user->userFullname = 'doze测试使用';
        $demo_user->userCitizenID = '110226199703041433';
        $demo_user->userEmail = 'demo@demo.com';
        $demo_user->userCellphone = '18953412586'; //填写 '' 会报错
        $demo_user->userLandline = '13245648';
        $demo_user->userAddress = '河南郑州';
        $demo_user->userLiaison = 'N/A';
        $demo_user->userLiaisonID = 0;
        $demo_user->userRole = Users::DEMO;
        $demo_user->userNote = 'N/A';
        $demo_user->generateAuthKey();
        $demo_user->UnixTimestamp = time() * 1000;

        if ($demo_user->save() && $auth->assign($demo, $demo_user->userID)) {
            $this->stdout('Complete' . PHP_EOL);
            return Controller::EXIT_CODE_NORMAL;
        }
        return Controller::EXIT_CODE_ERROR;
    }
}