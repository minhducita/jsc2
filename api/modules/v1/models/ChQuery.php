<?php

namespace api\modules\v1\models;

/**
 * This is the ActiveQuery class for [[Chanel]].
 *
 * @see Chanel
 */
class ChQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }*/

    /**
     * @inheritdoc
     * @return Organization[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Organization|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}