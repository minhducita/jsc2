<?php

namespace api\modules\v1\models;

use Yii;
use api\common\models\Memcache;

/**
 * This is the model class for table "checklist".
 *
 * @property string $id
 * @property string $name
 * @property integer $pos
 * @property integer $idCard
 * @property integer $idBoard
 */
class Checklist extends \yii\com\db\ModelActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'checklist';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pos', 'idCard', 'idBoard'], 'integer'],
            [['idCard', 'name'], 'required'],
            [['name'], 'string', 'max' => 255]
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
            'pos' => Yii::t('app', 'Pos'),
            'idCard' => Yii::t('app', 'Id Card'),
            'idBoard' => Yii::t('app', 'Id Board'),
        ];
    }
    public function fields()
    {
        $fields = parent::fields();
        $fields['id'] = function($model) {
          return intval($model->id);
        };
        $fields['idCard'] = function($model) {
            return intval($model->idCard);
        };
        $fields['idBoard'] = function($model) {
            return intval($model->idBoard);
        };
        $fields['checkItems'] = function($model) {
           return ChecklistItem::find()->where(['idChecklist'=>$model->id])->all();
        };
        return $fields;
    }

    public function extraFields()
    {
        return ['checklistitems'];
    }


    public function getChecklistitems()
    {
		$checkItem = $this->hasMany(ChecklistItem::className(), ['idChecklist' => 'id']);
        return ChecklistItem::findCacheAll($checkItem);
    }
	
	/********************** Memory Cache *************************/
	public function findCacheAll ($data, $relation = 0) 
	{
		$memcache = new Memcache;
		$memcache->data = $data;
		return $memcache->findCacheAll($relation);
	}
}
