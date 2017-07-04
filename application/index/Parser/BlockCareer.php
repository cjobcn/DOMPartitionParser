<?php
/**
 * Created by PhpStorm.
 * User: DFFuture
 * Date: 2017/5/23
 * Time: 22:22
 */

namespace app\index\Parser;

// 工具经历模块解析方法
class BlockCareer extends AbstractParser {
    protected $patterns = array(
        1=> '/(?<!时间：) (\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)$/',
        2=> '/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)$/',
        3=> '/^时间： (\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)$/'
    );

    /**
     * @param array $data      区块dom数组
     * @param string $methods  提取方案序号
     * @return array
     */
    public function parse($data, $methods = '') {
        $jobs = array();
        if($methods && is_string($methods)){
            $methods = explode(',', $methods);
        }

        foreach($methods as $method) {
            if(preg_match($this->patterns[$method], $data[0])) {
                $method = 'extract'.$method;
                //dump($method);
                $jobs = $this->$method($data);
                break;
            }
        }
        return $jobs;
    }

    public function extract1($data) {
        $length = count($data);
        $i = 0;
        $j = 0;
        $currentKey = '';
        $jobs = array();
        $job = array();
        $rules = array(
            array('city', '-所在地区：', 0),
            array('report_to', '-汇报对象：', 0),
            array('underlings', '-下属人数：', 0),
            array('duty', '-工作职责：|主要工作:'),
            array('performance', '-工作业绩：'),
        );
        while($i < $length) {
            //正则匹配
            if(preg_match('/(.+) (?P<start_time>\d{4}\D+\d{1,2})\D+(?P<end_time>\d{4}\D+\d{1,2}|至今|现在)$/',
                $data[$i], $match)) {

                $job = array();
                $job['company'] = $match[1];
                $job['start_time'] = Utility::str2time($match["start_time"]);
                $job['end_time'] = Utility::str2time($match['end_time']);
            }elseif(preg_match('/^(?P<start_time>\d{4}\D+\d{1,2})\D+(?P<end_time>\d{4}\D+\d{1,2}|至今|现在)$/',
                $data[$i], $match)) {

                $jobs[$j++] = $job;
                $jobs[$j-1]['position'] = $data[$i-1];
                $jobs[$j-1]['start_time'] = Utility::str2time($match["start_time"]);
                $jobs[$j-1]['end_time'] = Utility::str2time($match['end_time']);
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }elseif($currentKey){
                $jobs[$j-1][$currentKey] .=  $data[$i];
            }
            $i++;
        }
        return $jobs;
    }

    public function extract2($data) {
        $length = count($data);
        $i = 0;
        $j = 0;
        $currentKey = '';
        $timeSpan = '';
        $jobs = array();
        $job = array();
        $rules1 = array(
            array('industry', '公司行业：'),
            array('description', '公司描述：'),
            array('nature', '公司性质：'),
            array('size', '公司规模：'),
        );
        $rules2 = array(
            array('city', '工作地点：'),
            array('underlings', '下属人数：'),
            array('duty', '职责业绩：|工作职责：'),
            array('performance', '工作业绩：'),
            array('salary', '薪酬状况：'),
            array('department', '所在部门：'),
            array('report_to', '汇报对象：'),
        );
        $patterns = array(
            '/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)$/'
        );
        while($i < $length) {
            if(preg_match($patterns[0], $data[$i], $match)){
                $start = Utility::str2time($match[1]);
                $end = Utility::str2time($match[2]);
                if(!$timeSpan || ($timeSpan[0] < $start || $timeSpan[1] > $end)){
                    $job = array();
                    $timeSpan = array($start, $end);
                    $job['company'] = $data[++$i];
                }else{
                    $job['start_time'] = $start;
                    $job['end_time'] = $end;
                    $jobs[$j++] = $job;
                    $jobs[$j-1]['position'] = $data[$i-1];
                }
            }elseif($KV = $this->parseElement($data, $i, $rules1)){
                $job[$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }elseif($KV = $this->parseElement($data, $i, $rules2)){
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }elseif($j > 0 &&  in_array($currentKey, array('description', 'duty', 'performance'))) {
                $jobs[$j-1][$currentKey] .=  $data[$i];
            }

            $i++;
        }
        return $jobs;
    }

    public function extract3($data) {
        $length = count($data);
        $i = 0;
        $j = 0;
        $currentKey = '';
        $jobs = array();
        $rules = array(
            array('company', '公司：'),
            array('nature', '公司性质：'),
            array('industry', '行业：'),
            array('department', '部门：'),
            array('position', '职位：'),
            array('duty', '工作描述：'),
        );
        while($i < $length) {
            //正则匹配
            if(preg_match('/^时间：\s(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)$/',
                $data[$i], $match)) {
                $job = array();
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                $jobs[$j++] = $job;
                $jobs[$j-1]['position'] = $data[$i-1];
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }elseif($currentKey == 'duty'){
                $jobs[$j-1][$currentKey] .=  $data[$i];
            }
            $i++;
        }
        return $jobs;
    }
}
