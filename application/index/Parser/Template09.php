<?php
namespace app\index\Parser;

class Template09 extends AbstractParser {

    public $website  = '前程无忧(51job)';

     //区块标题
    protected $titles = array(
        //array('basic', '基本信息|个人简历'), 
        array('evaluation', '自我评价'), 
        array('target', '求职意向'), 
        array('career', '工作经验'), 
        array('projects', '项目经验'), 
        array('education', '教育经历'), 
        array('trainings', '培训经历'),
        array('certs', '证书'),
        array('prizes', '所获奖励'),
        array('practices', '社会实践'),
        array('languages', '语言能力'), 
        array('skills', 'IT技能'), 
        array('addition', '附加信息'),
        array('others', '其他信息')
    );

    //关键字解析规则
    protected $rules = array(
        array('update_time', '更新时间：|投递时间：'),
        array('resume_keywords', '简历关键字：'),
        array('name', '姓名：'),
        array('sex', '性别：'),
        array('birth_year', '出生日期：'), 
        array('city', '居住地：'), 
        array('work_year', '工作年限：'), 
        array('residence', '户口：'), 
        
        array('address', '地址：'), 
        array('postcode', '邮编：'), 
        array('email', '电子邮件：|E-mail：'), 
        array('phone', '移动电话：|电话：'), 

        array('current_salary', '目前年薪：|目前薪资：'), 
        array('base_front', '基本工资：'), 
        array('bonus', '年度奖金\/佣金：|奖金\/佣金：'),
        array('allowance',     '补贴\/津贴：'), 

        array('work_property', '工作性质：'),
        array('target_industry', '希望行业：'), 
        array('target_city', '目标地点：'), 
        array('target_salary', '期望工资：|期望薪资：'), 
        array('target_position', '目标职能：'),
        array('jump_time',      '到岗时间：'),
        array('work_status',    '求职状态：'),

        array('last_company',  '公司：'),
        array('industry',      '行业：'),
        array('last_position', '职位：'),

        array('degree',  '学历：'),
        array('major',   '专业：'),
        array('school',  '学校：'),
    );

    //判断模板是否匹配
    protected function isMatched($content) {
        
    }

     //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        return $content;
    }

    //根据模板解析简历
    public function parse($content) {
        $record = array();
        //判断内容与模板是否匹配
        //if(!$this->isMatched($content)) return false;
        //预处理
        $content = $this->preprocess($content);

        list($data, $blocks) = $this->domParse($content,'td', false);
        //dump($blocks);
        //dump($data);
        if(!$blocks) return false;
        //其他解析
        $i = 0;
        while($i < $blocks[0][1]-1){
            if(preg_match('/\(ID:(\d{5,})\)/',$data[$i],$match)){
                $record['true_id'] = $match[1];
                if(!$this->isKeyword($data[$i-2])){
                    $record['name'] = $data[$i-2];
                    if(preg_match('/匹配度/',$record['name'])){
                        $record['name'] = $data[$i-3];
                    }
                }
                preg_match('/男|女/',$data[$i-1],$match);
                $record['sex'] = $match[0];
                preg_match('/\d{4}(?=年)/',$data[$i-1],$match);
                $record['birth_year'] = $match[0];
                preg_match('/\d{3}cm/i',$data[$i-1],$match);
                $record['height'] = $match[0];
                preg_match('/已婚|未婚/',$data[$i-1],$match);
                $record['marriage'] = $match[0];
                break;
            }
            $i++;
        }
        $this->basic($data,0,$blocks[0][1]-1,$record);
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
        $rules = array(
            array('industry', '所属行业：'), 
            array('description', '公司描述：'),
            array('performance', '工作业绩：'), 
            array('report_to', '汇报对象：'),
            array('underlings', '下属人数：'), 
            array('referees', '证明人：'), 
            array('left_reason', '离职原因：'), 
        );
        $sequence = array('department', 'position', 'duty');
        $i = 0;
        $j = 0;
        $k = 0;
        $jobs = array();
        while($i < $length) {
            //正则匹配
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)：(.+)/', $data[$i], $match)) {
                $job = array();
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                $job['company'] = preg_split('/\(\d|\[/',$match[3])[0];
                $jobs[$j++] = $job;
            //关键字匹配
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                if(isset($jobs[$j-1]['industry'])) $k=1;
            //顺序匹配
            }elseif($k > 0){
                if($key = $sequence[$k-1]){
                    $jobs[$j-1][$key] = $data[$i];
                    $k++;
                }else{
                    $k = 0;
                }                         
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
            array('soft', '软件环境：'),
            array('hard', '硬件环境：'),
            array('dev', '开发工具：'),
            array('description', '项目描述：'), 
            array('duty', '责任描述：'),
            array('performance', '项目业绩：'), 
        );
        $i = 0;
        $j = 0;
        $projects = array();
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
            }
            $i++;
        }
        //dump($projects);
        $record['projects'] = $projects;
        return $projects;
    }

    public function education($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $k = 0;
        $education = array();
        $sequence = array('school', 'major', 'degree', 'class');
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)/', $data[$i], $match)){
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[1]);
                $edu['end_time'] = Utility::str2time($match[2]);
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
        //dump($education);
        $record['education'] = $education;
        return $education;
    }


    
}
