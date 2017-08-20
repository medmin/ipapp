<?php
/**
 * Author: guiyumin, goes by Eric Gui
 * Date: 2017-08-19
 * Time: 14:56
 * Github: https://www.github.com/medmin/ipapp
 * Email: guiyumin@gmail.com
 */
namespace app\models;

use yii\base\Model;
use yii\web\UploadedFile;
use yii;

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
                $file->saveAs(Yii::$app->params['filePath'] . $file->baseName . '.' . $file->extension);
            }
            return true;
        } else {
            return false;
        }
    }
}