<?php
namespace app\index\Parser;
use app\index\DiDom\Document;

abstract class AbstractParser {

    //通用正则
    protected $pattern = array(
        'phone' => '/^1[3|4|5|7|8][0-9]{9}$/',
        'email' => '/^\w+([-+.]\w*)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
        'time_range' => '/(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)/'
    );

    //分割符
    protected $separators = array(
        '<\/.+?>',      //html结束标签
        '\|',           // |
        '<br.*?>',      // 换行标签
       // '\r\n'
    );

    //区块标题（空格已被处理，不要包含空格）
    //格式：array("区块处理方法名", "区块标题关键字")
    //同模块处理方法名对应的不同关键字使用“|”隔开
    protected $titles = array();

    //关键字解析规则（空格已被处理，不要包含空格）
    //格式： array('属性名', '关键字', '关键字对应值与关键字的偏移量')
    //偏移量：关键字与其对应值的所在索引的差值
    //同个键名的不同关键字使用“|”隔开
    protected $rules = array();

    //模板中所有关键字
    protected $keywords = '';

    //判断模板是否匹配(移到ResumeParse统一判断)
    //abstract protected function isMatched($content);

    //根据模板解析简历
    abstract public function parse($content);

    //获取DOM数组
    //abstract public function getDomArray($content);

    public function __construct() {
        // 获取所有关键字，"|"分割
        $keywords = array_column($this->rules,1);
        $titles = array_column($this->titles,1);
        $keywords_str = implode('|',$keywords).'|'.implode('|',$titles);
        $this->keywords = $keywords_str;
    }

    function __call($functionName, $arguments) {
        return '';
    }

    //对简历内容预处理（默认不处理）
    public function preprocess($content) {
        return $content;
    }

    /*
     * 分区块,同时将dom树结构转换为一维数组（因为可以在一个循环中实现），这个数组这里成为DOM数组
     * @param string  $content    待解析内容
     * @param string  $tag        包裹数据的标签
     * @param boolean $all        是否保留空字符串数据，默认为true
     * @param boolean $partition  是否分区块，默认为true
     * @param array   $hData       带html标签的DOM数组
     * @return array  $data       dom解析后得到的数组（保存的是文本）
     *                $blocks     分区块数组array('标题名', 区块开始索引，区块结束索引)
     */
    public function domParse($content, $tag ='td',$all = true, $partition = true, &$hData = null) {
        $titles = $this->titles;
        $document = new Document($content);
        $tds = $document->find($tag);
        $i = 0;
        $j = 0;
        $blocks = array();
        $data = array();
        $hData = array();
        foreach($tds as $td) {
            if(count($td->find($tag.' '.$tag)) > 0){
                continue;
            }
            //将全角空格(E38080)和UTF8空格(C2A0)替换成半角空方
            $text = str_replace(array(chr(194).chr(160),'　'),' ',$td->text());
            //删除多余的不可见字符
            $text = trim(preg_replace('/\s+/',' ',$text));
            if($text || $all){
                $data[$i] = $text;
                $hData[$i] = $td->html();
                if($partition) {
                    if($method = $this->isTitle($text, $titles)){
                        $blocks[$j] = array($method, $i + 1);
                        if($j > 0)
                            $blocks[$j-1][2] = $i - 1;
                        $j ++;
                    }
                }     
                $i ++ ;
            }               
        }
        if($j > 0) $blocks[$j-1][2] = count($data) - 1;
        //dump($blocks);
        //dump($data);
        return $partition?array($data, $blocks):$data;
    }

    /**
     * 正则分割
     * @param $content
     * @param bool  $all
     * @param bool  $partition
     * @param array $separators   分割符，默认使用成员变量
     * @param array $hData       带html的data数据
     * @return array
     */
    public function pregParse($content, $all = false, $partition = true, $separators = array(), &$hData = array()) {
        $titles = $this->titles;
        if(!$separators) $separators = $this->separators;
        $pattern = '/'.implode('|',$separators).'/is';
        //对文档进行分割
        $htmls = preg_split($pattern,$content);
        //dump($htmls);
        $i = 0;
        $j = 0;
        $data = array();
        $blocks = array();
        $hData = array();
        foreach($htmls as $value) {
            //去除html标记，转换html实体
            $text = html_entity_decode(strip_tags($value));
            $text = trim(str_replace(array(chr(194).chr(160),'　'),' ',$text));
            if($text || $all){
                $data[$i] = $text;
                $hData[$i] = $value;
                if($partition) {
                    if($method = $this->isTitle($text, $titles)){
                        $blocks[$j] = array($method, $i + 1);
                        if($j > 0)
                            $blocks[$j-1][2] = $i - 1;
                        $j ++;
                    }
                }
                $i ++ ;
            }
        }
        if($j > 0) $blocks[$j-1][2] = count($data) - 1;
        //dump($blocks);
        //dump($data);
        return $partition?array($data, $blocks):$data;
    }
    
    //对dom数组分块
    public function partition($data) {
        $titles = $this->titles;
        $j = 0;
        $blocks = array();
        foreach($data as $i => $text){
            if($method = $this->isTitle($text, $titles)){
                $blocks[$j] = array($method, $i + 1);
                if($j > 0)
                    $blocks[$j-1][2] = $i - 1;
                $j ++;
            }
        }
        if($j > 0) $blocks[$j-1][2] = count($data) - 1;
        return $blocks;
        
    }

