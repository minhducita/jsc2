<?php

namespace api\modules\v1\controllers;

use api\common\controllers\RestApiController;
use api\modules\v1\controllers\NotificationsAccessController;
use api\common\models\UploadForm;

use yii\base\Model;
use api\modules\v1\models\Lists;
use api\modules\v1\models\Board;
use api\modules\v1\models\Card;
use api\modules\v1\models\Checklist;
use api\modules\v1\models\ChecklistItem;
use api\modules\v1\models\Comments;
use api\modules\v1\models\Attachments;
use api\modules\v1\models\CardMembers;
use api\modules\v1\models\BoardMemberships;

use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use yii\helpers\Json;
use yii\web\ServerErrorHttpException;
use yii\web\NotFoundHttpException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

use common\helpers\SlackMessengerHelper;


class ListsController extends RestApiController
{

    public function actionCreate()
    {
		$request = request()->getBodyParams();
		$model = new Lists([
			'scenario' => Model::SCENARIO_DEFAULT
		]);
		$model->load($request, '');
		$model->closed = Lists::LISTS_ACTIVE;
		$model->prefs = Json::encode(Lists::$prefsDefault);
		
		if(!empty($request['idList'])) { // when copy 1 list
			$listcopy = Lists::findOne($request['idList']);
			$model->prefs = $listcopy->prefs;
		} 
		
        if ($model->save()) { //create list
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
            $id = implode(',', array_values($model->getPrimaryKey(true)));
            $response->getHeaders()->set('Location', Url::toRoute(['view', 'id' => $id], true));
			
			//slack notiication
			$slackAction = "createLists";
			$this->sendSlack($slackAction, $model);
			
        } else if (!$model->hasErrors()) { //error when crate list
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }
		
		if(!empty($request['idList']) and !empty($model->id)) { // when copy list
			$this->Copy($request['idList'], $model);
			
		} else { // when create list success
			/* notification */
			$idMembers = BoardMemberships::find()->where(['idBoard'=>$model->idBoard])->select('idMember')->column();
			
			$params = array(
				"table"		=> 'list',
				"idTable"	=> $model->id,
				'dataParams'=>array('action'=>'created'),
				"datapost"	=> request()->getBodyParams(),
				"idSender"	=> $idMembers,
				"idReceive" => userIdentity()->getId(),
				"type"		=> 15
			);
			$this->_createNotification($params);
		}
		
		Lists::deleteCache($model->id, $model->idBoard);		
        return $model;
    }
	
