<?php
/**
 * Created by PhpStorm.
 * User: Roy
 * Date: 2018/9/27
 * Time: 17:06
 */
namespace app\index\Parser;
class Template21 extends AbstractParser{
    protected $city = array("530"=>"北京","538"=>"上海","763"=>"广州","765"=>"深圳","531"=>"天津","736"=>"武汉",
        "854"=>"西安","801"=>"成都","600"=>"大连","613"=>"长春","599"=>"沈阳","635"=>"南京","702"=>"济南","703"=>"青岛",
        "653"=>"杭州","639"=>"苏州","636"=>"无锡","654"=>"宁波","551"=>"重庆","719"=>"郑州","749"=>"长沙","681"=>"福州",
        "682"=>"厦门","622"=>"哈尔滨","565"=>"石家庄","664"=>"合肥","773"=>"惠州","576"=>"太原","831"=>"昆明","707"=>"烟台",
        "768"=>"佛山","691"=>"南昌","822"=>"贵阳","548"=>"广东","546"=>"湖北","556"=>"陕西","552"=>"四川","535"=>"辽宁",
        "536"=>"吉林","539"=>"江苏","544"=>"山东","540"=>"浙江","549"=>"广西","541"=>"安徽","532"=>"河北","533"=>"山西",
        "534"=>"内蒙古","537"=>"黑龙江","542"=>"福建","543"=>"江西","545"=>"河南","547"=>"湖南","550"=>"海南","553"=>"贵州",
        "554"=>"云南","555"=>"西藏","557"=>"甘肃","558"=>"青海","559"=>"宁夏","560"=>"新疆","562"=>"澳门","561"=>"香港","563"=>"台湾省","480"=>"国外");
    protected  $industry_json = '{"100000":"农/林/牧/渔","100100":"跨领域经营","120200":"耐用消费品（服饰/纺织/皮革/家具/家电）","120400":"快速消费品（食品/饮料/烟酒/日化）","120500":"石油/石化/化工","120600":"学术/科研","120700":"办公用品及设备","120800":"礼品/玩具/工艺美术/收藏品/奢侈品","121000":"汽车/摩托车","121100":"加工制造（原料加工/模具）","121200":"仪器仪表及工业自动化","121300":"医药/生物工程","121400":"医疗/护理/美容/保健/卫生服务","121500":"医疗设备/器械","129900":"大型设备/机电设备/重工业","130000":"能源/矿产/采掘/冶炼","130100":"电气/电力/水利","140000":"房地产/建筑/建材/工程","140100":"家居/室内设计/装饰装潢","140200":"物业管理/商业中心","150000":"交通/运输","160000":"IT服务(系统/数据/维护)","160100":"通信/电信运营、增值服务","160200":"计算机硬件","160400":"计算机软件","160500":"电子技术/半导体/集成电路","160600":"网络游戏","170000":"零售/批发","170500":"贸易/进出口","180000":"基金/证券/期货/投资","180100":"保险","200100":"政府/公共事业/非盈利机构","200300":"专业服务/咨询(财会/法律/人力资源等)","200302":"广告/会展/公关","200600":"酒店/餐饮","200700":"娱乐/体育/休闲","200800":"旅游/度假","201100":"教育/培训/院校","201200":"环保","201300":"检验/检测/认证","201400":"中介服务","210300":"媒体/出版/影视/文化传播","210500":"互联网/电子商务","210600":"印刷/包装/造纸","300000":"航空/航天研究与制造","300100":"通信/电信/网络设备","300300":"外包服务","300500":"银行","300700":"租赁服务","300900":"信托/担保/拍卖/典当","301100":"物流/仓储","990000":"其他"}';
    public function parse($content) {
        $jsonStr = preg_replace('/((?<=:)""(?!(,|}|，))|(?<!:)""(?=(,|})))/','"',$content);
        $jsonStr = preg_replace('/\r|\n/','',$jsonStr);
        $json = json_decode($jsonStr,true);
        if(!$json){
            $jsonStr1 = preg_replace('/\s/','',$content);
            $jsonStr1 = preg_replace('/\r|\n/','',$jsonStr1);
            $json = json_decode($jsonStr1,true);
        }
        if($json){
            $this->testFunc($json,$resume);
            $data['resume'] = $resume;
            $uinfo = $json['data'];
            $candidate = $uinfo['candidate'];
            $detail = $uinfo['detail'];
            $data['name'] = $candidate['userName'];
            $data['industry'] = $this->getIndustryName($detail['CurrentIndustry']);
            $data['target_industry'] = $this->getIndustryName($detail['DesiredIndustry']);
            $cityStr = $detail['DesiredCity'];
            $cityArr = explode(',',$cityStr);
            if($cityArr){
                foreach($cityArr as $key=>$value){
                    $cityNameArr[] = $this->city[$value];
                }
                $cityNameStr = implode(',',$cityNameArr);
                $data['target_city'] = $cityNameStr;
            }
            $data['birth_year'] = Utility::str2time($candidate['birthYear'].'-'.$candidate['birthMonth'].'-'.$candidate['birthDay'].' 0:0:0');
            if($detail['Gender']==1){
                $data['sex'] = '男';
            }elseif($detail['Gender']==2){
                $data['sex'] = '女';
            }
            $data['target_salary'] = $uinfo['target_salary'];
            //$data['target_salary'] = $uinfo['target_salary'];
            if($detail['WorkExperience']){
                foreach($detail['WorkExperience'] as $key=>$value){
                    $data['career'][$key]['company'] = $value['CompanyName'];
                    $data['company'][] = $value['CompanyName'];
                    $data['position'][] = $value['JobTitle'];
                    $data['career'][$key]['start_time'] = Utility::str2time($value['DateStart']);
                    $data['career'][$key]['end_time'] = Utility::str2time($value['DateEnd']?:'至今');
                    $data['career'][$key]['description'] = $value['WorkDescription'];
                    $data['career'][$key]['position'] = $value['JobTitle'];
                }
            }
            if($detail['EducationExperience']){
                foreach($detail['EducationExperience'] as $key=>$value){
                    $data['education'][$key]['school'] = $value['SchoolName'];
                    $data['education'][$key]['start_time'] = Utility::str2time($value['DateStart']);
                    $data['education'][$key]['end_time'] = Utility::str2time($value['DateEnd']);
                    $data['education'][$key]['degree'] = $this->getDegree($value['EducationLevel']);
                    $data['education'][$key]['major'] = $value['MajorName'];
                }
            }
            if($detail['ProjectExperience']){
                foreach($detail['ProjectExperience'] as $key=>$value){
                    $data['projects'][$key]['name'] = $value['ProjectName'];
                    $data['projects'][$key]['start_time'] = Utility::str2time($value['DateStart']);
                    $data['projects'][$key]['end_time'] = Utility::str2time($value['DateEnd']);
                    $data['projects'][$key]['duty'] = $value['ProjectDescription'];
                    $data['projects'][$key]['description'] = $value['ProjectDescription'];
                }
            }
            if($data['career']){//倒序排
                sort_arr_by_field($data['career'],'start_time',true);
            }
            if($data['education']){//倒序排
                sort_arr_by_field($data['education'],'start_time',true);
            }
            $data['last_company'] = $data['career'][0]['company'];
            $data['last_position'] = $detail['CurrentJobTitle'];
            $data['major'] = $data['education'][0]['major'];
            $data['degree'] = $data['education'][count( $data['education'])-1]['degree'];
            $data['school'] = $detail['GraduatedFrom'];
        }
        if(!$data['education'] || !$data['career'] || !$data['name']){
            sendMail(20,$content);
        }
        return $data;
    }
    public function getDegree($degree){
        switch($degree){
            case 0:
                $degreeStr = '未知';
                break;
            case 1:
                $degreeStr = '博士';
                break;
            case 3:
                $degreeStr = '硕士';
                break;
            case 4:
                $degreeStr = '本科';
                break;
            case 5:
                $degreeStr = '大专';
                break;
            case 10:
                $degreeStr = 'MBA';
                break;
            case 11:
                $degreeStr = 'EMBA';
                break;
            case 12:
                $degreeStr = '中专';
                break;
            default:
                $degreeStr = '未知';
                break;
        }
        return $degreeStr;
    }
    public function testFunc($array,&$str){
        foreach ($array as $value){
            if (is_array($value)) {
                $this->testFunc($value,$str);
            } else {
                $str = $str."<br>".$value;
            }
        }
    }
    public function getIndustryName($industry){
        $industry_json_arr = json_decode($this->industry_json,true);
        if(!is_array($industry)){
            $industry = explode(',',$industry);
        }
        foreach($industry as $value){
            if($value>0){
                $industry_arr[] = $industry_json_arr[$value];
            }
        }
        if($industry_arr){
            return implode(',',$industry_arr);
        }else{
            return '';
        }
    }
}