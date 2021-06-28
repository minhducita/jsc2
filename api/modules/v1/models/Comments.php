<?php

namespace api\modules\v1\models;

use api\common\models\Member;
use Yii;
use api\common\models\Memcache;

/**
 * This is the model class for table "comments".
 *
 * @property string $id
 * @property string $content
 * @property integer $addedDate
 * @property integer $idCard
 * @property integer $idBoard
 * @property integer $idMember
 */
class Comments extends \yii\com\db\ModelActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'comments';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['content'], 'string'],
            [['addedDate', 'idCard', 'idMember'], 'required'],
            [['addedDate', 'idCard', 'idMember'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'content' => Yii::t('app', 'Content'),
            'addedDate' => Yii::t('app', 'Added Date'),
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
        $fields['members'] = function($model) {
            return Member::findOne($model->idMember);
        };
        $fields['addedDate'] = function($model) {
            return date('d-M-Y H:i:s', $model->addedDate);
        };
        return $fields;
    }
	
	/********************** Memory Cache *************************/
	
	public function findCacheAll ($data, $relation = 0) 
	{
		$memcache = new Memcache;
		$memcache->data = $data;
		return $memcache->findCacheAll($relation);
	}
}
