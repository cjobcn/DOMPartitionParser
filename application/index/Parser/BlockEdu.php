<?php
/**
 * Created by PhpStorm.
 * User: DFFuture
 * Date: 2017/5/23
 * Time: 22:22
 */

namespace app\index\Parser;

// 教育经历模块解析方法
class BlockEdu extends  AbstractParser {

    protected $patterns = array(
        1 => array('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)：(.+)/', 0),
        2 => array('/(?:时间： )?(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)(月)?$/', 0 ),
        3 => array('/(.+?)（ (\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今) ）/', 0),
        4 => array('/（(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)）/', 1)
    );

    /**
     * @param array $data      区块dom数组
     * @param string $methods  提取方案序号
     * @return array
     */
    public function parse($data, $methods = '') {
        $education = array();
        //dump($data);
        if($methods && is_string($methods)){
            $methods = explode(',', $methods);
        }
        foreach($methods as $method) {
            if(preg_match($this->patterns[$method][0],
                $data[$this->patterns[$method][1]])) {
                $method = 'extract'.$method;
                //dump($method);
                $education = $this->$method($data);
                break;
            }
        }
        return $education;
    }


    //教育经历提取方案一
    public function extract1($data) {
        //初始化
        $length = count($data);
        $i = 0;
        $j = 0;
        $k = 0;
        $education = array();
        //规则
        $patterns = array(
            '/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)：(.+)/'
        );
        $sequence = array('major', 'degree');
        //循环
        while($i < $length) {
            if(preg_match($patterns[0], $data[$i], $match)) {
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[1]);
                $edu['end_time'] = Utility::str2time($match[2]);
                $edu['school'] = $match[3];
                $education[$j++] = $edu;
                $k = 1;
            }elseif($k > 0){
                if($key = $sequence[$k-1]){
                    $education[$j-1][$key] = $data[$i];
                    $k++;
                }else{
                    $k = 0;
                }
            }
            $i++;
        }
        return $education;
    }

    //教育经历提取方案二
    public function extract2($data) {
        $length = count($data);
        $i = 0;
        $j = 0;
        $k = 0;
        $currentKey = '';
        $education = array();
        $sequence = array('school');
        $rules = array(
            array('school', '学校：', 0),
            array('major', '-?专业：', 0),
            array('degree', '-?学历：', 0),
            array('class', '专业描述：', 0)
        );
        $patterns = array(
            '/^(?:时间： )?(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)/'
        );
        while($i < $length) {
            if(preg_match($patterns[0], $data[$i], $match)) {
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[1]);
                $edu['end_time'] = Utility::str2time($match[2]);
                $education[$j++] = $edu;
                $k = 1;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $education[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
                if($k > 0) $k--;
            }elseif($k > 0){
                if($key = $sequence[$k-1]){
                    $education[$j-1][$key] = $data[$i];
                    $k++;
                }else{
                    $k = 0;
                }
            }elseif($currentKey == 'class'){
                $education[$j-1][$currentKey] .=  $data[$i];
            }
            $i++;
        }
        return $education;
    }

    public function extract3($data) {
        $length = count($data);
        $i = 0;
        $j = 0;
        $education = array();
        $rules = array(
            array('major', '专业名称：'),
            array('degree', '学历：'),
        );
        $patterns = array(
            $this->patterns[3][0],
        );
        while($i < $length) {
            if(preg_match($patterns[0], $data[$i], $match)) {
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[2]);
                $edu['end_time'] = Utility::str2time($match[3]);
                $edu['school'] = $match[1];
                $education[$j++] = $edu;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $education[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
            }
            $i++;
        }
        return $education;
    }

    public function extract4($data) {
        $length = count($data);
        $i = 0;
        $j = 0;
        $education = array();
        $rules = array(
            array('major', '专业：'),
            array('degree', '学历：'),
            array('isNURS', '是否统招：')
        );
        $patterns = array(
            $this->patterns[4][0],
        );
        while($i < $length) {
            if(preg_match($patterns[0], $data[$i+1], $match)) {
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[1]);
                $edu['end_time'] = Utility::str2time($match[2]);
                $edu['school'] = $data[$i++];
                $education[$j++] = $edu;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $education[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
            }
            $i++;
        }
        return $education;
    }
}
