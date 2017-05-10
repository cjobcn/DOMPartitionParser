<?php
/**
 * Created by PhpStorm.
 * User: DFFuture
 * Date: 2017/5/5
 * Time: 20:55
 */

namespace app\index\Parser;

/**
 * 数据转换器
 * Class DataConverter
 * @package app\index\Parser
 */
class DataConverter {

    //属性到转换方法的映射关系
    protected $MethodMap = array(
        'update_time' => 'str2time',
        'email'       => 'email',
    );

    /**
     * 数据转换
     * @param $keyName string 属性名
     * @param $rawValue mixed 属性值
     * @return array  返回格式：array('属性名', '属性值')
     */
    public function convert($keyName, $rawValue) {
        $map = $this->MethodMap;
        $method = $map[$keyName];
        if($method){
            $value = $this->$method($rawValue);
            return array($keyName, $value);
        }else{
            //原值返回
            return array($keyName, $rawValue);
        }
    }

    /**
     * 多数据转换
     * @param $rawData array
     */
    public function multiConvert(&$rawData) {
        foreach($rawData as $key => $value) {
            $res = $this->convert($key, $value);
            $rawData[$res[0]] = $res[1];
        }
    }

    //转化为时间戳
    public function str2time($rawData) {
        return Utility::str2time($rawData)?:0;
    }

    //标准化电子邮件
    public function email($rawData) {
        $pattern = '/\w+(?:[-+.]\w*)*@\w+(?:[-.]\w+)*\.\w+(?:[-.]\w+)*/';
        if(preg_match($pattern, $rawData, $match)) {
            return $match[0];
        }else{
            return '';
        }
    }
}
