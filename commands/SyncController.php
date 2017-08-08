<?php
/**
 * Author: guiyumin, goes by Eric Gui
 * Date: 2017-08-08
 * Time: 21:25
 * Github: https://www.github.com/medmin/ipapp
 * Email: guiyumin@gmail.com
 */
namespace app\commands;

use yii\console\Controller;
use Yii;

class SyncController extends Controller
{
    public function actionSyncAjxxb()
    {
        (new \app\models\Sync())->syncPatents();
        $this->stdout('Complete');

    }

    public function actionSyncRwsl()
    {
        (new \app\models\Sync())->syncPatentevents();
        $this->stdout('Complete');
    }
}