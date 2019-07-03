<?php
/**
 * 评论通知推送至 Webhooks
 *
 * @package Comment2Hook
 * @author medmin
 * @version 1.0.0
 * @link https://github.com/SuperPHP/Typecho_Comment2Hook_Plugin
 */
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
        $url = new Typecho_Widget_Helper_Form_Element_Text('whUrl', NULL, NULL, _t('Webhooks URL'), _t("URL是必须的"));
        $form->addInput($url->addRule('required', _t('您必须填写 Webhook URL')));

        $key = new Typecho_Widget_Helper_Form_Element_Text('whKey', NULL, NULL, _t('Webhook Private Key'), _t('Webhooks密钥'));
        $form->addInput($key->addRule('required', _t('您必须填写 Webhook Private Key')));

        $excludeBlogger = new Typecho_Widget_Helper_Form_Element_Radio('excludeBlogger',
            array(
                '1' => '是',
                '0' => '否'
            ),'1', _t('当评论者为博主本人时不推送'), _t('启用后，若评论者为博主，则不会推送至 Webhooks'));
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

        $whUrl = $options->whUrl;
        $whKey = $options->whKey;
        $excludeBlogger = $options->excludeBlogger;

        if ($comment['authorId'] == 1 && $excludeBlogger == '1') {
            return $comment;
        }

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

        return $comment;
    }
}