<?php
/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
namespace yii\com\db;

use Yii;

class ModelActiveRecord extends \yii\db\ActiveRecord
{

    public function fields()
    {
        $fields = parent::fields();
        $formName = $this->formName();
        $len = strlen($formName);
        $formName = substr($formName, $len - 1) === 's' ? $formName : $formName . 's';
        if (($expandFields = request()->get(strtolower($formName) . '_fields'))) {
            $fields = array_intersect((array) $fields, $expandFields);
        }
        if (($expandModel = request()->get(strtolower($formName) . '_expand'))) {
            foreach ((array) $expandModel as $em) {
                if ($this->__isset($em)) {
                    $fields[$em] = function($model) use($em) {
                        return $model->$em;
                    };
                }
            }
        }

        return $fields;
    }
}