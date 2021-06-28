<?php
/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
namespace api\modules\v1\controllers;

use api\common\models\Member;
use api\modules\v1\controllers\NotificationsAccessController;
use api\common\models\UploadFile;
use api\modules\v1\models\Attachments;
use api\modules\v1\models\BoardLabels;
use api\modules\v1\models\Card;
use api\modules\v1\models\Board;
use api\modules\v1\models\CardMembers;
use api\modules\v1\models\BoardMemberships;
use api\modules\v1\models\Checklist;
use api\modules\v1\models\ChecklistItem;
use api\modules\v1\models\Comments;
use api\modules\v1\models\Lists;
use api\modules\v1\models\Options;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\Json;
use yii\web\ServerErrorHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use api\common\controllers\RestApiController;

use common\helpers\StringHelper;
use common\helpers\SlackMessengerHelper;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;
use api\common\models\UploadForm;


class CardController extends RestApiController
{	
    public function actionCreate()
    {
		Yii::error("chaobananhcontroller");
        Yii::warning(userIdentity()->getId());

        $model = new Card([
            'scenario' => Model::SCENARIO_DEFAULT
        ]);
        $model->load(request()->getBodyParams(), '');
        $model->name = StringHelper::StrRewrite(strtolower($model->displayName));
        $model->closed = Card::CARD_ACTIVE;
        $model->idMember = userIdentity()->getId();
		if($model->important){
			$model->important = 1;
		};
		if($model->urgent){
			$model->urgent = 1;
		};
        /**
         * set Defaults bages
         */
        $model->badges = Json::encode([
            'attachments' => 0,
            'checkItems' => 0,
            'checkItemsChecked' => 0,
            'comments' => 0,
            'description' => false,
            'due' => (!empty($model->due))? $model->due: null,
            'subscribed' => false,
            'viewingMemberVoted' => 0,
			'startDate'=> (!empty($model->startDate))? $model->startDate: null,
            'votes' => 0
        ]);
        if ($model->save()) {
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }
		/*notification*/
		$idCard    = Card::find()->select('idBoard')->where(['id'=>$model->id])->one();
		$idMembers = BoardMemberships::find()->select('idMember')->where(["idBoard"=>$idCard->idBoard])->column();
		if(!empty($idMembers)) {
			$params = array(
				'table'=>"card",
				'type'=> 12,// create card
				'dataParams'=> 'created',
				'idTable'=>$model->id,
				'datapost'=>request()->getBodyParams(),
				'idSender'=>$idMembers,
				'idReceive'=>userIdentity()->getId(),
			);
			$this->_createNotification($params);
			
			/*slack send notification */
			if(!empty($slackAction) && !empty($model)) {
				$slackAction = "createCard";
				$slackDataParams = $dataParams;
				$this->sendSlack($slackAction, $model, $slackDataParams);
			}
			
		}
		/* send slack notification*/		
		Card::deleteCache($model->id, $model->idBoard);
        return $model;
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        return $model;
    }

	public function actionUpdate($id){
        $model = $this->findModel($id);
		$notifyDue = $model->due;
		$request = request()->getBodyParams();
		if (!empty($request["parentId"]) && $request['parentId'] != $model->id) {
			$parent =  (!empty($model->parentId) && $model->parentId != null)?Json::decode($model->parentId):array();
			if(($key = array_search($request["parentId"], $parent)) !== false) {
				unset($parent[$key]);
			} else {
				$parent[] = $request["parentId"];
			}
			$request["parentId"] = Json::encode($parent);
		} else if (!empty($request["parentId"]) && $request['parentId'] == $model->id) {
			return $model;
		}
		
		if(!empty($request['idBoard']) && $model->idBoard != $request['idBoard']) {
			Card::deleteCache($model->id, $model->idBoard );
		}
		
        $model->load($request, '');
	
        $badges = Json::decode($model->badges);
        $badges['due'] = $model->due;
		$badges['startDate'] = $model->startDate;
		
        $model->badges = Json::encode($badges);
		$model->lastUpdated = time();
		
        if ($model->save() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }
		/*reqquest user relationship card and notification*/
		$request = request()->getBodyParams();
		
		/* update parent card relationship */
		if(!empty($request["parentId"])) {
			$relationCard = Card::findOne($request["parentId"]);
			if(!empty($relationCard)) {
				$parentid = Json::decode($relationCard->parentId);
				$parentid = (empty($parentid))? array() : $parentid;
				if(($key = array_search($model->id, $parentid)) !== false) {
					unset($parentid[$key]);
				} else {
					$parentid[] = $model->id;
				}	
				$parentid = Json::encode($parentid);
				$relationCard->load(array("parentId"=>$parentid),'') ;
				$relationCard->save();
			}
		}
		
		/*Notification */
		$types 		= array("closed"=>11,'due'=>18);
		$type 		= "";
		foreach($request as $k => $v) {
			$type = !empty($types[$k])?$types[$k]:"";
			if($k == 'due' && !empty($notifyDue)) {
				$dataParams['action'] = 'changed';
				$dataParams['ColumContent'] = date("m/d/Y", $v/1000);	// slack notification	
				$slackAction = "changedDueCard";
			} else if($k == 'due') {
				$dataParams['ColumContent'] = date("m/d/Y", $v/1000);// slack notification
				$dataParams['action'] = 'added';// slack notification
				$slackAction = "addedDueCard";
			} else {
				$dataParams['ColumChange'] = $k;// slack notification
				$dataParams['ColumContent'] = $v;// slack notification
				$dataParams['action'] = 'edited';
				$slackAction = "updateCard";
			}
		}
		if(!empty($type)) {
			$idCard    = Card::find()->select('idBoard')->where(['id'=>$id])->one();
			$idMembers = BoardMemberships::find()->select('idMember')->where(["idBoard"=>$idCard->idBoard])->column();
			if(!empty($idMembers)) {
				$params = array(
					'table'		=>	"card",
					'type'		=> 	$type,
					'dataParams'	=>$dataParams,
					'idTable'	=>	$id,
					'datapost'	=>	$request,
					'idSender'	=>	$idMembers,
					'idReceive'	=>	userIdentity()->getId(),
				);
				$this->_createNotification($params);
			}
		} 
		/*notification*/
	
		/*slack send notification */
		 if(!empty($slackAction) && !empty($model)) {
			$slackDataParams = $dataParams;
			$this->sendSlack($slackAction, $model, $slackDataParams);
		 }
		
		Card::deleteCache($model->id, $model->idBoard);
		
		
		if(!empty($request["parentId"])) {
			
			Card::deleteCache($request["parentId"]);
		}
		
        return $model;
    }

