<?php
/**
 * Created by PhpStorm.
 * User: DFFuture
 * Date: 2017/11/14
 * Time: 20:06
 */

namespace tests;
use app\index\Parser\ResumeParser;
use think\Exception;


class ParserTest extends TestCase {

    //测试的模板
//    protected $testTemplates = array("09");   //如果是空数组，就测试所有模板

     protected $testTemplates = array("01", "02", "03", "04", "05", "06",
         "07", "08", "09", "10", "11", "12", "13", "15", "16" , "17");

    protected $templateDir = ROOT_PATH.'resumes/';
    protected $expectedDir = ROOT_PATH.'resumes/expected/';


    /**
     * 获取实际的解析结果
     * @param string $exampleFile
     * @param string $templateId   模板ID
     * @return string
     */
    private function getParsedResult($exampleFile, $templateId) {
        $content = $this->getResume($exampleFile, $templateId);
        $ResumeParser = new ResumeParser();
        $record = $ResumeParser->parse($content,$templateId);
        $result = array(
            "template" => $templateId,
            "data" => $record
        );
        return json_encode($result);
    }

    //获取简历内容，编码统一为UTF-8
    private function getResume($exampleFile, $templateId) {
        $Parser = new ResumeParser();
        $path = $this->templateDir.$templateId.'/'.$exampleFile;
        //dump($path);
        $content = $Parser->readDocument($path);
        //dump($content);
        $content = $Parser->convert2UTF8($content);
        return $content;
    }

    /**
     * 获取期望的解析结果
     * @param string $exampleFile
     * @param $templateId
     * @return bool|null|string
     */
    private function getExpectedResult($exampleFile, $templateId) {
        $index = strrpos($exampleFile, ".");
        //$ext = substr($exampleFile, $index + 1);
        $filename = substr($exampleFile, 0, $index);
        $path = $this->expectedDir.$templateId."/{$filename}.json";
        if(is_file($path)){
            $result = file_get_contents($path);
            //json_decode($result, true);
            return $result;
        }
        return null;
    }

    /**
     * 读取目录，获取目录下的文件
     * @param $templateId
     * @return array|null
     */
    public function readDir($templateId) {
        $templateDir = $this->templateDir.$templateId;
        if($files = scandir($templateDir)) {
            return $files;
        }else{
            self::throwException(new Exception("{$templateId}测试简历目录不存在！"));
        }
        return null;
    }

    /**
     * 测试解析结果
     * 测试命令：php think unit（如果在Windows的CMD下，需要设置编码为UTF-8，只要输入命令：CHCP 65001即可）
     */
    public function testParser() {
        foreach($this->testTemplates as $templateId) {
            if($files = $this->readDir($templateId)) {
                foreach($files as $file) {
                    if(!in_array($file, array(".", ".."))) {
                        dump($templateId."/".$file);
                        $actual = $this->getParsedResult($file, $templateId);
                        $expected = $this->getExpectedResult($file, $templateId);
                        $this->assertJsonStringEqualsJsonString($expected, $actual);
                    }
                }
            }
        }
    }
}