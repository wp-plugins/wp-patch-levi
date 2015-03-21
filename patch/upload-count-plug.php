<?php
/*
 * wordpress 文章附件统计
 */

class GetAttachedMediaLevi
{
	private $_post = null;
	
	/**
	 * 这里不管三七二十一，都返回空数组来阻止wordpress去查询数据（查出来的数据是不对的）
	 * 还有一件要做的事情，避免$GLOBALS['post']存在，破坏返回为空，这里需要删除$GLOBALS['post']
	 * 还要做最后一件事，将$GLOBALS['post']记录下来，获取数据后再还原回去
	 * 
	 * @return array:
	 */
	public function getAttachedMediaArgs() 
	{
		if (isset($GLOBALS['post'])) 
		{
			$this->_post = $GLOBALS['post'];
			unset($GLOBALS['post']);
		}
		
		return array();
	}
	
	public function getAttachedMedia($children, $type, $post) 
	{
		// 如果没有提供ID，则采用当前文章ID，若当前文章没有ID，返回空
		if (!$post) 
		{
			if ($this->_post) 
			{
				$post = $this->_post;
			}
			else 
			{
				return array();
			}
		}
		
		empty($type) && $type = 'any';
		$key = sprintf('attach_meda-%d', $post->ID);
		
		if ($this->_post) 
		{
			$GLOBALS['post'] = $this->_post;
		}
		
		// 先从cache中拿出来
		if (false != ($cache = wp_cache_get($key, 'wp-plus')))
		{
			if (isset($cache[$type])) 
			{
				return $cache[$type];
			}
		}
		else 
		{
			$cache = array();
		}
		
		$data = array();
		if (false != ($info = $this->getAttacheInfo($post->ID))) 
		{
			$arg = array(
				'include' => $info,
				'post_type' => 'attachment',
				'post_mime_type' => $type == 'any' ? '' : $type,
				'posts_per_page' => -1,
				'orderby' => 'menu_order',
				'order' => 'ASC'
			);
		
			$data = get_posts($arg);
		}
		
		$cache['type'] = $data;
		wp_cache_set($key, $cache, 'wp-plus', WEEK_IN_SECONDS);
		
		return $data;
	}
	
	public function getAttacheInfo($post_id) 
	{
		global $wpdb;
		
		if (false != ($info = get_post_meta($post_id, '_wp_post_attach_total_', true)))
		{
			return $info;
		}
		
		if (false == ($post_content = get_post($post_id)->post_content)) 
		{
			return '';
		}
		
		$data = array();
		$dir_info = wp_upload_dir();
		
		if (stristr($post_content, $dir_info['baseurl']))
		{
			$url_pate = sprintf('/(href|src)=.*?%s([^"\'>\s]+?)["\'>\s]/is', preg_quote($dir_info['baseurl'].'/', '/'));
			if (preg_match_all($url_pate, $post_content, $match))
			{
				$files = array_flip(array_flip($match[2]));
				$sql = "SELECT `post_id` FROM `%s` WHERE `meta_key`='_wp_attached_file' AND (`meta_value`='%s');";
					
				$row = $wpdb->get_col(sprintf($sql, $wpdb->postmeta, implode("' OR `meta_value`='", $files)));
				$row && $data = $row;
			}
		}
		
		if (stristr($post_content, '[gallery') && preg_match_all('/\[gallery\s+ids=(["\']?)([^"\'\]]*)\1\s*\]/is', $post_content, $gallery))
		{
			$where = array();
			$gallery = explode(',', implode(',', $gallery[2]));
		
			foreach ($gallery as $val)
			{
				$where[] = '`post_id`='.trim($val);
			}
				
			$sql = sprintf("SELECT `post_id` FROM `%s` WHERE `meta_key`='_wp_attached_file' AND (%s);", $wpdb->postmeta, implode(' OR ', $where));
			(FALSE != ($row = $wpdb->get_col($sql))) && $data = array_merge($data, $gallery);
		}
		
		if (FALSE != ($thumb_id = get_post_meta($post_id, '_thumbnail_id')))
		{
			$data[] = $thumb_id[0];
		}
		
		if (false != ($data = apply_filters('wp_post_attach_total', $data, $post_id))) 
		{
			$data = array_flip(array_flip($data));
			$data = implode(',', $data);
			
			update_post_meta($post_id, '_wp_post_attach_total_', $data);
			return $data;
		}
		else 
		{
			return '';
		}
	}
	
	public function savePost($post_id, $post = null, $update = true) 
	{
		if ($update) 
		{
			delete_post_meta($post_id, '_wp_post_attach_total_');
			wp_cache_delete('attach_meda-'.$post_id, 'wp-plus');
		}
	}
}