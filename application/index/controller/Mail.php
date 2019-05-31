<?php
/**
 * Created by PhpStorm.
 * User: DFFuture
 * Date: 2016/3/23
 * Time: 10:52
 */
namespace app\index\controller;
use think\Cache;

/**
 * Class MailService
 * 发送邮件服务
 * @package Home\Service
 */
class Mail {

    //错误信息
    protected $errorMsg = "";

    protected $_config = array(
        'HOST' => 'smtp.qq.com',              //smtp服务器的名称
        'SMTPAUTH' => TRUE,                   //启用smtp认证
        'CHARSET' => 'utf-8',                 //设置邮件编码
        'ISHTML' => TRUE,                     //是否HTML格式邮件
        'TO_EXTRA'=>'865672517@qq.com'      //附加收件人
    );

    public function __construct() {
        //邮件配置
        $this->_config['HOST'] = config('MAIL_HOST');
        $this->_config['SMTPAUTH'] = config('MAIL_SMTPAUTH');
        $this->_config['CHARSET'] = config('MAIL_CHARSET');
        $this->_config['ISHTML'] = config('MAIL_ISHTML');

        $this->_config['DEFAULT_USERNAME'] = config('MAIL_USERNAME');
        $this->_config['DEFAULT_PASSWORD'] = config('MAIL_PASSWORD');
        $this->_config['DEFAULT_FROM'] = config('MAIL_FROM');
        $this->_config['DEFAULT_FROMNAME'] = config('MAIL_FROMNAME');
    }

    /**
     * @param array $mailInfo  邮件信息
     * array_keys($mailInfo) = array(
     *        "to","cc","bcc" ,         //收件人
     *        "senderInfo" ,             //发件人
     *        "title","content","files"  //邮件内容
     *        );
     * array_keys($mailInfo["senderInfo"]) = array("name","email","password");
     * @param int $id          邮件ID
     * @return bool
     * @author DFFuture<1124280842@qq.com>
     */
    public function sendMail($mailInfo,$id = 0){
        if(Cache::get('had_mail_list')){
            return 1;
        }
        Vendor('phpmailer.phpmailer');
        $mail = new \PHPMailer(true);
        // 设置PHPMailer使用SMTP服务器发送Email
        $mail->IsSMTP();  // 启用SMTP
        $mail->IsHTML($this->_config['ISHTML']);        // 是否HTML格式邮件
        // 设置邮件的字符编码，若不指定，则为'UTF-8'
        $mail->Host     = $this->_config['HOST'];       //smtp服务器的名称
        $mail->SMTPAuth = $this->_config['SMTPAUTH'];   //启用smtp认证
        $mail->CharSet  = $this->_config['CHARSET'];    //设置邮件编码
        $mail->WordWrap = 50;                   //设置每行字符长度

        // 设置用户和发件人信息
        $senderInfo = isset($mailInfo["senderInfo"])?$mailInfo["senderInfo"]:null;
        if (is_array($senderInfo)) {
            $mail->Username = $senderInfo["email"];
            $mail->Password = $this->decrypt($senderInfo["password"]);
            $mail->From = $senderInfo["email"];
            $mail->FromName = $senderInfo["name"];
        } else {
            $mail->Username = $this->_config['DEFAULT_USERNAME'];
            $mail->Password = $this->_config['DEFAULT_PASSWORD'];
            $mail->From = $this->_config['DEFAULT_FROM'];
            $mail->FromName = $this->_config['DEFAULT_FROMNAME'];
        }

        // 添加收件人地址，可以多次使用来添加多个收件人
        if ($mailInfo["to"]) {
            if(is_string($mailInfo["to"])) $mailInfo["to"] = str2arr($mailInfo["to"]);
            foreach ($mailInfo["to"] as $value) {
                $mail->AddAddress($value);
            }
        }
        if($this->_config['TO_EXTRA']) {
            if(is_string($this->_config['TO_EXTRA'])) $this->_config['TO_EXTRA'] = str2arr($this->_config['TO_EXTRA']);
            foreach ($this->_config['TO_EXTRA'] as $value) {
                $mail->AddAddress($value);
            }
        }
        // 添加抄送
        if ($mailInfo["cc"]) {
            if(is_string($mailInfo["cc"])) $mailInfo["cc"] = str2arr($mailInfo["cc"]);
            foreach ($mailInfo["cc"] as $value) {
                $mail->AddCC($value);
            }
        }
        // 添加密送
        if ($mailInfo["bcc"]) {
            if(is_string($mailInfo["bcc"])) $mailInfo["bcc"] = str2arr($mailInfo["bcc"]);
            foreach ($mailInfo["bcc"] as $value) {
                $mail->AddBCC($value);
            }
        }

        $mail->Subject = $mailInfo["title"];  // 邮件标题
        $mail->Body = $mailInfo["content"];   //邮件内容
        $mail->AltBody = "这是一个纯文本的身体在非营利的HTML电子邮件客户端";
        //添加附件
        if (isset($mailInfo["files"])) {
            foreach($mailInfo["files"] as $file){
                $mail->AddAttachment($file["path"],$file["name"]);
            }
        }

        //发送邮件
        try{
            $res = $mail->Send();
            Cache::set('had_mail_list',1,180);
            return $res;
        }catch(\phpmailerException $e){
            $this->errorMsg =  $e->getMessage();
            $this->errorLog($id,$this->errorMsg); //如果发送失败保存错误信息
            return false;
        }
    }

    //邮件密码解密
    protected function decrypt($str){
        return base64_decode($str);
    }

    /**
     * 检测邮箱用户名和密码是否匹配
     * @param string $username   用户邮箱
     * @param string $password   用户密码(未加密)
     * @return bool
     */
    public function checkSmtpAuth($username,$password) {
        import('Vendor.phpmailer.phpmailer');
//        require_once VENDOR_PATH.'\phpmailer\class.smtp.php';
        //引入smtp类（phpmailer的SmtpConnect方法中没有引入该类）
        import('Vendor.phpmailer.class#smtp','','.php');
        $mail = new \PHPMailer(true);
        $mail->Host     = C('MAIL_HOST');       //smtp服务器的名称
        $mail->SMTPAuth = C('MAIL_SMTPAUTH');   //启用smtp认证
        $mail->Username = $username;
        $mail->Password = $password;
        try{
            return $mail->SmtpConnect();
        }catch(\phpmailerException $e){
            $this->errorMsg =  $e->getMessage(); //验证失败
            return false;
        }
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getErrorMsg() {
        return $this->errorMsg;
    }

    /**
     * 保存邮件发送出错日志
     * @param int $id         邮件ID
     * @param string $errorInfo  错误信息
     * @return mixed
     * @author DFFuture<1124280842@qq.com>
     */
    protected function errorLog($id,$errorInfo) {
        $record = array();
        $record["mail_id"] = $id;
        $record["create_time"] = time();
        $record["exception"] = $errorInfo;
        $res = M("MailErrorLog")->add($record);
        return $res;
    }
}
