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
use EasyWeChat\Foundation\Application;
use app\modules\wechat\models\TemplateForm;

class UploadForm extends Model
{

    /**
     * @var UploadedFile[]
     */
    public $patentFiles;
    public $ajxxb_id;
    public $eventType;

    public function rules()
    {
        return [
            [['patentFiles'], 'file', 'skipOnEmpty' => false, 'maxFiles' => 5],
            ['ajxxb_id', 'required'],
            ['eventType', 'required'],
            // 验证事务类型不等于0
            ['eventType', 'compare', 'compareValue' => 0, 'operator' => '!=', 'message' => '请选择专利事务和微信通知的类型'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'eventType' => Yii::t('app', 'Event TYPE'),
        ];
    }

    public function upload()
    {
        if ($this->validate()) {

            foreach ($this->patentFiles as $file)
            {
                $path_parts = pathinfo($file);
                $extension= strtolower($path_parts["extension"]);

                if (!in_array($extension, ['tif', 'png', 'jpg', 'doc', 'docx', 'xls', 'xlsx','ppt', 'pptx', 'pdf', 'zip', 'rar', '7z', 'txt'])
                ) {
                    $this->addError('patentFiles', '格式错误,不允许上传 '. $extension .' 格式的文件');
                    return false;
//                    throw new yii\web\ForbiddenHttpException('不允许上传'. $extension . '类型的文件'. PHP_EOL . '可以上传的文件后缀有：tif, png, jpg, doc, docx, xls, xlsx, ppt, pptx, pdf, zip, rar, 7z, txt');
                }
            }

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
                    $fileObj->fileUpdateUserID = Yii::$app->user->id;
                    $fileObj->fileUpdatedAt = time();

                    if(!$fileObj->save())
                    {
//                        print_r($fileObj->errors);exit;
                        throw new \Exception();
                    }


                    // 判断是否创建事务，发送微信通知
                    if ($this->eventType != -2) {
                        // 新建一条专利事务的记录
                        $eventObj = new Patentevents();

                        $eventObj->eventRwslID = uniqid() . '_' . $fileObj->fileID; // 加上id是为了方便后期判断这个event属于那个文件
                        $eventObj->patentAjxxbID = $this->ajxxb_id;
                        $eventObj->eventContentID = 'file';
                        // $eventObj->eventContent = Rwsl::rwdyIdMappingContent()['file']
                        //     .': '. $file->baseName . '.' . $file->extension;
                        $eventObj->eventContent = Patentevents::eventTypes()[$this->eventType]
                            .': '. $file->baseName . '.' . $file->extension;
                        $eventObj->eventCreatUnixTS = time() *1000;
                        $eventObj->eventCreatPerson = '';
                        $eventObj->eventStatus = 'INACTIVE';
                        $eventObj->eventFinishUnixTS = time() *1000;
                        $eventObj->eventFinishPerson = '';
                        // 专利信息
                        $patent = Patents::findOne(['patentAjxxbID' => $this->ajxxb_id]);
                        $eventObj->eventUserID = $patent->patentUserID;
                        $eventObj->eventUsername = $patent->patentUsername;
                        if(!$eventObj->save())
                        {
                            throw new \Exception();
                        }

                        // 判断是否发送微信通知
                        if ($this->eventType != -1) {
                            // 发送微信通知
                            $user = WxUser::findOne(['userid' => $patent->patentUserID]);
                            if ($user) {
                                $options = [
                                    'debug'  => YII_DEBUG,
                                    'app_id' => Yii::$app->params['wechat']['id'],
                                    'secret' => Yii::$app->params['wechat']['secret'],
                                    'token'  => Yii::$app->params['wechat']['token'],
                                    'aes_key' => Yii::$app->params['wechat']['aes_key'],
                                    'log' => [
                                        'level' => 'debug',
                                        'file'  => Yii::$app->params['wechat_log_path'], // XXX: 绝对路径！！！！
                                    ]
                                ];
                                $app = new Application($options);
                                $notice = $app->notice;

                                $data = [
                                    'first' => '您好，您的专利有新进展',
                                    'keyword1' => $patent->patentTitle, //数据OK
                                    'keyword2' => Patentevents::eventTypes()[$this->eventType],
                                    'remark' => '如果有任何疑问，请拨打0451-88084686',
                                ];
                                $messageID = $notice->send([
                                    'touser' => $user->fakeid,
                                    'template_id' => TemplateForm::PROJECT_PROGRESS_NOTIFICATION,
                                    'url' => 'https://kf.shineip.com/',
                                    'data' => $data,
                                ]);
                            }
                        }
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