	public function actionDelete($id) 
	{
		$idMember = userIdentity()->getId();
		
		$model_card = Card::find()->select("card.*, board_memberships.idMember as idMemberCard")
			->leftJoin('board_memberships', 'board_memberships.idBoard  = card.idBoard')
			->where("board_memberships.idMember = ".$idMember." AND card.id = ".$id)
			->one();
		
		if(!empty($model_card)) {
			/*notification*/
			$idMembers = BoardMemberships::find()->select('idMember')->where(["idBoard"=>$model_card->idBoard])->column();
			if(!empty($idMembers)) {
				$params = array(
					'table'=>"card",
					'type'=> 13,// remove card
					'dataParams' => array('action'=>'removed'),
					'idTable'=>$id,
					'datapost'=>request()->getBodyParams(),
					'idSender'=>$idMembers,
					'idReceive'=>userIdentity()->getId(),
				);
				$this->_createNotification($params);
			}
			
			/*slack send notification */
			if(!empty($slackAction) && !empty($model_card)) {
				$slackAction = "deleteCard";
				$this->sendSlack($slackAction, $model_card, $slackDataParams);
			}
		}
		
		if(!empty($model_card) && $model_card->delete()) {
			$type_image = array('image/jpeg', 'image/gif', 'image/png', 'image/bmp', 'image/tiff');
			// delete card member
			
			CardMembers::deleteAll('idCard = '.$model_card->id);
			//delete attachments
			
			$model_cardattachments = Attachments::find()->where(['idCard'=>$model_card->id])->all();
			if(!empty($model_cardattachments)) {
				$uploadForm = new UploadFile();
				foreach($model_cardattachments as $attachment) {
					if( in_array( $attachment->mimeType, $type_image ) ) {
						$previews  = json_decode($attachment->previews);
						foreach($previews as $preview) {
							$path = Yii::getAlias( $uploadForm->uploadFolder.str_replace("assets/img/", "", $preview->url));
							if(file_exists( $path )) {
								unlink( $path );
							}
						}
					}
					$path = Yii::getAlias($uploadForm->uploadFolder.str_replace("assets/img/", "", $attachment->url));
					if(file_exists( $path )) {
						unlink( $path );
					}
				}
				Attachments::deleteAll( 'idCard = ' . $model_card->id );
			}
			
			//delete checklist
			Checklist::deleteAll('idCard = '.$model_card->id);
			//delete checklist item
			$sql = "DELETE checklist_item FROM checklist_item INNER JOIN checklist ON checklist.id = checklist_item.idChecklist WHERE idCard = ".$model_card->id;
			db()->createCommand($sql)->execute();
			
			// delete comments
			Comments::deleteAll('idCard = '.$model_card->id);
			
			Card::deleteCache($model_card->id, $model_card->idBoard);
			
			return $model_card;
		} else {
			throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
		}		
	}
	
    public function actionSort()
    {
        if (($request = request()->getBodyParams())) {
            if (isset($request['idList']) && !empty($request['sort'])) {
                $idList = (int) $request['idList'];
				$idCard = (int) $request['idCard'];
                $sort = array_filter((array) $request['sort']);
                if (Lists::findOne($idList) === null) {
                    throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
                }
				
				$sql = "SELECT lists.*,card.pos as cardpos FROM lists INNER JOIN card ON card.idList = lists.id WHERE card.id = ".$idCard;// notification dataParams
				$dataParams['listOld'] = db()->createCommand($sql)->queryOne();
				
                $sql = 'UPDATE `card` SET `pos` =  CASE `id`';
                foreach ($sort as $k => $id) {
                    $sql.= sprintf(' WHEN %d THEN %d', $id, $k);
                }
                $sql.= ' END, `idList` = '. (int) $request['idList'].'  WHERE `id` IN ('. implode(',', $sort) . ')';
                
				if (db()->createCommand($sql)->execute()) {
					
					/* notification */
					$dataParams['listNew']  = array();
					
					if($dataParams['listOld']['id'] != $idList) {
						$sql = "SELECT lists.*,card.pos as cardpos FROM lists INNER JOIN card ON card.idList = lists.id WHERE card.id = ".$idCard." AND card.idList = ".$idList;// notification dataParams
						$dataParams['listNew'] = db()->createCommand($sql)->queryOne();
						$dataParams['action'] = 'moved';
						$slackAction = "moveCard";
					} else {
						$dataParams['action'] = 'changed position';
						$slackAction = "changedPositionCard";
					}
					
					$idBoardMembers = BoardMemberships::find()->select('idMember','')->where(['idBoard'=>$dataParams['listOld']['idBoard']])->column();
					$params = array(
						'table'=>'card',
						'idTable'=>$idCard,
						'dataParams'=>$dataParams,
						'datapost'=>request()->getBodyParams(),
						'idReceive'=>userIdentity()->getId(),
						'idSender'=>$idBoardMembers,
						'type'=>14
					);
					$this->_createNotification($params);
					Card::deleteCache($idCard, $dataParams['listOld']['idBoard']);
					
					/*slack send notification */
					$slackDataParams = $dataParams;
					$CardFind = $this->findModel($idCard);
					$List = Lists::findOne($CardFind->idList);
					$Board = Board::findOne($slackDataParams['listOld']['idBoard']);
					$slackDataParams['UserdisplayName'] = userIdentity()->displayName;
					$slackDataParams['cardName'] = $CardFind->displayName;
					$slackDataParams['idCard'] = $CardFind->id;
					$slackDataParams['listName'] = $List->name;
					SlackMessengerHelper::sendSlackMessenger($slackAction, $Board, $slackDataParams);
					
					/* end notification */
                    return [
                        'idList' => (int) $request['idList'],
                        'sort' => $sort
                    ];
					
                } else {
                    throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
                }
				
            } else {
                throw new ServerErrorHttpException('Server error.');
            }
        }
    }

