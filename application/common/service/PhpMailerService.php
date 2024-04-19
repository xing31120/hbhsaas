<?php


namespace app\common\service;


use PHPMailer\PHPMailer\PHPMailer;

class PhpMailerService
{
    public function sendEmail($email, $subject, $body)
    {
        if(empty($email) || empty($subject) || empty($body) ){
            return errorReturn('error param');
        }
//        new PHPMailer()
//        Vendor('PHPMailer.PHPMailerAutoload');//这里引入一下刚才安装扩展的自动加载类
        $mail = new PHPMailer(); // 实例化类，新建
        $mail->isSMTP(); //  开启SMTP
        $mail->CharSet = 'utf8'; // 设置编码
        $mail->Host = 'mail.hbhinstitute.com'; // SMTP服务器
        $mail->SMTPAuth = true; // smtp需要鉴权 这个必须是true
        $mail->Username = 'reservation@hbhinstitute.com'; // 发信人的账号，这个需要是开启stmp服务的邮箱号
        $mail->Password = "ufh9aUzj0ws7"; // 密码，非邮箱密码，是SMTP生成的密码，也就是授权码
        $mail->From = 'reservation@hbhinstitute.com'; // 发信人的地址
        $mail->SMTPSecure = 'ssl'; // 采用ssl协议，这里采用了加密，端口需要进行开放：465或587
        $mail->Port = 465; // 端口号
        $mail->FromName = "hbhinstitute.com"; // 发件人昵称
        $mail->addAddress($email); // 收信人地址
        $mail->addReplyTo($email); //回复的时候回复的邮箱，建议和发信人一样
        $mail->Subject = $subject; // 邮件主题，看自己需求
        $mail->Body = $body; // 邮件内容
        $mail->AltBody = "";
        $result = $mail->send();
        if (!$result) {
//            return json([400, $mail->ErrorInfo]);
            return errorReturn($mail->ErrorInfo);
        } else {
//            return json([200, '验证码已经发送成功']);
            return successReturn(['msg' => '发送成功', 'data' => $result]);
        }
    }
}
