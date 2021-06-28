<?php
/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
namespace api\modules\v1\controllers;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\caching\Cache;
use yii\helpers\Url;

use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use common\helpers\StringHelper;

use yii\web\ServerErrorHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;


use api\common\controllers\RestApiController;
use api\common\models\User;
use api\common\models\Member;


use api\modules\v1\models\Chanel;
use api\modules\v1\models\ChanelUser;
use api\modules\v1\models\PasswordForm;

/*
 ** library upload image 
*/
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use yii\web\UploadedFile;
use api\common\models\UploadForm;

class MeController extends RestApiController
{
	private $keyCache='members';
	
    public function actionIndex()
	{
		return userIdentity();
    }
	
	public function actionChangePassword(){
		$model = new PasswordForm();
		if($model->load(Yii::$app->getRequest()->getBodyParams(),'') && $model->validate()){
			if($model->changePassword())
				return array('success'=>true, 'message'=>'Password was successfully changed');
			else{
				throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
			}
		}
		return $model;
	}
	
	public function actionUpdateProfile(){
		$member = Member::find()->where(['authKey'=>$_GET['access-token']])->one();
		if($member->load(Yii::$app->getRequest()->getBodyParams(),'') && $member->validate()){
			if($member->save()){
				return $member;
			} else {
				throw new ServerErrorHttpException('Failed to update profile the object for unknown reason.');
			}
		}
		return $member;
	}
	
	public function actionChangeAvatar() {
		$member = Member::find()->where(['authKey'=>$_GET['access-token']])->one();
		$uploadForm = new UploadForm();
        $uploadForm->file = UploadedFile::getInstanceByName('file');
		$nameImg = 170;
		$nameThumb = 50;
        if ($uploadForm->upload("profiles/".md5('member_' . $member->id),$nameImg)) {
			
            $path = $uploadForm->uploadFolder . '/profiles/' . md5('member_' . $member->id) . '/'.$nameImg. '.' . $uploadForm->file->extension;			
			$imagine = new Imagine();
            try {
                $path50x50 = str_replace($nameImg.'.', $nameThumb.'.', $path);
                $imagine->open(Yii::getAlias($path))
                    ->resize(new Box(50, 50))
                    ->save(Yii::getAlias($path50x50), ['quanlity' => 70]);
					if($uploadForm->file->extension != 'jpg')
						$member->avatarHash = md5('member_' . $member->id)."_".$uploadForm->file->extension;
					else
						$member->avatarHash = md5('member_' . $member->id);
					$member->typeimg = 1;
					$member->save();
			
				return $member;
            } catch (\Exception $e) {
                //$transaction->rollBack();
                throw new ServerErrorHttpException('Failed to upload file to server.');
            }
        } else {
            throw new ServerErrorHttpException('Failed to upload file to server.');
        }
	}
	
}