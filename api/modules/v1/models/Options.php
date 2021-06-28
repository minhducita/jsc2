<?php

namespace api\modules\v1\models;

use Yii;

/**
 * This is the model class for table "options".
 *
 * @property integer $idMixed
 * @property string $model
 * @property string $key
 * @property string $value
 */
class Options extends \yii\com\db\ModelActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'options';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['idMixed', 'model'], 'required'],
            [['idMixed'], 'integer'],
            [['value'], 'string'],
            [['model', 'key'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'idMixed' => Yii::t('app', 'Id Mixed'),
            'model' => Yii::t('app', 'Model'),
            'key' => Yii::t('app', 'Key'),
            'value' => Yii::t('app', 'Value'),
        ];
    }
    /*
     * @params array $options set key, value
     * @params string $modelName
     */
    public static function setOptions(array $options, $idModel, $modelName)
    {
        $newOptions = [];
        foreach ($options as $key => $value) {
            if (is_numeric($key)) {
                unset($options['key']);
            }
            $newOptions[] = [
                $idModel,
                $modelName,
                $key,
                $value,
            ];
        }
        return db()->createCommand()->batchInsert(self::tableName(), ['idMixed', 'model', 'key', 'value'], $newOptions)->execute();
    }

    /*
     * @params array $options set key, value
     * @params string $modelName
     */
    public static function updateOptions(array $options, $idModel, $modelName)
    {
        $updateOptions = 'UPDATE `' . self::tableName() .'` SET `value` = CASE `key`';
        foreach ($options as $key => $value) {
            if (is_numeric($key)) {
                unset($options['key']);
            }
            $updateOptions .= sprintf(" WHEN '%s' THEN '%s'", $key, $value);
        }
        $updateOptions .= " END WHERE `idMixed`={$idModel} AND `model`='{$modelName}'";

        return db()->createCommand($updateOptions)->execute();
    }

}
