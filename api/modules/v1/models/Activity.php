<?php

namespace api\modules\v1\models;

use Yii;

/**
 * This is the model class for table "activity".
 *
 * @property string $id
 * @property string $type
 * @property string $data
 * @property string $key
 * @property integer $idBoard
 * @property integer $idMember
 * @property integer $addedDate
 */
class Activity extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'activity';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['data', 'idBoard', 'idMember', 'addedDate'], 'required'],
            [['data'], 'string'],
            [['idBoard', 'idMember', 'addedDate'], 'integer'],
            [['type'], 'string', 'max' => 100],
            [['key'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'type' => Yii::t('app', 'Type'),
            'data' => Yii::t('app', 'Data'),
            'key' => Yii::t('app', 'Key'),
            'idBoard' => Yii::t('app', 'Id Board'),
            'idMember' => Yii::t('app', 'Id Member'),
            'addedDate' => Yii::t('app', 'Added Date'),
        ];
    }
}
