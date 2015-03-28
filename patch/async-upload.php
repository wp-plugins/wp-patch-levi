<?php
class AsyUpload
{
	private $_clean = TRUE;
	private $_maxFileAge = 18000;
	
	/*
	 * file_data_name，是设置前后台字段一致
	 * 比如默认是file，那么就需要通过$_FILE['file']来获取当前上传的对象
	 * 
	 * 如果我需要设置不同的字段来进行区别，那么就可以设置file_data_name
	 * 比如：file_data_name: auto-file
	 * 那么获取当前对象就是$_FILE['auto-file']
	 */
	private $_options = array(
		'file_data_name' => 'async-upload'
	);
	
	public function setUploadHeader()
	{
		if (strrchr($_SERVER['PHP_SELF'], '/wp-admin/async-upload.php'))
		{
			set_time_limit(5 * 60);
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: post-check=0, pre-check=0', false);
			header('Pragma: no-cache');
		}
	}
	
	/**
	 * 主要是截获async-upload中检查数据这段，覆盖检查字段
	 * @param string $action
	 */
	public function checkAdminRefererUpload($action)
	{
		if ($action == 'media-form' && strrchr($_SERVER['PHP_SELF'], '/wp-admin/async-upload.php'))
		{
			$post_id = 0;
			$key = $this->_options['file_data_name'];
			if ( isset( $_REQUEST['post_id'] ) ) {
				$post_id = absint( $_REQUEST['post_id'] );
				if ( ! get_post( $post_id ) || ! current_user_can( 'edit_post', $post_id ) )
					$post_id = 0;
			}
				
			// 就改了这个地方
			$post_data = isset($_REQUEST['name']) ? array('post_title' => $_REQUEST['name']) : array();
			$id = media_handle_upload( $key, $post_id, $post_data, array(
				'test_form' => false, 'action' => 'wp_handle_levi_upload'
			));
				
			if ( is_wp_error($id) ) {
				// 这里修改了，获取code判断当前是否为分批上传
				if (is_array($error = $id->get_error_message()))
				{
					echo $error['msg'];
					exit;
				}
				
				echo '<div class="error-div error">
		<a class="dismiss" href="#" onclick="jQuery(this).parents(\'div.media-item\').slideUp(200, function(){jQuery(this).remove();});">' . __('Dismiss') . '</a>
		<strong>' . sprintf(__('&#8220;%s&#8221; has failed to upload due to an error'), esc_html($_FILES[$key]['name']) ) . '</strong><br />' .
		esc_html($error) . '</div>';
				exit;
			}
				
			if ( $_REQUEST['short'] ) {
				// Short form response - attachment ID only.
				echo $id;
			} else {
				// Long form response - big chunk o html.
				$type = $_REQUEST['type'];
					
				/**
				 * Filter the returned ID of an uploaded attachment.
				 *
				 * The dynamic portion of the hook name, `$type`, refers to the attachment type,
				 * such as 'image', 'audio', 'video', 'file', etc.
				 *
				 * @since 2.5.0
				 *
				 * @param int $id Uploaded attachment ID.
				*/
				echo apply_filters( "async_upload_{$type}", $id );
			}
				
			exit;
		}
	}
	
