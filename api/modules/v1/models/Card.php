<?php

namespace api\modules\v1\models;

use Yii;
use api\common\models\Member;
use api\common\models\Memcache;

use api\modules\v1\models\Members;
use api\modules\v1\models\Notifications;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * This is the model class for table "card".
 *
 * @property string $id
 * @property string $displayName
 * @property string $name
 * @property string $desc
 * @property integer $due
 * @property string $badges
 * @property integer $idAttachmentCover
 * @property integer $pos
 * @property integer $closed
 * @property integer $dateLastActivity
 * @property string $idLabels
 * @property integer $idMember
 * @property integer $idList
 * @property integer $idBoard
 */
class Card extends \yii\com\db\ModelActiveRecord
{
    const CARD_ACTIVE = 0;
    const CARD_CLOSED = 1;
	
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'card';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['desc', 'badges', 'parentId'], 'string'],
            [['due', 'startDate', 'idAttachmentCover', 'pos', 'closed', 'dateLastActivity', 'dateLastActivity', 'lastUpdated', 'idMember', 'idList', 'idBoard', 'important', 'urgent'], 'integer'],
            [['idList', 'idBoard', 'displayName', 'name'], 'required'],
            [['idLabels'], 'string', 'max' => 255],
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
            'due' => Yii::t('app', 'Due'),
			'startDate' => Yii::t('app', 'start Date'),
            'badges' => Yii::t('app', 'Badges'),
            'idAttachmentCover' => Yii::t('app', 'Id Attachment Cover'),
            'pos' => Yii::t('app', 'Pos'),
            'closed' => Yii::t('app', 'Closed'),
            'dateLastActivity' => Yii::t('app', 'Date Last Activity'),
			'lastUpdated'=> Yii::t('app', 'Date Last updateted'),
            'idLabels' => Yii::t('app', 'Id Labels'),
            'idMember' => Yii::t('app', 'Id Member'),
            'idList' => Yii::t('app', 'Id List'),
            'idBoard' => Yii::t('app', 'Id Board'),
			'parentId' => Yii::t('app','Parent Id'),
			'important' => Yii::t('app','Important'),
			'urgent' => Yii::t('app','Urgent')
        ];
    }
	
    public function fields()
    {
        $fields = parent::fields();
        $fields['id'] = function($model) {
            return intval($model->id);
        };
        $fields['idBoard'] = function($model) {
            return intval($model->idBoard);
        };
        $fields['idMember'] = function($model) {
            return intval($model->idMember);
        };
        $fields['idList'] = function($model) {
            return intval($model->idList);
        };
        $fields['idAttachmentCover'] = function($model) {
            return intval($model->idAttachmentCover);
        };
        $fields['badges'] = function($model) {
            return Json::decode($model->badges);
        };
        $fields['dateLastActivity'] = function($model) {
            return date('m/d/Y H:i:s', $model->dateLastActivity);
        };
        $fields['idLabels'] = function($model) {
            return Json::decode($model->idLabels);
        };
		$fields['lastUpdated'] = function($model) {
			if(!empty($model->lastUpdated) && $model->lastUpdated != null) {
				return date('m/d/Y H:i', $model->lastUpdated);
			}
			return $model->lastUpdated;
		};
		$fields['parentId'] = function($model) {
			if(!empty($model->parentId)) {
				 return Json::decode($model->parentId);
			}
			return array();
        };
		$fields['desc'] = function($model) {
			if(!empty($model->desc))
				 return preg_replace(
              "~[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]~",
              "<a target=\" _blank \" href=\"\\0\">\\0</a>", 
              $model->desc);
		};
		$fields['important'] = function($model) {
            return intval($model->important);
        };
		$fields['urgent'] = function($model) {
            return intval($model->urgent);
        };
        return $fields;
    }
	
    public function extraFields()
    {
        return ['attachments', 'checklists', 'members', 'memberShips' ,'comments', 'lists', 'notifications'];
    }

    public function getAttachments()
    {
        $attachments = $this->hasMany(Attachments::className(),['idCard' => 'id']);
		return Attachments::findCacheAll($attachments, 1);
    }

    public function getChecklists()
    {
		$checklist = $this->hasMany(Checklist::className(), ['idCard' => 'id']);
        return Checklist::findCacheAll($checklist , 1);
    }

    public function getComments()
    {
		$comments = $this->hasMany(Comments::className(), ['idCard' => 'id'])->orderBy(['addedDate' => SORT_DESC]);
        return Comments::findCacheAll($comments, 1);
    }
	
    public function getMembers()
    {
        $member =  Member::find()->where([
            'id' => CardMembers::find()->select(['idMember'])->where(['idCard' => $this->id])
        ]);
		return Member::findCacheAll($member);
    }
    
    public function getLists()
    {
        return $this->hasOne(Lists::className(), ['id' => 'idList']);
    }

    public function badges()
    {
        $badges =  Options::find()->select(['key', 'value'])->where([
            'idMixed' => $this->id,
            'model'   => $this->formName()
        ])->all();
        return ArrayHelper::map($badges, 'key', 'value');
    }
    
    public function getNotifications() 
	{
		return Notifications::find()
				->andFilterWhere(['or',['=','name','card'],['=','name','checklist'],['=','name','checklistitem']])
				->andFilterWhere(['or',['LIKE','data','"idCard":"'.$this->id.'"'],['LIKE','data','"idCard":'.$this->id]])
				->orderBy('id DESC')
				->all();
	}
	
	public function getMemberShips() 
	{
		$memberShips = $this->hasMany(Members::className(),['id' => 'idMember'])->select('username, displayName, initialsName, id, avatarHash, typeimg') -> viaTable('card_members',['idCard'=>'id']);
		return Members::findCacheAll($memberShips, 1);
	}
	
	/********************* Memory Cache *************************/
	public function findCacheAll ($data, $relation = 0) 
	{
		$memcache =  new Memcache;
		$memcache->data = $data;
		return $memcache->findCacheAll($relation);
	}
	
	public function findCacheOne($id = 0, $data="", $relation= 0) {
		$memcache = new Memcache;
		$memcache->key = 'card';
		$memcache->data = !empty($data) ? $data : Card::find()->where(['id' => $id]);
		$memcache->id = $id;
		return $memcache->findCacheOne($relation);
	}
	
	public static function deleteCache ($id=0, $idBoard=0) {
		$memcache = new Memcache;
		
        // $idMember[] = 15;
        
        $idMember[] = userIdentity()->getId();
        Yii::error("chaobananhmodel");
        Yii::warning(userIdentity()->getId());
		$memcache->key = 'card';
		$memcache->id = $id;
		
		$boardmember = BoardMember::find()->where(['idBoard'=> $idBoard])->select('idMember')->all();
		if(!empty($boardmember)) {
			foreach($boardmember as $item) 
			{
				$idMember[] = $item->idMember;
				$memcache->setFindKey($item->idMember);
			}
		}
		
		
		/* delete cache memory */
		// $keyfind = "`idBoard`='$0'";
		// $memcache->setFindKey($idBoard, $keyfind);
		// $keyfind = "`idCard`='$0'";
		// $memcache->setFindKey($id, $keyfind);
		// $memcache->setdelRedisCache(array('card','card_members'));
		// $memcache->deleteCache();
		
		/* delete cache page */
		$memcache->deleteCachePage($idMember, "card");
		$memcache->deleteCachePage($idMember, "board");	
		$memcache->deleteCachePage($idMember, "search");
		$memcache->deleteCachePage($idMember, "notifications");	
    }

    /**
     * Auth: Minhanh
     * Email: minhanh.itqn@gmail.com
     * Description: delete cache when create card from slack
     */
    public static function deleteCacheslack($id = 0, $idBoard = 0, $idmember = 15) {
        $memcache = new Memcache;
        $idMember[] = $idmember;
        $memcache->key = 'card';
        $memcache->id = $id;
        $boardmember = BoardMember::find()->where(['idBoard' => $idBoard])->select('idMember')->all();
        if (!empty($boardmember)) {
            foreach ($boardmember as $item) {
                $idMember[] = $item->idMember;
                $memcache->setFindKey($item->idMember);
            }
        }
        /* delete cache page */
        $memcache->deleteCachePage($idMember , "card");
        $memcache->deleteCachePage($idMember , "board");
        $memcache->deleteCachePage($idMember , "search");
        $memcache->deleteCachePage($idMember , "notifications");
    }
}
