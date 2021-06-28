<?php

namespace api\modules\v1\models;

use Yii;

/**
 * This is the model class for table "card_members".
 *
 * @property string $idCard
 * @property string $idMember
 */
class CardMembers extends \yii\com\db\ModelActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'card_members';
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
            'idCard' => Yii::t('app', 'Id Card'),
            'idMember' => Yii::t('app', 'Id Member'),
        ];
    }
}
