<?php

namespace api\modules\v1\models;

use api\common\models\Memcache;
use Yii;

/**
 * This is the model class for table "organization_memberships".
 *
 * @property string $id
 * @property integer $idMember
 * @property integer $idOrganization
 * @property string $memberType
 * @property string $orgMemberType
 */
class OrganizationMemberships extends \yii\com\db\ModelActiveRecord
{
    const NORMAL_TYPE = 'normal';
    const ADMIN_TYPE = 'admin';
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'organization_memberships';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['idMember', 'idOrganization'], 'required'],
            [['idMember', 'idOrganization'], 'integer'],
            [['memberType'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'idMember' => Yii::t('app', 'Id Member'),
            'idOrganization' => Yii::t('app', 'Id Organization'),
            'memberType' => Yii::t('app', 'Member Type'),
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
	/********************* Memory Cache *************************/

	public function findCacheAll ($data, $relation = 0) 
	{
		$memcache = new Memcache();
		$memcache->data = $data;
		return $memcache->findCacheAll($relation);
	}
}
