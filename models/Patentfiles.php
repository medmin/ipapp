<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "patentfiles".
 *
 * @property integer $fileID
 * @property string $patentAjxxbID
 * @property string $fileName
 * @property string $filePath
 * @property string $fileUploadUserID
 * @property string $fileUploadedAt
 * @property string $fileUpdateUserID
 * @property string $fileUpdatedAt
 * @property string $fileNote
 *
 * @property Patents $patentAjxxb
 */
class Patentfiles extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'patentfiles';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['patentAjxxbID', 'fileUploadedAt'], 'required'],
            [['fileUploadedAt', 'fileUpdatedAt', 'fileUploadUserID', 'fileUpdateUserID'], 'integer'],
            [['patentAjxxbID'], 'string', 'max' => 20],
            [['fileName', 'filePath', 'fileNote'], 'string', 'max' => 1000],
            [['patentAjxxbID'], 'exist', 'skipOnError' => true, 'targetClass' => Patents::className(), 'targetAttribute' => ['patentAjxxbID' => 'patentAjxxbID']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'fileID' => Yii::t('app', 'File ID'),
            'patentAjxxbID' => Yii::t('app', 'Patent Ajxxb ID'),
            'fileName' => Yii::t('app', 'File Name'),
            'filePath' => Yii::t('app', 'File Path'),
            'fileUploadUserID' => Yii::t('app', 'File Upload User ID'),
            'fileUploadedAt' => Yii::t('app', 'File Uploaded At'),
            'fileUpdateUserID' => Yii::t('app', 'File Update User ID'),
            'fileUpdatedAt' => Yii::t('app', 'File Updated At'),
            'fileNote' => Yii::t('app', 'File Note'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPatentAjxxb()
    {
        return $this->hasOne(Patents::className(), ['patentAjxxbID' => 'patentAjxxbID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUploadUser()
    {
        return $this->hasOne(Users::className(), ['userID' => 'fileUploadUserID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdateUser()
    {
        return $this->hasOne(Users::className(), ['userID' => 'fileUpdateUserID']);
    }
}
