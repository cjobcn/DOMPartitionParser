<?php
namespace app\index\Parser;

class Utility {
    
    /**
    * 字符串半角和全角间相互转换
    * @param string $str 待转换的字符串
    * @param int  $type TODBC:转换为半角；TOSBC，转换为全角
    * @return string 返回转换后的字符串
    */
    static function convertStrType($str, $type) {
        $sbc = array( 
            '０' , '１' , '２' , '３' , '４' , 
            '５' , '６' , '７' , '８' , '９' , 
            'Ａ' , 'Ｂ' , 'Ｃ' , 'Ｄ' , 'Ｅ' , 
            'Ｆ' , 'Ｇ' , 'Ｈ' , 'Ｉ' , 'Ｊ' , 
            'Ｋ' , 'Ｌ' , 'Ｍ' , 'Ｎ' , 'Ｏ' , 
            'Ｐ' , 'Ｑ' , 'Ｒ' , 'Ｓ' , 'Ｔ' , 
            'Ｕ' , 'Ｖ' , 'Ｗ' , 'Ｘ' , 'Ｙ' , 
            'Ｚ' , 'ａ' , 'ｂ' , 'ｃ' , 'ｄ' , 
            'ｅ' , 'ｆ' , 'ｇ' , 'ｈ' , 'ｉ' , 
            'ｊ' , 'ｋ' , 'ｌ' , 'ｍ' , 'ｎ' , 
            'ｏ' , 'ｐ' , 'ｑ' , 'ｒ' , 'ｓ' , 
            'ｔ' , 'ｕ' , 'ｖ' , 'ｗ' , 'ｘ' , 
            'ｙ' , 'ｚ' , '－' , '　' , '：' ,
            '．' , '，' , '／' , '％' , '＃' ,
            '！' , '＠' , '＆' , '（' , '）' ,
            '＜' , '＞' , '＂' , '＇' , '？' ,
            '［' , '］' , '｛' , '｝' , '＼' ,
            '｜' , '＋' , '＝' , '＿' , '＾' ,
            '￥' , '￣' , '｀'
         );
    
        $dbc = array( //半角
            '0', '1', '2', '3', '4', 
            '5', '6', '7', '8', '9', 
            'A', 'B', 'C', 'D', 'E', 
            'F', 'G', 'H', 'I', 'J', 
            'K', 'L', 'M', 'N', 'O', 
            'P', 'Q', 'R', 'S', 'T', 
            'U', 'V', 'W', 'X', 'Y', 
            'Z', 'a', 'b', 'c', 'd', 
            'e', 'f', 'g', 'h', 'i', 
            'j', 'k', 'l', 'm', 'n', 
            'o', 'p', 'q', 'r', 's', 
            't', 'u', 'v', 'w', 'x', 
            'y', 'z', '-', ' ', ':',
            '.', ',', '/', '%', ' #',
            '!', '@', '&', '(', ')',
            '<', '>', '"', '\'','?',
            '[', ']', '{', '}', '\\',
            '|', '+', '=', '_', '^',
            '￥','~', '`'   
        );
        if($type == 'TODBC'){
            return str_replace( $sbc, $dbc, $str ); //半角到全角
        }elseif($type == 'TOSBC'){
            return str_replace( $dbc, $sbc, $str ); //全角到半角
        }else{
            return $str;
        }
    }

    /**
     * 读取文档
     * @param $path
     * @return bool|string
     */
    static public function readDocument($path) {
        $path = iconv("UTF-8", "GBK", $path);
        $content = file_get_contents($path);
        return $content;
    }

    //转码为UTF-8
    static public function convert2UTF8($content) {
        $encodingList = array('UTF-8','GBK','GB2312');
        $encoding = mb_detect_encoding($content,$encodingList,true);

        if(!$encoding){
            $content = iconv('UCS-2', "UTF-8", $content);
            if(!$content) return false;       
        }elseif($encoding != 'UTF-8'){
            $content = mb_convert_encoding($content,'UTF-8',$encoding); 
        }
        $content = str_ireplace('gb2312','UTF-8',$content);
        return $content;
    }

    //字符串转时间戳
    static public function str2time($str) {
        if(!is_string($str)) return false;
        //如果是现在或至今，取时间戳最大值
        if(preg_match('/^至今|现在$/',$str))
            return $UP_TO_NOW = 2147483647;
        $str = preg_replace('/\D+/', '-', $str);
        $str = preg_replace(array('/^-/','/-$/'), '', $str);
        return strtotime($str);
    }
}
