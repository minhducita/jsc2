<?php
namespace common\helpers;

class StringHelper extends \yii\base\Component
{
    public function printStr($string,$length = 0){
        $arrWords = explode(" ",$string);
        $stringLast = "";
        if(count($arrWords) <= $length){ $stringLast = $string;}
        else{
            for($i=0;$i<$length;$i++){	$stringLast .= $arrWords[$i]." ";}
            $stringLast  .= " ...";
        }return $stringLast;
    }

    public static function StrRewrite($str='', $string="default"){
		
        if($str != '') {
            #---------------------------------a
            $arr = array("á", "à", "ả", "ã", "ạ", "â", "ậ","ầ","ấ","ẩ","ẫ", "ă", "ặ", "ắ", "ằ", "ặ","ẳ","ẵ","Á", "À", "Ả", "Ã", "Ạ", "Â", "Ậ","Ấ","Ầ","Ẩ","Ẫ","Ă","Ắ","Ằ","Ẳ","Ẵ","Ặ");
            $str = str_replace($arr, "a", $str);
            #---------------------------------d
            $arr = array("đ","Đ");
            $str = str_replace($arr, "d", $str);
            #---------------------------------e
            $arr = array("é", "è", "ẻ", "ẽ", "ẹ", "ê", "ể","ế", "ề", "ệ","ễ","E","É","È","Ẻ","Ẽ","Ẹ","Ê","Ế","Ề","Ể","Ễ","Ệ");
            $str = str_replace($arr, "e", $str);
            #---------------------------------i
            $arr = array("í", "ì", "ỉ", "ĩ", "ị","Í","Ì","Ỉ","Ĩ","Ị");
            $str = str_replace($arr, "i", $str);
            #---------------------------------o
            $arr = array("ó", "ò", "ỏ", "õ", "ọ", "ô", "ố","ồ","ổ","ỗ","ộ","ơ","ớ","ờ","ở","ỡ","ợ","O","Ó","Ò","Ỏ","Õ","Ọ","Ô","Ố","Ồ","Ổ","Ỗ","Ộ","Ơ","Ớ","Ờ","Ở","Ỡ","Ợ");
            $str = str_replace($arr, "o", $str);
            #---------------------------------u
            $arr = array("ú", "ù", "ủ", "ũ", "ụ", "U","Ú","Ù","Ủ","Ũ","Ụ","ư","ứ","ừ","ử","ữ","ự","Ư","Ứ","Ừ","Ử","Ữ","Ự");
            $str = str_replace($arr, "u", $str);
            #---------------------------------y
            $arr = array("ý", "ỳ", "ỷ", "ỹ", "ỵ","Y","Ý","Ỳ","Ỷ","Ỹ","Ỵ");
            $str = str_replace($arr, "y", $str);
			#---------------------------------
			if($string == 'default')
				$str = strtolower($str);
            #---------------------------------
			$arr = array("?", "  "," ", "","\"", ".", "&quot;", "'", "&#039;","''","`","\'","\"","\\","\/","/","+","-","*","%","$","~","!","@","#","^","&","(",")","|",",",">","<", ":", '）', '（');
			$str = str_replace($arr, "-", $str);					

            $str = preg_replace('/[^a-zA-Z0-9一-龠ぁ-ゔァ-ヴー々〆〤０-９Ａ-ｚァ-ンｧ-ﾝﾞﾟ _\-\+\&]/','',$str);
            $str = str_replace("--","-",$str);
			$str = preg_replace('/\s\s+/', '', $str);
			
        }
        return $str;
    }
}
?>