<?php
namespace app\index\Parser;

class Template17 extends AbstractParser {
    //模块标题
    protected $titles = array(
        array('evaluation', '自我评价'),
        array('education', '教育经历'),
        array('projects', '项目经历'),
        array('career', '工作经历'),
        array('languages', '语言能力'),
        array('addition', '附加信息'),
        array('history', '历史版本'),
    );

    //关键字解析规则
    protected $rules = array(
        array('true_id', '简历编号：'),
        array('update_time', '最后登录：'),
        array('name', '姓名：'),
        array('sex', '性别：'),
        array('age', '年龄：'),
        array('phone', '联系电话：'),
        array('email', '电子邮件：'),
        array('degree', '教育程度：'),
        array('marriage', '婚姻状况：'),
        array('city', '所在地：'),
        array('work_year', '工作年限：'),
        array('industry', '所在行业：'),
        array('last_company', '公司名称：'),
        array('last_position', '所任职位：'),
        array('current_salary', '目前薪资：'),
        array('target_industry', '期望行业：'),
        array('target_position', '期望职位：'),
        array('target_city', '期望地点：'),
        array('target_salary', '期望月薪：'),
    );

    protected $separators = array(
        '<\/.+?>',      //html结束标签
        '\|',           // |
        '<br.*?>',      // 换行标签
        '(&nbsp;)+',
        '\n'
    );

