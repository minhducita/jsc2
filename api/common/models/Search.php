<?php
namespace api\common\models;

use yii\base\Model;
use Yii;

use api\modules\v1\models\Organization;
use api\modules\v1\models\OrganizationMemberships;

use api\modules\v1\models\Board;
use api\modules\v1\models\BoardMemberships;

use api\modules\v1\models\Card;

class Search extends Model
{
	public $nameSearch;
	public $idMember;
	
	public function rules() {
		 return [
			['nameSearch','string'],
			['idMember','integer']
		 ];
	}
	
	public function fields() {
		return ['organizations', 'cards', 'boards'];
	}
	
	public function getOrganizations() {
		return  Organization::find()->where([
					'id'=>organizationMemberships::find()->select('idOrganization')->where(['idMember'=>$this->idMember])
				])
				->andFilterWhere(['or',['like', 'displayName', $this->nameSearch], ['like', 'name', $this->nameSearch], ['like', 'desc', $this->nameSearch]])
				->limit(10)
				->all();
	}
	
	public function getBoards() {
		return  Board::find()
				->where([
					'id'=>BoardMemberships::find()->select('idBoard')->where(['idMember'=>$this->idMember])
				])
				->andFilterWhere(['or',['like', 'displayName', $this->nameSearch], ['like', 'name', $this->nameSearch], ['like', 'desc', $this->nameSearch]])
				->limit(10)
				->asArray()
				->all();
	}
	
	public function getCards() {
		$cards = Card::find()
				->with(['attachments','memberShips', 'lists'])
				->andWhere([
						"idBoard" => BoardMemberships::find()->select('idBoard')->where(
									[
									  'idMember' => $this->idMember,
									  'idBoard' => Board::find()->select('id')->where(['closed'=>0])
									])
				])
				->andWhere(['closed'=>0])
				->andFilterWhere(['or',['like', 'displayName', $this->nameSearch], ['like', 'name', $this->nameSearch], ['like', 'desc', $this->nameSearch], ['like', 'id', $this->nameSearch]])
				->limit(10)
				->all();
		$cardExpend = $cards;
		if(count($cards) > 0) {
			$key = 0;
			foreach($cards as $card) {
				$cardExpend[$key] = $card->getAttributes();
				$cardExpend[$key]['badges'] = json_decode($cardExpend[$key]['badges']);
				$cardExpend[$key]['idLabels'] = json_decode($cardExpend[$key]['idLabels']);
				if(!empty($card->attachments)){
					foreach($card->attachments as $attachment) {
						if($card->idAttachmentCover == $attachment->id) {
							$attachAttributes = $attachment->getAttributes();
							$attachAttributes['previews'] = json_decode($attachAttributes['previews']);
							$cardExpend[$key]['attachments'] = $attachAttributes;
						}
					}
				} else {
					$cardExpend[$key]['attachments'] = [];
				}
				if(!empty($card->memberShips)) {
					foreach($card->memberShips as $member) {
						$typeimg = $member->typeimg;
						$arrayFilters = array_filter($member->getAttributes());
						$arrayFilters['typeimg'] = $typeimg;
						$cardExpend[$key]['members'][] = $arrayFilters;
					}
				} else {
					$cardExpend[$key]['members'] = [];
				}
				if(!empty($card->lists)) {
					$cardExpend[$key]['list']  = $card->lists->getAttributes();
				} else {
					$cardExpend[$key]['list'] = [];
				}
				$key++;	
			}
		}
		return $cardExpend;
	}
	public function getCardsId() {
		$cards = Card::find()
				->with(['attachments','memberShips', 'lists'])
				->andWhere([
						"idBoard" => BoardMemberships::find()->select('idBoard')->where(
									[
									  'idMember' => $this->idMember,
									  'idBoard' => Board::find()->select('id')->where(['closed'=>0])
									])
				])
				->andWhere(['closed'=>0])
				->andWhere(['id' => $this->nameSearch])
				->limit(10)
				->all();
		$cardExpend = $cards;
		if(count($cards) > 0) {
			$key = 0;
			foreach($cards as $card) {
				$cardExpend[$key] = $card->getAttributes();
				$cardExpend[$key]['badges'] = json_decode($cardExpend[$key]['badges']);
				$cardExpend[$key]['idLabels'] = json_decode($cardExpend[$key]['idLabels']);
				if(!empty($card->attachments)){
					foreach($card->attachments as $attachment) {
						if($card->idAttachmentCover == $attachment->id) {
							$attachAttributes = $attachment->getAttributes();
							$attachAttributes['previews'] = json_decode($attachAttributes['previews']);
							$cardExpend[$key]['attachments'] = $attachAttributes;
						}
					}
				} else {
					$cardExpend[$key]['attachments'] = [];
				}
				if(!empty($card->memberShips)) {
					foreach($card->memberShips as $member) {
						$typeimg = $member->typeimg;
						$arrayFilters = array_filter($member->getAttributes());
						$arrayFilters['typeimg'] = $typeimg;
						$cardExpend[$key]['members'][] = $arrayFilters;
					}
				} else {
					$cardExpend[$key]['members'] = [];
				}
				if(!empty($card->lists)) {
					$cardExpend[$key]['list']  = $card->lists->getAttributes();
				} else {
					$cardExpend[$key]['list'] = [];
				}
				$key++;	
			}
		}
		return $cardExpend;
	}
}
