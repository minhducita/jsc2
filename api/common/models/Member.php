<?php
namespace api\common\models;

use api\modules\v1\models\Board;
use api\modules\v1\models\BoardMember;
use api\modules\v1\models\BoardStar;
use api\modules\v1\models\Organization;
use api\modules\v1\models\OrganizationMember;
use api\modules\v1\models\Notifications;
use api\modules\v1\models\NotificationsMember;
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\web\IdentityInterface;

use yii\data\ActiveDataProvider;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $passwordHash
 * @property string $passwordResetToken
 * @property string $email
 * @property string $authKey
 * @property integer $status
 * @property integer $typeimg
 * @property integer $addedDate
 * @property integer $updatedDate
 * @property string $displayName
 * @property string $initialsName
 * @property string $password write-only password
 */
class Member extends \yii\com\db\ModelActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 1;
    const NORMAL_ROLE = 0;
    const ADMIN_ROLE = 1;
		
	const KEYCACHEALL = "memberidmember";
	const KEYCACHEONE = "memberid";
	
	/**
     * @inheritdoc
    */
    public static function tableName()
    {
        return '{{%member}}';
    }
	
    /**
     * @inheritdoc
     
    public function behaviors() 
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ModelActiveRecord::EVENT_BEFORE_INSERT => ['addedDate', 'updatedDate'],
                ],
                'value' => new Expression('NOW()'),
            ],
        ]; 
    }
	*/
    /**
     * @inheritdoc
     */
	
    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],
			['typeimg', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],
			[['displayName','initialsName'],'required'],
			['initialsName','string','length'=>[0,3]]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['authKey' => $token]);
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'passwordResetToken' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return boolean
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }
        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->passwordHash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->passwordHash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->authKey = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->passwordResetToken = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->passwordResetToken = null;
    }
	
    public function fields()
    {
        $fields = parent::fields();
        $fields['id'] = function($model) {
          return intval($model->id);
        };
        unset($fields['passwordHash'], $fields['passwordResetToken'], $fields['authKey'], $fields['status'], $fields['addedDate'], $fields['updatedDate']);
		return $fields;
    }

    public function extraFields()
    {
        return ['organizations', 'boardStars', 'boards', 'boardCloses', 'notifications'];
    }

    public function getOrganizations()
    {
		$organizations = Organization::find()->where([
			'id' => OrganizationMember::find()->select(['idOrganization'])->where(['idMember' => $this->id])
		]);
		return Organization::findCacheAll($organizations);	
    }

    public function getBoardStars()
    {
		$boardStart =  $this->hasMany(BoardStar::className(), ['idMember' => 'id'])->select(['id', 'idBoard', 'pos'])->where(['isShow' => 1]);		
		return Board::findCacheAll($boardStart);
	}
	
    public function getBoards()
    {	
        $condition = ['idMember' => $this->id];
        if (($closed = request()->get('board_closed')) !== null) {
            $condition['closed'] = (int) $closed;
        }
		
		$board = Board::find()->where([
			'id' => BoardMember::find()->select(['idBoard'])->where($condition)
		]);
		
		return Board::findCacheAll($board);
    }
	
	public function getBoardCloses() 
	{
		$board = Board::find()->where([
					'id'=> BoardMember::find()->select(['idBoard'])->where(['idMember' => $this->id, 'closed'=>1]),
					'idMember'=> $this->id
		]);
		return Board::findCacheAll($board);
	}
	
    public function getNotifications()
    {	
		$idMember = userIdentity()->getId();
		$notifications =  Notifications::find()->andwhere([
            'id' => NotificationsMember::find()->select(['idNotification'])->where(['idMember'=>$idMember])
		])->andwhere("type <> :type",[':type' => 0])->orderBy('id DESC')->limit(20);
		return Notifications::findCacheAll($notifications);
    }
	
	/********************* Memory Cache *************************/
	public function findCacheAll ($data) 
	{
		$memcache = new Memcache;
		$memcache->data = $data;
		return $memcache->findCacheAll();
	}
	
	public function findCacheOne($id = 0, $data="") {
		$memcache = new Memcache;
		$memcache->data = !empty($data) ? $data : Member::find()->where(['id' => $id]);
		$memcache->id = $id;
		$memcache->key = "member";
		return $memcache->findCacheOne();
	}
	
	public static function deleteCache ($id=0) {
		/* delete Cache File */
		$memcache = new Memcache;
		$memcache->setFindKey($id);
		$memcache->id = $id;
		$memcache->key = "member";
		$memcache->setdelRedisCache("member");
		$memcache->deleteCache();
	}
}
