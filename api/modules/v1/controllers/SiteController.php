<?php
/**
 * Author: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
namespace api\modules\v1\controllers;

use api\common\models\ContactForm;
use api\common\models\Member;
use yii\filters\ContentNegotiator;
use yii\rest\Controller;
use Yii;

class SiteController extends Controller
{
    private static $_app_models = [
        0 => '\api\common\models\User',
        1 => '\api\common\models\Admin',
    ];
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator'] = [
            'class' => ContentNegotiator::className(),
            'formats' => [
                'application/json' => \yii\web\Response::FORMAT_JSON,
            ],
        ];
        return $behaviors;
    }
    /**
     * @param int $app 0 (frontend) | 1 (backend)
     */
    public function actionLogin($app = 0)
    {
        if (!isset(self::$_app_models[$app])) {
            throw new \yii\web\HttpException(404);
        }
        $model = new \api\common\models\LoginForm();
        $model->app = $app;
		
        if ($model->load(Yii::$app->getRequest()->getBodyParams()) && $model->login()) {
            return ['token' => userIdentity()->getAuthKey()];
        } else {
            $model->validate();
            return $model;
        }
    }
	public function actionRegister($app = 0){
		if (!isset(self::$_app_models[$app])) {
            throw new \yii\web\HttpException(404);
        }
		$model = new \api\common\models\SignupForm();
		if($model->load(Yii::$app->getRequest()->getBodyParams(),'SignupForm')){ // load successs
			$result = $model->signup();
			if($result){ // signup successs
				return ['token' => $result->authKey];
			}
		}
		$model->validate();
        return $model;
	}
	private function randomPassword($len){
		// random password
		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $len; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
	private function resetPassword($token)
	{
		$member = new Member();
		$infoMember = $member->findOne(['passwordResetToken'=>$token]);
		if(!empty($infoMember)){
			$password  = $this->randomPassword(6);
			$infoMember->setPassword($password);
			$infoMember->generatePasswordResetToken();
			$infoMember->save();
			$this->sendmailForgot($infoMember['email'],'passwordReset-html',$infoMember,$password);
			return true;
		}
		return false;
	}
	public function actionForgot($app = 0)
	{
		if (!isset(self::$_app_models[$app])) {
            throw new \yii\web\HttpException(404);
        }
		$postdata = Yii::$app->getRequest()->getBodyParams();
		if(!empty($postdata['token'])){ // reset password
			return $this->resetPassword($postdata['token']);
		}else{ // send mail token
			$model = new \api\common\models\SignupForm();
			$box = array();
			if($model->load(Yii::$app->getRequest()->getBodyParams(),'ForgotForm') && !$model->checkemail()){
				$getErrors = $model->getErrors();
				if(!empty($getErrors['email'][0]) && stripos($getErrors["email"][0],'has already been taken') >  0){
					//send mail active code
					$postdata = $postdata['ForgotForm'];
					$member = new Member();
					$infoMember =  $member->findOne(["email"=>$postdata['email']]);
					$this->sendmailForgot($postdata['email'],'passwordResetToken-html',$infoMember);
					$box 		=  array("success"=>1,"message"=>"Please check your email to receive your password");
				}else if(!empty($getErrors['email'])){
					$box = array("feild"=>"email","message"=>$getErrors['email'][0]);
				}else
					$box = array("feild"=>"email","message"=>"Email is not registered");
			} 
			else{
				$box = array("feild"=>"email","message"=>"Email is not a valid email address.");
			}
			return $box;
		}
	}
	private function sendmailForgot($email,$template,$member,$password=""){
		$path_temp_mail  =  '@common/mail/'.$template;
		$body 		 	 = 	$this->renderPartial($path_temp_mail,['user' => $member,'password'=>$password]);
		$To 			 = 	$email;
		$subject 		 =  'Password reset for' . \Yii::$app->name;
		$header 	= 		"From: ".Yii::$app->params['supportName']." <".Yii::$app->params['supportEmail'].">\r\n"; 
		$header.= 		"MIME-Version: 1.0\r\n"; 
		$header.= 		"Content-Type: text/html; charset=ISO-8859-1\r\n"; 
		$header.= 		"X-Priority: 1\r\n"; 		
		mail($To,$subject,$body,$header);
		/*return Yii::$app->mailer->compose($template, ['user' => $member,'password'=>$password])
			-> setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->params['supportName'] . ' noreply'])
			-> setTo($email)
			-> setSubject('Password reset for' . \Yii::$app->name)
			-> send();
	
	
		$to = "lynam1990@gmail.com";
		$subject = "";
		$txt = "Hello world!";
		$headers = "From: lyphuocnam1990@gmail.com" . "\r\n" .
		"CC: lyphuocnam1990@gmail.com";

		mail($to,$subject,$txt,$headers);*/
		/*return Yii::$app->mailer->compose()
						->setFrom(['hungphamgoro@gmail.com' => 'Jooto'])
						->setTo('lynam1990@gmail.com')
						->setSubject('Message subject')
						->setTextBody('Plain text content')
						->setHtmlBody('<b>HTML content</b>')
						->send();*/
	}
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return 1;
    }
    public function actionOptions()
    {
        \Yii::$app->getResponse()->setStatusCode(200);
    }
}