    public function actionCreateLabel($id)
    {
        $model = $this->findModel($id);
        $boardLabelModel = new BoardLabels();
        $boardLabelModel->load(request()->getBodyParams(), '');
        if ($boardLabelModel->save()) {
           $labels = Json::decode($model->idLabels);
           if (!in_array($boardLabelModel->id, $labels)) {
               $labels[] = $boardLabelModel->id;
               $model->idLabels = Json::encode($labels);
			   $model->lastUpdated = time();
               $model->update();
           }
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
        } else {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }					
		Card::deleteCache($id, $model->idBoard);
        return $boardLabelModel;
    }

    public function actionAddIdlabel($id)
    {
        $model = $this->findModel($id);
        $labels = Json::decode($model->idLabels);
        $params = request()->getBodyParams();
		$model->lastUpdated = time();
        if (!in_array($params['value'], $labels)) {
            $labels[] = $params['value'];
            $model->idLabels = Json::encode($labels);
        }
        if ($model->save()) {
            $boardlabel = BoardLabels::findOne($params['value']);
			$boardlabel->updateCounters(['used' => 1]);
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
        } else {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }
		
		//slack notification
		if(!empty($boardlabel) && !empty($model)) {
			$slackAction = "addIdLabelCard";	
			$slackDataParams['labelName'] = $boardlabel->name;
			$this->sendSlack($slackAction, $model, $slackDataParams);
		}

		Card::deleteCache($id, $model->idBoard);
        return $params['value'];
    }

    public function actionDeleteIdlabel($id, $idLabels)
    {
        $model = $this->findModel($id);
		$model->lastUpdated = time();
        $labels = Json::decode($model->idLabels);
        if (in_array($idLabels, $labels)) {
            unset($labels[array_search($idLabels, $labels)]);
            $model->idLabels = Json::encode(array_values($labels));
        }
        if ($model->save()) {
            $boardlabel = BoardLabels::findOne($idLabels);
			$boardlabel->updateCounters(['used' => -1]);
            $response = Yii::$app->getResponse();
            $response->setStatusCode(204);
        } else {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }
		
		//slack notification
		if(!empty($boardlabel) && !empty($model)) {
			$slackAction = "deleteIdLabelCard";	
			$slackDataParams['labelName'] = $boardlabel->name;
			$this->sendSlack($slackAction, $model, $slackDataParams);
		}
		
		Card::deleteCache($id, $model->idBoard);
    }

    public function actionCreateMember($id) // add memer $v�o card
    {
        $model = new CardMembers();
        $model->idCard = $id;
        $model->load(request()->getBodyParams(), '');
        if ($model->save()) {
			$this->setLastUpdated($id);
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
        } else {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }
		$card    = Card::find()->select('idBoard,idList')->where(['id'=>$id])->one();
		$idMembers = BoardMemberships::find()->select('idMember')->where(["idBoard"=>$card->idBoard])->column();
		$params = array(
			"table" => 'card',
			'idTable'=> $id,
			'type' => 8,
			'dataParams'=>array('action'=>'added'),
			'datapost'	=>request()->getBodyParams(),
			'idEffectMember'	=>$model->idMember,
			'idSender'	=>$idMembers,
			'idReceive' => userIdentity()->getId(),		
		);
		
		/* slack notification*/
		if(!empty($card)) {
			$slackAction = "createMemberCard";	
			$slackUser  = Member::find()->select('displayName')->where(["id"=>$model->idMember])->one();
			$slackDataParams['memberName'] = $slackUser->displayName;
			$this->sendSlack($slackAction, $card, $slackDataParams);
		}	

		Card::deleteCache($id, $card->idBoard);
		$this->_createNotification($params);
        return Member::findOne($model->idMember);
    }
    public function actionDeleteMember($id, $idMember)
    {
        $model = CardMembers::findOne([
            'idCard' => $id,
            'idMember' => $idMember
        ]);
        if ($model === null) {
            throw new NotFoundHttpException;
        }
        if ($model->delete() === false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        } 
		$card = $this->setLastUpdated($id);		
		$idMembers = BoardMemberships::find()->select('idMember')->where(["idBoard"=>$card->idBoard])->column();
		$params = array(
			"table" => 'card',
			'idTable'=> $id,
			'dataParams'=>array('action'=>'removed'),
			'type' => 9,
			'datapost'	=>request()->getBodyParams(),
			'idEffectMember'	=>$idMember,
			'idSender'	=>$idMembers,
			'idReceive' => userIdentity()->getId(),		
		);
		
		/* slack notification*/
		if(!empty($card)) {
			$slackAction = "deleteMemberCard";	
			$slackUser  = Member::find()->select('displayName')->where(["id"=>$model->idMember])->one();
			$slackDataParams['memberName'] = $slackUser->displayName;
			$this->sendSlack($slackAction, $card, $slackDataParams);
		}		

		Card::deleteCache($id, $card->idBoard);
		$this->_createNotification($params);
        Yii::$app->getResponse()->setStatusCode(204);
    }

