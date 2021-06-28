<?php

namespace api\modules\v1\models;

use Yii;

/**
 * This is the model class for table "board_star".
 *
 * @property string $id
 * @property integer $idBoard
 * @property integer $idMember
 * @property integer $pos
 */
class BoardStar extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'board_star';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['idBoard', 'idMember'], 'required'],
            [['idBoard', 'idMember', 'pos'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'idBoard' => Yii::t('app', 'Id Board'),
            'idMember' => Yii::t('app', 'Id Member'),
            'pos' => Yii::t('app', 'Pos'),
        ];
    }
	
	
	/********************** Memory Cache *************************/
	public function findCacheAll ($data, $relation = 0) 
	{
		$memcache = new Memcache;
		$memcache->data = $data;
		return $memcache->findCacheAll($relation);
	}
}
