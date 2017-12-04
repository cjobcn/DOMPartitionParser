<?php
namespace app\index\controller;
use app\index\Parser\DataExtractor;
use app\index\Parser\DataConverter;
use app\index\Parser\ResumeParser;
use think\Controller;

class TemplateTest extends Controller {

	//显示解析结果
    public function printResult() {
        $result = $this->getResult();
		//dump($result["template"]);
		//dump($result["data"]);
        return json($result);
    }

    public function getResult() {
        $content = $this->getResume();
        $ResumeParser = new ResumeParser();
        $record = $ResumeParser->parse($content,$templateId);
        $result = array(
            "template" => $templateId,
            "data" => $record
        );
        return $result;
    }

    //获取简历内容，编码统一为UTF-8
    public function getResume() {       
		$Parser = new ResumeParser();
		$path = $this->templateDir.'/'.$this->templateId.'/'.$this->path[$this->templateId][$this->pathIndex];
		//dump($path);
	    $content = $Parser->readDocument($path);
		//dump($content);
		$content = $Parser->convert2UTF8($content);
		return $content;
	}

	//打印简历内容
	public function resume() {
		$content = $this->getResume();
		//dump($content);
		//ParserLog::toSupport($content);
		echo $content;
	}

	//查看dom数组
    public function dom() {
		$Parser = new ResumeParser();
		$content = $this->getResume();

		$data = $Parser->getDomArray($content);
		//dump($data);
		$this->assign('data',$data);
		return $this->fetch('dom');
		
	}

    public function preg() {
        $Parser = new ResumeParser();
        $content = $this->getResume();

        $data = $Parser->getPregArray($content);
        //dump($data);
        $this->assign('data',$data);
        return $this->fetch('dom');
    }


    protected $templateDir = ROOT_PATH.'resumes';
	protected $templateId = '09';
    protected $pathIndex = 14;

    protected $path = array(
        '00' => array(
            '00109094.html',
            '1495433609.html',
            '智联招聘_蒋莹_中文_20131226_40902574.html',
            '1495075697.html',
            '00109094.html',
            '1495079827.html'
        ),
        '01' => array(
            'E01.html',
            'E02.html',
            'E03.html',
            'E04.html',
        ),
		'02' => array(
			'E01.html',
			'E02.html',
		),
		'03' => array(
			'E01.html',
			'E02.html',
		),
        '04' => array(
            'E01.html',
        ),
		'05' => array(
            'E01.html',
            'E02.html',
        ),
		'06' => array(
		    'E01.html',
            'E02.html',
            'E03.html',
        ),
		'07' => array(
            'E01.htm'
        ),
		'08' => array(
            'E01.htm'
        ),
		'09' => array(
		    'E01.mht',
		    'E02.mht',
            'E03.mht',
            'E04.mht',
            'E05.mht',
            'E06.mht',
		    'E07.htm',
		    'E08.htm',
            'E09.htm',
            'E10.htm',
		    'E11.html',
		    'E12.html',
		    'E13.html',
		    'E14.html',
		    'E15.html',
            'E16.html',
            'E17.html',
            'E18.html',
            'E19.html',
            'E20.html',
            'E21.html'
        ),
		'10' => array(
		    'E01.doc',
		    'E02.doc',
		    'E03.doc',
            'E04.doc',
		    'E05.htm',
            'E06.htm',
            'E07.Html',
            'E08.Html',
            'E09.html',
            'E10.html'
        ),
		'11' => array(
            'E01.htm',
		    'E02.html',
		    'E03.html',
		    'E04.html',
		    'E05.html',
		    'E06.html',
            'E07.html',
            'E08.html',
            'E09.html',
        ),
		'12' => array(
			'E01.html',
            'E02.html',
            'E03.html',
		),
        '13' => array(
            'E01.html',
            'E02.html',
            'E03.html',
            'E04.html',
            'E05.html',
            'E06.html',
        ),
        '14' => array(
            'E01.html',
            'E02.html',
            'E03.html'
        ),
        '15' => array(
            'E01.html',
        ),
        '16' => array(
            'E01.html',
            'E02.html',
            'E03.html'
        ),
        '17' => array(
            'E01.html',
            'E02.html'
        ),
        'to_support' => array(
            '3110746.html',
            '51job_saurabhgoyal(322093637).mht',
            '1495621843.html'
        ),
    );
}