    //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $redundancy = array(
            '/<head>.+?<\/head>/is',
            '/<script.*?>.+?<\/script>/is',
            '/<style.*?>.+?<\/style>/is',
            '/搜索同事/',
            '/<\/font>/'
        );
        $content = preg_replace($redundancy, '', $content);
        return $content;
    }

    //根据模板解析简历
    public function parse($content) {
        $record = array();
        $content = $this->preprocess($content);
        list($data, $blocks) = $this->pregParse($content,
            false, true, $this->separators, $hData);
        //dump($hData);
        //dump($blocks);
        $end = $blocks?$blocks[0][1]-2:count($data)-1;
        $this->basic($data,0,$end, $record);
        if(preg_match_all('/<img class=\"(email|telphone)\"\s+src=\"(.+?)\">/s', $content, $match)){
            foreach($match[1] as $index=>$keyword) {
                $keyword = ($keyword == 'telphone')?'phone':'email';
                $record[$keyword . '_image'] = $match[2][$index];
            }
        }
        foreach($blocks as $block){
            $function = $block[0];
            $this->$function($data, $block[1], $block[2],$record, $hData,$content);
        }
        if(!$record){
            sendMail(17,$content);
        }
        //dump($record);
        return $record;
    }

    //获取DOM数组
    public function getDomArray($content) {
        $content = $this->preprocess($content);
        return $this->pregParse($content, false, false);
    }

    public function evaluation($data, $start, $end, &$record) {
        $i = $start;
        $evaluation = '';
        while($i <= $end){
            $evaluation .= $data[$i++];
        }
        $evaluation = preg_replace('/.*自我评价：/s','',$evaluation);
        $record['self_str'] = $evaluation;

        return $evaluation;
    }

    public function career($data, $start, $end, &$record, $hData) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $hData = array_slice($hData,$start, $length);
        $i = 0;
        $j = 0;
        $currentKey = '';
        $jobs = array();
        $rules = array(
            array('underlings', '下属人数：'),
            array('city', '所在地区：'),
            array('duty', '工作职责和业绩：'),
            array('report_to', '汇报对象：'),
            array('department', '所在部门：')
        );
        $pattern = '/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)$/';
        $patterns2 = array(
            'size' => '/(少于)?\d+-(\d+)?人/',
            'industry' => '/data-selector="colleague_\d+/',
            'position' => '/<div class="job-list-title"><strong>/',
            'description' => '/resume-work-info/'
        );
        $natures = '中外合营(合资/合作),外商独资/外企办事处,私营/民营企业,'.
            '国有企业,国内上市公司,政府机关／非盈利机构,事业单位,其他';
        while($i < $length) {
            //正则匹配
            if(preg_match($pattern, $data[$i], $match)) {
                $job = array();
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                $job['company'] = $data[++$i];
                $i++;
                $jobs[$j++] = $job;
            }elseif(strpos($natures, $data[$i]) !=false){
                $jobs[$j-1]['nature'] = $data[$i];
                $jobs[$j-1]['industry'] = $data[++$i];
            }elseif($key = $this->pregExtract($hData[$i], $patterns2)){
                $jobs[$j-1][$key] = $data[$i];
                $currentKey = $key;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }elseif($currentKey == 'duty' | $currentKey == 'description'){
                $jobs[$j-1][$currentKey] .=  '#br#'.$data[$i];
            }
            $i++;
        }
        $record['career'] = array_merge($jobs);
        return $jobs;
    }

    public function pregExtract($data, $patterns) {
        foreach($patterns as $key=>$pattern){
            if(preg_match($pattern, $data))
                return $key;
        }
        return false;
    }
    //老的解析
    public function education1($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $BlockEdu = new BlockEdu();
        $education = $BlockEdu->parse($data, '4');
        $record['education'] = $education;
        return $education;
    }
    public function education2($html){
        if(preg_match('/<\/i>教育经历<\/h2>/',$html)){  //获取第一段学校
            preg_match('/<h2><i class="icons32 icons32-education"><\/i>教育经历<\/h2>[\s\S]+?(?=<h2>|$)/',$html,$education);
            preg_match_all('/<table>.+?<\/table>/',$education[0],$educations);
            if($educations[0]){
                //$last_education = array_pop($educations[0]);
                $timePattern = '/（(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)）/';
                $educationArr = array();
                foreach($educations[0] as $key=>$value){
                    preg_match('/(?<=<strong>).+?(?=（|<\/strong>)/',$value,$school);
                    preg_match('/(?<=<td>专业：).+?(?=<)/',$value,$major);
                    preg_match('/(?<=<td>学历：).+?(?=<)/',$value,$degree);
                    //preg_match('/[^（    ]+?(?=\.)/',$value,$school_year);
                    preg_match($timePattern,$value,$timematch);
                    $educationArr[$key]['school'] = HtmlToText($school[0]);
                    $educationArr[$key]['major'] = HtmlToText($major[0]);
                    $educationArr[$key]['degree'] = HtmlToText($degree[0]);
                    $educationArr[$key]['start_time'] = Utility::str2time($timematch[1]);
                    $educationArr[$key]['end_time'] = Utility::str2time($timematch[2]);
                }
                return array_merge($educationArr);
            }
        }
        return null;
    }
    //有插图的解析
    public function education($data, $start, $end, &$record,$hData,$html) {
        $html = preg_replace('/\s\B/','',$html);
        preg_match('/<h2><i class="icons32 icons32-education"><\/i>教育经历<\/h2>[\s\S]+?(?=<h2>|$)/',$html,$education);
        preg_match_all('/<div class="info">.+?<\/div>/',$education[0],$educations);
        if(!$educations[0]){
            $educationArr = $this->education2($html);
            if($educationArr){
                $record['education'] = $educationArr;
            }else{
                $this->education1($data, $start, $end, $record);
            }
        }else{
            $data = $educations[0];
            $timePattern = '/（(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)）/';
            foreach($data as $key=>$value){
                preg_match('/(?<=<p>).+(?=（)/',$value,$school);
                preg_match('/(?<=<p class="degree">).+(?=<\/p>)/',$value,$degreemajor);
                $degreemajor = HtmlToText($degreemajor[0]);
                $degreemajorArr = explode('|',$degreemajor);
                $educationArr[$key]['school'] = $school[0];
                $educationArr[$key]['degree'] = $degreemajorArr[0];
                $educationArr[$key]['major'] = $degreemajorArr[1];
                preg_match($timePattern,$value,$timematch);
                $educationArr[$key]['start_time'] = Utility::str2time($timematch[1]);
                $educationArr[$key]['end_time'] = Utility::str2time($timematch[2]);
            }
            $record['education'] = array_merge($educationArr);
            return $education;
        }
    }


    public function projects($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $projects = array();
        $rules = array(
            array('position', '项目职务：'),
            array('company', '所在公司：'),
            array('description', '项目简介：'),
            array('duty', '项目职责：'),
            array('performance', '项目业绩：'),
        );
        $currentKey = '';
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)$/', $data[$i], $match)){
                $project = array();
                $project['start_time'] = Utility::str2time($match[1]);
                $project['end_time'] = Utility::str2time($match[2]);
                $project['name'] = $data[++$i];
                $projects[$j++] = $project;
            }elseif($KV = $this->parseElement($data, $i, $rules)){
                $projects[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            } elseif($currentKey){
                $projects[$j-1][$currentKey] .=  '#br#'.$data[$i];
            }
            $i++;
        }
        $record['projects'] = array_merge($projects);
        return $projects;

    }

}
