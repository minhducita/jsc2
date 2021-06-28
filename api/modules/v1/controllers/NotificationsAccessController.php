<?php
/**
 * Auth: Lý Phước Nam
 * Email: lynam1990@gmail.com
 */
namespace api\modules\v1\controllers;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

use api\common\models\Member;
use api\modules\v1\models\Notifications;
use api\modules\v1\models\NotificationsMember;
use api\modules\v1\models\Organization;
use api\modules\v1\models\Board;
use api\modules\v1\models\Card;
use api\modules\v1\models\Lists;

class NotificationsAccessController 
{
	public function createNotifyConformTable($params) {
		/*param = [table,idTable,type,idReceive,idSender,datapost]*/
		$datajson = $this->getModelTable($params['table'],$params['idTable'],$params['idSender']);

		if(!empty($datajson['idBoard'])) {
			$board = Board::findOne($datajson['idBoard']);
			$datajson['dataBoard'] = $board->getAttributes();
		} 
		
		if($params['table'] == 'board') {
			$datajson['idBoard'] = $datajson['id'];
		} else if($params['table'] == 'card') {
			$datajson['idCard'] = $datajson['id'];
		}
		
		if(!empty($params['idComment'])) {
			$datajson['idComment'] = $params['idComment'];
		}
		
        $datajson['datapost'] = $params['datapost'];
		if(!empty($params['dataParams'])) {
			$datajson['dataParams'] = $params['dataParams'];
		}
		
		$datajson = json_encode($datajson);
		if(!empty($params["idSender"])) {
			$notification = new Notifications();
			$notification->type = $params['type'];
			$notification->data = $datajson;
			$notification->idReceive = $params['idReceive'];
			$notification->addedDate = time();
			$notification->name = $params['table'];
			$notification->idEffectMember = !empty($params['idEffectMember'])?$params['idEffectMember']:0;
			if(is_array($params['idSender'])) {
				if($notification->save()) {
					$insertactive = 0;
					$valueInsert = " INSERT INTO notifications_member (`idNotification`,`read`,`idMember`) VALUES ";
					foreach($params['idSender'] as $v) {
						if($v !== $notification->idReceive){
							$insertactive = 1;
							$valueInsert .= "(".$notification->id.",0,".$v."),";
						}
					}
					if($insertactive == 1) {
						db()->createCommand(trim($valueInsert,","))->execute();
					} 
				}
			} else if($params['idSender'] != $notification->idReceive) {
				if($notification->save()) {
					$notifyMember = new NotificationsMember();
					$notifyMember->idNotification = $notification->id;
					$notifyMember->read = 0;
					$notifyMember->idMember = $params['idSender'];
					$notifyMember->save();
				}				
			}
		}
	}
	private function getModelTable($table="", $idTable=0, $idSender=0) {
		if(empty($table) || empty($idTable)) {
			return false;
		}		
		$query = "";
		switch($table) {
			case 'organization':
				if(empty($idSender)) {
					$query = ',(select GROUP_CONCAT(idMember) from organization_member  where  idOrganization = organization.id) as listmember';
				}
				return Organization::find()->select("* ".$query)->where("id = ".$idTable)->one()->getAttributes();
			break;
			
			case 'board':
				if(empty($idSender)) {
					$query = ',(select GROUP_CONCAT(idMember) from board_member  where  idBoard = board.id) as listmember';
				}
				return Board::find()->select("* ".$query)->where("id = ".$idTable)->one()->getAttributes();
			break;
			
			case 'card':
				if(empty($idSender)) {
					$query = ',(select GROUP_CONCAT(idMember) from card_members  where  idCard = card.id) as listmember';
				}
				return Card::find()->select("* ".$query)->where("id = ".$idTable)->one()->getAttributes();
			break;
			
			case 'list':
				return Lists::find()->select("* ".$query)->where("id = ".$idTable)->one()->getAttributes();
			break;
			
			case 'checklist':
				$sql = "SELECT checklist.*,card.id as cardId,card.displayName as cardDisplayName,card.name as cardName
						From checklist INNER JOIN card ON checklist.idCard = card.id Where checklist.id = ".$idTable;
				return db()->createCommand($sql)->queryOne();
			break;
			
			case 'checklistitem':
				$sql = "SELECT checklist_item.*,card.id as cardId,card.displayName as cardDisplayName,card.name as cardName,checklist.idBoard
						From checklist_item 
						INNER JOIN checklist ON checklist.id = checklist_item.idChecklist
						INNER JOIN card ON checklist.idCard = card.id Where checklist_item.id = ".$idTable;
				return db()->createCommand($sql)->queryOne();
			break;
		}
	}
}
?>