	public function actionDeleteAttachments($id, $idAttachment) {
		$idMember = userIdentity()->getId();
		$uploadForm = new UploadFile();
		$attachments = Attachments::find()->from('attachments')->where(['attachments.id'=>$idAttachment])->innerJoin("card",'card.id = attachments.idCard')->andWhere(['card.id'=>$id])->innerJoin("board_member","board_member.idBoard = card.idBoard")->andWhere(["board_member.idMember"=> $idMember])->one();
		if(!empty($attachments)){
			$type_image = array('image/jpeg', 'image/gif', 'image/png', 'image/bmp', 'image/tiff');
			
			if(in_array($attachments->mimeType,$type_image)) {
				$attachments->previews = json_decode($attachments->previews);
				foreach($attachments->previews as $value){
					$path = Yii::getAlias($uploadForm->uploadFolder.str_replace("assets/img/","",$value->url));
					if(file_exists($path)) {
						unlink($path);
					}
				}
			}
			
			$path = Yii::getAlias($uploadForm->uploadFolder.str_replace("assets/img/","",$attachments->url));
			if(file_exists($path)) {
				unlink($path);
			}
			
			// delete cache
			$card = Card::findOne($id);
			Card::deleteCache($card->id, $card->idBoard);	
			
			/* slack notification*/
			if(!empty($card)) {
				$slackAction = "removeFileCard";	
				$slackDataParams['fileName'] = $attachments->name;
				$slackDataParams['fileType'] = "file";
				$this->sendSlack($slackAction, $card, $slackDataParams);
			}
			
			if($attachments->delete()) {
				$this->updateBadges($id,0,"-","attachments");
				$this->setLastUpdated($id);
				return ['idAttachment'=>$idAttachment];
			} else {
				throw new ServerErrorHttpException('Failed to delete file to server.');
			}
		} else {
			throw new ServerErrorHttpException('Failed to delete file to server.');
		}
	}

	private function saveImage($id,$path,$uploadForm,$model) { // save when this is image
		/**@Minhquyen20190603 - edit cover images upload - performance pages loading */
		$wout = 0;
		$hout = 0;
		list($maxwth, $maxhig) = array('300','300');
		list($wth, $hig, $tpe, $attr) = getimagesize(Yii::getAlias($path));
		if($wth > $maxwth || $hig > $maxhig){
			if($wth > $maxwth && $hig > $wth){
				$hout = 300;
				//$wout = 300 * ($hig / $wth);
				$wout = 300 * ($wth / $hig);
			}
			else if($wth > $maxwth && $hig < $wth){
				$wout = 300;
				$hout = 300 * ($hig / $wth);
			}
		}
		else {
			$wout = $wth;
			$hout = $hig;
		}

		$imagine = new Imagine();
		$transaction = Attachments::getDb()->beginTransaction();
        try {
			$path70x50 = str_replace('.', '-70x50.', $path);
			$imagine->open(Yii::getAlias($path))
					->resize(new Box(70, 50))
                    ->save(Yii::getAlias($path70x50), ['quanlity' => 50]);
			$path300x300 = str_replace('.', '-300x300.', $path);
			$imagine->open(Yii::getAlias($path))
					//->resize(new Box(300, 300))
					->resize(new Box($wout, $hout)) //@Minhquyen20190603 - edit cover images upload - performance pages loading
                    ->save(Yii::getAlias($path300x300), ['quanlity' => 70]);
			$attachModel = new Attachments();
			$attachModel->name = $uploadForm->file->name;
			$attachModel->bytes = filesize(Yii::getAlias($path));
			$attachModel->mimeType = $uploadForm->file->type;
			$attachModel->isUploaded = true;
			$attachModel->uploadedDate = time();
			$attachModel->idCard = $id;
			$attachModel->idMember = userIdentity()->getId();
			$attachModel->url = str_replace('@frontend/web', '', $path);
			$attachModel->previews = Json::encode([
				[
					'width' => 70,
					'height' => 50,
					'typefile'=> $uploadForm->file->extension,
					'bytes' => filesize(Yii::getAlias($path70x50)),
					'url' => str_replace('@frontend/web', '', $path70x50)
				],[
					'width' => 300,
					'height' => 300,
					'typefile'=> $uploadForm->file->extension,
					'bytes' => filesize(Yii::getAlias($path300x300)),
					'url' => str_replace('@frontend/web', '', $path300x300)
				],
			]);
			$attachModel->save();
			$model->idAttachmentCover = $attachModel->id;
			$model->save();
			$transaction->commit();
			$response = Yii::$app->getResponse();
			$response->setStatusCode(201);
			$this->setLastUpdated($id);
			
			/*slack notification*/
			
			
			return $attachModel;
		} catch (\Exception $e) {
			$transaction->rollBack();
			throw new ServerErrorHttpException('Failed to upload file to server.');
		} 
	}
	
	private function getFileName($typefile) {
		$linkRoot = '/assets/img/file/';
		if(in_array($typefile,array('doc','docx'))) {
			return $linkRoot."doc";
		} else if (in_array($typefile,array('xlsx','xls','csv'))) {
			return $linkRoot."xls"; 
		} else if (in_array($typefile,array("pdf"))) {
			return $linkRoot."pdf";
		} else if (in_array($typefile,array("txt"))) {
			return $linkRoot."txt"; 
		} else if (in_array($typefile,array("pptx"))) {
			return $linkRoot."pptx"; 
		} else if (in_array($typefile,array("rar",'zip'))) {
			return $linkRoot."rar"; 
		}
	}
	
