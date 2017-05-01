<?php
/**
 * Created by PhpStorm.
 * User: DFFuture
 * Date: 2017/5/1
 * Time: 17:40
 */

namespace app\index\Parser;


class CommonParser extends AbstractParser {

    //区块标题
    protected $titles = array(
        array('target', '求职意向'),
        array('evaluation', '自我评价'),
        array('career', '工作经历|工作经验'),
        array('projects', '项目经历|项目经验'),
        array('education', '教育经历'),
        array('school_situation', '在校学习情况'),
        array('practices', '在校实践经验'),
        array('training', '培训经历'),
        array('certs', '证书'),
        array('languages', '语言能力'),
        array('skills', '专业技能|IT技能'),
        array('prizes', '获得荣誉|所获奖励'),
        array('others', '附件'),
        array('interest', '兴趣爱好'),
        array('resume_content', '简历内容'),
        array('addition', '附加信息'),
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

    public function preprocess($content) {
        $redundancy = array(
            '<head>.+?<\/head>',
            '<script.*?>.+?<\/script>',
            '<style.*?>.+?<\/style>'
        );
        $pattern = '/'.implode('|', $redundancy).'/is';
        $content = preg_replace($pattern, '', $content);
        return $content;
    }

    public function parse($content) {
        $content = $this->preprocess($content);
        list($data, $blocks) = $this->pregParse($content);
        dump($data);
        dump($blocks);
    }

}