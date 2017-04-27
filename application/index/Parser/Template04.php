<?php
namespace app\index\Parser;

class Template04 extends AbstractParser {
    protected $website = '中国人才热线';
    //模块标题
    protected $titles = array(
        array('basic' , '【基本信息】'),
        array('link' , '【联系方式】'),
        array('career', '【工作经验】'),
        array('projects', '【工作经历详细介绍】'),
        array('skills' , '【技能专长】'),
        array('education'  , '【教育背景】'),
    );

    //关键字解析规则
    protected $rules = array(
        array('name', '姓名：'),
        array('sex', '性别：'),
        array('height', '身高：'),
        array('birth_year', '出生日期：'),
        array('marriage', '婚姻状况：'),
        array('city', '目前所在地：'),
        array('residence', '户囗所在地：'),

        array('degree', '最高学历：'),
        array('major', '专业：'),
        
        array('last_position', '目前岗位：'),
        array('industry', '目前行业：'),
        array('current_salary', '目前月薪：'),

        array('target_salary', '期望月薪：'),
        array('target_industry', '意向行业：'),
        array('target_position', '意向岗位：'),
        array('target_city', '意向地区：'),
        array('jump_time', '可到岗时间：'),          //到岗时间

        array('phone', '手机号码：'),
        array('email', 'Email：'),
        array('address', '通信地址：'),
    );

    //判断模板是否匹配
    protected function isMatched($content) {
        return preg_match('/\(编号:J\d{7}\)的简历/',$content);
    }

    //根据模板解析简历
    public function parse($content) {
        $record = array();
        if(!$this->isMatched($content)) return false;

        list($data, $blocks) = $this->domParse($content);
        //dump($blocks);
        //dump($data);
        if(!$blocks) return false;
        for($i=0;$i<$blocks[0][1];$i++) {
            if(preg_match('/(?<=最后更新简历时间：)\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/',$data[$i],$match))
                $record['update_time'] = $match[0];
        }
        
        foreach($blocks as $block){
            $this->$block[0]($data, $block[1], $block[2],$record);
        }

        //dump($record);
        return $record;
    }

    //获取DOM数组
    public function getDomArray($content) {
        $content = $this->preprocess($content);
        return $this->domParse($content, 'td', true, false);
    }

    public function link($data, $start, $end, &$record) {
        $this->basic($data, $start, $end, $record);
    }

    public function career($data, $start, $end, &$record) {
        $rules = array(
            array('salary', '月薪：'),     
        );
        $pattern = '/^(?P<start_time>\d{4}\D+\d{1,2})\D*(?P<end_time>\d{4}\D+\d{1,2}|至今)/';
        $sequence = array('pattern',array('position', 'company', 'duty'));
        $conditions = array(
            'rules' => $rules,
            'pattern' => $pattern,
            'sequence' => $sequence
        );
        $record['career'] = $this->blockParse($data, $start, $end, $conditions);
        return $record['career'];
    }

    public function skills($data, $start, $end, &$record) {
        if($start<= $end){
            $record['skills'] = $data[$start];
        }
    }

    public function education($data, $start, $end, &$record) {
        $pattern = '/^(?P<start_time>\d{4}\D+\d{1,2})\D*(?P<end_time>\d{4}\D+\d{1,2}|至今)$/';
        $sequence = array('pattern',array('degree', 'major', 'school', 'class'));
        $conditions = array(
            'pattern' => $pattern,
            'sequence' => $sequence
        );
        $record['education'] = $this->blockParse($data, $start, $end, $conditions);
        return $record['education'];
    }

}
