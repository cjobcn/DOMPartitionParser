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
 * Class DataExtractor
 * @package app\index\Parser
 */
class DataExtractor {

    //属性到提取方法的映射关系
    protected $MethodMap = array(
        'email' => 'email',
    );

    /**
     * 提取数据
     * @param $keyName   string  属性名
     * @param $originData  mixed 源数据
     * @return array|bool  成功返回键值对，失败返回false
     */
    public function extract($keyName,$originData) {
        $map = $this->MethodMap;
        $method = $map[$keyName];
        if($method){
            $value = $this->$method($originData);
            //提取成功，返回键值对
            if($value !== false)
                return array($keyName, $value);
        }
        //提取失败
        return false;

    }

    //电子邮件提取
    public function email($originData) {
        $pattern = '/\w+(?:[-+.]\w*)*@\w+(?:[-.]\w+)*\.\w+(?:[-.]\w+)*/';
        if(preg_match($pattern, $originData, $match)) {
            return $match[0];
        }else{
            return false;
        }
    }


}
