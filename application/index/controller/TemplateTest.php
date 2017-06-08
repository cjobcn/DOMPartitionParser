<?php
namespace app\index\controller;
use app\index\Parser\DataExtractor;
use app\index\Parser\ParserLog;
use app\index\Parser\ResumeParser;
use think\Controller;

class TemplateTest extends Controller {

	//测试解析结果
    public function test() {
        $content = $this->getResume();
		//echo $content;
		$ResumeParser = new ResumeParser();
		$record = $ResumeParser->parse($content,$templateId);
		dump($templateId);
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

    public function xiace() {
        $extractor = new DataExtractor();
        $rawData = '邮箱：112353@qq.com 手机：17717291341';
        $data = $extractor->extract('email', $rawData);
        dump($data);

    }

    protected $templateDir = ROOT_PATH.'resumes';
	protected $templateId = '09';
    protected $pathIndex = 2;

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
            '1495621402.html',
            '1495077986.html',
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
            '简历编号18840522-罗杭金-设计总监-猎聘网简历.html',
            '1495079828_1.html',

        ),
		'06' => array(
		    '84bccee4c14848a595c9f41fc4f54a14.html',
            '13716043902-男-北京_北京-484876634.html',
            '13810051957-男-北京_北京-1969920255.html',
        ),
		'07' => array(
            '1120010099812101王梅松.htm'
        ),
		'08' => array(
            '6000000004278595陈乙文(13917403172).htm'
        ),
		'09' => array(
		    '饶云飞-男-本科-架构师，技术负责人-8~9年.mht',
		    '51job_陶琼(90100780).mht58629.htm',
		    '51job_唐海平(770823).mht48600.htm',
		    '14074001.html',
		    '白帆_25580364.mht',
		    '1495711621.html',
		    '319646526(2015-01-04).mht',
		    '51job_胡晨奕(304023727).mht',
		    '51job_方冬杰(317207780).mht',
		    '15720.html',
		    '319451522.html',
		    '317527749.html',
		    '51job_常珂(17637850).htm',
            '51job_洪颖(7848354).mht57038.htm',
            '13482013240-男-上海_上海-1086136892.html',
            '100501.html',
            '0_20151226153102565.html',
            '51job_曹潇彬(320245978).html',
            '1495621211.html',
            '1495622019.html'
        ),
		'10' => array(
		    '黄涛-男-大专-Unity3D客户端开发工程师-4年.doc',
		    '孙盟盟-男-本科-开发工程师Android-91年.doc',
		    '智联招聘_邓樊_实习生_中文_20150604_23263415.doc',
            'JM192230554R90250000000.doc',
		    '(Zhaopin.com) 应聘 Production Manager 生产经理-扬州-王立志.htm',
            '(Zhaopin.com) 应聘 python工程师-南京-闫运.htm',
            '36_199.Html',
            '39_60.Html',
            '346881.html',
            '375825.html'
        ),
		'11' => array(
		    '794289.html',
		    '1495621166.html',
		    '1495621090.html',
		    '1495075699_1.html',
		    'JM005403686R90250000000.html',
            '周莉娜.html',
            '米卫开.html',
            'JR148209749R90000000000.htm',
            '1495077992.html',
        ),
		'12' => array(
			'jm501718846r90250000000-薛明转.html',
            'jm307095523r90250000000-马超.html',
            '335202968-王宁.html',

		),
        '13' => array(
            '51job_陈炎森(57896284)_new.html',
            '9209062.html',
            '1495079819.html',
            '1495079822.html',
            '1495079828.html',
            '1495079821.html',
        ),
        '14' => array(
            '1495623578.html',
            '1495436749.html',
            '1495436748.html'
        ),
        'to_support' => array(
            '3110746.html',
            '51job_saurabhgoyal(322093637).mht',
            '1495621843.html'
        ),
    );
}
