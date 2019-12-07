<?php
/**
 * 评论通知推送至 Webhooks
 *
 * @package Comment2Hook
 * @author medmin
 * @version 1.1.0
 * @link https://github.com/SuperPHP/Typecho_Comment2Hook_Plugin
 */

require __DIR__ . '/libs/ServerChan.php';

class Comment2Hook_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate() {
        Typecho_Plugin::factory('Widget_Feedback')->comment = array('Comment2Hook_Plugin', 'triggerHook');
        return _t('Comment2Hook插件已经激活');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate() {}

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form) {

        $service = new Typecho_Widget_Helper_Form_Element_Select('service', [
            "server_chan" => "Server酱",
            "ttanli_email" => "Email服务",
            "ttanli_bot" => "Telegram Bot"
        ], 'server_chan', _t('选择使用何种通知服务'), _t('暂时仅支持Server酱'));
        $form->addInput($service->addRule('required', _t('您必须选择一项通知服务')));

        $queue = new Typecho_Widget_Helper_Form_Element_Radio('queue', [
            "yes" => '是',
            "no"  => '否'
        ], 'no', _t('是否使用队列'),_t('使用队列，可以改善用户体验'));
        $form->addInput($queue);

        $whUrl = new Typecho_Widget_Helper_Form_Element_Text('whUrl', NULL, NULL, _t('Webhooks URL'), _t("URL是必须的"));
        $form->addInput($whUrl);

        $whKey = new Typecho_Widget_Helper_Form_Element_Text('whKey', NULL, NULL, _t('Webhook Private Key'), _t('Webhooks密钥（根据服务选填）'));
        $form->addInput($whKey);

        $excludeBlogger = new Typecho_Widget_Helper_Form_Element_Radio('excludeBlogger',
            array(
                '1' => '是',
                '0' => '否'
            ),'1', _t('当评论者为博主本人时不推送'), _t('建议选"否"，否则不会推送博主本人的留言至 Webhooks'));
        $form->addInput($excludeBlogger);
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    /**
     * 推送至 Webhooks
     *
     * @access public
     * @param array $comment 评论结构
     * @param Typecho_Widget $post 被评论的文章
     * @return $comment
     */
    public static function triggerHook($comment, $post) {
        $options = Typecho_Widget::widget('Widget_Options')->plugin('Comment2Hook');

        $service = $options->service;
        $queue = $options->queue;
        $whUrl = $options->whUrl;
        $whKey = $options->whKey;
        $excludeBlogger = $options->excludeBlogger;

        if ($comment['authorId'] == 1 && $excludeBlogger == '1') {
            return $comment;
        }
        
        if ($service == 'server_chan'){
            if ($queue == 'yes'){
                try{
                    $job = $service; // the sole purpose is to make the terminology understandable
                    $payload = '';
                    $serverChan = new ServerChan();
                    $newJobAdded = $serverChan->enqueue($job, $payload);
                    if ($newJobAdded != 1){
                        return $comment;
                    }
                }catch( Exception $e){
                    return $comment;
                }
            }else{
                $text = "博客收到新留言 内容摘要 " . substr($comment['text'], 0, 20);
                $desp = "作者：".$comment['author']."\n\n评论内容：" . $comment['text'];
                $serverChan = new ServerChan($whUrl, $text, $desp);
                $serverChan->trigger();
            }
        }else{
            $headers = array();
            $headers[] = "Content-type: application/json";
            $headers[] = "Authorization: Bearer " . $whKey;
            $url = $whUrl;
            $data = array(
                'title' => $post->title,
                'author' => $comment['author'],
                'content' => $comment['text']
            );

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_exec($ch);
            curl_close($ch);
        }

        return $comment;
    }
}