<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use yii\console\Controller;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class HelloController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     */
    public function actionIndex($message = 'hello world')
    {
        echo $message . "\n";
    }

    /**
     * base64 图片下载
     * 
     */
    public function actionImage()
    {
        $str = file_get_contents("https://www.staticfile.org/assets/images/qiniu.png");
        $base64 = base64_encode($str);
        echo $base64;
        // 图片类型只能根据 data:image/png;base64 来判断
        $file_size = file_put_contents('qiniu.png',base64_decode($base64));
        echo $file_size; // 文件大小
    }

    /**
     * 初始化一个系统管理员
     *
     * @param string $username
     * @param string $password
     * @param string $organization
     * @param string $fullname
     * @param string $citizenID
     * @param string $email
     * @return bool|int
     */
    public function actionAdminInit($username = 'admin', $password = '123456', $organization = 'SHINEIP', $fullname = '系统管理员', $citizenID = '123456789012345678', $email = 'admin@shineip.com')
    {
        if (\app\models\Users::findOne(['userUsername' => 'admin'])) return Controller::EXIT_CODE_NORMAL;
        $admin = new \app\models\Users();
        $admin->userUsername = $username;
        $admin->setPassword($password);
        $admin->userOrganization = $organization;
        $admin->userFullname = $fullname;
        $admin->userCitizenID = $citizenID;
        $admin->userEmail = $email;
        $admin->userCellphone = 'N/A'; //填写 '' 会报错
        $admin->userLandline = 'N/A';
        $admin->userAddress = 'N/A';
        $admin->userLiaison = 'N/A';
        $admin->userLiaisonID = 0;
        $admin->userRole = 2;
        $admin->userNote = 'N/A';
        $admin->generateAuthKey();
        $admin->UnixTimestamp = time() * 1000;
        if (!$admin->save()) {
            print_r($admin->errors);
            return Controller::EXIT_CODE_ERROR;
        }else{
            return Controller::EXIT_CODE_NORMAL;
        }
    }
}
