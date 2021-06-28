<?php

namespace api\modules\v1\models;

use api\common\models\Member;
use api\common\models\Memcache;

use api\modules\v1\models\Notifications;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\Link;
use yii\web\Linkable;
use yii\helpers\Url;
use common\helpers\StringHelper;

/**
 * This is the model class for table "board".
 *
 * @property string $id
 * @property string $displayName
 * @property string $name
 * @property string $desc
 * @property string $prefs
 * @property integer $subcribed
 * @property integer $closed
 * @property integer $dateLastActivity
 * @property integer $idChanel
 * @property integer $idMember
 */
class Board extends \yii\com\db\ModelActiveRecord implements Linkable
{
    const BOARD_ACTIVE = 0;
    const BOARD_CLOSED = 1;
	
    public static $boardPrefsDefault = [
		'backgroundType' => 'color',
        'background' => 'blue',
        'backgroundBrightness' => 'dark',
        'backgroundColor' => '#0079BF',
        'backgroundImage' => null,
        'backgroundImageScaled' => null,
        'backgroundTile' => false,
        'canBePrivate' => true,
        'canBePublic' => true,
        'comments' => 'members',
        'selfJoin' => true,
        'voting' => 'members',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'board';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['displayName', 'idMember'], 'required'],
            [['desc', 'labelNames'], 'string'],
            [['subcribed', 'closed', 'dateLastActivity', 'idOrganization', 'idMember'], 'integer'],
            [['displayName', 'name', 'slackChanel'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'displayName' => Yii::t('app', 'Display Name'),
            'name' => Yii::t('app', 'Name'),
            'desc' => Yii::t('app', 'Desc'),
            'prefs' => Yii::t('app', 'Prefs'),
            'subcribed' => Yii::t('app', 'Subcribed'),
            'closed' => Yii::t('app', 'Closed'),
            'dateLastActivity' => Yii::t('app', 'Added Date'),
            'idOrganization' => Yii::t('app', 'Id Organization'),
            'idMember' => Yii::t('app', 'Id Member'),
			'slackChanel' => Yii::t('app', "Slack Chanel")
        ];
    }
	
    /**
     * @inheritdoc
     * @return ChQuery the active query used by this AR class.
     
    public static function find()
    {
        return new ChQuery(get_called_class());
    }*/
	
    public function fields()
    {
        $fields = parent::fields();
        $fields['id'] = function($model) {
            return intval($model->id);
        };
		
		$fields['idMember'] = function($model) {
            return intval($model->idMember);
        };
		
		$fields['name'] = function($model) {
			return StringHelper::StrRewrite($model->name);
		};

        $fields['prefs'] = function($model) {
            return Json::decode($model->prefs);
        };
        $fields['labelNames'] = function($model) {
            return Json::decode($model->labelNames);
        };
        return $fields;
    }

    public function getLinks()
    {
		$this->name = StringHelper::StrRewrite($this->name);
        return [
            'url' => '/b/' . $this->id . '/' . $this->name,
        ];
    }

    public function extraFields()
    {
        return ['organizations', 'lists', 'listCount', 'members', 'cards', 'memberShips', 'labels' , 'boardStars','notifications'];
    }

    public function getLabels()
    {
        $label = $this->hasMany(BoardLabels::className(), ['idBoard' => 'id']);
		return BoardLabels::findCacheAll($label);
    }

    public function getMembers()
    {
        $member = Member::find()->where([
            'id' => BoardMember::find()->select(['idMember'])->where(['idBoard' => $this->id])
        ]);
		return Member::findCacheAll($member);
    }

    public function getMemberShips()
    {
        $boarMemberShips = BoardMemberships::find()->select([
            'id',
            'idMember',
            'memberType',
            'orgMemberType'
        ])->where([
            'idBoard' => $this->id
        ]);
		return BoardMemberships::findCacheAll($boarMemberShips);
    }

