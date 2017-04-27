<?php
/*
 * 通用模板格式
 */
namespace app\index\Parser;

class Template extends AbstractParser {
     //区块标题
    protected $titles = array();

    //关键字解析规则
    protected $rules = array();

    //判断模板是否匹配
    protected function isMatched($content) {
        
    }

     //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $patterns = array(
            '/<(td|h3|h2|h5)/i',
            '/<\/(td|h3|h2|h5)>/i',
            '/\||<br.*?>/i',
        );
        $replacements = array(
            '<div',
            '</div>',
            '</div><div>',
        );
        $content = preg_replace($patterns, $replacements, $content);
        return $content;
    }

    //根据模板解析简历
    public function parse($content) {
        $record = array();
        //预处理
        $content = $this->preprocess($content);
        list($data, $blocks) = $this->domParse($content, 'div');
        dump($blocks);
        dump($data);
        $end = $blocks[0][1]-2?:count($data)-1;
        //其他解析
        
        //各模块解析
        foreach($blocks as $block){
            $this->$block[0]($data, $block[1], $block[2],$record);
        }
        
        //dump($record);
        return $record;
    }

    //获取DOM数组
    public function getDomArray($content) {
        $content = $this->preprocess($content);
        return $this->domParse($content, 'div', true, false);
    }

    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(       
        );
        $sequence = array();
        $i = 0;
        $j = 0;
        $jobs = array();
        while($i < $length) {
            //正则匹配
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)$/', $data[$i], $match)) {
                $job = array();
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                $jobs[$j++] = $job;
                $k = 1;
            //关键字匹配
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }elseif($k > 0){
                if($key = $sequence[$k-1]){
                    $jobs[$j-1][$key] = $data[$i];
                    $k++;
                }else{
                    $k = 0;
                }
            }elseif($currentKey){
                $jobs[$j-1][$currentKey] .=  $data[$i];
            }
            $i++;
        }
        //dump($jobs);
        $record['career'] = $jobs;
        return $jobs;
    }

    public function education($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $education = array();
        $rules = array(
            array('major', '专业：'), 
            array('degree', '学历：'), 
        );
        $sequence = array('school');
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)/', $data[$i], $match)){
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[1]);
                $edu['end_time'] = Utility::str2time($match[2]);
                $education[$j++] = $edu;
                $k = 1;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $education[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
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
        //dump($education);
        $record['education'] = $education;
        return $education;
    }

    public function projects($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('company', '所在公司：'),
            array('desciption', '项目描述：'),
        );
        $sequence = array('name');
        $i = 0;
        $j = 0;
        $projects = array();
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)/', $data[$i], $match)){
                $project = array();
                $project['start_time'] = Utility::str2time($match[1]);
                $project['end_time'] = Utility::str2time($match[2]);
                $projects[$j++] = $project;
                $k = 1;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $projects[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
            }elseif($k > 0){
                if($key = $sequence[$k-1]){
                    $projects[$j-1][$key] = $data[$i];
                    $k++;
                }else{
                    $k = 0;
                }     
            }
            $i++;                                
        }
        $record['projects'] = $projects;
        return $projects;
    }

}
