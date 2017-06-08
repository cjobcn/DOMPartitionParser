<?php
namespace app\index\Parser;

class Template08 extends AbstractParser {
     //区块标题
    protected $titles = array(
        array('summary', '基本概况'), 
        array('current', '职业近况'), 
        array('edu', '教育概况'), 
        array('salary', '薪水情况'), 
        array('basic', '基本资料'), 
        array('career', '工作经验'), 
        array('projects', '项目经验'),
        array('education', '教育背景'), 
        array('languages', '语言及方言'), 
        array('trainings', '培训经历'),
        array('addition', '职业技能与特长'),
        array('skills', 'IT技能'), 
        array('others', '附件'),
    );

    //关键字解析规则
    protected $rules = array(
        array('phone', '手机：'), 
        array('email', '电子邮箱：'), 
        array('city', '现居住城市：'), 
        array('address', '通讯地址：'), 
        array('work_status', '求职状态：'), 
        array('birth_year', '出生日期：'), 
        array('residence', '户口所在地：'), 
        array('marriage', '婚姻状况：'), 
        array('self_str', '自我评价'), 
        array('target_salary', '期望薪水：')
    );

     //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $patterns = array(
            '/<dd|<dt|<b/i',
            '/<\/dd>|<\/dt>|<\/b>/i',
            '/<h3>/i',
            '/<\/span>\s*?<span.*?>/i'
        );
        $replacements = array(
            '<div',
            '</div>',
            '<h3>#h3#',
            '|',
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
        list($data, $blocks) = $this->domParse($content, 'div');
        //dump($blocks);
        //dump($data);
        if(!$blocks) return false;
        //其他解析
        $i = 0;
        $end = $blocks[0][1];
        while($i < $end) {
            if(preg_match('/#h3#(.+)/', $data[$i], $match)) {
                $record['name'] = $match[1];
            }elseif(preg_match('/更新日期：(\d{4})\D+(\d{1,2})\D+(\d{1,2})/', $data[$i], $match)){
                $record['update_time'] = $match[1].'-'.$match[2].'-'.$match[3];
            }
            $i++;
        }
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

    public function summary($data, $start, $end, &$record) {
        if(preg_match('/男|女/', $data[$start], $match)){
            $record['sex'] = $match[0];
        }      
    }

    public function current($data, $start, $end, &$record) {
        $data = explode('|', $data[$start]);
        $record['last_position'] = $data[0];
        $record['last_company'] = $data[1];
        $record['industry'] = $data[2];
    }

    public function edu($data, $start, $end, &$record) {
        $data = explode('|', $data[$start]);
        $record['degree'] = $data[0];
        $record['major'] = $data[1];
        $record['school'] = $data[2];
    }

    public function salary($data, $start, $end, &$record) {
        $this->basic($data, $start, $end, $record);
    }

    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        
        $rules = array(
            array('industry', '所属行业：'), 
            array('city', '工作地点：'), 
            array('nature', '工作性质：'), 
            array('position_class', '职位类别：'), 
            array('position_level', '职位级别：'), 
            array('duty', '职责和业绩：'),
            array('report_to', '汇报人：'),
            array('underlings', '下属：'), 
        );
        $keywords = implode(',',array_column($rules, 1));
        $jobs = array();
        $i = 0;
        $j = 0;
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)/',$data[$i], $match)){
                $job = array();
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                $job['company'] = $data[++$i];
                if(preg_match('/(.+)?\[(.+)?\]/', $data[++$i], $match)) {
                    $job['position'] = $match[1];
                    $job['department'] = $match[2];
                }else{
                    $job['position'] = $data[$i];
                }         
                $jobs[$j] = $job;
                $j++;
            }elseif($KV = $this->parseElement($data, $i, $rules, $keywords)){
                $jobs[$j-1][$KV[0]] = $KV[1];
            }
            $i++;
        }
        $record['career'] = $jobs;
        return $jobs;
    }

    public function education($data, $start, $end, &$record) {
        $pattern = '/^(?P<start_time>\d{4}\D+\d{1,2})\D*(?P<end_time>\d{4}\D+\d{1,2}|至今|现在)$/';
        $rules = array(
            array('major', '专业：'),
        );
        $sequence = array('pattern', array('school', 'degree'));
        $conditions = array(
            'pattern' => $pattern,
            'rules' => $rules,
            'sequence'=> $sequence
        );
        $record['education'] = $this->blockParse($data, $start, $end, $conditions);
        return $record['education'];
    }

    public function projects($data, $start, $end, &$record) {
        $rules = array(
            array('description', '项目描述：'),
            array('duty', '项目职责：'),
        );
        $sequence = array('pattern', array('name'));
        $pattern = '/^(?P<start_time>\d{4}\D+\d{1,2})\D+(?P<end_time>\d{4}\D+\d{1,2}|至今|现在)$/';
        $conditions = array(
            'rules' => $rules,
            'pattern' => $pattern,
            'sequence'=> $sequence
        );
        $record['projects'] = $this->blockParse($data, $start, $end, $conditions);
        return $record['projects'];
    }

}