	private function Copy($idList, $model) { // copy Card
		/* insert cards */
		$model_cards = Card::find()->where(['idList'=>$idList])->orderBy('id')->all();
		
		$rows_card = $rows = ArrayHelper::getColumn($model_cards, 'attributes');
		$card = new Card();
		$attributes = $card->attributes();unset($attributes[0]);
		// filter card
		foreach($rows as $key => $row) {
			$idCard[] = $row['id']; 
			$row['idList']	 = $model->id;
			if(!empty($row['badges'])) {
				$badges = json_decode($row['badges']);
				$badges->comments = 0;
				$row['badges'] = json_encode($badges);
			}
			unset($row['id']);
			$rows[$key] = $row;
		}
		
		if(!empty($rows)) { // luu card
			db()->createCommand()->batchInsert(Card::tableName(),$attributes,$rows)->execute();
			
			$model_cards_new 	= Card::find()->where(['idList'=>$model->id])->orderBy('id')->all();
			$rows_cards_new  	= ArrayHelper::getColumn($model_cards_new, 'attributes');
			foreach ($rows_cards_new as $row){
				$idCardNew[] = $row['id'];
			}
			
			/* end insert cards */
			
			/* Insert checklist */
			$checklist 			= Checklist::find()->where('idCard IN ('.implode(",",$idCard).')')->orderBy('idCard')->all();
			$rows_checklist_old = $rows_checklist 	= ArrayHelper::getColumn($checklist, 'attributes');
			
			/*$comments 			= Comments::find()->where('idCard IN ('.implode(",",$idCard).')')->orderBy('idCard')->all();
			$rows_comments 		= ArrayHelper::getColumn($comments, 'attributes');
			*/
			
			$attachments 		= Attachments::find()->where('idCard IN ('.implode(",",$idCard).')')->orderBy('idCard')->all();
			$rows_attachments	= ArrayHelper::getColumn($attachments, 'attributes');
			
			$cardmembers 		= CardMembers::find()->where('idCard IN ('.implode(",",$idCard).')')->orderBy('idCard')->all();
			$rows_cardmembers	= ArrayHelper::getColumn($cardmembers, 'attributes');
			
			$copy = new FileHelper();
			$uploadForm = new UploadForm();
			$idAttachmentCover = array();
			foreach($rows_card as $key => $val) {  // get list insert
			
				foreach($rows_checklist as $key1 =>$val1) { // get list checklist insert
					if($val1["idCard"] == $val["id"] && !empty($rows_checklist[$key1])) {
						$idChecklist[] 	= $val1['id'];
						unset($val1['id']);
						$val1['idCard'] = $model_cards_new[$key]['id'];
						$rows_checklist[$key1] = $val1;
					}
				}
				
				foreach($rows_attachments as $key1=>$val1) { //get list attachments insert
					if($val1["idCard"] == $val["id"]) {
						$idVal1 = $val1['id'];
						unset($val1['id']);
						$val1['idCard'] 	= $model_cards_new[$key]['id']; // id Card new
						$val1['idMember'] 	= userIdentity()->getId();
						/*copy file image*/
						$pathUrl		= $uploadForm->getAlias(str_replace("assets/img/","",$val1['url']));
						$val1['url']    = $uploadForm->copyFile($pathUrl);
						
						$mimeTypes = array("image/jpeg","image/gif",'image/png','image/bmp');
						if(in_array($val1['mimeType'],$mimeTypes)) {
							$previews	= json_decode($val1['previews']);
							if(!empty($previews)){
								foreach ($previews as $k => $v) {
									$path = $uploadForm->getAlias(str_replace("assets/img/","",$v->url));
									$previews[$k]->url = $uploadForm->copyFile($path,'thumbnail');
								}
								$val1['previews'] = json_encode($previews);
							} 
							/* end copy file image */
						} 
						
						if($idVal1 == $val['idAttachmentCover']) { // get attacment file cover
							$idAttachmentCover[] = $val1;
						}
						$rows_attachments[$key1] = $val1;
					}
				}
				
				/*foreach($rows_comments as $key1=>$val1) { //get list comments insert
					if($val1["idCard"] == $val["id"]) {
						unset($val1['id']);
						$val1['idCard'] 	= $model_cards_new[$key]['id'];
						$rows_comments[$key1] = $val1;
					}
				} */
				
				foreach($rows_cardmembers as $key1=>$val1) { //get list comments insert
					if($val1["idCard"] == $val["id"]) {
						$val1['idCard'] 	= $model_cards_new[$key]['id'];
						$rows_cardmembers[$key1] = $val1;
					}
				}
			}

			if(!empty($rows_checklist)) {
				$model = new Checklist();
				$attributes = $model->attributes();unset($attributes[0]);
				db()->createCommand()->batchInsert(Checklist::tableName(),$attributes,$rows_checklist)->execute();
			}
			
			/*
			if(!empty($rows_comments)) {
				$model = new Comments();
				$attributes = $model->attributes();unset($attributes[0]);
				db()->createCommand()->batchInsert(Comments::tableName(),$attributes,$rows_comments)->execute();
			}*/
			
			if(!empty($rows_attachments)) { // insert attachments
				$model = new Attachments();
				$attributes = $model->attributes();unset($attributes[0]);
				db()->createCommand()->batchInsert(Attachments::tableName(),$attributes,$rows_attachments)->execute();
			}
			
			if(!empty($idAttachmentCover)) { //update card id Attachment cover
				$getUrl = "";
				foreach($idAttachmentCover as $attacment) {
					$getUrl .= ",'".$attacment["url"]."'";
				}
				$getIdAttachmentCover = Attachments::find()->select('id, idCard')->where('url IN ('.trim($getUrl ,",").')')->all();
				
				foreach($getIdAttachmentCover as $attacment) {
					foreach($model_cards_new as $card) {
						if($card['id'] == $attacment->idCard) {
							$cardUpdate = Card::findOne($card['id']);
							$cardUpdate->idAttachmentCover = $attacment->id;
							$cardUpdate->save();						
						}
					}
				}
			}
			
			if(!empty($rows_cardmembers)) {
				$model = new CardMembers();
				$attributes = $model->attributes();
				db()->createCommand()->batchInsert(CardMembers::tableName(),$attributes,$rows_cardmembers)->execute();
			}
			
			/* insert checklist_item và insert card_members */
			if(!empty($idChecklist)) {
				
				$checklist_new 			= Checklist::find()->where('idCard IN ('.implode(",",$idCardNew).')')->orderBy('idCard')->all();
				$rows_checklist_new 	= ArrayHelper::getColumn($checklist_new, 'attributes');
				
				$checklist_item 		= ChecklistItem::find()->where('idChecklist IN ('.implode(",",$idChecklist).')')->orderBy('idChecklist')->all();
				$rows_checklist_item 	= ArrayHelper::getColumn($checklist_item, 'attributes');
				
				foreach($rows_checklist_old as $key => $val) {
					foreach($rows_checklist_item as $key1=>$val1) {
						if($val1["idChecklist"] == $val["id"]) {
							unset($val1['id']);
							$val1['idChecklist'] 	= $rows_checklist_new[$key]['id'];
							$rows_checklist_item[$key1] = $val1;
						}
					}
				}
			}
			
			if(!empty($rows_checklist_item)) {
				$model = new ChecklistItem();
				$attributes = $model->attributes();unset($attributes[0]);
				db()->createCommand()->batchInsert(ChecklistItem::tableName(),$attributes,$rows_checklist_item)->execute();
			}
		}
	}
	
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
		$request = request()->getBodyParams();
		if(!empty($request['idBoard']) && $model->idBoard != $request['idBoard']) { // check idBoard 
			Lists::deleteCache($model->id, $model->idBoard);
			$condition = [
				'and',
				["=",'idBoard',$model->idBoard],
				["=",'idList',$model->id]
			];
			$listCard = Card::find()->where(["idBoard"=>$model->idBoard,"idList"=>$model->id])->all();
			$idCard = "";
			if($listCard) {
				foreach($listCard as $item) {
					$idCard[] = $item->id ;
				}
			}
			/* update card */
			Card::updateAll($request, $condition);
			/* Check list*/
			if(!empty($idCard)) {
				$condition = ['and',
					["in",'idCard',$idCard],
					["=",'idBoard',$model->idBoard]
				];
				Checklist::updateAll($request,$condition);
			}
			Lists::deleteCache($model->id, $request['idBoard']);
		}
	
