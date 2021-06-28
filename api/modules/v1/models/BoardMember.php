<?php

namespace api\modules\v1\models;

use Yii;

/**
 * This is the model class for table "board_user".
 *
 * @property string $idBoard
 * @property string $idMember
 */
class BoardMember extends \yii\com\db\ModelActiveRecord
{
	const KEYCACHEALL = 'boardmemberidmember';
	const KEYCACHEONE = 'boardmemberidboard';
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'board_member';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['idMember'], 'required'],
            [['idMember', 'idBoard'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'idBoard' => Yii::t('app', 'Id Board'),
            'idMember' => Yii::t('app', 'Id Member'),
        ];
    }
	
/********************* Memory Cache *************************/
	
}
