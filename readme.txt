=== wp-patch-levi ===
Contributors: cgfeel
Donate link: 
Tags: upload, attachment, plupload, big file, google fonts, gravater
Requires at least: 3.1.0
Tested up to: 4.1.1
Stable tag: 0.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

wordpress第三方补丁包，修复wordpress程序功能中的不足和缺陷

== Description ==

**插件支持平台：**
http://levi.cg.am/archives/4603

有什么问题以及意见请在这里提出来，会根据请款做出修正

网站速度补丁包：

* google字体：使用360开源CDN替换google CDN，对所有主题、插件、页面均可通用匹配、替换；详细见：http://levi.cg.am/archives/4609
* gravater头像：替换gravater头像URL为https，解决在中国地区无法查看头像图片的问题；

博客附件补丁包：

* 附件修复：修正wordpress文章附件统计不正确，详细见：http://levi.cg.am/archives/3849
* 大文件上传：使用新的HTML5技术切割大文件，让wordpress能够接受上传GB级的文件，详细见：http://levi.cg.am/archives/4603

== Screenshots ==

1. 补丁包列表

== Installation ==

在线安装方法：

1. 点击“安装插件”搜索`wp-patch`即可找到插件
2. 点击安装插件，等待wordpress在线安装完毕
3. 在插件管理中启动插件

离线安装方法：

1. 下载离线插件包并解压
2. 复制目录到`/wp-content/plugins`下
3. 在插件管理中启动插件

== Changelog ==

= 0.2.1 =
* 新增补丁：解决google字体在中国地区无法正常使用的问题（通用解决方案）；
* 新增补丁：解决gravater头像在中国地区无法正常使用的问题；

= 0.1.4 =
* BUG修复：修复一处Hook，单一的博客不存在的系统函数调用；

= 0.1.3 =
* BUG修复：服务器上传的暂存目录路径不正确；

= 0.1.2 =
* BUG修复：启动插件后，初始数据没有添加；

= 0.1.1 =
* BUG修复：更新上传配置后，保存的数据类型错误；
* BUG修复：配置模板未调用配置数据；

= 0.1 =
* 新增文章附件统计补丁；
* 新增wordpress上传大文件功能；

== Upgrade Notice ==

= 0.2.1 =
* 增加网站打开速度补丁包；

= 0.1.4 =
* 单一博客hook修复；

= 0.1.3 =
* 文件暂存路径修复；

= 0.1.2 =
* 初始错误修复；

= 0.1.1 =
* 错误修复；

= 0.1 =
* 补丁包发布上线；

== Frequently Asked Questions ==

问：补丁包安装后如何设置使用

答：补丁包启用后无需设置即可使用

——————

问：补丁包和wordpress其他插件有什么不一样吗

答：补丁包是对wordpress现有功能进行完善及问题修复，不额外增加第三方应用，属于“纯天然绿色插件”

== Filters ==

文件中使用的钩子(hook)比较多，这里列举一部分

### google字体

通过ob函数，检查输出的内容，替换google字体连接，能够通用匹配所有主题样式

* 后台钩子：admin_enqueue_scripts、admin_head
* 后台登录钩子：wp_admin_css、login_head
* 前台钩子：wp_head

### gravater头像

* get_avatar

### 附件统计：

* save_post：主要用于记录每次新增和修改文章时添加的附件

### 大文件上传：

大文件上传配置信息：

* plupload_init
* plupload_default_param
* plupload_default_settings

大文件上传处理

* check_admin_referer：截获异步上传，进行处理
* check_ajax_referer：截获ajax异步上传，进行处理
* wp_handle_levi_upload_prefilter：这里很关键，需要配合 `'action' => 'wp_handle_levi_upload'` 一起看

检查配置中允许的文件格式

* pre_update_option_upload_filetypes：对应单一博客
* pre_update_site_option_upload_filetypes：对应多博客站点