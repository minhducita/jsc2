<?php

namespace api\modules\v1\models;

use api\modules\v1\models\NotificationsMember;
use api\modules\v1\models\Members;
use api\common\models\Memcache;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "notifications".
 *
 * @property integer $id
 * @property integer $type
 * @property string  $data
 * @property integer $addedDate
 * @property integer $readed
 * @property integer $idSender
 * @property integer $idReceive
 */
class Notifications extends \yii\com\db\ModelActiveRecord 
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
       
        return 'notifications';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'data', 'addedDate', 'idReceive','name'], 'required'],
            [['type', 'addedDate','idReceive','idEffectMember'], 'integer'],
            [['data'], 'string']
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
            'addedDate' => Yii::t('app', 'Added Date'),
            'idReceive' => Yii::t('app', 'Id Receive'),
			'name'    => Yii::t('app', 'Table name')
        ];
    }
	
	public function fields() 
	{
        $fields = parent::fields();
        $fields['type'] = function($model) {
            return intval($model->type);
        };		
        $fields['id'] = function($model) {
            return intval($model->id);
        };		
        $fields['data'] = function($model) {
            return json_decode($model->data);
        };		
        $fields['idReceive'] = function($model) {
            return intval($model->idReceive);
        };		
        $fields['addedDate'] = function($model) {
            return date("Y-m-d H:i:s",$this->addedDate);
        };
		$fields['idEffectMember'] = function($model) {
            return intval($model->idEffectMember);
        };		
        return $fields;
	}
	
	public function extraFields() {
		return ['members','effectMember','notifyMember'];
	}
	
	public function getMembers() {
        return Members::find()->where(['id'=>$this->idReceive])->one();
	}
	
	public function getNotifyMember(){
		$idMember = userIdentity()->getId();
		return NotificationsMember::find()->where(['idNotification'=>$this->id,'idMember'=>$idMember])->one();
		
	}
	
	public function getEffectMember() {
		 return Members::find()->where(['id'=>$this->idEffectMember])->one();
	}
	
	/********************* Memory Cache *************************/

	public function findCacheAll ($data, $relation = 0) 
	{
		$memcache = new Memcache;
		$memcache->data = $data;
		return $memcache->findCacheAll($relation);
	}
}
