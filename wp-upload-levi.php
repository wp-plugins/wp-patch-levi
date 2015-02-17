<?php
/**
 * Plugin Name: wordpress 补丁包
 * Plugin URI: http://levi.cg.am
 * Description: 第三方wordpress补丁包，修复wordpress漏洞、程序缺陷
 * Version: 0.1.3
 * Network: true
 * Author: Levi
 * Author URI: http://levi.cg.am
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

include 'upload/upload-count-plug.php';
include 'upload/Yii_bloger_upload.php';
include 'upload/async-upload.php';

define('WP_UPLOAD_LEVI', true);
/*
 * 文章附件统计
 */
add_action('save_post', 'save_post', 100, 2);

/*
 * 文件上传
 */
$upload = new Yii_bloger_upload();

//展示允许上传类型、文件大小
wp_register_script('media-new-change-size', plugins_url('upload/js/media_new.js', __FILE__), array('jquery'));

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

add_filter('wp_handle_levi_upload_prefilter', array($async, 'uploadToTmp'), 1);
add_filter('wp_handle_levi_upload_prefilter', 'check_upload_size');

// 检查上传文件的格式
add_filter('pre_update_option_upload_filetypes', array($upload, 'setOption'));
add_filter('pre_update_site_option_upload_filetypes', array($upload, 'setOption'));

// 如果没有开启多博客网站需要添加配置
if (!is_multisite()) 
{
	include 'upload/upload_general.php';
	
	add_filter('pre_site_option_upload_space_check_disabled', 'upload_space_check_disabled');
	add_action('admin_init', 'register_upload_fields');
	add_action('admin_menu', array($upload, 'addMenu'));
	
	register_activation_hook(__FILE__, 'update_create_option');
} 
else 
{
	add_action('network_admin_menu', array($upload, 'addSiteMenu'));
}