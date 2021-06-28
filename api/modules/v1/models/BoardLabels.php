<?php

namespace api\modules\v1\models;

use Yii;
use api\common\models\Memcache;

/**
 * This is the model class for table "board_labels".
 *
 * @property integer $id
 * @property string $name
 * @property string $color
 * @property integer $used
 * @property integer $idBoard
 */
class BoardLabels extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'board_labels';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['used', 'idBoard'], 'integer'],
            [['idBoard'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['color'], 'string', 'max' => 60]
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
            'color' => Yii::t('app', 'Color'),
            'used' => Yii::t('app', 'Used'),
            'idBoard' => Yii::t('app', 'Id Board'),
        ];
    }
	
	/********************* Memory Cache *************************/
	
	public function findCacheAll ($data, $relation = 0) 
	{
		$memcache = new Memcache;
		$memcache->data = $data;
		return $memcache->findCacheAll($relation);
	}
}
