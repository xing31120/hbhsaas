<?php
/**
 * user: 企业微信群机器人
 * Date：2020/11/19
 * Time: 10:17
 */

namespace app\common\service\workSendMessage;


use GuzzleHttp\Client;

class WorkSendMessageService
{

    private $url = "";
    protected $domain_url = "https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=";
    private $type;

    public function __construct($url='')
    {
        if(!empty($url)){
            $this->url = $this->domain_url.$url;
        }
    }

    /**
     * 设置环境
     * LFL 2020-08-08 17:09:42
     * @param string $url 推送地址
     * @param string $type 类型
     * @return $this
     */
    public function setEnv($url = '', $type = 'work')
    {
        $url = $url ? $this->domain_url.$url: $this->domain_url.env('SAASWORK.SAAS_ERROR_PUSH','');
        $this->url = $url;
        $this->type = $type;
        return $this;
    }

    /**
     * 推送原始消息
     * LFL 2020-08-08 17:09:46
     * @param $body
     * @return $this|bool
     */
    public function sendRaw($body)
    {
        $url = $this->url;
        if (empty($url)) {
            return false;
        }
        $client = new Client(['timeout' => 5, 'verify' => false]);
        $client->post($url, [
            'json' => $body,
        ]);
        return $this;
    }

    /**
     * 推送文本消息
     * LFL 2020-08-08 17:10:22
     * @param $content
     * @param array $mentionedList
     * @param array $mentionedMobileList
     * @return $this
     */
    public function sendText($content, $mentionedList = [], $mentionedMobileList = [])
    {
        $body = [
            'msgtype' => 'text',
            'text' => [
                'content' => $content,
                'mentioned_list' => $mentionedList,
                'mentioned_mobile_list' => $mentionedMobileList,
            ],
        ];

        $this->sendRaw($body);
        return $this;
    }

    /**
     * 推送markdown消息
     * LFL 2020-08-08 17:10:56
     * @param $content
     * @return $this
     */
    public function sendMarkDown($content)
    {
        $body = [
            'msgtype' => 'markdown',
            'markdown' => [
                'content' => $content,
            ],
        ];

        $this->sendRaw($body);
        return $this;
    }

    /**
     * 推送图片消息
     * LFL 2020-08-08 17:10:56
     * @param $content
     * @return $this
     */
    public function sendImage($content)
    {
        $body = [
            'msgtype' => 'image',
            'image' => [
                'base64' => $content,
                'md5' => md5(base64_decode($content)),
            ],
        ];

        $this->sendRaw($body);
        return $this;
    }
}