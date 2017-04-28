<?php
namespace app\index\controller;
use app\index\Parser\ResumeParser;
use think\Controller;

class TemplateTest extends Controller {

	//测试解析结果
    public function test() {
        $content = $this->getResume();
		//echo $content;
		$ResumeParser = new ResumeParser();
		$record = $ResumeParser->parse($content);
		dump($record);
		//return json($record);
    }

    public function getResume() {       
		$Parser = new ResumeParser();
		$path = $this->templateDir.'/'.$this->templateId.'/'.$this->path[$this->templateId][$this->pathIndex];
		//dump($path);
	    $content = $Parser->readDocument($path);
		//dump($content);
		$content = $Parser->convert2UTF8($content);
		return $content;
	}

	//查看简历内容
	public function resume() {
		$content = $this->getResume();
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
    protected $templateDir = ROOT_PATH.'resumes';
	protected $templateId = '11';
    protected $pathIndex = 3;

    protected $path = array(
        '01' => array(
            '00110225.html',
            '10102.html',
        ),
		'02' => array(
			'20151115071952872.html',
			'20151115071250685.html',
			'20151115062800372.html',
		),
		'03' => array(
			'100313.html',
			'160073.html',
		),
        '04' => array(
            '180790.html',
        ),
		'05' => array(
            '15821698556-男-上海_上海-1944886465.html',
            '简历编号18840522-罗杭金-设计总监-猎聘网简历.html'
        ),
		'06' => array(

        ),
		'07' => array(),
		'08' => array(),
		'09' => array(),
		'10' => array(),
		'11' => array(
		    'JM005403686R90250000000.html',
            '周莉娜.html',
            '米卫开.html',
            'JR148209749R90000000000.htm'
        ),
		'12' => array(
			'jm501718846r90250000000-薛明转.html',
            'jm307095523r90250000000-马超.html',
            '335202968-王宁.html',

		),
    );
}
