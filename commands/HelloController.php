<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\eac\Rwsl;
use app\models\Patentevents;
use app\models\Patents;
use app\models\Users;
use yii\console\Controller;
use Faker\Factory;

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
     * 身份证是随机的（并非真实用户）
     *
     * @param string $username
     * @param string $password
     * @param string $organization
     * @param string $fullname
     * @param string $citizenID
     * @param string $email
     * @return bool|int
     */
    public function actionAdminInit($username = 'admin', $password = '123456', $citizenID = '370211198106135297', $organization = 'SHINEIP', $fullname = '系统管理员', $email = 'admin@shineip.com')
    {
        if (Users::findOne(['userUsername' => 'admin'])) return Controller::EXIT_CODE_NORMAL;
        $admin = new Users();
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
        $admin->userRole = Users::ROLE_ADMIN;
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

    public function actionPatentsFaker()
    {
        $start = time();
        $faker = Factory::create('zh_CN');
        for ($i = 1; $i <= 3000; $i++) {
            $userID = array_rand([5 => 5, 3 => 3, 4 => 4, 9 => 9, 10 => 10]);
            $liaisonID = array_rand([8 => 8, 7 => 7]);
            $patent = new Patents();
            $patent->patentAjxxbID = substr($faker->uuid,0,20);
            $patent->patentEacCaseNo = 'FK_' . $faker->date($format = 'Ymd', $max = 'now');
            $patent->patentType = 'invent';
            $patent->patentUserID = $userID;
            $patent->patentUsername = Users::findOne($userID)->userFullname;
            $patent->patentUserLiaisonID = $liaisonID;
            $patent->patentUserLiaison = Users::findOne($liaisonID)->userFullname;
            $patent->patentAgent = $faker->name;
            $patent->patentProcessManager = $faker->name;
            $patent->patentTitle = $faker->text(40);
            $patent->patentNote = $faker->text(100);
            $patent->UnixTimestamp = $faker->unixTime($max = 'now') * 1000;
            if (!$patent->save()) {
                echo '<pre>';
                print_r($patent->errors);
                echo '</pre>';
                exit;
            }
        }
        $this->stdout('OK,Time Consuming:' . time() - $start);
    }

    public function actionEventsFaker($ajxbid)
    {
        $patent = Patents::findOne(['patentAjxxbID' => $ajxbid]);
        if ($patent == null) {
            $this->stdout('检查id是否正确');
            return Controller::EXIT_CODE_ERROR;
        }
        $faker = Factory::create();
        for ($i = 0; $i <= 4; $i++) {
            $con_key = array_rand(Rwsl::rwdyIdMappingContent());
            $event = new Patentevents();
            $event->eventRwslID = '0FC' . strtoupper($faker->word);
            $event->eventContent = Rwsl::rwdyIdMappingContent()[$con_key];
            $event->eventContentID = $con_key;
            $event->eventNote = $faker->text(100);
            $event->patentAjxxbID = $ajxbid;
            $event->eventUserID = $patent->patentUserID;
            $event->eventUsername = $patent->patentUsername;
            $event->eventUserLiaison = $patent->patentUserLiaison;
            $event->eventUserLiaisonID = $patent->patentUserLiaisonID;
            $event->eventCreatPerson = Factory::create('zh_CN')->name;
            $event->eventCreatUnixTS = $faker->unixTime($max = 'now') * 1000;
            $event->eventFinishPerson = ($i % 2 == 0 ? 'N/A' : Factory::create('zh_CN')->name);
            $event->eventFinishUnixTS = ($i % 2 == 0 ? 0 : time());
            if ($i == 4) {
                $event->eventSatus = 'PENDING';
            }
            if ($i % 2 != 0 && $i != 4) {
                $event->eventSatus = 'INACTIVE';
            }
            $event->save();
        }
        return Controller::EXIT_CODE_NORMAL;
    }
}