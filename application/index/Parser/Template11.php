<?php
namespace app\index\Parser;

class Template11 extends AbstractParser {
    //区块标题
    protected $titles = array(
        array('target', '求职意向'), 
        array('evaluation', '自我评价'), 
        array('career', '工作经历'),
        array('projects', '项目经历'),
        array('education', '教育经历'),
        array('school_situation', '在校学习情况'),
        array('practices', '在校实践经验'),
        array('trainings', '培训经历'),
        array('certs', '证书'), 
        array('languages', '语言能力'), 
        array('skills', '专业技能'), 
        array('prizes', '获得荣誉'),
        array('others', '附件'),
        array('interest', '兴趣爱好'),
        array('resume_content', '简历内容')
    );

    //关键字解析规则
    protected $rules = array(
        array('update_time', '简历更新时间：'),
        array('true_id', 'ID:'),
        array('name', '姓名：'),
        array('city', '现居住地：'),
        array('postcode', '邮编：'),
        array('phone', '手机：'),
        array('residence', '户口：'),
        array('email', 'E-mail：'), 
        array('ID' , '身份证：'),
        array('target_city', '期望工作地区：'),
        array('target_salary', '期望月薪：'), 
        array('work_status', '目前状况：'),
        array('target_position', '期望从事职业：'), 
        array('target_industry', '期望从事行业：'),
    );


    //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $patterns = array(
            '/<(td|h3|h2|h5|em)/i',
            '/<\/(td|h3|h2|h5|em)>/i',
            '/\||<br.*?>/i',
            '/<div[^>]+class="main-title-fl fc6699cc"[^>]*>/is',
        );
        $replacements = array(
            '<div',
            '</div>',
            '</div><div>',
            '<div>姓名：',
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
        //dump($blocks);
        //dump($data);
        $end = $blocks[0][1] - 2 > 0?$blocks[0][1] - 2:count($data) - 1;
        $this->basic($data, 0 , $end, $record);
        //其他解析
        $i = 0;
        while($i <= $end ) {

            if(!isset($record['sex'])){
                if(preg_match('/男|女/', $data[$i], $match)){
                    $record['sex'] = $match[0];
                    if(preg_match('/(\d{4})\s*年/', $data[$i], $match)) {
                        $record['birth_year'] = $match[1];
                    }
                    if(preg_match('/未婚|已婚/', $data[$i], $match)){
                        $record['marriage'] = $match[0];
                    }
                    if(preg_match('/(\d+)年工作经验/', $data[$i], $match)){
                        $record['work_year'] = intval($match[1]);
                    }
                }
            }
            if(!isset($record['phone']))
                if(preg_match($this->pattern['phone'],$data[$i], $match)){
                    $record['phone'] = $match[0];
                }
            if(!isset($record['email']))
                if(preg_match($this->pattern['email'],$data[$i], $match)){
                    $record['email'] = $match[0];
                }
            $i++;
        }
        if(!isset($record['update_time'])){
            if(preg_match('/resumeUpdateTime.innerHTML = "(.+?)";/',$content,$match));
                $record['update_time'] = $match[1];
        }
        if(isset($record['name'])) {
            if(preg_match('/工作经验|\d+年/', $record['name'])) {
                $record['name'] = '';
            }
        }

        //各模块解析
        foreach($blocks as $block){
            $this->$block[0]($data, $block[1], $block[2],$record);
        }
        if(!$record){
            sendMail(11,$content);
        }
        //dump($record);
        return $record;
    }

    //获取DOM数组
    public function getDomArray($content) {
        $content = $this->preprocess($content);
        return $this->domParse($content, 'div', true, false);
    }

    public function target($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        while($i < $length) {
            $KV = $this->parseElement($data, $i);
            if($KV){
                $record[$KV[0]] = $KV[1];
            }
            $i++;
        }
    }

    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('nature', '企业性质：'), 
            array('size', '规模：'), 
            array('duty', '工作描述：'),
            array('report_to', '汇报对象：', 0),
            array('underlings', '下属人数：', 0),
            array('salary', '年收入：', 0),
            array('performance', '业绩描述：', 0),
        );
        $i = 0;
        $j = 0;
        $k = 0;
        $currentKey = '';
        $jobs = array();
        while($i < $length) {
            //正则匹配
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今) (.+?) （\d.+?）/', $data[$i], $match)) {
                $job = array();
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                $job['company'] = $match[3];
                $jobs[$j++] = $job;
                $k = $i;
            //关键字匹配
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
                if($currentKey == 'nature') {
                    $jobs[$j-1]['industry'] = $data[$i-1];
                    if(!isset($jobs[$j-1]['salary']) && $i-1 > $k+1){
                        $jobs[$j-1]['position'] = $data[$k+1];
                    }
                }
            }elseif(preg_match('/元\/月/', $data[$i])){
                $jobs[$j-1]['salary'] = $data[$i];
                $m = $i - 1;
                while($m > $k) {
                    if($jobs[$j-1]['position']){
                        $jobs[$j-1]['department'] = $data[$m];
                    }else{
                        $jobs[$j-1]['position'] = $data[$m];
                    } 
                    $m--;
                }
            }else{
                if($currentKey == 'duty') {
                    $jobs[$j-1][$currentKey] .= '#br#'.$data[$i];
                }                       
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
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今) (.+)/', $data[$i], $match)) {
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[1]);
                $edu['end_time'] = Utility::str2time($match[2]);
                $info = preg_split('/\s+/', $match[3]);
                if(($info_length = count($info)) > 1){
                    //dump($info);
                    $edu['school'] = implode(' ', array_slice($info, 0 ,$info_length-2));
                    $edu['major'] = $info[$info_length - 2];
                    $edu['degree'] = $info[$info_length - 1];
                    $education[$j++] = $edu;
                }

            }
            //dump($data[$i]);
            $i++;
        }
        //dump($education);
        $record['education'] = $education;
        return $education;
    }

    public function projects($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $projects = array();
        $rules = array(
            array('soft', '软件环境：'),
            array('hard', '硬件环境：'),
            array('dev', '开发工具：'),
            array('duty', '责任描述：' ),
            array('description', '项目描述：'),
        );
        $currentKey = '';
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今) (.+)/', $data[$i], $match)) {
                $project = array();
                $project['start_time'] = Utility::str2time($match[1]);
                $project['end_time'] = Utility::str2time($match[2]);
                $project['name'] = $match[3];
                $projects[$j++] = $project;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $projects[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }elseif($currentKey){
                $projects[$j-1][$currentKey] .= $data[$i];             
            }
            $i++;
        }
        $record['projects'] = $projects;
        return $projects;
    }
}
