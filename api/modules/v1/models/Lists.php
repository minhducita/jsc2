<?php

namespace api\modules\v1\models;

use Yii;
use api\common\models\Memcache;

/**
 * This is the model class for table "lists".
 *
 * @property string $id
 * @property string $name
 * @property integer $pos
 * @property integer $closed
 * @property integer $idBoard
 */
class Lists extends \yii\com\db\ModelActiveRecord
{
    const LISTS_ACTIVE = 0;
    const LISTS_CLOSED = 1;
	
	const KEYCACHEALL  = "listidmember";
	const KEYCACHEONE  = "listid";
	
	public static $prefsDefault = [
        'backgroundColor' => '#e2e4e6',
		'color' => '#333',
    ];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lists';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pos', 'closed', 'idBoard', 'sort'], 'integer'],
            [['idBoard', 'name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['closed'],'safe'],
			
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'pos' => Yii::t('app', 'Pos'),
            'closed' => Yii::t('app', 'Closed'),
            'idBoard' => Yii::t('app', 'Id Board'),
			'prefs' => Yii::t('app', 'Prefs'),
			'sort' => Yii::t('app', 'Sort'),
        ];
    }
    public function fields()
    {
        $fields = parent::fields();
        $fields['id'] = function($model) {
            return intval($model->id);
        };
		$fields['prefs'] = function ($model) {
			return json_decode($model->prefs);
		};
        return $fields;
    }

    public function extraFields()
    {
        return ['cards'];
    }

    public function getCards()
    {
        return $this->hasMany(Card::className(), ['idList' => 'id'])->orderBy(['card.pos' => 'ASC']);
    }
	
	/********************* Memory Cache *************************/
	public function findCacheAll ($data, $relation = 0) 
	{
		$memcache = new Memcache();
		$memcache->data = $data;
		return $memcache->findCacheAll($relation);
	}
	
	public function findCacheOne($id = 0, $data="", $relation = 0) {
		$memcache = new Memcache();
		$memcache->data = (!empty($data)) ? $data : Lists::find()->where(['id' => $id]);
		$memcache->id = $id;
		$memcache->key = "list";
		return $memcache->findCacheOne($relation);
	}
	
	public static function deleteCache ($id=0, $idBoard=0) {
		$keyfind = "`idBoard`='$0'";
		$memcache = new Memcache();
		$idMember[] = userIdentity()->getId();
		
		$boardmember = BoardMember::find()->where(['idBoard'=> $idBoard])->select('idMember')->all();
		if(!empty($boardmember)) {
			foreach($boardmember as $item) 
			{
				$idMember[] = $item->idMember;
				$memcache->setFindKey($item->idMember);
			}
		}	
		$memcache->id = $id;
		
		/* delete memory cache */
		// $memcache->key = "list";
		// $memcache->setFindKey($idBoard, $keyfind);
		// $memcache->setdelRedisCache("lists");
		// $memcache->deleteCache();
		
		/* delete cache page */
		$memcache->deleteCachePage($idMember, 'board');
	}
}
