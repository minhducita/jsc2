<?php

namespace api\modules\v1\models;

use api\common\models\Member;
use api\common\models\Memcache;
use Yii;

/**
 * This is the model class for table "chanel".
 *
 * @property string $id
 * @property string $displayName
 * @property string $name
 * @property string $desc
 * @property string $sourceHash
 * @property string $shortUrl
 * @property string $url
 * @property string $website
 * @property integer $permission
 * @property integer $idMember
 */
class Organization extends \yii\com\db\ModelActiveRecord
{
    const ORGANIZATION_PRIVATE = 0;
    const ORGANIZATION_PUBLIC = 1;
	
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'organization';
    }

    /**
     * @inheritdoc
    */
    public function rules()
    {
        return [
            [['displayName', 'idMember','name'], 'required'],
            [['desc'], 'string'],
            [['permission', 'idMember'], 'integer'],
            [['displayName', 'name'], 'string', 'max' => 150],
            [['sourceHash'], 'string', 'max' => 32],
            [['website'], 'string', 'max' => 100],
            [['displayName'], 'filter', 'filter' => 'trim'],
			['displayName', 'match', 'pattern' => '/^.*[a-zA-Z0-9一-龠ぁ-ゔァ-ヴー々〆〤].*$/i', 'message' => Yii::t('app', 'Team Name must have at least 1 letter')],
            [['displayName'], 'unique'],
			['name', 'unique', 'targetClass' => '\api\modules\v1\models\Organization', 'message' => 'This Short Name has already been taken.'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' 			=> Yii::t('app', 'ID'),
            'displayName' 	=> Yii::t('app', 'Team Name'),
            'name' 			=> Yii::t('app', 'Name'),
            'desc' 			=> Yii::t('app', 'Desc'),
            'sourceHash' 	=> Yii::t('app', 'Source Hash'),
            'website' 		=> Yii::t('app', 'Website'),
            'permission' 	=> Yii::t('app', 'Permission'),
            'idMember' 		=> Yii::t('app', 'Id Member'),
        ];
    }

    /**
     * @inheritdoc
     * @return ChQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ChQuery(get_called_class());
    }

    public function fields()
    {
        $fields = parent::fields();
        $fields['id'] = function($model) {
            return intval($model->id);
        };
        $fields['idMember'] = function($model) {
            return intval($model->idMember);
        };
		$fields['logo'] = function ($model) {
			if(!empty($model->logo)) {
				$model->logo = $model->logo."?".time();
			}
			if(!file_exists(Yii::getAlias("@frontend/web/assets/img",$model->logo))) {
				$model->logo = "";
			}
			return $model->logo;
		};
		$fields['logo_preview'] = function ($model) {
			$jsons = json_decode($model->logo_preview);
			if(!empty($jsons)) {
				foreach ($jsons as $key =>$json) {
					$jsons[$key]->url = $json->url."?".time();
					if(!file_exists(Yii::getAlias("@frontend/web/assets/img",$jsons[$key]->url))) {
						$jsons[$key]->url = "";
					}
				}
			}
			return $jsons;
		};
        return $fields;
    }
    public function getBoards()
    {
        $condition = [];
        if (($closed = request()->get('board_closed')) !== null) {
            $condition['closed'] = (int) $closed;
        }
        $board = $this->hasMany(Board::className(), ['idOrganization' => 'id'])->where($condition);
		return Board::findCacheAll($board);
    }
	
    public function extraFields()
    {
        return ['boards', 'members', 'memberShips', 'memberTypeShip'];
    }

    public function getMembers()
    {
        $member = Member::find()->where([
            'id' => OrganizationMember::find()->select(['idMember'])->where(['idOrganization' => $this->id])
        ]);
		return Member::findCacheAll($member);
    }

    public function getMemberShips()
    {
        $organizations = OrganizationMemberships::find()->select([
            'id',
            'idMember',
            'memberType'
        ])->where([
            'idOrganization' => $this->id
        ]);
		return OrganizationMemberships::findCacheAll($organizations);
    }
	
	public function getMemberTypeShip() {
		return $this->hasOne(OrganizationMemberships::className(),['idOrganization'=>'id']);
	}
	
    public function isMemberInOrganization($idMember, $idChanel)
    {
        return OrganizationMember::find()->where([
           'idMember' => (int) $idMember,
           'idOrganization' => (int) $idChanel,
        ])->exists();
    }
	
	
	/********************* Memory Cache *************************/

	public function findCacheAll ($data) 
	{
		$memcache = new Memcache();
		$memcache->data = $data;
		return $memcache->findCacheAll();
	}
	public function findCacheOne($id = 0, $data="") {
		//config get cache
		$memcache = new Memcache;
		$memcache->id = $id;
		if(!empty($data)) {
			$memcache->data = $data;
		} else {
			$memcache->data = Organization::find()->where(['id' => $id]);
		}		
		$memcache->key = 'organization';
		return $memcache->findCacheOne();
	}

	public static function deleteCache ($id=0) {
		$idMember = array(userIdentity()->getId());

		$memcache = new Memcache;	
		
		$organizationMember = OrganizationMember::find()->where(['idOrganization' => $id])->select('idMember')->all();
		if(!empty($organizationMember)) {
			foreach($organizationMember as $orMember) {
				$idMember[] = $orMember->idMember;
				$memcache->setFindKey($orMember->idMember);
			}
		}
		
		/* delete memory cache */
		// $keyfind = "`idOrganization`='$0'"; 
		// $memcache->setFindKey(userIdentity()->getId());
		// $memcache->setFindKey($id, $keyfind);
		// $memcache->id = $id;
		// $memcache->key = "organization";
		// $memcache->setdelRedisCache("organization", "organization_member", "organization_memberships");
		// $memcache->deleteCache();
		
		/*delete memcache page */
		
		$memcache->deleteCachePage($idMember, "me");
		$memcache->deleteCachePage($idMember, "organization");
		$memcache->deleteCachePage($idMember, "search");		
	}
}
