<?php
namespace app\index\Irregular;
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
    //同模块处理方法名的不同关键字使用“|”隔开
    protected $titles = array();

    //关键字解析规则（空格已被处理，不要包含空格）
    //格式： array('属性名', '关键字', '关键字对应值与关键字的偏移量')
    //偏移量：关键字与其对应值的所在索引的差值
    //同个键名的不同关键字使用“|”隔开
    protected $rules = array();

    //模板中所有关键字
    protected $keywords = '';

    //判断模板是否匹配
    //abstract protected function isMatched($content);

    //根据模板解析简历
    abstract public function parse($content);

    //获取DOM数组
    //abstract public function getDomArray($content);

    public function __construct() {
        $keywords = array_column($this->rules,1);
        $titles = array_column($this->titles,1);
        $keywords_str = implode('|',$keywords).'|'.implode('|',$titles);
        $this->keywords = $keywords_str;
    }

    function __call($functionName, $arguments) {
        return '';
    }

    //对简历内容预处理
    public function preprocess($content) {
        return $content;
    }

    /*
     * 分区块,同时将dom转换为数组（因为可以在一个循环中实现）
     * @param string $content 待解析内容
     * @param string $tag     包含数据的标签
     * @param boolean $partition 是否分区块，默认为true
     * @param boolean $all     是否保留空字符串数据
     * @return array $data dom解析后得到的数组（保存的是文本） 
     *               $blocks  分区块数组array('标题名', 区块开始索引，区块结束索引)
     */
    public function domParse($content, $tag ='td',$all = true, $partition = true, &$htmls = null) {
        $titles = $this->titles;
        $document = new Document($content);
        $tds = $document->find($tag);
        $i = 0;
        $j = 0;
        $blocks = array();
        $data = array();
        $htmls = array();
        foreach($tds as $td) {
            if(count($td->find($tag.' '.$tag)) > 0){
                continue;
            }
            //将全角空格(E38080)和UTF8空格(C2A0)替换成半角空方
            $text = str_replace(array(chr(194).chr(160),'　'),' ',$td->text());
            $text = preg_replace('/\s+/',' ',$text);
            $text = trim($text);
            if($text || $all){
                $data[$i] = $text;
                $htmls[$i] = $td->html();
                if($partition) {
                    foreach($titles as $key=>$title){
                        $text = preg_replace('/\s+/','',$text);
                        if(preg_match('/('.$title[1].')/', $text)){
                            $blocks[$j] = array($title[0], $i);
                            if($j > 0){
                                if($i>$blocks[$j-1][1]){
                                    $blocks[$j-1][2] = $i - 1;
                                }else{
                                    unset($blocks[$j-1]);
                                }
                            }
                            $j ++;
                            unset($titles[$key]);
                        }
                    }
                }
                $i ++ ;
            }               
        }
        if($j > 0)
            $blocks[$j-1][2] = count($data) - 1;       
        // dump($blocks);
        //dump($data);
        return $partition?array($data, $blocks):$data;
    }

    /**
     * 正则分割
     * @param $content
     * @param bool $all
     * @param bool $partition
     * @param array $separators
     * @return array
     */
    public function pregParse($content, $all = false, $partition = true, $separators = array()) {
        $titles = $this->titles;
        if(!$separators) $separators = $this->separators;
        $pattern = '/'.implode('|',$separators).'/is';
        $htmls = preg_split($pattern,$content);
        //dump($htmls);
        $data = array();
        $blocks = array();
        $i = 0;
        $j = 0;
        foreach($htmls as $value) {
            $text = html_entity_decode(strip_tags($value));
            $text = str_replace(array(chr(194).chr(160),'　'),' ',$text);
            $text = trim($text);
            if($text || $all){
                $data[$i] = $text;
                if($partition) {
                    foreach($titles as $key=>$title){
                        $text = preg_replace('/\s+/','',$text);
                        if(preg_match('/('.$title[1].')/', $text)){
                            $blocks[$j] = array($title[0], $i + 1);
                            if($j > 0)
                                $blocks[$j-1][2] = $i - 1;
                            $j ++;
                            unset($titles[$key]);
                        }
                    }
                }
                $i ++ ;
            }
        }
        if($j > 0)
            $blocks[$j-1][2] = count($data) - 1;
        // dump($blocks);
        //dump($data);
        return $partition?array($data, $blocks):$data;
    }
    
    //对dom数组分块
    public function partition($data) {
        $titles = $this->titles;
        $j = 0;
        $blocks = array();
        foreach($data as $i => $text){
            foreach($titles as $key=>$title){
                $text = preg_replace('/\s+/','',$text);
                if(preg_match('/^('.$title[1].')$/', $text)){
                    $blocks[$j] = array($title[0], $i);
                    if($j > 0)
                        $blocks[$j-1][2] = $i;
                    $j ++;
                    unset($titles[$key]);
                }
            }
        }
        if($j > 0)
            $blocks[$j-1][2] = count($data); 
        return $blocks;
    }

    /* 
     * 根据关键字规则解析关键字对应的值
     * @param string $keyword 关键字字段
     * @param $value null: 去下一个值   int:取下$value个值 string: 值为value
     * @param array $rules 关键字解析规则，默认使用类的关键字解析规则
     * @return string 关键字对应键名 
     */
    public function parseKeyword($keyword, &$value, $rules = '') {
        $value = null;
        if(!$rules) $rules = $this->rules;
        $keyword = preg_replace('/\s+/','',$keyword);
        foreach($rules as $rule) {
            if(isset($rule[2])) {
                if(preg_match('/('.$rule[1].')(.*)/', $keyword, $data)){
                    if($rule[2] === 0){
                        $value = $data[2];
                    }else{
                        $value = $rule[2];
                    }
                    //dump($data);
                    return $rule[0];
                }
            }else{
                if(preg_match('/'.$rule[1].'/', $keyword)) {
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

    //基础信息
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
            $evaluation .= $data[$i++];        
        }
        $record['self_str'] = $evaluation;
        return $evaluation;
    }

    public function blockParse($data, $start, $end, $conditions) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
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

    //获取详细关键信息
    public function getMessage($Experiences,$pattern){
        preg_match_all("/(?:[\x{4e00}-\x{9fa5}]|[a-zA-Z])+/u",$Experiences['content'],$contentArr);
        //vde($contentArr);
        foreach($contentArr[0] as $key=>$value){
            if(preg_match($pattern, $value, $position))
                return $position[0];
        }
    }

}