    /**
     * 是否是区块标题，如果是，返回区块对应方法
     * @param $text string 文本内容
     * @param null $titles 标题列表
     * @return string|bool 标题对应方法名
     */
    public function isTitle($text, &$titles=null) {
        if(!$titles) $titles = $this->titles;
        foreach($titles as $key=>$title){
            //去除空格等符号
            $text = preg_replace('/\s+/','',$text);
            if(preg_match('/^('.$title[1].')$/', $text)){
                unset($titles[$key]);
                return $title[0];
            }
        }
        return false;
    }

    /* 
     * 根据关键字规则解析关键字对应的值
     * @param string $keyword 关键字字段
     * @param mixed $value 【int】:在DOM数组中取下$value个值 【string】: 值为value
     * @param array $rules 关键字解析规则，默认使用类的关键字解析规则
     * @return string 关键字对应键名 
     */
    public function parseKeyword($keyword, &$value, $rules = array()) {
        if(!$rules) $rules = $this->rules;
        //去除空格（所以规则中不能有空格）
        $keyword = preg_replace('/\s+/','',$keyword);
        foreach($rules as $rule) {
            if(isset($rule[2])) {
                if(preg_match('/^('.$rule[1].')(.*)/', $keyword, $data)){
                    if($rule[2] === 0){
                        $value = $data[2];
                    }else{
                        $value = $rule[2];
                    }
                    //dump($data);
                    return $rule[0];
                }
            }else{
                if(preg_match('/^('.$rule[1].')$/', $keyword)) {
                    $value = 1;
                    return $rule[0];
                }elseif(preg_match('/^('.$rule[1].')(.+)/', $keyword, $data)){
                    $value = $data[2];
                    return $rule[0];
                }
            }           
        }
        return false;
    }

    /**
     * 解析数组元素，获取键-值对-偏移量
     * @param array $data
     * @param int $i
     * @param array $rules
     * @param string $keywords
     * @return array|bool
     */
    public function parseElement($data, $i, $rules = array(), $keywords = "") {
        $key = $this->parseKeyword($data[$i],$value, $rules);
        if(!$keywords){
            $keywords = implode('|',array_column($rules, 1));
        }
        if($key){
            if(is_string($value)){
                return array($key, $value, 0);
            }elseif(is_int($value) && !$this->isKeyword($data[$i+$value], $keywords)){
                return array($key, $data[$i+$value], $value);
            }
        }
        return false;
    }

    //是否是关键字
    public function isKeyword($word, $keywords = '') {
        if(!$keywords) $keywords = $this->keywords;
        $word = preg_replace('/\s/','',$word);
        return preg_match('/^('.$keywords.')/', $word);
    }

    //清理数据
    public function clean($data) {
        $data = html_entity_decode(strip_tags($data));
        $find = array("\n","\t","\r");
        $data = str_replace($find,"",$data);
        $data = str_replace("，",",",$data);
        return trim($data);
    }

    //基础信息根据关键字提取
    public function basic($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        while($i < $length) {
            $KV = $this->parseElement($data, $i);
            if($KV && !$record[$KV[0]]){
                $record[$KV[0]] = $KV[1];
            }
            $i++;
        }
    }

    //目前职位概况
    public function current($data, $start, $end, &$record) {
        $this->basic($data, $start, $end, $record);
    }

    //求职意向
    public function target($data, $start, $end, &$record) {
        $this->basic($data, $start, $end, $record);
    }

    //自我评价
    public function evaluation($data, $start, $end, &$record) {
        $i = $start;
        $evaluation = '';
        while($i <= $end){  
            $evaluation .= '#br#'.$data[$i++];
        }
        $record['self_str'] = $evaluation;
        return $evaluation;
    }

    /**
     * 区块解析通用方法
     * @param array $data
     * @param $start
     * @param $end
     * @param $conditions
     * @return array
     */
    public function blockParse($data, $start, $end, $conditions) {
        $length = $end - $start + 1;
        $data = array_slice($data, $start, $length);
        //关键字提取所用的规则
        $rules = $conditions['rules'];
        //顺序提取对应的键名
        //格式：array('pattern'或 关键字键名, 键名列表)
        $sequence = $conditions['sequence'];
        //正则提取所用的正则，需要用到(?<name>pattern)子组命名 
        $pattern = $conditions['pattern'];
        $i = 0;
        $j = 0;
        $objects = array();
        while($i < $length) {      
            //正则提取
            if($pattern && preg_match($pattern, $data[$i], $match)) {  
                $object = array();
                foreach($match as $key=>$value){
                    if(is_string($key)){
                        if(strpos($key,'time')!== false)
                            $object[$key] = Utility::str2time($value);
                        else
                            $object[$key] = $value;
                    }               
                }
                $objects[$j++] = $object;
                if($sequence[0] == 'pattern') $k = 1; 
            //关键字提取
            }elseif($rules && $KV = $this->parseElement($data, $i, $rules)){
                $objects[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                if(isset($objects[$j-1][$KV[0]])) $k = 1;
            //顺序提取
            }elseif(isset($k) && $k > 0){
                if($key = $sequence[1][$k-1]){
                    $objects[$j-1][$key] = $data[$i];
                    $k++;
                }else{
                    $k = 0;
                }                         
            }
            $i++;
        }
        return $objects;
    }

}
