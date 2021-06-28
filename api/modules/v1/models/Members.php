<?php 
namespace api\modules\v1\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use api\common\models\Memcache;

class Members extends ActiveRecord {
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'member';
    }
    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['username', 'email', 'password'], 'required'],
            ['email', 'email'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields() {
        return ['username','displayName','initialsName','id','avatarHash','typeimg'];
    }
	
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'email' => 'Email',
            'addDate' => 'Create Time',
            'updateDate' => 'Update Time',
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