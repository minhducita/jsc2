<?php

namespace api\modules\v1\models;

use Yii;
use yii\helpers\Json;

use api\common\models\Memcache;

/**
 * This is the model class for table "attachments".
 *
 * @property string $id
 * @property string $name
 * @property integer $bytes
 * @property string $mimeType
 * @property string $edgeColor
 * @property string $previews
 * @property string $url
 * @property integer $isUploaded
 * @property integer $uploadedDate
 * @property integer $idCard
 * @property integer $idMember
 */
class Attachments extends \yii\com\db\ModelActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'attachments';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'url', 'isUploaded', 'idCard', 'idMember'], 'required'],
            [['bytes', 'isUploaded', 'uploadedDate', 'idCard', 'idMember'], 'integer'],
            [['previews'], 'string'],
            [['name', 'url'], 'string', 'max' => 255],
            [['mimeType', 'edgeColor'], 'string', 'max' => 200],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'bytes' => Yii::t('app', 'Bytes'),
            'mimeType' => Yii::t('app', 'Mime Type'),
            'edgeColor' => Yii::t('app', 'Edge Color'),
            'previews' => Yii::t('app', 'Previews'),
            'url' => Yii::t('app', 'Url'),
            'isUploaded' => Yii::t('app', 'Is Uploaded'),
            'uploadedDate' => Yii::t('app', 'Uploaded Date'),
            'idCard' => Yii::t('app', 'Id Card'),
            'idMember' => Yii::t('app', 'Id Member'),
        ];
    }

    public function fields()
    {
        $fields = parent::fields();
        $fields['id'] = function($model) {
            return intval($model->id);
        };
        $fields['idMember'] = function($model) {
            return intval($model->idMember);
        };
        $fields['idCard'] = function($model) {
            return intval($model->idCard);
        };
        $fields['previews'] = function($model) {
            return Json::decode($model->previews);
        };
        $fields['uploadedDate'] = function($model) {
            return date('Y/m/d H:i:s', $model->uploadedDate);
        };
        return $fields;
    }
	/********************* Memory Cache *************************/
	public function findCacheAll ($data, $relation=0) 
	{
		$memcache = new Memcache;
		$memcache->data = $data;
		return $memcache->findCacheAll($relation);
	}

	public static function deleteCache ($id=0) 
	{
		$memcache = new Memcache;
		$memcache->id = $id;
		
		$boardmember = BoardMember::find()->where(['idBoard'=> $id])->select('idMember')->all();
		if(!empty($boardmember)) {
			foreach($boardmember as $item) 
			{
				$memcache->setFindKey($item->idMember);
			}
		}
		
		$keyfind = "`idBoard`='$0'";
		$memcache->setFindKey($id, $keyfind);
		$memcache->deleteCache();
	}
}