	private function saveFileApplication($id,$path,$uploadForm,$model,$typefile) { // save file not image
		$nameFile = $this->getFileName($typefile);
		$attachModel = new Attachments();
		$attachModel->name = $uploadForm->file->name;
		$attachModel->bytes = filesize(Yii::getAlias($path));
		$attachModel->mimeType = $uploadForm->file->type;
		$attachModel->isUploaded = true;
		$attachModel->uploadedDate = time();
		$attachModel->idCard = $id;
		$attachModel->idMember = userIdentity()->getId();
		$attachModel->url = str_replace('@frontend/web', '', $path);
		$attachModel->previews = Json::encode([
			[
				'width' => 70,
				'height' => 70,
				'bytes' => filesize(Yii::getAlias($path)),
				'typefile'=> $uploadForm->file->extension,
				'url' => $nameFile."-70x70.png"
			],[
				'width' => 300,
				'height' => 300,
				'typefile'=> $uploadForm->file->extension,
				'bytes' => filesize(Yii::getAlias($path)),
				'url' => $nameFile."-300x300.png"
			],
		]);
		$attachModel->save();
		$model->save();
		$response = Yii::$app->getResponse();
		$response->setStatusCode(201);
		$this->setLastUpdated($id);
		return $attachModel;
	}
	
    public function actionAttachments($id)
    {
        $model = $this->findModel($id);
        $uploadForm = new UploadFile();
        $uploadForm->file = UploadedFile::getInstanceByName('file');
		if ($uploadForm->upload(md5('card_' . $id))) {
            $path = $uploadForm->uploadFolder . '/' . md5('card_' . $id) . '/'. md5($uploadForm->file->baseName) . '.' . $uploadForm->file->extension;
			$typefile = explode(".",$uploadForm->file->name);
			$typefile = $typefile[count($typefile)-1];
			$this->updateBadges($id,0,"+","attachments");
			
			Card::deleteCache($model->id, $model->idBoard); // delete cache when add file
			$slackDataParams = array();
			if(in_array($typefile,array('jpg','png','gif','bmp'))) {
				$slackDataParams['fileType'] = "image";
				$file =  $this->saveImage($id,$path,$uploadForm,$model);
			} else {
				$slackDataParams['fileType'] = "file";
				$file = $this->saveFileApplication($id,$path,$uploadForm,$model,$typefile);
			}
			
			/* slack notification*/
			if(!empty($model)) {
				$slackAction = "addFileCard";	
				$slackDataParams['fileName'] = $file->name;
				$this->sendSlack($slackAction, $model, $slackDataParams);
			}

			return $file;
        } else {
            throw new ServerErrorHttpException('Failed to upload file to server.');
        }
    }

    public function actionCreateChecklists($id)
    {
        $model = new Checklist([
            'scenario' => Model::SCENARIO_DEFAULT
        ]);
        $model->load(request()->getBodyParams(), '');
        $model->idCard = $id;
        if ($model->save()) {
			$this->setLastUpdated($id);
            $response = Yii::$app->getResponse();
            $response -> setStatusCode(201);
			
			/* Notification */
			$idMembers  = BoardMemberships::find()->select('idMember')->where(['idBoard'=>$model->idBoard])->column();
			$params = array(
				"table"			=> "checklist",
				"idTable"		=> $model->id,
				'type'			=> 19,
				'dataParams'	=> array("action"=>'created'),
				'idReceive' 	=> userIdentity()->getId(),
				'idSender'  	=> $idMembers,
				'datapost'		=> request()->getBodyParams()
			);
			
			/* slack notification*/
			$card = Card::findOne($id);
			if(!empty($card)) {
				$slackAction = "addChecklistCard";	
				$slackDataParams['checklistName'] = $model->name;
				$this->sendSlack($slackAction, $card, $slackDataParams);
			}
			
			Card::deleteCache($model->idCard, $model->idBoard);
			$this->_createNotification($params);
			/* end Notification */
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }
        return $model;
    }

    public function actionUpdateChecklists($id, $idChecklist)
    {
        $model = Checklist::findOne([
            'id' => $idChecklist,
            'idCard' => $id
        ]);
        if ($model === null) {
            throw new NotFoundHttpException;
        }
        $model->load(request()->getBodyParams(), '');

        if ($model->save() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }
		$card = $this->setLastUpdated($id);
		/* Notification */
		$idMembers  = BoardMemberships::find()->select('idMember')->where(['idBoard'=>$model->idBoard])->column();
		$params = array(
			'table'			=> 'checklist',
			'idTable'		=> $model->id,
			'type'			=> 21,
			'dataParams'	=> array("action"=>'deleted'),
			'idReceive' 	=> userIdentity()->getId(),
			'idSender'  	=> $idMembers,
			'datapost'		=> request()->getBodyParams()
		);
		$this->_createNotification($params);

		/* slack notification*/
		
		if(!empty($card)) {
			$slackAction = "updateChecklistCard";	
			$slackDataParams['checklistName'] = $model->name;
			$request = request()->getBodyParams();
			foreach($request as $k => $v) {
				$slackDataParams['ColumChange'] = $k;
				$slackDataParams['ColumContent'] = $v;
			}
			$this->sendSlack($slackAction, $card, $slackDataParams);
		}

		Card::deleteCache($model->idCard, $model->idBoard);
        return $model;
    }

    public function actionDeleteChecklists($id, $idChecklist)
    {
        $model = Checklist::findOne([
            'id' => $idChecklist,
            'idCard' => $id
        ]);
        if ($model === null) {
            throw new NotFoundHttpException;
        }
		$card = $this->setLastUpdated($id);
		/* Notification */
		$idMembers  = BoardMemberships::find()->select('idMember')->where(['idBoard'=>$model->idBoard])->column();
		$params = array(
			'table'			=> 'checklist',
			'idTable'		=> $model->id,
			'type'			=> 20,
			'dataParams'	=> array("action"=>'deleted'),
			'idReceive' 	=> userIdentity()->getId(),
			'idSender'  	=> $idMembers,
			'datapost'		=> request()->getBodyParams()
		);
		$this->_createNotification($params);
		
		/* slack notification*/
		if(!empty($card)) {
			$slackAction = "deleteChecklistCard";	
			$slackDataParams['checklistName'] = $model->name;
			$this->sendSlack($slackAction, $card, $slackDataParams);
		}

        if ($model->delete() === false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }
		Card::deleteCache($model->idCard, $model->idBoard);
        Yii::$app->getResponse()->setStatusCode(204);
    }

