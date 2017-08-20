<?php
/**
 * Author: guiyumin, goes by Eric Gui
 * Date: 2017-08-19
 * Time: 14:56
 * Github: https://www.github.com/medmin/ipapp
 * Email: guiyumin@gmail.com
 */
namespace app\models;

use app\models\eac\Rwsl;
use yii\base\Model;
use yii\web\UploadedFile;
use yii;
use yii\db\Transaction;

class UploadForm extends Model
{

    /**
     * @var UploadedFile[]
     */
    public $patentFiles;
    public $ajxxb_id;

    public function rules()
    {
        return [
            [['patentFiles'], 'file', 'skipOnEmpty' => false, 'maxFiles' => 5],
            ['ajxxb_id', 'required'],
        ];
    }

    public function upload()
    {
        if ($this->validate()) {
            foreach ($this->patentFiles as $file) {


                $isolationLevel = Transaction::SERIALIZABLE;
                $transaction = Yii::$app->db->beginTransaction($isolationLevel);
                try
                {
                    //新建一条专利文件的记录
                    $fileObj = new Patentfiles();

                    $fileObj->patentAjxxbID = $this->ajxxb_id;
                    $fileObj->fileName = $file->baseName;
                    $fileObj->filePath = Yii::$app->params['filePath']
                        . $this->ajxxb_id
                        .'_'. date("Ymd") .'_'. uniqid()
                        . '.'. $file->extension ;
                    $fileObj->fileUploadUserID = Yii::$app->user->id;
                    $fileObj->fileUploadedAt = time();

                    $fileObj->save();
                    if(!$fileObj->save())
                    {
//                        print_r($fileObj->errors);exit;
                        throw new \Exception();
                    }


                    //新建一条专利事务的记录
                    $eventObj = new Patentevents();

                    $eventObj->eventRwslID = uniqid();
                    $eventObj->patentAjxxbID = $this->ajxxb_id;
                    $eventObj->eventContentID = 'file';
                    $eventObj->eventContent = Rwsl::rwdyIdMappingContent()['file']
                        .': '. $file->baseName . '; '
                        . '专利：'.Patents::findOne(['patentAjxxbID' => $this->ajxxb_id])->patentTitle;
                    $eventObj->eventCreatUnixTS = time() *1000;
                    $eventObj->eventCreatPerson = '';
                    $eventObj->eventStatus = 'INACTIVE';
                    $eventObj->eventFinishUnixTS = time() *1000;
                    $eventObj->eventFinishPerson = '';
                    $eventObj->eventUserID = 0;

                    $eventObj->save();
                    if(!$eventObj->save())
                    {
                        throw new \Exception();
                    }

                    $transaction->commit();
                }
                catch (\Exception $e)
                {

                    $transaction->rollBack();

                    throw $e;
                }


                $file->saveAs($fileObj->filePath);
            }
            return true;
        } else {
            return false;
        }
    }


}