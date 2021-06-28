<?php

namespace api\modules\v1\models;

use Yii;
use api\common\models\Memcache;

/**
 * This is the model class for table "board_memberships".
 *
 * @property string $id
 * @property integer $idMember
 * @property integer $idBoard
 * @property string $memberType
 * @property string $orgMemberType
 */
class BoardMemberships extends \yii\db\ActiveRecord
{
    const ADMIN_ROLE = 'admin';
    const NORMAL_ROLE = 'normal';
    const ORG_ADMIN_ROLE = 'admin';
    const ORG_NORMAL_ROLE = 'normal';
	/*key cache*/
	const KEYCACHEALL = 'boardmembershipsidmember';
	const KEYCACHEONE = 'boardmembershipsidboard';
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'board_memberships';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['idMember', 'idBoard'], 'required'],
            [['idMember', 'idBoard'], 'integer'],
            [['memberType', 'orgMemberType'], 'string']
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
        return $fields;
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'idMember' => Yii::t('app', 'Id Member'),
            'idBoard' => Yii::t('app', 'Id Board'),
            'memberType' => Yii::t('app', 'Member Type'),
            'orgMemberType' => Yii::t('app', 'Org Member Type'),
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
