<?php

namespace api\modules\v1\models;

use Yii;
use api\common\models\Member;
/**
 * This is the model class for table "organization_member".
 *
 * @property string $idOrganization
 * @property string $idMember
 */
class OrganizationMember extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'organization_member';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['idMember'], 'required'],
            [['idMember'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'idOrganization' => Yii::t('app', 'Id Organization'),
            'idMember' => Yii::t('app', 'Id Member'),
        ];
    }
	
	public function extraFields()
    {
        return ['member'];
    }
	
	public function getMember() {
		return Member::find()->where(['id'=>$this->idMember])->one();
	}
}
