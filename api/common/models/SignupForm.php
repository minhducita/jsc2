<?php
namespace api\common\models;

use api\common\models\Member;
use yii\base\Model;
use Yii;

/**
 * Signup form
 */
class SignupForm extends Model
{
    public $username;
    public $email;
    public $password;
	public $displayName;
	public $initialName;
	public $avatarHash;
	public $passwordRepeat;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
			['displayName','required'],
			['displayName', 'unique', 'targetClass' => '\api\common\models\Member', 'message' => 'This displayName has already been taken.'],

            ['username', 'filter', 'filter' => 'trim'],
            ['username', 'required'],
            ['username', 'unique', 'targetClass' => '\api\common\models\Member', 'message' => 'This username has already been taken.'],
            ['username', 'string', 'min' => 2, 'max' => 255],

            ['email', 'filter', 'filter' => 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => '\api\common\models\Member', 'message' => 'This email address has already been taken.'],

            ['password', 'required'],
            ['password', 'string', 'min' => 6],
			
			['passwordRepeat','required'],
			['passwordRepeat','compare','compareAttribute'=>'password','message'=>"Passwords don't match"]
        ];
    }

    /**
     * Signs user up.
     *
     * @return Member|null the saved model or null if saving fails
     */
    public function signup() {
        if (!$this->validate()) {
			return false;
		}
		$user = new Member();
		$user->username = $this->username;
		$user->email = $this->email;
		$user->displayName = $this->displayName;
		$user->initialsName = $this->getCharaterFirst($this->displayName);
		$user->setPassword($this->password);
		$user->generateAuthKey();
		$user->generatePasswordResetToken();
		if ($user->save()) {
            return $user;
		}
    }
	public function Checkemail(){
		if (!$this->validate()) {
			return false;
		}
		return true;
	}
	private function getCharaterFirst($char){
		$char = explode(" ",$char);
		$char_start = "";
		$maxLength = 3;
		foreach($char as $key => $val){
			$substr = strtoupper(substr($val,0,1));
			if(!is_numeric($substr) && $key < $maxLength){
				$char_start .= $substr;
			}
		}
		return $char_start;
	}
}
