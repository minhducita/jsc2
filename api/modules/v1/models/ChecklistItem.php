<?php

namespace api\modules\v1\models;
use api\common\models\Memcache;
use Yii;

/**
 * This is the model class for table "checklist_item".
 *
 * @property string $id
 * @property string $name
 * @property integer $pos
 * @property integer $state
 * @property integer $idChecklist
 */
class ChecklistItem extends \yii\com\db\ModelActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'checklist_item';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pos', 'state', 'idChecklist', 'due'], 'integer'],
            [['idChecklist', 'name'], 'required'],
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
            'state' => Yii::t('app', 'State'),
            'idChecklist' => Yii::t('app', 'Id Checklist'),
			'due' => Yii::t('app', 'deadline'),
        ];
    }

    public function fields()
    {
        $fields = parent::fields();
        $fields['id'] = function($model) {
            return intval($model->id);
        };
        $fields['idChecklist'] = function($model) {
            return intval($model->idChecklist);
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
