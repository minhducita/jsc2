<?php

namespace api\modules\v1\models;


use api\modules\v1\models\Members;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "notifications_member".
 *
 * @property integer $idMember
 * @property integer $idNotification
 * @property integer  $read
 */
class NotificationsMember extends \yii\com\db\ModelActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'notifications_member';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['idMember', 'idNotification', 'read'], 'required'],
            [['idMember', 'idNotification', 'read'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'idMember' 			=> Yii::t('app', 'idMember'),
            'idNotification' 	=> Yii::t('app', 'idNotification'),
            'read'				=> Yii::t('app', 'read'),
        ];
    }    
   public function fields() 
	{
        $fields = parent::fields();
        $fields['idMember'] = function($model) {
            return intval($model->idMember);
        };		
        $fields['idNotification'] = function($model) {
            return intval($model->idNotification);
        };		
        $fields['read'] = function($model) {
            return intval($model->read);
        };		
        return $fields;
	} 
    
}
