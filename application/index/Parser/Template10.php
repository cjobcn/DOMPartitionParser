<?php
namespace app\index\Parser;

class Template10 extends AbstractParser {
     //区块标题
    protected $titles = array(
        array('career', '工作经历'),
        array('projects', '项目经验'),
        array('education', '教育经历'), 
        array('practices', '在校实践经验'),
        array('trainings', '培训经历'), 
        array('certs', '证书'), 
        array('languages', '语言能力'), 
        array('skills', '专业技能'), 
        array('prizes', '获得荣誉'),
        array('others', '附件'),
    );

    //关键字解析规则
    protected $rules = array(
        array('email', 'E-mail:'), 
        array('self_str', '自我评价'), 
        array('target_position', '期望从事职业：'), 
        array('targte_salary', '期望月薪：'), 
        array('work_status', '目前状况：'),
    );

    //判断模板是否匹配
    protected function isMatched($content) {
        
    }

     //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $patterns = array(
            '/<br.*?>/i',
            '/<p/',
            '/<\/p>/'
        );
        $replacements = array(
            '</td><td>',
            '<td',
            '<\/td>'
        );
        $content = preg_replace($patterns, $replacements, $content);
        return $content;
    }

    //根据模板解析简历
    public function parse($content) {
        $record = array();
        //预处理
        $content = $this->preprocess($content);

        list($data, $blocks) = $this->domParse($content,'td', false);
        //dump($blocks);
        //dump($data);
        if(!$blocks) return false;
        //其他解析
        $i = 0;
        $patterns = array(
            array('sex', '/男|女/'),
            array('marriage', '/未婚|已婚/'),
            array('birth_year', '/(\d{4})\s*年/', 1),
            array('residence', '/(?<=户口：)[^\|]+/'),
            array('city', '/(?<=现居住于)[^\|]+/'),
        );
        while($i < $blocks[0][1]-2) {
            if($data[$i] == '智联招聘') {
                $record['name'] = $data[++$i];   
            }
            if(preg_match('/\|/',$data[$i])){
                foreach($patterns as $pattern){
                    if(preg_match($pattern[1], $data[$i], $match)) {
                        $index = $pattern[2]?:0;
                        $record[$pattern[0]] = $match[$index];
                    }                     
                }
            }
            if(preg_match('/(\d{11})\s*\(手机\)/', $data[$i], $match)) {
                $record['phone'] = $match[1];
            }
            $i++;
        }
        $this->basic($data, 0, $blocks[0][1]-2, $record);
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
        return $this->domParse($content, 'td', false, false);
    }

    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $jobs = array();
        $rules = array(
            array('description', '公司描述：'),
            array('duty', '工作职责：|工作職責：'),
            array('performance', '工作业绩：'),
            array('position', '职务：'),
            array('size', '规模:'),
            array('salary', '月薪：'),
        );
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)：/', $data[$i], $match)) {
                $job = array();
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                
                if(strpos($data[$i+1], '|') !== false){
                    $info = preg_split('/\|\s+/', $data[++$i]);
                    $job['company'] = $info[0];
                    $job['department'] = $info[1];
                    $job['position'] = end($info);
                }else{
                    $job['company'] = $data[++$i];
                }   
                if(strpos($data[$i+1], '|') !== false){
                    $info = preg_split('/\|\s+/', $data[++$i]);
                    $job['industry'] = $info[0];
                    $job['nature'] = $info[1];
                    $job['size'] = $info[2];
                    $job['salary'] = $info[3];
                }          
                $jobs[$j++] = $job;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }else{
                if(!$currentKey) $currentKey = 'duty';
                $jobs[$j-1][$currentKey] .= $data[$i];             
            }
            $i++;
        }
        //dump($jobs);
        $record['career'] = $jobs;
        return $jobs;
    }

    public function projects($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $projects = array();
        $rules = array(
            array('soft', '软件环境：', 0),
            array('hard', '硬件环境：', 0),
            array('dev', '开发工具：', 0), 
            array('duty', '责任描述：' , 0),
            array('department', '涉及部门：', 0),
            array('performance', '专案业绩：|项目业绩：'),
        );
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)：(.+)/', $data[$i], $match)) {
                $project = array();
                $project['start_time'] = Utility::str2time($match[1]);
                $project['end_time'] = Utility::str2time($match[2]);
                $project['name'] = $match[3];
                $projects[$j++] = $project;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $projects[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }else{
                $projects[$j-1][$currentKey] .= $data[$i];             
            }
            $i++;
        }
        $record['projects'] = $projects;
        return $projects;
    }

    public function education($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $education = array();
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)：(.+)/', $data[$i], $match)) {
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[1]);
                $edu['end_time'] = Utility::str2time($match[2]);
                $info = explode(' | ', $match[3]);
                $edu['school'] = $info[0];
                $edu['major'] = $info[1];
                $edu['degree'] = $info[2];
                $education[$j++] = $edu;
            }
            $i++;
        }

        //dump($education);
        $record['education'] = $education;
        return $education;
    }

}
