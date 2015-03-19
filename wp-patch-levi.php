<?php
/**
 * Plugin Name: wordpress 补丁包
 * Plugin URI: http://levi.cg.am
 * Description: 第三方wordpress补丁包，修复wordpress漏洞、程序缺陷
 * Version: 0.2.4
 * Network: true
 * Author: Levi
 * Author URI: http://levi.cg.am
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

include 'patch/upload-count-plug.php';
include 'patch/Yii_bloger_upload.php';
include 'patch/async-upload.php';
include 'patch/network.php';

define('WP_UPLOAD_LEVI', true);

/*
 * 替换google字体
 */
add_action('admin_enqueue_scripts', 'replace_star', 1);
add_action('admin_head', 'replace_fonts', 9999);

add_action('wp_head', 'replace_star', 1);
add_action('wp_head', 'replace_fonts', 9999);

add_filter('wp_admin_css', 'check_css', 1, 2);
add_action('login_head', 'replace_fonts', 9999);

/*
 * 解决avatar头像问题
 */
add_filter('get_avatar', 'get_ssl_avatar');

/*
 * 文章附件统计
 */
add_action('save_post', 'save_post', 100, 2);

/*
 * 文件上传
 */
$upload = new Yii_bloger_upload();

//展示允许上传类型、文件大小
wp_register_script('media-new-change-size', plugins_url('patch/js/media_new.js', __FILE__), array('jquery'));

add_filter('plupload_init', array($upload, 'plupload_init'));
add_filter('plupload_default_params', array($upload, 'plupload_default_params'));
add_filter('plupload_default_settings', array($upload, 'plupload_default_settings'));

add_action('media_upload_image', array($upload, 'media_upload_image'));

// 移除默认的钩子，添加新的钩子
// 修改上传大小提示信息
add_filter('upload_size_limit', array($upload, 'upload_size_limit_filter'));
remove_filter('upload_size_limit', 'upload_size_limit_filter');

add_action('admin_print_scripts-media-upload-popup', array($upload, 'load_js'));
add_action('admin_print_scripts-media-new.php', array($upload, 'load_js'));
add_action('post-upload-ui', array($upload, 'post_upload_ui'));

// 上传处理
$async = new AsyUpload();

add_action('admin_init', array($async, 'setUploadHeader'));
add_action('check_admin_referer', array($async, 'checkAdminRefererUpload'));
add_action('check_ajax_referer', array($async, 'checkAjaxRefererUpload'));

// 分割文件进行上传处理
add_filter('wp_handle_levi_upload_prefilter', array($async, 'uploadToTmp'), 1);

// 检查上传文件的格式
add_filter('pre_update_option_upload_filetypes', array($upload, 'setOption'));
add_filter('pre_update_site_option_upload_filetypes', array($upload, 'setOption'));

// 如果没有开启多博客网站需要添加配置
if (!is_multisite()) 
{
	include 'patch/upload_general.php';
	
	add_filter('pre_site_option_upload_space_check_disabled', 'upload_space_check_disabled');
	add_action('admin_init', 'register_upload_fields');
	add_action('admin_menu', array($upload, 'addMenu'));
	
	register_activation_hook(__FILE__, 'update_create_option');
} 
else 
{
	// check_upload_size : 单一的博客是没有这个函数的 @Todo: 需要一个检查文件大小的函数
	add_filter('wp_handle_levi_upload_prefilter', 'check_upload_size');
	add_action('network_admin_menu', array($upload, 'addSiteMenu'));
}