    public function actionCreateCheckitem($id, $idChecklist)
    {
        $model = new ChecklistItem([
            'scenario' => Model::SCENARIO_DEFAULT
        ]);
        $model->load(request()->getBodyParams(), '');
        $model->idChecklist = $idChecklist;
        if ($model->save()) {
			$card = $this->setLastUpdated($id);
			/* Notification */
			$checklist  = Checklist::findOne($idChecklist);
			$idMembers  = BoardMemberships::find()->select('idMember')->where(['idBoard'=>$checklist->idBoard])->column();
			$params = array(
				'table'			=> 'checklistitem',
				'idTable'		=> $model->id,
				'type'			=> 22,
				'dataParams'	=> array("action"=>'created'),
				'idReceive' 	=> userIdentity()->getId(),
				'idSender'  	=> $idMembers,
				'datapost'		=> request()->getBodyParams()
			);
			$this->_createNotification($params);
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
			$this->updateBadges(0,$idChecklist,"","checkItems");
			
			/* slack notification*/
			if(!empty($card)) {
				$slackAction = "addChecklistItemCard";	
				$slackDataParams['checklistItemName'] = $model->name;
				$this->sendSlack($slackAction, $card, $slackDataParams);
			}
			
			Card::deleteCache($checklist->idCard, $checklist->idBoard);
			Lists::deleteCache($checklist->id);
			
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }
        return $model;
    }

    public function actionUpdateCheckitem($id, $idChecklist, $idCheckitem)
    {
        $model = ChecklistItem::findOne([
            'id' => $idCheckitem,
            'idChecklist' => $idChecklist
        ]);
        if ($model === null) {
            throw new NotFoundHttpException;
        }
        $model->load(request()->getBodyParams(), ''); 

        if ($model->save() === false && !$model->hasErrors()) {
			
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }
		
		$checklist  = Checklist::findOne($idChecklist);
		/* Notification 
		$idMembers  = BoardMemberships::find()->select('idMember')->where(['idBoard'=>$checklist->idBoard])->column();
		$params = array(
				'table'			=> 'checklistitem',
				'idTable'		=> $model->id,
				'type'			=> 23,
				'dataParams'	=> array("action"=>'edited'),
				'idReceive' 	=> userIdentity()->getId(),
				'idSender'  	=> $idMembers,
				'datapost'		=> request()->getBodyParams()
		);
		$this->_createNotification($params);*/
		$card = $this->setLastUpdated($id);
		/* update bages Card */
		$request = request()->getBodyParams();
		if(isset($request['state'])) {
			$this->updateBadges(0,$idChecklist,"","checkItems");
		}
		
		/* slack notification*/
		if(!empty($card)) {
			$slackAction = "updateChecklistItemCard";			
			foreach($request as $k => $v) {
				$slackDataParams['ColumChange'] = $k;
				$slackDataParams['ColumContent'] = $v;
			}
			$this->sendSlack($slackAction, $card, $slackDataParams);
		}
		
		
		Card::deleteCache($checklist->idCard, $checklist->idBoard);
		Lists::deleteCache($checklist->id);
        return $model;
    }

    public function actionDeleteCheckitem($id, $idChecklist, $idCheckitem)
    {
        $model = ChecklistItem::findOne([
            'id' => $idCheckitem,
            'idChecklist' => $idChecklist
        ]);
		$card = $this->setLastUpdated($id);
		/* Notification */
		$checklist  = Checklist::findOne($idChecklist);
		$idMembers  = BoardMemberships::find()->select('idMember')->where(['idBoard'=>$checklist->idBoard])->column();
		$params = array(
			'table'			=> 'checklistitem',
			'idTable'		=> $model->id,
			'type'			=> 24,
			'dataParams'	=> array("action"=>'deleted'),
			'idReceive' 	=> userIdentity()->getId(),
			'idSender'  	=> $idMembers,
			'datapost'		=> request()->getBodyParams()
		);
		$this->_createNotification($params);
		/* end Notification */
        if ($model === null) {
            throw new NotFoundHttpException;
        }
        if ($model->delete() === false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }
		$this->updateBadges(0,$idChecklist,"","checkItems");
		
		/* slack notification*/
		if(!empty($card)) {
			$slackAction = "deleteChecklistItemCard";			
			$slackDataParams['checklistItemName'] = $model->name;
			$this->sendSlack($slackAction, $card, $slackDataParams);
		}		
		
        Yii::$app->getResponse()->setStatusCode(204);
		Card::deleteCache($checklist->idCard, $checklist->idBoard);
		Lists::deleteCache($checklist->id);
    }

    public function actionCreateComment($id)
    {
        $model = new Comments([
            'scenario' => Model::SCENARIO_DEFAULT
        ]);
        $model->idCard = $id;
        $model->idMember = userIdentity()->getId();
        $model->addedDate = time();
        $model->load(request()->getBodyParams(), '');
        if ($model->save()) {
			$card = $this->setLastUpdated($id);
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }

		$idMembers = BoardMemberships::find()->select('idMember')->where(["idBoard"=>$card->idBoard])->column();
		if(!empty($idMembers)) {
			$params = array(
				'table'=>"card",
				'type'=> 10,
				'idTable'=>$id,
				'dataParams'=>array('action'=>'created'),
				'datapost'=>request()->getBodyParams(),
				'idComment'=> $model->id,
				'idSender'=>$idMembers,
				'idReceive'=>userIdentity()->getId(),
			);
			$this->_createNotification($params);
		}
		
		$request = request()->getBodyParams();
		
		/* slack notification*/
		if(!empty($card)) {
			$slackAction = "addCommentCard";		
			$slackDataParams['contentComment'] = $request['content'];
			$this->sendSlack($slackAction, $card, $slackDataParams);
		}		
		
		$this->updateBadges($id,0,"+","comments");
		Card::deleteCache($id, $card->idBoard);
        return $model;
    }