    public function getLists()
    {
        $condition = [];
        /* if (($lists_closed = request()->get('lists_closed')) !== null) {
			$condition['closed'] = (int) $lists_closed;
        }*/
        $list = $this->hasMany(Lists::className(), ['idBoard' => 'id'])->where($condition)->orderBy(['pos' => 'ASC']);
		return Lists::findCacheAll($list);
	}
	
	public function getListCount() {
		$condition = [];
        if (($lists_closed = request()->get('lists_closed')) !== null) {
           $condition['closed'] = (int) $lists_closed;
        }
       return $this->hasMany(Lists::className(), ['idBoard' => 'id'])->where($condition)->orderBy(['pos' => 'ASC'])->count('id');
	}
	
    public function getOrganizations()
    {
        return Organization::findCacheOne($this->idOrganization);
    }

    public function getCards()
    {
        $condition = [];
        if (($card_closed = request()->get('card_closed')) !== null) {
            $condition['closed'] = (int) $card_closed;
        }
        $card = $this->hasMany(Card::className(), ['idBoard' => 'id'])->orderBy(['card.pos' => 'ASC']);
		return Card::findCacheAll($card);
    }

    public function starsCount()
    {
        $starsCount = $this->hasOne(BoardStar::className(), ['idBoard' => 'id'])->where([
            'idMember' => userIdentity()->getId(),
            'isShow' => 1
        ])->count('id');
        return intval($starsCount);
    }
	
	public function getBoardStars()
    {
		$boardStart = BoardStar::find()->select(['id', 'idBoard', 'pos'])->where(['idMember' => userIdentity()->getId(),'isShow' => 1]);
		return Board::findCacheAll($boardStart);
    }

	public function getNotifications() {
		$notifications  = Notifications::find()
				->andFilterWhere(['or',['=','name','board'],['=','name','card'],['=','name','checklist'],['=','name','checklistitem']])
				->andFilterWhere(['or',['LIKE','data','"idBoard":"'.$this->id.'"'],['LIKE','data','"idBoard":'.$this->id]])
				->orderBy('id DESC')
				->limit($_GET['notify_limit'],0);
		return Notifications::findCacheAll($notifications);
	}
	
    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->dateLastActivity = time();
            return true;
        } else {
            return false;
        }
    }
	
	/********************* Memory Cache *************************/
	
	public function findCacheAll ($data, $relation = 0) 
	{
		$memcache = new Memcache;
		$memcache->data = $data;
		return $memcache->findCacheAll($relation);
	}

	public function findCacheOne($id = 0, $data="", $relation = 0) 
	{
		$memcache = new Memcache;
		$memcache->id = $id;
		$memcache->data = !empty($data) ? $data : Board::find()->where(['id'=> $id]);
		$memcache->key = "board";
		return $memcache->findCacheOne($relation);
	}

	public static function deleteCache ($id=0) 
	{
		$memcache = new Memcache;
		$memcache->id = $id;
		
		$idMember[] = userIdentity()->getId();
		$boardmember = BoardMember::find()->where(['idBoard'=> $id])->select('idMember')->all();
		if(!empty($boardmember)) {
			foreach($boardmember as $item) 
			{
				$idMember[] = $item->idMember;
				$memcache->setFindKey($item->idMember);
			}
		}
		
		/* delete rediscache */
		
		// $memcache->key = 'board';
		// $memcache->setFindKey(userIdentity()->getId());
		// $keyfind = "`idBoard`='$0'";
		// $memcache->setFindKey($id, $keyfind);
		// $memcache->setdelRedisCache('board','board_labels','board_member', 'board_memberships', 'board_star');
		// $memcache->deleteCache();
		
		/*delete memcache page */
		$idMember = array_unique($idMember);
		$memcache->deleteCachePage($idMember, "board");	
		$memcache->deleteCachePage($idMember, "me");
		$memcache->deleteCachePage($idMember, "search");	
	}
}
