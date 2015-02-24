<?php
class Yii_bloger_upload 
{
	private $_max_size = 1500;
	private $_upload_filetypes = 'jpg jpeg png gif';
	
	public function __construct()
	{
		$maxk = get_option('fileupload_maxk');
		$this->_max_size = is_numeric($maxk) ? $maxk : get_site_option('fileupload_maxk', 1500);
	}
	
	public function setOption($value) 
	{
		$type = explode('|', implode('|', array_keys(wp_get_mime_types())));
		$value = explode(' ', $value);
		
		return implode(' ', array_intersect($value, $type));
	}
	
	public function media_upload_image()
	{
		define('MEDIA_UPLOAD_IMG', true);
	}
	
	public function wpUploadList() 
	{
		include 'template/wp_patch_list.htm';
	}
	
	public function addSiteMenu() 
	{
		add_submenu_page('settings.php', '补丁包列表', '补丁包列表', 'manage_network_users', 'wp_upload_list', array($this, 'wpUploadList'));
	}
	
	public function addMenu() 
	{
		add_submenu_page('options-general.php', '补丁包列表', '补丁包列表', 'manage_options', 'wp_upload_list', array($this, 'wpUploadList'));
	}
	
	/*
	 * 这里需要将AUTH_COOKIE作为参数传过去，否则前台无法获取到cookies，而判断为没有登录
	 */
	public function plupload_init(Array $data)
	{
		$data = $this->plupload_default_settings($data);
		$data['multipart_params']['auth_cookie'] = $_COOKIE[AUTH_COOKIE];
		
		return $data;
	}
	
	public function plupload_default_settings(Array $data)
	{
		$data['chunk_size'] = 204800;
		$data['max_file_size'] = $this->_max_size.'kb';
		$data['filters']['mime_types'][0]['extensions'] = str_replace(' ', ',', $this->_get_upload_filetypes());
		
		return $data;
	}
	
	public function plupload_default_params(Array $data)
	{
		$data['auth_cookie'] = $_COOKIE[AUTH_COOKIE];
		return $data;
	}
	
	public function upload_size_limit_filter()
	{
		return 1024 * $this->_max_size;
	}
	
	public function load_js()
	{
		wp_enqueue_script('media-new-change-size');
	}
	
	public function post_upload_ui()
	{
		$size = get_site_option('upload_space_check_disabled');
		printf('<p>当前可上传的文件类型有：%s</p>', $this->_get_upload_filetypes());
		printf('<script id="maxsize-tips" type="text/html">'.__( 'Maximum upload file size: %d%s.' ).' 如果需要上传大文件，请切换为“新的上传工具”。</script>', $size ? $size : 1, 'MB');
	}
	
	private function _get_upload_filetypes()
	{
		$type = (defined('MEDIA_UPLOAD_IMG') && MEDIA_UPLOAD_IMG) ? $this->_upload_filetypes : get_option('upload_filetypes', get_site_option('upload_filetypes'));
		return $type ? $type : $this->_upload_filetypes;
	}
}