    public function actionUpdateComment($id, $idComment)
    {
        $model = Comments::findOne([
            'id' => $idComment,
            'idCard' => $id
        ]);
        if ($model === null) {
            throw new NotFoundHttpException;
        }
        $model->load(request()->getBodyParams(), '');
        if ($model->save() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }
		$this->setLastUpdated($id);
		$card  = $this->findModel($id);
		
		/* slack notification*/
		if(!empty($card)) {
			$slackAction = "updateCommentCard";			
			$request = request()->getBodyParams();
			foreach($request as $k => $v) {
				$slackDataParams['ColumChange'] = $k;
				$slackDataParams['ColumContent'] = $v;
			}
			$this->sendSlack($slackAction, $card, $slackDataParams);
		}
		
		
		Card::deleteCache($id, $card->idBoard);
        return $model;
    }

    public function actionDeleteComment($id, $idComment)
    {
        $model = Comments::findOne([
            'id' => $idComment,
            'idCard' => $id
        ]);
        if ($model === null) {
            throw new NotFoundHttpException;
        }
		$commend = $model->content;
		
        if ($model->delete() === false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }
		$card = $this->setLastUpdated($id);
		$this->updateBadges($id,0,"-","comments");
		
		/* slack notification*/
		if(!empty($card)) {
			$slackAction = "deleteCommentCard";			
			$slackDataParams['contentComment'] = $commend;
			$this->sendSlack($slackAction, $card, $slackDataParams);
		}
		
		
        Yii::$app->getResponse()->setStatusCode(204);		
		Card::deleteCache($id, $card->idBoard);
    }

    public function findModel($id)
    {
        $model = Card::findCacheOne($id);
        if ($model === null) {
            throw new NotFoundHttpException;
        }
        return $model;
    }
	
	public function actionIndex(){
		$idMember = userIdentity()->getId();
		 return new ActiveDataProvider([
            'query' => Card::find()->where([
                'id' => CardMembers::find()->select("idCard")->where(['idMember'=>$idMember])
            ]),
        ]);
		return $model;
	}
	
	public function actionUpdateCardAll($id){
		$request = request()->getBodyParams();
		$list  = Lists::findOne($id);
		if(!empty($request['idchange'])) 
		{
			Card::updateAll(['idList'=>$request['idchange']],'idList = '.$id);
			$card = Card::find()->where('idList = '.$request['idchange'])->all();
		} 
		else if (!empty($request['closed'])) 
		{
			Card::updateAll(['closed'=>1],'idList = '.$id);
			$card = Card::find()->where('idList = '.$id)->all();
		}
		Card::deleteCache( 0 ,$list->idBoard);
		return $card;
	}
	
	/** --TA--
     * CARD SELECTED
     *  param: listId old: $id
     *  request: listId NEW $idchange, $arr_check_card
     * return: $card
    **/
    public function actionUpdateCardSelected($id)
    {
        $request = request()->getBodyParams();
        $idchange = $request[0]['idchange'];
        $arr_check_card = $request[1];

        $list = Lists::findOne($id);
        if (!empty($idchange)) {
            $condition = ['and',
                ['=', 'idList', $id],
                ['in', 'id', $arr_check_card],
            ];
            Card::updateAll(['idList' => $idchange], $condition);
            $card = Card::find()->where('idList = ' . $idchange)->all();
        } else{
            $card = Card::find()->where('idList = ' . $id)->all();
        }
        Card::deleteCache(0, $list->idBoard);
        return $card;
    }

    /** --TA--
     * ANOTHER BOARD
     *  request: listId = $id, model object to = idList, idBoard ...
     *  return: $card
     **/
    public function actionUpdateCardAnotherBoard($id)
    {
        $request = request()->getBodyParams();
        $list = Lists::findOne($id);
        if ($request['idList'] != $id) {
            $antributes = [
                'idList' => $request['idList'],
                'idBoard' => $request['idBoard']
            ];
            Card::updateAll($antributes, 'idList = ' . $id);
            $card = Card::find()->where('idList = ' . $request['idList'])->all();
        } else $card = Card::find()->where('idList = ' . $id)->all();
        Card::deleteCache(0, $list->idBoard);
        return $card;
    }
	