	/**
	 * 主要是截获async-upload中检查数据这段，覆盖检查字段
	 * @param string $action
	 */
	function checkAjaxRefererUpload($action)
	{
		if ($action == 'media-form' && strrchr($_SERVER['PHP_SELF'], '/wp-admin/async-upload.php'))
		{
			$key = $this->_options['file_data_name'];
			if ( ! current_user_can( 'upload_files' ) ) {
				wp_send_json_error( array(
					'message'  => __( "You don't have permission to upload files." ),
					'filename' => $_FILES[$key]['name'],
				) );
			}
				
			if ( isset( $_REQUEST['post_id'] ) ) {
				$post_id = $_REQUEST['post_id'];
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					wp_send_json_error( array(
						'message'  => __( "You don't have permission to attach files to this post." ),
						'filename' => $_FILES[$key]['name'],
					) );
				}
			} else {
				$post_id = null;
			}
				
			$post_data = isset( $_REQUEST['post_data'] ) ? $_REQUEST['post_data'] : array();
				
			// If the context is custom header or background, make sure the uploaded file is an image.
			if ( isset( $post_data['context'] ) && in_array( $post_data['context'], array( 'custom-header', 'custom-background' ) ) ) {
				$wp_filetype = wp_check_filetype_and_ext( $_FILES[$key]['tmp_name'], $_FILES[$key]['name'], false );
				if ( ! wp_match_mime_types( 'image', $wp_filetype['type'] ) ) {
					wp_send_json_error( array(
						'message'  => __( 'The uploaded file is not a valid image. Please try again.' ),
						'filename' => $_FILES[$key]['name'],
					) );
				}
			}
				
			// 就改了这个地方
			$attachment_id = media_handle_upload( $key, $post_id, $post_data, array(
				'test_form' => false, 'action' => 'wp_handle_levi_upload'
			) );
				
			if ( is_wp_error( $attachment_id ) ) {
				$error = $attachment_id->get_error_message();
				wp_send_json_error( array(
					'message'  => is_array($error) ? $error['msg'] : $error,
					'filename' => $_FILES[$key]['name'],
				) );
			}
				
			if ( isset( $post_data['context'] ) && isset( $post_data['theme'] ) ) {
				if ( 'custom-background' === $post_data['context'] )
					update_post_meta( $attachment_id, '_wp_attachment_is_custom_background', $post_data['theme'] );
					
				if ( 'custom-header' === $post_data['context'] )
					update_post_meta( $attachment_id, '_wp_attachment_is_custom_header', $post_data['theme'] );
			}
				
			if ( ! $attachment = wp_prepare_attachment_for_js( $attachment_id ) )
				wp_die();
				
			wp_send_json_success( $attachment );
			exit;
		}
	}
	
	/*
	 * 关于上传文件许可的类型限制，有两种情况：
	 *   1.管理员后台设置
	 *   2.超出了系统所有允许的文件范围，比如rar
	 */
	public function uploadToTmp($file)
	{	
		$fileName = isset($_REQUEST['name']) ? $_REQUEST['name'] : (isset($file['name']) ? $file['name'] : uniqid('file_'));
		$targetDir = get_temp_dir();
		$filePath = $targetDir.$fileName;
		
		// Chunking might be enabled
		$chunk = isset($_REQUEST['chunk']) ? intval($_REQUEST['chunk']) : 0;
		$chunks = isset($_REQUEST['chunks']) ? intval($_REQUEST['chunks']) : 0;
		
		try 
		{
			$this->_clean && $this->_cleanTargetDir($targetDir, $filePath);
			if (FALSE == ($out = fopen($filePath.'.part', $chunks ? 'ab' : 'wb'))) 
			{
				throw new Exception('Failed to open output stream.');
			}

			$key = $this->_options['file_data_name'];
			if (!isset($_FILES[$key])) 
			{
				if (FALSE == ($in = fopen('php://input', 'rb'))) 
				{
					throw new Exception('Failed to open output stream.');
				}
			}
			else 
			{
				if ($file['error'] || !is_uploaded_file($file['tmp_name'])) 
				{
					throw new Exception('Failed to move uploaded file.');
				}
				
				if (FALSE == ($in = fopen($file['tmp_name'], 'rb'))) 
				{
					throw new Exception('Failed to open output stream.');
				}
			}
			
			while (FALSE != ($buff = fread($in, 4096)))
			{
				fwrite($out, $buff);
			}
			
			fclose($out);
			fclose($in);
			
			if (!$chunks || ($chunk == $chunks - 1)) 
			{
				rename($filePath.'.part', $filePath);
				
				$ext = wp_check_filetype_and_ext($filePath, $fileName);
				return array(
					'name' => $fileName, 
					'type' => $ext['type'] ? $ext['type'] : 'text/plain',
					'tmp_name' => $filePath,
					'error' => 0,
					'size' => filesize($filePath)
				);
			} else 
			{
				throw new Exception('正在分批上传', 206);
			}
		}
		catch (Exception $e)
		{
			$file['error'] = array('msg' => $e->getMessage(), 'code' => $e->getCode());
			return $file;
		}
	}
	
	private function _cleanTargetDir($targetDir, $filePath)
	{
		if (FALSE == ($dir = opendir($targetDir))) 
		{
			throw new Exception('Failed to open temp directory.');
		}
		
		while (FALSE !== ($file = readdir($dir)))
		{
			$tmpfilePath = $targetDir.DIRECTORY_SEPARATOR.$file;
			
			// If temp file is current file proceed to the next
			if ($tmpfilePath == $filePath.'.part') 
			{
				continue;
			}
			
			if (strstr($file, '.part') && (filemtime($tmpfilePath) < time() - $this->_maxFileAge)) 
			{
				@unlink($tmpfilePath);
			}
		}
	}
}