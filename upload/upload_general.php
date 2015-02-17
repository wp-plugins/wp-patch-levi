<?php
// 单博客，控制台-常规设置中添加选项
function register_upload_fields()
{
	add_settings_section('upload_fields_group', '上传设置', 'upload_fields_group_tmp', 'general');
	add_settings_field('upload_filetypes', '上传文件类型', 'fields_upload_html', 'general', 'upload_fields_group', array('id' => 'upload_filetypes'));
	add_settings_field('fileupload_maxk', '最大上传文件的大小', 'fields_upload_html', 'general', 'upload_fields_group', array('id' => 'fileupload_maxk'));

// 	register_setting('general', 'upload_space_check_disabled');
// 	register_setting('general', 'blog_upload_space');
	register_setting('general', 'upload_filetypes');
	register_setting('general', 'fileupload_maxk');
}

function upload_fields_group_tmp()
{
	echo '<p>请根据当前博客需求，设置上传文件配置信息</p>';
}

function fields_upload_html($arg)
{
	include 'template/upload_general_field.htm';
}

// 安装、注册插件时，添加初始数据
function update_create_option($value)
{
	add_option('upload_filetypes', 'jpg jpeg png gif mp3 mov avi wmv midi mid pdf zip');
	add_option('fileupload_maxk', '1048577');
}

// 不限制单一博客容量
function upload_space_check_disabled() 
{
	return 1;
}