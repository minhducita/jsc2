<?php 
namespace api\modules\v1\models;
    
use Yii;
use yii\base\Model;
use api\common\models\Member;
    
class PasswordForm extends \yii\db\ActiveRecord {
	public $oldpass;
	public $newpass;
	public $repeatnewpass;
        
	public function rules(){
		return [
			[['oldpass','newpass','repeatnewpass'],'required'],
			['oldpass','findPasswords'],
			['repeatnewpass','compare','compareAttribute'=>'newpass'],
		];
	}
        
	public function findPasswords($attribute, $params) {
		$member = Member::find()->where([
			'username'=>Yii::$app->user->identity->username
		])->one();
		if(!$member->validatePassword($this->oldpass))
			$this->addError($attribute,'Old password is incorrect');
	}
        
	public function attributeLabels(){
		return [
			'oldpass'=>'Old Password',
			'newpass'=>'New Password',
			'repeatnewpass'=>'Repeat New Password',
		];
	}
	public function changePassword(){
		$member = Member::findOne(userIdentity()->getId());
		$member->setPassword($this->newpass);
		if($member->save()){
			return true;
		}	
		return false;
	}
}
?>