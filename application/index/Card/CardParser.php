<?php
/**
 * Created by PhpStorm.
 * User: Roy
 * Date: 2018/7/20
 * Time: 18:08
 */
namespace app\index\Card;
use app\index\Parser\CommonParser;
use app\index\Parser\DataConverter;
use app\index\Parser\Utility;

class CardParser {
    /**
     * 模板序号对应的匹配正则，采用顺序匹配
     * @var array
     */
    public $templateIDs = array(
        '1001' => '/<a href="\/resume\/showresumedetail\//',//列表
    );

    /**
     * 判断是否为英文简历
     * @param $content string 简历内容
     * @return bool
     */
    public function isEnglish($content) {
        if(preg_match('/The latest work|The highest education|Career Objective|Self-Assessment|Work Experiences/i', $content) &&
            !preg_match('/最近工作|最高学历|工作经|自我评价/', $content)) {
            return true;
        }

        return false;
    }

    /**
     * 判断简历是否无效
     * @param $content
     * @return bool
     */
    public function isInvalid($content) {
        $invalidPatterns = array(
            '该简历已被求职者删除，无法查看!',  //简历已被删除
            '^\{\"',                        //json格式
        );
        $pattern = '/'.implode('|', $invalidPatterns).'/';
        if(preg_match($pattern, $content)){
            return true;
        }
        return false;
    }

    /**
     * 读取文档(windows下需要将路径转为GBK)
     * @param $path
     * @return bool|string
     */
    public function readDocument($path) {
        return Utility::readDocument($path);
    }

    //转码为UTF-8
    public function convert2UTF8($content) {
        return Utility::convert2UTF8($content);
    }

    /**
     * 获取模板ID
     * @param $resume
     * @return int|string
     */
    public function getTemplateID($resume) {
        foreach($this->templateIDs as $id => $pattern){
            if(preg_match($pattern, $resume)){
                return strval($id);
            }
        }
        return false;
    }

    /**
     * 获取模板类
     * @param $resume
     * @param string $namespace
     * @return string
     */
    public function getTemplateClass($resume, $namespace = __NAMESPACE__) {
        foreach($this->templateIDs as $id => $pattern){
            if(preg_match($pattern, $resume)){
                $templateClass = $namespace.'\ReportTemplate'.$id;
                return $templateClass;
            }
        }
        return false;
    }

    /**
     * 解析简历
     * @param $resume string 简历内容
     * @param $templateId string  简历模板编号
     * @return mixed
     */
    public function parse($resume, &$templateId = '') {
        //排除英文简历和不合法的简历
        if($this->isEnglish($resume) || $this->isInvalid($resume)) return false;
        //配对简历模板
        $namespace = __NAMESPACE__;
        $templateId = $this->getTemplateID($resume);
        if($templateId)
            $templateClass = $namespace.'\Template'.$templateId;
        //dump($templateClass);
        $record = null;
        if(isset($templateClass)){
            $template = new $templateClass();
            $record = $template->parse($resume);
            if($record){
                $Converter = new DataConverter();
                $Converter->multiConvert($record);
            }
        }
        return $record;
    }

    /**
     * 获取DOM数组(pregParse或domParse获得)
     * @param $resume
     * @return mixed
     */
    public function getDomArray($resume) {
        $templateClass = $this->getTemplateClass($resume);
        $data = null;
        if($templateClass){
            $template = new $templateClass();
            $data = $template->getDomArray($resume);
        }

        return $data;
    }

    /**
     * 获取正则分割后的数组
     * @param $resume
     * @return array
     */
    public function getPregArray($resume) {
        $Parser = new CommonParser();
        $resume = $Parser->preprocess($resume);
        //dump($resume);
        $data = $Parser->pregParse($resume, false, false);
        return $data;
    }
}