<?php 
namespace common\helpers;

use Yii;

class SlackMessengerHelper extends \yii\base\Component
{
	public static function sendSlackMessenger($module, $board, $param) {
		
		$messenger = "";
		$urlIdCard = !empty($param['idCard']) ? "?card=".$param['idCard'] : "";
		$url = Yii::$app->params['appDomain']."#/b/".$board->id."/".$board->name.$urlIdCard; 
		if(!empty($board->slackChanel)) {
			$param['cardName']  = (!empty($param['cardName']))? strip_tags($param['cardName']) : "";
			switch($module) {
				case "createCard" :
					$messenger = $param['UserdisplayName']. " created '".$param['cardName']. "' CARD (".$param['idCard'].") in '".$param['listName']."' lIST.";
				break;
				case "deleteCard" :
					$messenger = $param['UserdisplayName']. " deleted '".$param['cardName']. "' CARD (".$param['idCard'].") in '".$param['listName']."' LIST.";
				break;
				case "updateCard": 
					$messenger =  $param['UserdisplayName']. " updated ".$param['ColumChange']. " CARD (".$param['idCard'].") ' content '".$param['ColumContent']."' in '".$param['listName']."' LIST.";
				break;
				case "changedDueCard": 
					$messenger = $param['UserdisplayName']. " changed due date (".$param['ColumContent']. ") to the CARD(".$param['idCard'].") '".$param['cardName']."' in '".$param['listName']."' LIST.";
				break;
				case "addedDueCard": 
					$messenger = $param['UserdisplayName']. " created due date (".$param['ColumContent'].") to the card(".$param['idCard'].") '".$param["cardName"]."' in '".$param['listName']."' LIST.";
				break;
				// move card
				case "moveCard":
					$messenger = $param['UserdisplayName']." moved the CARD(".$param['idCard'].") '".$param['cardName']."' from '".$param['listOld']['name']."' LIST to '". $param['listNew']['name']."' LIST.";
				break;
				case "changedPositionCard": 
					$messenger = $param['UserdisplayName']. " changed position the Card(".$param['idCard'].") '".$param["cardName"]."' in '".$param['listName']."' lIST. ";
				break;
				// memeber
				case "createMemberCard":
					$messenger = $param['UserdisplayName']. " added ".$param['memberName']." to the  Card (".$param['idCard'].") '".$param['cardName']."' in '".$param['listName']."' LIST. ";
				break;
				case "deleteMemberCard":
					$messenger = $param['UserdisplayName']. " removed ".$param['memberName']." to the  Card (".$param['idCard'].") '".$param['cardName']."' in '".$param['listName']."' LIST. ";
				break;
				// attachement
				case "addFileCard":
					$messenger = $param['UserdisplayName']. " added ".$param['fileType']." '".$param['fileName']."' to the Card (".$param['idCard'].") '".$param['cardName']."' in '".$param['listName']."' LIST. ";
				break;
				case "removeFileCard":
					$messenger = $param['UserdisplayName']. " removed ".$param['fileType']." '".$param['fileName']."' to the Card (".$param['idCard'].") '".$param['cardName']."' in '".$param['listName']."' LIST. ";
				break;
				// checklist 
				case "addChecklistCard":
					$messenger = $param['UserdisplayName']. " added checklist '".$param['checklistName']."' to the Card (".$param['idCard'].") '".$param['cardName']."' in '".$param['listName']."' LIST.";
				break;
				case "deleteChecklistCard":
					$messenger = $param['UserdisplayName']. " removed checklist '".$param['checklistName']."' to the Card (".$param['idCard'].") '".$param['cardName']."' in '".$param['listName']."' LIST.";
				break;
				case "updateChecklistCard": 
					$messenger =  $param['UserdisplayName']. " updated ".$param['ColumChange']." checklist with  content '".$param['ColumContent']."' to the card (".$param['idCard'].") '".$param['cardName']."' in '".$param['listName']."' LIST.";
				break;
				// checklist item 
				case "addChecklistItemCard":
					$messenger = $param['UserdisplayName']. " added checklist item '".$param['checklistItemName']."' to the Card (".$param['idCard'].") '".$param['cardName']."' in '".$param['listName']."' LIST.";
				break;
				case "deleteChecklistItemCard":
					$messenger = $param['UserdisplayName']. " removed checklist item '".$param['checklistItemName']."' to the Card (".$param['idCard'].") '".$param['cardName']."' in '".$param['listName']."' LIST. ";
				break;
				case "updateChecklistItemCard": 
					$messenger =  $param['UserdisplayName']. " updated checklist item ".$param['ColumChange']." with  content '".$param['ColumContent']."' to the card (".$param['idCard'].") '".$param['cardName']."' in '".$param['listName']."' LIST.";
				break;
				// command item  
				case "addCommentCard":
					$messenger = $param['UserdisplayName']. " added comment '".$param['contentComment']."'  to the Card (".$param['idCard'].") '".$param['cardName']."' in '".$param['listName']."'LIST. ";
				break;
				case "deleteCommentCard":
					$messenger = $param['UserdisplayName']. " removed comment '".$param['contentComment']."' to the Card (".$param['idCard'].") '".$param['cardName']."' in '".$param['listName']."' LIST. ";
				break;
				case "updateCommentCard": 
					$messenger =  $param['UserdisplayName']. " updated comment ".$param['ColumChange']." with content '".$param['ColumContent']."' to the card (".$param['idCard'].") '".$param['cardName']."' in '".$param['listName']."' LIST.";
				break;
				
				// Card Label
				case "addIdLabelCard":
					$param["labelName"] = !empty($param["labelName"])? "'".$param["labelName"]."'" : "";
					$messenger = $param['UserdisplayName']. " added label '".$param["labelName"]."' to the Card(".$param['idCard'].") '".$param['cardName']."' in '".$param['listName']."' LIST. ";
				break;
				case "deleteIdLabelCard":
					$param["labelName"] = !empty($param["labelName"])? "'".$param["labelName"]."'" : "";
					$messenger = $param['UserdisplayName']. " removed label '".$param["labelName"]."' to the Card(".$param['idCard'].") '".$param['cardName']."' in '".$param['listName']."' LIST. ";
				break;
				
				// LISTs
				case "createLists":
					$messenger = $param["UserdisplayName"]." created '".$param['listName']."' LIST";
				break;
				case "updateLists":
					$messenger = $param["UserdisplayName"]." updated ".$param['ColumChange']." LIST with content '".$param['ColumContent']."'";
				break;
				case "sortLists":
					$messenger = $param["UserdisplayName"]." changed position '".$param['listName']."' LIST";
				break;
			};
			
			$slack = Yii::$app->slack;
			$slack->defaultChannel = $board->slackChanel;
			$slack->send('Board "'.$board->displayName.'"', 'JSC', [
				[
				 'text' => $messenger." <".$url."| Click here>"			
				]
			]);
			return true;
		}
		return false;
	}
}