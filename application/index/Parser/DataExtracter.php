<?php
/**
 * Created by PhpStorm.
 * User: DFFuture
 * Date: 2017/5/5
 * Time: 21:01
 */

namespace app\index\Parser;

/**
 * 数据提取器
 * Class DataExtracter
 * @package app\index\Parser
 */
class DataExtracter {

    //属性到提取方法的映射关系
    protected $MethodMap = array(
        'email' => 'email',
    );

    public function extract($keyName,$originData) {
        $map = $this->MethodMap;
        $method = $map[$keyName];
        if($method){
            $value = $this->$method($originData);
            if($value)
                return array($keyName, $value);
        }
        //未提取
        return false;

    }

    //电子邮件
    public function email($originData) {
        $pattern = '/\w+(?:[-+.]\w*)*@\w+(?:[-.]\w+)*\.\w+(?:[-.]\w+)*/';
        if(preg_match($pattern, $originData, $match)) {
            return $match[0];
        }else{
            return false;
        }
    }


}