<?php
/**
 * Auth: Lý Phước Nam
 * Email: lynam1990@gmail.com
 */
namespace api\modules\v1\controllers;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use yii\web\ServerErrorHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;

use api\common\controllers\RestApiController;
use api\common\models\Member;
use api\common\models\Memcache;

use api\modules\v1\models\Notifications;
use api\modules\v1\models\NotificationsMember;
use api\modules\v1\models\Organization;
use api\modules\v1\models\Board;
use api\modules\v1\models\Card;

class NotificationsController extends RestApiController
{
	public function actionIndex() {
		$request = Yii::$app->request->get();
		$idMember = userIdentity()->getId();
		if(isset($request['limit']) && isset($request['per_page']) && isset($request['type'])) { // get notification limit
			$model = Notifications::find()->andWhere('type <> :type', [':type'=>0]);
			if($request['type'] == 'board' && !empty($request['idBoard'])) { // get notification board
				$model -> andFilterWhere(['or',['like','data','"idBoard":"'.$request['idBoard'].'"'],['like','data','"idBoard":'.$request['idBoard']]]);
			} else if ($request['type'] == 'member') { // get notification member
				$model -> andFilterWhere(['idReceive'=>userIdentity()->getId()]);
			}
			$model  ->orderBy('id DESC')
					->limit($request['limit'])
					->offset($request['per_page']);
			return new ActiveDataProvider([ 
					'query' =>  $model,
					'pagination'=> false
			]);
			
		} else if (!empty($request['type']) && $request['type'] == 'board' && !empty($request['idBoard'])) { 
			return new ActiveDataProvider([
				'query'  => Notifications::find()->andFilterWhere(['or',['like','data','"idBoard":"'.$request['idBoard'].'"'],['like','data','"idBoard":'.$request['idBoard']]])->orderBy('id DESC')
			]);			
				
		} else {
			return new ActiveDataProvider([
				'query' => Notifications::find()->where([
					'id' =>NotificationsMember::find()->select('idNotification')->where(['idMember'=>$idMember,'read'=>0])
				])->orderBy('id DESC')
			]);
		}
	}
	public function actionReadAll(){
		$idMember = userIdentity()->getId();
		db()->createCommand("UPDATE notifications_member SET `read` = 1 WHERE `idMember` = ".$idMember)->execute();
		
		// delete cache
		$memcache = new Memcache();
		$memcache->deleteCachePage(array($idMember), 'me');
	}
}
?>