		if (isset($request['type']) && $request['type'] === 'prefs/background') {
            $prefs = Json::decode($model->prefs);
            $model->prefs = Json::encode(array_merge($prefs, array_intersect_key($request, $prefs)));
		}
		
        $model->load($request, '');
        if ($model->save() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }
		
		/* notification */
		$idMembers = BoardMemberships::find()->where(['idBoard'=>$model->idBoard])->select('idMember')->column();
		$action = (isset($request['closed']))?($request['closed'] == 1)?'Closed':'Opened':'Edited';
		$params = array(
			"table"		=> 'list',
			"idTable"	=> $model->id,
			'dataParams'=>array('action'=>$action),
			"datapost"	=> $request,
			"idSender"	=> $idMembers,
			"idReceive" => userIdentity()->getId(),
			"type"		=> isset($request['closed'])?16:0 //16: notification cloese open board
		);
		$this->_createNotification($params);
		Lists::deleteCache($model->id, $model->idBoard);
		
		//slack notiication
		$slackDataParams = array();
		$slackAction = "updateLists";
		foreach($request as $k => $v) {
			$slackDataParams['ColumChange'] = $k;
			$slackDataParams['ColumContent'] = $v;
		}
		$this->sendSlack($slackAction, $model, $slackDataParams);
		
        return $model;
    }

    public function actionSort()
    {
        if (($request = request()->getBodyParams()) && !empty($request['sort'])) {
			$list = Lists::find()->where(['id'=>$request['idList']])->one();
			$dataParams['listOld'] = $list->getAttributes();
            $sort = array_filter($request['sort']);
            $sql = 'UPDATE `lists` SET `pos` =  CASE `id`';
            foreach ($sort as $k => $id) {
                $sql.= sprintf(' WHEN %d THEN %d', $id, $k);
            }
            $sql.= ' END WHERE `id` IN ('. implode(',', $sort) . ')';
            db()->createCommand($sql)->execute();		
			$idMembers = $idMembers = BoardMemberships::find()->where(['idBoard'=>$dataParams['listOld']['idBoard']])->select('idMember')->column();
			$dataParams['action'] = 'changed position';
			$params = array(
				'table'			=>'list',
				'idTable'		=>$request['idList'],
				'dataParams'	=>$dataParams,
				'datapost'		=>request()->getBodyParams(),
				'idReceive'		=>userIdentity()->getId(),
				'idSender'		=>$idMembers,
				'type'			=>17
			);
			$this->_createNotification($params);
			
			//slack notiication
			$slackDataParams = array();
			$slackAction = "sortLists";
			$this->sendSlack($slackAction, $list, $slackDataParams);
        }
		//Lists::deleteCache(0, $dataParams['listOld']['idBoard']);
		Board::deleteCache($dataParams['listOld']['idBoard']);
    }
	
	public function actionViewBoard($id) 
	{
		/* need cache provider */
		return new ActiveDataProvider([
			'query' => Lists::find()->where(['idBoard'=>$id])
		]);
	}
	
    public function findModel($id)
    {
        $model = Lists::findCacheOne($id);
        if ($model === null) 
		{
            throw new NotFoundHttpException;
        }
        return $model;
    }
	
	private function _createNotification($params) 
	{
		$notification = new NotificationsAccessController();
		$notification ->createNotifyConformTable($params);
	}
	
	private function sendSlack($slackAction, $Lists, $slackDataParams=array()) {
		$slackBoard = Board::findOne($Lists->idBoard);
		$slackDataParams['UserdisplayName'] = userIdentity()->displayName;
		$slackDataParams['listName'] = $Lists->name;
		SlackMessengerHelper::sendSlackMessenger($slackAction, $slackBoard, $slackDataParams);
	}
}
