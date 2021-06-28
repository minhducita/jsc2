<?php
/**
 * Author: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
namespace api\common\models;

use yii\base\Model;
use yii\imagine\Image;
use Yii;

class UploadForm extends Model
{
    public $file;

    public $uploadFolder = '@frontend/web/assets/img';
	
	public $dirFolderImg = '/assets/img';

    public function rules()
    {
        return [
            [['file'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg, gif, jpeg', 'maxSize' => 1024 * 1024 * 2, 'checkExtensionByMimeType'=>false],
        ];
    }
	public function getAlias($urlImg="") {
		return Yii::getAlias($this->uploadFolder.$urlImg);
	}
	
	public function copyFile($path,$type='normal') {
		/* copy url file img new*/
		$path1 = explode(".",$path);
		$path2 = explode("/",$path1[0]);
		$nameImg = $path2[count($path2)-1];
		if($type == 'thumbnail') {
			$nameImg = explode("-",$nameImg);
			$pathnew = str_replace($nameImg[0],md5($nameImg[0].time()),$path1[0]).".".$path1[1];
		} else {
			$pathnew = str_replace($nameImg,md5($nameImg.time()),$path1[0]).".".$path1[1];
		}
		if(file_exists($path))
			copy($path,$pathnew);
		return $this->dirFolderImg.str_replace($this->getAlias(),"",$pathnew);
		/* end copy file image*/
	}
    public function upload($path = null,$name="")
    {
		$pathMkdir = $path;

        if (!empty($path)) {
            $path = Yii::getAlias($this->uploadFolder) . '/' . rtrim($path, '/');
        } else {
            $path = Yii::getAlias($this->uploadFolder);
        }

		$pathArray = explode("/",$pathMkdir);
		$linkMkdir = "";	
		foreach($pathArray as $value) {
			$linkMkdir .= "/".$value;
			if(!is_dir( Yii::getAlias($this->uploadFolder).$linkMkdir)) {
				mkdir(Yii::getAlias($this->uploadFolder).$linkMkdir , 0777, true);
				chmod(Yii::getAlias($this->uploadFolder).$linkMkdir , 0777);
			}
		}

        if ($this->validate()) {
			$nameImg = !empty($name)?$name: md5($this->file->baseName);
			$linkSaveImg = $path . '/' . $nameImg . '.' . $this->file->extension;
            $this->file->saveAs($linkSaveImg);
            return true;
        } else {
            return false;
        }
    }
}