	private function _createNotification($params) {
		$notification = new NotificationsAccessController();
		$notification ->createNotifyConformTable($params);
	}
	/*
		Card Badges
		params: typeBadges(interger), method(string), idCard or idChecklist(interger), model(array);
	*/
	private function updateBadges($idCard, $idChecklist=0,$sym="",$types="") {
		if($types == "checkItems") {
			$findChecklist = Checklist::findOne($idChecklist);
			$idCard = $findChecklist->idCard;
			$checkItems = db()->createCommand("SELECT checklist_item.* FROM checklist_item
				INNER JOIN checklist ON checklist.id = checklist_item.idChecklist
				WHERE checklist.idCard = $idCard
			")->queryAll();
			$card = Card::findOne($idCard);
			$badges = (array) json_decode($card->badges);
			$badges['checkItemsChecked'] = 0;
			$badges['checkItems'] = 0;
			foreach($checkItems as $checkItem) {
				if($checkItem['state'] == 1) {
					$badges['checkItemsChecked'] += 1;
				}
				$badges['checkItems'] += 1;
			}
			$card->badges = json_encode($badges);
			$card->save();
		} else {
			$card = Card::findOne($idCard);
			$badges = (array) json_decode($card->badges);
			$flagUpdate = 0;
			$types = explode(",",$types);
			foreach($types as $type) {
				if(!empty($sym)) {
					if($sym == '+') {
						$badges[$type] += 1;
						$flagUpdate  = 1;
					} else if ($badges[$type] > 0 && $sym == '-' ){
						$badges[$type] -= 1;
						$flagUpdate  = 1;
					}
				} else {
					$badges[$type] = $count;
				}
			}
			if($flagUpdate == 1) {
				$card->badges = json_encode($badges);
				$card->save();
			}
		}	
		Card::deleteCache($card->id, $card->idBoard);
	}
	
	public function actionBadgesAuto() 
	{
		$aId = userIdentity()->getId();
		if($aId == 1) {
			$cards = Card::find()->all();
			foreach ($cards as $key => $card) {
				$badges = array(
					'attachments'=>0,
					'checkItems'=> 0,
					'checkItemsChecked'=> 0,
					'comments'=> 0,
					'description'=> 0,
					'due'=>0,
					'subscribed'=> 0,
					'viewingMemberVoted'=>0 ,
					'votes'=>0,
				);
				$badges['attachments'] = Attachments::find()->where(['idCard'=>$card->id])->count();
				$badges['comments'] = Comments::find()->where(['idCard'=>$card->id])->count();
				$checklistitems = ChecklistItem::find()->innerJoin('checklist','checklist.id = checklist_item.idChecklist')->where(['checklist.idCard'=>$card->id])->all();
				if($checklistitems) {
					foreach ($checklistitems as $checkItem) {
						$badges['checkItems'] += 1;
						if($checkItem->state == 1) {
							$badges['checkItemsChecked'] += 1;
						}
					}
				}
				$cardsave = Card::findOne($card->id);
				$cardsave->badges = json_encode($badges);
				$cardsave->save();
				Card::deleteCache($cardsave->id, $cardsave->idBoard);
			}
		}
	}
	
	public function actionCopyCard($id) {
		$request = request()->getBodyParams();
		
		$card = Card::findOne($id);
		/* Create card  */
		$cardnew = new Card($card);
		$cardnew->load($request,"");
		$cardnew->name = StringHelper::StrRewrite($cardnew->displayName);
		$cardnew->id = null;
		// access badges
		$badges = json_decode($cardnew->badges);
		$badges->comments = 0;
		$cardnew->badges = json_encode($badges);		
		$cardnew->save();
		
		/* add attachment */
		$attachments = Attachments::find()->where(['idCard' => $id])->all();
		$copy = new FileHelper();
		$uploadForm = new UploadForm();

		foreach($attachments as $attachment) { //get list attachments insert
			$attachmentNew = new Attachments($attachment);
			$cardActiveCoverAttachment = 0;
			/* access copy file*/
			$mimeTypes = array("image/jpeg","image/gif",'image/png','image/bmp'); // config image
			
			$pathUrl= $uploadForm->getAlias(str_replace("assets/img/","",$attachmentNew->url));
			$attachmentNew->url = $uploadForm->copyFile($pathUrl);
			if(in_array($attachmentNew->mimeType,$mimeTypes)) { // check image, copy image thumb 
				$previews	= json_decode($attachmentNew->previews);
				if(!empty($previews)){ 
					foreach ($previews as $k => $v) {
						$path = $uploadForm->getAlias(str_replace("assets/img/","",$v->url));
						$previews[$k]->url = $uploadForm->copyFile($path,'thumbnail');
					}
					$attachmentNew->previews = json_encode($previews);
				}
			}
			if($attachmentNew->id == $cardnew->idAttachmentCover) {
				$cardActiveCoverAttachment = 1;
			}
			/* access save attachment*/	
			$attachmentNew->id = "";
			$attachmentNew->idCard = $cardnew->id;
			$attachmentNew->save();
			if($cardActiveCoverAttachment > 0) {
				$cardnew->idAttachmentCover = $attachmentNew->id;
				$cardnew->save();
			}			
		}		
		/* add member */
		$cardmembers = CardMembers::find()->where(['idCard'=>$id])->all();
		if($cardmembers) {
			foreach ($cardmembers as $cardmember) {
				$cardmemberNew = new CardMembers($cardmember);
				$cardmemberNew->idCard = $cardnew->id;
				$cardmemberNew->save();
			}
		}
		/* check list and checklist item*/
		$findChecklist = Checklist::find()->where(['idCard'=>$id])->all();
		foreach($findChecklist as $check) {
			$checkListNew = new Checklist($check);
			$checkListNew->id	  = "";
			$checkListNew->idCard = $cardnew->id;
			$checkListNew->save();
			$checkitems = ChecklistItem::find()->where(['idChecklist' => $check->id])->all();
			if(!empty($checkitems)) {
				foreach( $checkitems as $checkItem) {
					$checkItemNew = new ChecklistItem($checkItem);
					$checkItemNew->id = "";
					$checkItemNew->idChecklist = $checkListNew->id;
					$checkItemNew->save();
				}
			}
		}
		Card::deleteCache($cardnew->id, $card->idBoard);
		return $cardnew;
	}
	
	private function setLastUpdated($idCard) {
		$model = Card::findOne($idCard);
		$model->lastUpdated = time();
		$model->save();
		Card::deleteCache($model->id, $model->idBoard);
		return $model;
	}
	
	private function sendSlack($slackAction, $cardFind, $slackDataParams=array()) {
		$slackBoard = Board::findOne($cardFind->idBoard);
		$slackList  = Lists::findOne($cardFind->idList);
		$slackDataParams['UserdisplayName'] = userIdentity()->displayName;
		$slackDataParams['cardName'] = $cardFind->displayName;
		$slackDataParams['idCard'] = $cardFind->id;
		$slackDataParams['listName'] = $slackList->name;
		SlackMessengerHelper::sendSlackMessenger($slackAction, $slackBoard, $slackDataParams);
	}


	
/**
 * Auth: MinhAnh
 * Email: minhanh.itqn@gmail.com
 */


}