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
            '/搜索同事/'
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
                $record[$keyword . '_image'] = $match[2][$index];
            }
        }
        foreach($blocks as $block){
            $this->$block[0]($data, $block[1], $block[2],$record, $hData);
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
            array('city', '工作地区：'),
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
                $jobs[$j-1][$currentKey] .=  $data[$i];
            }
            $i++;
        }
        $record['career'] = $jobs;
        return $jobs;
    }

    public function pregExtract($data, $patterns) {
        foreach($patterns as $key=>$pattern){
            if(preg_match($pattern, $data))
                return $key;
        }
        return false;
    }

    public function education($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $BlockEdu = new BlockEdu();
        $education = $BlockEdu->parse($data, '4');
        $record['education'] = $education;
        return $education;
    }

}
