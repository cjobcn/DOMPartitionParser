<?php
/*
 * 通用模板格式
 */
namespace app\index\Parser;

class Template03 extends AbstractParser {
     //区块标题
    protected $titles = array(
        array('basic', '个人信息'), 
        array('target', '求职意向'), 
        array('evaluation', '自我评价'), 
        array('career', '工作经历'), 
        array('education', '教育经历'), 
        array('trainings', '培训经历'),
        array('certs', '证书'), 
        array('languages', '语言能力'), 
        array('others', '爱好\/特长'),
    );

    //关键字解析规则
    protected $rules = array(
        array('name', '姓名：'), 
        array('sex', '性别：'), 
        array('birth_year', '出生日期：'), 
        array('degree', '学历：'), 
        array('major', '专业：'), 
        array('city', '现居住地：'), 
        array('work_begin', '参加工作时间：'), 
        array('residence', '户口所在地：'), 
        array('ID', '身份证：'), 
        array('phone', '联系电话：'), 
        array('email', '电子邮件：'), 
        array('address', '通信地址：'), 
        array('target_position', '期望从事职业：'), 
        array('target_industry', '期望从事行业：'), 
        array('target_city', '期望工作地区：'), 
        array('target_salary', '期望月薪：'), 
    );

    //判断模板是否匹配
    protected function isMatched($content) {
        
    }

     //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $patterns = array(
            '/<(li|h3|span)/i',
            '/<\/(li|h3|h4|span)>/i',
            '/<h4>/i',
            '/<br>|<style.*?>.*?<\/style>/i',

        );
        $replacements = array(
            '<div',
            '</div>',
            '<div>姓名：',
            '',
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
        //$end = $blocks[0][1]-2?:count($data)-1;
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

    //工作经历解析
    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $sequence = array('company', 'position', 'industry', '', 'nature', 'size' ,'salary','duty');
        $i = 0;
        $j = 0;
        $k = 0;
        $jobs = array();
        while($i < $length) {
            //正则匹配
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)：$/', $data[$i], $match)) {
                $job = array();
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                $jobs[$j++] = $job;
                $k = 1;
            }elseif($k > 0){
                if($key = $sequence[$k-1]){
                    $jobs[$j-1][$key] = $data[$i];
                }
                $k++;              
            }
            $i++;
        }
        //dump($jobs);
        $record['career'] = $jobs;
        return $jobs;
    }

    //教育经历解析
    public function education($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $k = 0;
        $education = array();
        $sequence = array('school','major','degree');
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)：$/', $data[$i], $match)){
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

    //项目经历解析
    public function projects($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('company', '所在公司：'),
            array('description', '项目描述：'),
        );
        $sequence = array('name');
        $i = 0;
        $j = 0;
        $k = 0;
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
