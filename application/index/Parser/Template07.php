<?php
namespace app\index\Parser;

class Template07 extends AbstractParser {
     //区块标题
    protected $titles = array(
        array('target', '求职意向'), 
        array('career', '工作经验'), 
        array('projects', '项目经验'),
        array('education', '教育背景'), 
        array('trainings', '培训经历'),
        array('languages', '外语/方言'),
        array('addition', '职业技能与特长'),
    );

    //关键字解析规则
    protected $rules = array(
        array('sex', '性别：'), 
        array('email', '电子邮箱：'), 
        array('phone', '手机：'), 
        array('address', '通信地址：'), 
        array('birth_year', '出生日期：'), 
        array('residence', '户口所在地：'), 
        array('industry', '现从事行业：'), 
        array('last_position', '现从事职业：'), 
        array('targey_salary', '期望薪水：'), 
        array('target_industry', '期望从事行业：'), 
        array('target_position', '期望从事职业：'), 
        array('target_city', '期望工作地区：'), 
        array('jump_time', '到岗时间：'),
        array('self_str', '自我评价：'),
    );

    //判断模板是否匹配
    protected function isMatched($content) {
        
    }

     //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $patterns = array(
            '/<(span|th|td|h4|p)/i',
            '/<\/(span|th|td|h4|p|h5)>/',
            '/<br.*?>/i',
            '/<h5>/i'
        );
        $replacements = array(
            '<div',
            '</div>',
            '#br#',
            '<div>#h5#'
        );
        $content = preg_replace($patterns, $replacements,$content);
        return $content;
    }

    //根据模板解析简历
    public function parse($content) {
        $record = array();
        //判断内容与模板是否匹配
        //if(!$this->isMatched($content)) return false;
        //预处理
        $content = $this->preprocess($content);

        list($data, $blocks) = $this->domParse($content, 'div', false);
        //dump($blocks);
        //dump($data);
        if(!$blocks) return false;
        $i = 0;
        $end = $blocks[0][1] - 2;
        //其他解析
        preg_match('/<h2>(.+?)<\/h2>/i',$content, $match);
        $record['name'] = $match[1];
        while($i < $end) {
            if(preg_match('/更新日期 (\d{4}\D\d{2}\D\d{2})/', $data[$i], $match)) {
                $record['update_time'] = $match[1];
            }elseif(strpos($data[$i], "#br#") !== false){
                $basic = explode("#br#", $data[$i]);
                $this->basic($basic, 0, count($basic) - 1, $record);
                $i++;
                break;
            }
            $i++;
        }
        $this->basic($data, $i, $end, $record);
        if($record['self_str']) {
            $record['self_str'] = str_replace('#br#', "\r\n", $record['self_str']);
        }
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
        return $this->domParse($content, 'div', false, false);
    }

    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules1 = array(
            array('description', '公司简单描述：'), 
            array('nature', '公司性质：'), 
            array('size', '公司规模：'), 
            
        );
        $rules2 = array(
            array('city', '工作地点：', 0), 
            array('reportto', '汇报对象：', 0), 
            array('duty', '工作职责和业绩：'),
            array('left_reason', '离职\/换岗原因：')
        );
        $job = array();
        $jobs = array();
        $i = 0;
        $j = 0;
        while($i < $length) {
            if(preg_match('/#h5#(.+)/', $data[$i], $match)){
                $job['company'] = $match[1];
            }elseif(preg_match('/(\d{4}年\d{1,2}月)\D+(\d{4}年\d{1,2}月|现在)/',$data[$i], $match)){
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                $position = explode('/',$data[++$i]);
                $job['position'] = $this->clean($position[0]);
                if($position[1])
                    $job['department'] = $position[1];
                //dump($job);
                $jobs[$j] = $job;
                $j++;
            }elseif($KV = $this->parseElement($data, $i, $rules1)){
                $job[$KV[0]] = $KV[1];
            }elseif($KV = $this->parseElement($data, $i, $rules2)){
                $jobs[$j-1][$KV[0]] = $KV[1];
                if($jobs[$j-1]['duty']){
                    $jobs[$j-1]['duty'] = str_replace('#br#', "\r\n", $jobs[$j-1]['duty']);
                }
            }
            $i++;
        }
        $record['career'] = $jobs;
        return $jobs;
    }

    public function education($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $education = array();
        while($i < $length) {
            $edu = array();
            if(preg_match('/(\d{4}年\d{1,2}月)\D+(\d{4}年\d{1,2}月|现在)/',$data[$i], $match)) {
                $edu['start_time'] = Utility::str2time($match[1]);
                $edu['end_time'] = Utility::str2time($match[2]);
                $edu['school'] = $data[++$i];
                $major = explode(' ',$data[++$i]);
                $edu['major'] = $major[0];
                $edu['degree'] = end($major);
                $education[$j++] = $edu;
            }else{
                $education[$j-1]['class'] = str_replace('#br#', "\r\n", $data[$i]);
            }
            $i++;
        }
        $record['education'] = $education;
        return $education;
    }
}
