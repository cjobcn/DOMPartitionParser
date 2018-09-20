<?php
namespace app\index\Parser;

//51Job简历新模板
class Template13 extends AbstractParser {

    //区块标题
    protected $titles = array(
        array('target', '求职意向'),
        array('career', '工作经验'),
        array('projects', '项目经验'),
        array('education', '教育经历'),
        array('school_situation','在校情况'),
        array('skills', '技能\/语言'),
        array('certs', '证书'),
        array('trainings', '培训经历'),
        array('addition', '附加信息'),
        array('others', '下载查看完整简历'),
    );

    //关键字解析规则
    protected $rules = array(
        array('true_id', '简历ID：'),
        array('update_time', '更新时间：'),
        array('name', '姓名：', 0),
        array('city', '现居住'),
        array('last_position', '职位：'),
        array('last_company', '公司：'),
        array('industry', '行业：'),
        array('major', '专业：'),
        array('school', '学校：'),
        array('degree', '学历\/学位：'),
        array('residence', '户口\/国籍：'),
        array('marriage', '婚姻状况：'),
        array('current_salary', '目前年收入：'),
        array('height', '身高：'),
    );

    //判断模板是否匹配
    protected function isMatched($content) {

    }

    public function preprocess($content) {
        $redundancy = array(
            '/<head>.+?<\/head>/is',
            '/<script.*?>.+?<\/script>/is',
            '/<style.*?>.+?<\/style>/is',
            '/(&nbsp;)*标签：/',
            '/<span class="guan">已关联<\/span>/',
            '/<span class="small_lab" id="spanProcessStatusHead" style="display:none;">流程状态：/'
        );
        $content = preg_replace($redundancy, '', $content);
        $content = preg_replace('/class="name".*?>/','>姓名：', $content);
        return $content;
    }

    public function parse($content) {
        $record = array();
        //预处理
        $content = $this->preprocess($content);
        //分隔网页数据
        list($data, $blocks) = $this->pregParse($content);
        //dump($data);
        //dump($blocks);

        //其他解析
        $patterns = array(
            array('phone' , $this->pattern['phone']),
            array('email', $this->pattern['email']),
            array('sex', '/男|女/'),
            array('birth_year', '/（\d{4}年/'),
            array('work_year', '/\d+(?=年工作经验)/'),

        );
        $length = $blocks[0][1]-1 > 0 ?$blocks[0][1]-1 : count($data);
        $basic = array_slice($data,0, $length);
        $this->basic($basic, 0, $length - 1, $record);
        $i = 0;
        while($i < $length){
            foreach($patterns as $key => $pattern) {
                if(preg_match($pattern[1], $data[$i], $match)) {
                    $record[$pattern[0]] = $match[0];
                    unset($patterns[$key]);
                    break;
                }
            }
            $i++;
        }
        //各模块解析
        foreach($blocks as $block){
            $fun = $block[0];
            $this->$fun($data, $block[1], $block[2],$record);
        }
        if(!$record){
            sendMail(13,$content);
        }
        //dump($record);
        return $record;
    }

    //获取DOM数组
    public function getDomArray($content) {
        $content = $this->preprocess($content);
        return $this->pregParse($content, false, false);
    }

    public function target($data, $start, $end, &$record) {
        $rules = array(
            array('target_salary','期望薪资：'),
            array('target_city', '地点：'),
            array('target_position', '职能：'),
            array('target_industry', '行业：'),
            array('jump_time', '到岗时间：'),
            array('self_str', '自我评价：'),
        );
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        while($i < $length) {
            $KV = $this->parseElement($data, $i, $rules);
            if($KV && !$record[$KV[0]]){
                $record[$KV[0]] = $KV[1];
            }
            $i++;
        }
    }

    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('duty', '工作描述：'),
            array('underlings', '下属：'),
            array('report_to', '汇报对象：'),
            array('performance', '主要业绩：'),
        );
        $natures = '外资（欧美）,外资（非欧美）,合资,国企,民营公司,上市公司,创业公司,外企代表处,政府机关,事业单位,非营利机构';
        $i = 0;
        $j = 0;
        $extracted = array();
        $currentKey = '';
        $jobs = array();
        $status = 0;
        while($i < $length) {
            $data[$i] = preg_replace('/\s/','',$data[$i]);
            //正则匹配
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)$/', $data[$i], $match)) {
                $job = array();
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                //$job['position'] = $data[$i-3];
                $job['company'] = $data[$i+1];
                $jobs[$j++] = $job;
                $currentKey = '';
                //关键字匹配
            }elseif(preg_match('/^\(\d.+?\)$/',$data[$i])){
                $jobs[$j-1]['company'] = $data[$i-1];
                $extracted[] = $i-1;
                $extracted[] = $i;
                $jobs[$j-1]['industry'] = $data[++$i];
                $extracted[] = $i;
                $status = 1;
            }elseif(preg_match('/^\[\d.+?\]$/s',$data[$i])){
                if($data[$i-2] == '(兼职)'){
                    $jobs[$j-1]['position'] = $data[$i-3];
                    $jobs[$j-1]['company'] = $data[$i-1];
                }else{
                    $jobs[$j-1]['position'] = $data[$i-3];
                    $jobs[$j-1]['department'] = $data[$i-2];
                    $jobs[$j-1]['company'] = $data[$i-1];
                    $jobs[$j-1]['industry'] = $data[++$i];
                }
                $status = 2;
            }elseif(preg_match('/^(少于)?\d+(-\d+)?人/',$data[$i])){
                    $jobs[$j-1]["size"] = $data[$i];
                    $extracted[] = $i;
            }elseif(strpos($natures, $data[$i]) !== false){
                $jobs[$j-1]["nature"] = $data[$i];
                $extracted[] = $i;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                if($KV[0] == 'duty' && $status == 1) {
                    if($data[$i-1] == '(兼职)'){
                        $k = $i-2;
                    }else{
                        $k = $i-1;
                    }
                    $jobs[$j-1]['position'] = $data[$k];
                    if(in_array($k, $extracted)) {
                        unset($jobs[$j-1]['industry']);
                    }
                    if(!in_array($k-1, $extracted)){
                        $jobs[$j-1]['department'] = $data[$k-1];
                    }
                }
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }elseif($currentKey){
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
            array('company', '所属公司：'),
            array('description', '项目描述：'),
            array('duty', '责任描述：'),
        );
        $sequence = array('name');
        $i = 0;
        $j = 0;
        $k = 0;
        $projects = array();
        $currentKey = '';
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
                $currentKey = $KV[0];

            }elseif($k > 0){
                if($key = $sequence[$k-1]){
                    $projects[$j-1][$key] = $data[$i];
                    //$k++;
                    $k = 0;
                }
            } elseif($currentKey) {
                $projects[$j-1][$currentKey] .=  '#br#'.$data[$i];
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
        $k = 0;
        $education = array();
        $rules = array(
            array('class', '专业描述：'),
        );
        $sequence = array('school', 'degree', 'major');
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)$/', $data[$i], $match)){
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

}
