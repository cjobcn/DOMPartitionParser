<?php
namespace app\index\Parser;

class Template05 extends AbstractParser {

    protected $website = '猎聘网';
     //区块标题
    protected $titles = array(
        //array('basic', '个人信息'), 
        array('current', '目前职业概况'), 
        array('target', '职业发展意向'), 
        array('career', '工作经历'), 
        array('education', '教育经历'), 
        array('languages', '语言能力'), 
        array('projects', '项目经历'),
        array('evaluation', '自我评价'),
        array('addition', '附加信息'),
    );

    //关键字解析规则
    protected $rules = array(
        array('update_time', '最新登录：'), 
        array('name', '姓名：'), 
        array('sex', '性别：'), 
        array('phone', '手机号码：'), 
        array('age', '年龄：'), 
        array('email', '电子邮件：'), 
        array('degree', '教育程度：'), 
        array('work_year', '工作年限：'), 
        array('marriage', '婚姻状况：'), 
        array('work_status', '职业状态：'), 
        array('city', '所在地：'), 
        array('nationality', '国籍：'),
        array('residence', '户籍：'),

        array('industry', '所在行业：'), 
        array('last_company', '公司名称：'), 
        array('last_position', '所任职位：'), 
        array('current_salary', '目前年薪：'), 

        array('target_industry', '期望行业：'), 
        array('target_position', '期望职位：'), 
        array('target_city', '期望地点：'), 
        array('target_salary', '期望年薪：'), 
    );

    //判断模板是否匹配
    protected function isMatched($content) {
        $pattern = '/简历编号：\d{8}|猎聘通/';
        return preg_match($pattern,$content);
    }

    //对简历内容预处理,使内容可以被解析
    public function preprocess($content) {
        $pattern = array(
            '/<(section|th|h2|td|h4)/i',
            '/<\/(section|th|h2|td|h4)>/i',
            '/<br.*?>|&nbsp;?/i',
            '/\|/i',
        );
        $replacements = array(
            '<div',
            '</div>',
            '',
            '</div><div>',
        );
        $content = preg_replace($pattern, $replacements, $content);
        return $content;
    }

    //根据模板解析简历
    public function parse($content) {
        $record = array();
        //预处理
        $content = $this->preprocess($content);

        list($data, $blocks) = $this->domParse($content,'div');
        //dump($blocks);
        //dump($data);
        $end = $blocks[0][1]-2?:count($data)-1;
        //其他解析
        $this->basic($data,0,$end, $record);
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

    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules1 = array(
            array('nature', '公司性质：', 0),
            array('size', '公司规模：', 0),
            array('industry', '公司行业：', 0),
            array('desciption', '公司描述：', 0),
        );
        $rules2 = array(
            array('city', '所在地区：'),
            array('duty', '工作职责：'),
            array('department', '所在部门：'),
            array('reportto', '汇报对象：'),
            array('underlings', '下属人数：'),
            array('salary', '薪酬情况：'),
            array('duty', '工作职责：')
        );
        $pattern1 = '/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今) (?P<company>.+?) (（\d+.+）)$/';
        $pattern2 = '/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今) (.+)/';
        $i = 0;
        $j = 0;
        $jobs = array();
        while($i < $length) {
            if(preg_match($pattern1, $data[$i], $match)){
                $job = array();
                $job['company'] = $match['company'];
            }elseif($KV = $this->parseElement($data, $i, $rules1)){
                $job[$KV[0]] = $KV[1];
                $i = $i + $KV[2];
            }elseif(preg_match($pattern2, $data[$i], $match)){
                $job['position'] = $match[3];
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                $jobs[$j++] = $job;
            }elseif($KV = $this->parseElement($data, $i, $rules2)) {
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
            }
            $i++;
        }
        $record['career'] = $jobs;
        return $jobs;
    }

    public function projects($data, $start, $end, &$record) {
        $rules = array(
            array('position', '项目职务：'),
            array('compamy', '所在公司：'),
            array('desciption', '项目简介：'),
            array('duty', '项目职责：'),
            array('performance', '项目业绩：'),
        );
        $pattern = '/^(?P<start_time>\d{4}\D+\d{1,2})\D+(?P<end_time>\d{4}\D+\d{1,2}|至今) (?P<name>.+?)$/';
        $conditions = array(
            'rules' => $rules,
            'pattern' => $pattern,
        );
        $record['projects'] = $this->blockParse($data, $start, $end, $conditions);
        return $record['projects'];
    }

    public function education($data, $start, $end, &$record) {
        $pattern = '/^(?P<school>.+?)(?P<start_time>\d{4}\D+\d{1,2})\D*(?P<end_time>\d{4}\D+\d{1,2}|至今)$/';
        $rules = array(
            array('major', '专业：', 0),
            array('degree', '学历：', 0),
        );
        $conditions = array(
            'pattern' => $pattern,
            'rules' => $rules
        );
        $record['education'] = $this->blockParse($data, $start, $end, $conditions);
        return $record['education'];
    }

}
