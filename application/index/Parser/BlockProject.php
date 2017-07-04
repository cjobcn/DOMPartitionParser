<?php
/**
 * Created by PhpStorm.
 * User: DFFuture
 * Date: 2017/5/23
 * Time: 22:23
 */

namespace app\index\Parser;

// 项目经历模块解析方法
class BlockProject extends AbstractParser {

    protected $patterns = array(
        1=> '/.+/',
    );

    /**
     * @param array $data      区块dom数组
     * @param string $methods  提取方案序号
     * @return array
     */
    public function parse($data, $methods = '') {
        $projects = array();
        if($methods && is_string($methods)){
            $methods = explode(',', $methods);
        }
        foreach($methods as $method) {
            if(preg_match($this->patterns[$method], $data[0])) {
                $method = 'extract'.$method;
                //dump($method);
                $projects = $this->$method($data);
                break;
            }
        }
        return $projects;
    }

    public function extract1($data) {
        $length = count($data);
        $i = 0;
        $j = 0;
        $currentKey = '';
        $projects = array();
        $rules = array(
            array('position', '项目职务：'),
            array('description', '项目描述：'),
            array('duty', '项目职责：'),
            array('performance', '项目业绩：'),
        );
        $patterns = array(
            '/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)/'
        );
        while($i < $length) {
            if(preg_match($patterns[0], $data[$i], $match)){
                $project = array();
                $project['start_time'] = Utility::str2time($match[1]);
                $project['end_time'] = Utility::str2time($match[2]);
                $project['name'] = $data[$i-1];
                if($j > 0 && isset($projects[$j-1]['performance'])){
                    $projects[$j-1]['performance'] = str_replace($data[$i-1], '', $projects[$j-1]['performance']);
                }
                $projects[$j++] = $project;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $projects[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }if($j > 0 && $currentKey){
                $projects[$j-1][$currentKey] .=  $data[$i];
            }
            $i++;
        }
        return $projects;
    }

    public function extract2($data) {

    }
}
