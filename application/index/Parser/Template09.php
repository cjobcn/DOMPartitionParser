<?php
namespace app\index\Parser;

class Template09 extends AbstractParser {

    public $website  = '前程无忧(51job)';

     //区块标题
    protected $titles = array(
        //array('basic', '基本信息|个人简历'), 
        array('evaluation', '自我评价'), 
        array('target', '求职意向'),
        array('career', '工作经验|工作经历'),
        array('projects', '项目经验|项目经验：'),
        array('education', '教育经历'), 
        array('trainings', '培训经历'),
        array('certs', '证书'),
        array('prizes', '所获奖励|所获奖项'),
        array('practices', '社会实践|学生实践经验|校内职务|社会经验'),
        array('languages', '语言能力'), 
        array('skills', 'IT技能'), 
        array('addition', '附加信息'),
        array('others', '其他信息|简历小精灵')
    );

    //关键字解析规则
    protected $rules = array(
        array('applicant_company', '应聘职位：'),
        array('applicant_position', '应聘公司：'),
        array('update_time', '更新时间：|投递时间：'),
        array('resume_keywords', '简历关键字：'),
        array('name', '姓名：'),
        array('height', '身高：'),
        array('marriage', '婚姻状况：'),
        array('sex', '性别：'),
        array('birth_year', '出生日期：'), 
        array('city', '居住地：'), 
        array('work_year', '工作年限：|工作经验：'),
        array('residence', '户口：'), 
        
        array('address', '地址：'), 
        array('postcode', '邮编：'), 
        array('email', '电子邮件：|E-mail：'), 
        array('phone', '移动电话：|电话：|手机：'),

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

    //分割符
    protected $separators = array(
        '<\/.+?>',      //html结束标签
        '\|',           // |
        '\/?<br.*?>',      // 换行标签
        // '\r\n'
    );

     //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $redundancy = array(
            '/<head>.+?<\/head>/is',
            '/<script.*?>.+?<\/script>/is',
            '/<style.*?>.+?<\/style>/is',
            '/<div class="keydiv">.+?<\/div>/s'   //删除关键字标签，否则会对分区产生影响
        );
        $content = preg_replace($redundancy, '', $content);
        $content = preg_replace(array('/\?/', '/<a name="basic_position">/'), array(' ', '姓名：'), $content);
        return $content;
    }

    //根据模板解析简历
    public function parse($content) {
        $record = array();
        //判断内容与模板是否匹配
        //if(!$this->isMatched($content)) return false;
        //预处理
        $content = $this->preprocess($content);
        //判断是否是word转换的html文档，如果是采用DOM解析，否则采用PREG解析
        if(preg_match('/urn:schemas-microsoft-com:office:office/',$content)){
            list($data, $blocks) = $this->domParse($content, 'td', false);
        }else{
            list($data, $blocks) = $this->pregParse($content);
        }
        //dump($blocks);
        //dump($data);
        //其他解析
        $length = $blocks[0][1]?$blocks[0][1] - 1:count($data);
        $basic = array_slice($data, 0 , $length);
        $i = 0;
        while($i < $length) {
            $KV = $this->parseElement($basic, $i);
            if($KV){
                $record[$KV[0]] = $KV[1];
                unset($basic[$i]);
                $i = $i + $KV[2];
                unset($basic[$i]);
            }
            $i++;
        }
        if(isset($record['address'])) {
            if(preg_match('/(.+?)（邮编：(\d+)）/s',$record['address'], $match)) {
                $record['address'] = trim($match[1]);
                $record['postcode'] = $match[2];
            }
        }
        $patterns = array(
            array('name', '/(.+)\s*\(ID:(\d{5,})\)/', 1),
            array('true_id', '/\(ID:(\d{5,})\)/', 1),
            array('sex', '/^(男|女)$/', 1),
            array('marriage', '/^(未婚|已婚)$/', 1),
            array('birth_year', '/（(\d{4})\s*年/', 1),
            array('work_year', '/(.+?年(以上)?)工作经验/', 1),
        );
        $i = 0;
        $extracted = array();
        $restBasic = array_values($basic);
        //dump($basic);
        while($i < count($restBasic)){
            if($restBasic[$i]){
                foreach($patterns as $key=>$pattern){
                    if(preg_match($pattern[1], $restBasic[$i], $match)) {
                        $index = $pattern[2]?:0;
                        $record[$pattern[0]] = $match[$index];
                        $extracted[] = $i;
                    }
                }
            }
            $i++;
        }
        //dump($extracted);
        if(!isset($record['name'])) {
            $k = $extracted[0] - 1;
            while($k >= 0){
                if (!preg_match('/匹配度|标签：|应届毕业生|在读学生|\%|51job|基 本 信 息|简历/', $restBasic[$k]) &&
                    !$this->isKeyword($restBasic[$k])) {
                    $record['name'] = $restBasic[$k];
                    break;
                }
                $k --;
            }
        }
        //各模块解析
        foreach($blocks as $block){
            $this->$block[0]($data, $block[1], $block[2],$record);
            //dump($record);
        }

        //dump($record);
        return $record;
    }

    //获取DOM数组
    public function getDomArray($content) {
        $content = $this->preprocess($content);
        if(preg_match('/urn:schemas-microsoft-com:office:office/',$content)){
            return $this->domParse($content, 'td', false, false);
        }else{
            return $this->pregParse($content, false, false);
        }
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
        $currentKey = '';
        $matchType = 0;
        while($i < $length) {
            //正则匹配
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)：(.+)/', $data[$i], $match)) {
                if($j==0) {
                    if(preg_match('/\((少于)?\d|\[/',$match[3]))
                        $matchType = 1;
                }
                if($matchType == 1 && preg_match('/\((少于)?\d|\[/',$match[3])) {
                    $job = array();
                    $job['start_time'] = Utility::str2time($match[1]);
                    $job['end_time'] = Utility::str2time($match[2]);
                    $job['company'] = preg_split('/\((少于)?\d|\[/',$match[3])[0];
                    if(preg_match('/(少于)?\d+(-\d+)?人/',$match[3], $size)){
                        $job['size'] = $size[0];
                    }
                    $jobs[$j++] = $job;
                    $k = 1;
                }elseif($matchType == 0){
                    $job = array();
                    $job['start_time'] = Utility::str2time($match[1]);
                    $job['end_time'] = Utility::str2time($match[2]);
                    $job['company'] = preg_split('/(\(|\（)(少于)?\d|\[/',$match[3])[0];
                    $jobs[$j++] = $job;
                    $k = 1;
                }else{
                    $jobs[$j-1]['duty'] .= $data[$i];
                }
            //关键字匹配
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                //if(isset($jobs[$j-1]['industry'])) $k=1;
                $currentKey = $KV[0];
            //顺序匹配
            }elseif($k > 0 && $k < count($sequence) + 1){
                if(strlen($data[$i]) > 100){
                    $k = count($sequence);
                }
                if($key = $sequence[$k-1]){
                    $jobs[$j-1][$key] = $data[$i];
                    $currentKey = $key;
                    $k++;
                }
            }elseif($currentKey == 'duty' || $currentKey == 'performance') {
                $jobs[$j-1][$currentKey] .=  '#br#'.$data[$i];
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
            array('company', '公司：'),
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
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)(：| )(.+)/', $data[$i], $match)) {
                $project = array();
                $project['start_time'] = Utility::str2time($match[1]);
                $project['end_time'] = Utility::str2time($match[2]);
                $project['name'] = trim($match[4]);
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
        $degreeDict = array('初中及以下', '初中', '高中', '中技', '中专', '大专', '本科', '硕士', '博士', 'MBA');
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)$/', $data[$i], $match)){
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
        foreach($education as $key => $edu) {
            if(in_array($edu['major'], $degreeDict)){
                $education[$key]['class'] = $edu['degree']?:'';
                $education[$key]['degree'] = $edu['major'];
                $education[$key]['major'] = '';
            }elseif(!in_array($edu['degree'], $degreeDict)){
                $education[$key]['class'] = $edu['degree'];
                $education[$key]['degree'] = '';
            }
        }
        //dump($education);
        $record['education'] = $education;
        return $education;
    }


    
}
