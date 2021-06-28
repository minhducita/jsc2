<?php
/**
 * author: Ly Phuoc Nam
 * date update: 16/08/2016
 */
namespace api\common\models;

use yii\base\Model;
use Yii;
use yii\helpers\Url;

class Memcache extends Model
{
	public $data = array(); // class query;
	public $dataOne = array();
	public $id = 0;  // get find cache one
	public $key = ""; // get find cache one ex: board || organization ...
	public $keylist = "listSelect"; // list select,
	private $_findkey = array(); // delete cache
	public $duration = 806400 ; // set time cache
	private $_listRedisDeleteCache = array();
	
	/*** desc: config cache page ***/
	private $_listAction = array('view', 'index');

	public function setdelRedisCache ($value) {
		if(!empty($value)) {
			if(is_array($value)) {
				$this->_listRedisDeleteCache = $value;
			} else {
				$this->_listRedisDeleteCache[] = $value;
			}
			
		}
	}
	
	public function setFindKey($keyitem, $key="`idMember`='$0'") {
		if(is_array($keyitem)) {
			foreach($keyitem as $i => $v) {
				$this->_findkey[] = str_replace("$".$i,$v,$key);
			}
		} else {
			$this->_findkey[] = str_replace("$0",$keyitem,$key);
		}
	}
	
	/**
	 * desc: findCache get data cache
	 * Param:
		 - $data: (object|array) Class Query
	 */
	public function findCacheAll ($relation = 0) 
	{
		$controllerName = Yii::$app->controller->id;
		$data = $this->data;
		// if(!in_array($controllerName, $this->listRedisCache)) {
			if($relation == 0)
				return $data->all();
			else
				return $data;
		// }
		
		$memcache = Yii::$app->rediscache;
		//config get cache
		$key = $data->createCommand()->getRawSql();			
		$keyarray = explode("FROM", $key);
		$count  = count($keyarray);		
		if( $count  > 2){
			unset($keyarray[0]);
			$key =  implode("FROM", $keyarray);
			$this->keylist = explode("WHERE", $key);
			$this->keylist = $this->keylist[0];
		} else {
			$key = $keyarray[1];
			$this->keylist = $key;
		}
		
		$keymd5 = md5($key);		
		//get and set list key cache 
		$listkey = $memcache->get($this->keylist);
		$listkey = !empty($listkey) ? unserialize($listkey) : array();
		
		if(!in_array($key, $listkey)) {
			$listkey[] = $key;
			$memcache->set($this->keylist, serialize($listkey)); 
		} 
		
		// return cache;
		$getCache = $memcache->get($keymd5);
		if(empty($getCache)) {
			$value = $data->all();
			if(!empty($value)) {
				$memcache->set($keymd5, $value);
				return $value;
			} else {
				$memcache->set($keymd5, $data);
			}
		}
		else {
			return $getCache;
		}
	}
	
	/**
	 * desc: find and save by id
	 * param: $id
	 **/
	public function findCacheOne($relation = 0) {
		//config get cache
		if($relation == 0)
			return $this->data->one();
		else
			return  $this->data;
		
		$memcache = Yii::$app->rediscache;
		$key = md5($this->key.$this->id);
		$getCache = $memcache->get($key);
		if(!empty($getCache)) {
			return $getCache;
		} else if ($this->id != 0 && is_numeric($this->id)) {
			$value = $this->data->one();
			$memcache->set($key, $value);
			return $value;
		} 
	}
	
	/**
	 * desc: delete cache
	 * param: 
		- id: id when update row query
	 */
	public function deleteCache () 
	{
		//config delete cache
		// $keylist  = $this->keylist; //'keyOrganizations';
		// $memcache = Yii::$app->rediscache;

		// if(!empty($this->_findkey) and count($this->_findkey) > 1) {
			// get list cache
			// $listkey = $memcache->get($keylist);
			// $listkey = !empty($listkey) ? unserialize($listkey) : array();
				
			// delete cache 
			// $listkeynew = array();
			// foreach ($listkey as $key) {
				// $keymd5 = md5($key);
				// foreach($this->_findkey as $findkey) {
					// if(strpos($key, $findkey) !== false) {
						// $memcache->delete($keymd5);
					// } else {
						// $listkeynew[] = $key;
					// }
				// }			
			// }
			
			// update list key 
			// $memcache->set($keylist, serialize($listkeynew));
		// }
		
		// delete cache one 
		// if($this->id != 0 && is_numeric($this->id)) { 
			// $keydel = md5($this->key.$this->id);
			// $memcache->delete($keydel);	
		// }			
	}
	
	/**
	 * Desc: Cache Page
	 * $param: array (
			'actionName' => "",
			'accessToken' => "",
			'controllerName'= "",
		)
	 * $result : array,
	 */
		
	/* find cache and set cache*/
	public function setCachePage($param = array(), $result) {
		if(!empty($result) && !empty($param['actionName']) && in_array($param['actionName'], $this->_listAction) && $param['controllerName'] && $param['accessToken']) { 
			
			$memcache = Yii::$app->cache;
			
			$member = Member::findIdentityByAccessToken($param['accessToken']);

			$key = md5("member".$member->id.$param['controllerName'].$param['actionName']);
			$url = md5($param['url']); 
			
			$keyurl = $memcache->get($key);		
			$keyurl = !empty($keyurl) ? $keyurl : array();		
			if(!in_array($url, $keyurl)) {
				$keyurl[] = $url;
				$memcache->set($key, $keyurl);
 			}	
			$memcache->set($url, serialize(json_encode($result)), $this->duration);
			return $result;
		}
		
	}
	
	public function findCachePage($param = array()) 
	{
		$memcache = Yii::$app->cache;
		$key = md5($param['url']);		
		$getCache = $memcache->get($key);
		if(!empty($getCache)) {
			return unserialize($getCache);
		}  	
		return ;
	}
 	
	/* delete cache */
	public function deleteCachePage($idMember, $controllerName) {
		$memcache = Yii::$app->cache;
		if(!empty($idMember) && !empty($controllerName) && (is_array($idMember) || is_object($idMember))) {
			foreach($idMember as $item) {
				foreach($this->_listAction as $action) {
					$key = md5("member".$item.$controllerName.$action);
					$list  = $memcache->get($key);
					if(!empty($list)) {
						foreach($list as $keyurl) {
							$memcache->delete($keyurl);
						}
					}
				}
			}
		} else if(!empty($idMember) && !empty($controllerName)) {
			if(!empty($getCache)) {
				foreach($this->_listAction as $action) {
					$key = md5("member".$item.$controllerName.$action);
					$list = $memcache->get($key);
					foreach($list as $keyurl) {
						$memcache->delete($keyurl);
					}
				}
			}
		}
	}
}
