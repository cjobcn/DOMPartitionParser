<?php
namespace app\index\Parser;

class Template12 extends AbstractParser {
     //区块标题
    protected $titles = array(
        array('career', '工作经历:'), 
        array('projects', '项目经验:'), 
        array('education', '教育经历:'),
        array('skills', '技能:'),
        array('languages', '语言水平:'),
    );

    //关键字解析规则
    protected $rules = array(
        array('true_id', '来源ID:', 0),
        array('name', '姓名:', 0), 
        array('sex', '性别:', 0), 
        array('birth_year', '出生日期:', 0), 
        array('marriage', '婚否:'),
        array('height', '身高:', 0), 
        array('degree', '学历:', 0),
        array('work_begin', '参加工作时间:', 0), 
        array('residence', '户口所在地:', 0), 
        array('city', '现居住城市:', 0), 
        array('phone', '手机号码:', 0),
        array('qq', 'QQ:', 0),
        array('address', '地址:', 0), 
        array('email', 'Email:', 0), 
        array('postcode', '邮编:', 0), 
        array('target_city', '期望工作地点:', 0), 
        array('target_position', '期望从事职业:', 0), 
        array('target_industry', '期望从事行业:', 0), 
        array('target_salary', '期望月薪(税前):', 0), 
        array('current_salary', '当前年薪:', 0), 
        array('self_str', '简介:'), 
        array('photo', '照片:', 0),
        array('homepage', '主页:', 0),
        array('update_time', '更新日期:', 0),
    );

    //判断模板是否匹配
    protected function isMatched($content) {
        $pattern = '/来源ID:[\d\w]+<br>/';
        return preg_match($pattern, $content);
    }

     //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $patterns = array(
            '/\r\n|<br.*?>/i',
            '/：/',
            '/&nbsp;?/'
        );
        $replacements = array(
            '</div><div>',
            ':',
            ' '
        );
        $content = preg_replace($patterns, $replacements, $content);
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
        $length = $blocks[0][1] - 1 > 0?$blocks[0][1] - 1:count($data);
        //其他解析
        $i = 0;
        $currentKey = '';
        while($i < $length) {
            if($KV = $this->parseElement($data, $i)) {
                $record[$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }else{
                if($currentKey == 'self_str') {
                    $record[$currentKey] .= $data[$i];
                }                    
            }
            $i++;
        }
        //各模块解析
        foreach($blocks as $block){
            $this->$block[0]($data, $block[1], $block[2],$record);
        }
        if(!$record){
            sendMail(12,$content);
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
        
        $rules = array(
            array('duty', '工作内容:', 0), 
            array('left_reason', '离职理由:', 0),
        );
        $j = 0;
        $i = 0;
        $jobs = array();
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)\|(.+)/', $data[$i], $match)){
                $job = array();
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                $info = explode('|',$match[3]);
                //dump($info);
                $job['city'] = $info[0];
                $job['company'] = $info[1];
                $job['nature'] = $info[2];
                $job['size'] = $info[3];
                $job['industry'] = $info[4];
                $job['salary'] = $info[5];
                $jobs[$j++] = $job;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
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
        
        $rules = array(
            array('description', '项目介绍:'), 
            array('duty', '责任描述:'), 
        );
        $j = 0;
        $i = 0;
        $currentKey = '';
        $projects = array();
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)\|(.+)/', $data[$i], $match)) {
                $project = array();
                $project['start_time'] = Utility::str2time($match[1]);
                $project['end_time'] = Utility::str2time($match[2]);
                $info = explode('|',$match[3]);
                //dump($info);
                $project['name'] = $info[0];
                $projects[$j++] = $project;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $projects[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }else{
                $projects[$j-1][$currentKey] .= '#br#'.$data[$i];
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
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今) (.+)/', $data[$i], $match)) {
                //dump($match);
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[1]);
                $edu['end_time'] = Utility::str2time($match[2]);
                $info = explode(' ', $match[3]);
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
