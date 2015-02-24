<?php
/*
 * wordpress 文章附件统计
 */

function save_post($post_id, $post)
{
	global $wpdb;
	if (empty($_POST))
	{
		return ;
	}

	$data = array();
	$content = $post->post_content;
	
	$dir_info = wp_upload_dir();
	if (stristr($content, $dir_info['baseurl'])) 
	{
		$url_pate = sprintf('/(href|src)=.*?%s([^"\'>\s]+?)["\'>\s]/is', preg_quote($dir_info['baseurl'].'/', '/'));
		if (preg_match_all($url_pate, $content, $match)) 
		{
			$files = array_flip(array_flip($match[2]));
			$sql = "SELECT `post_id` FROM `%s` WHERE `meta_key`='_wp_attached_file' AND (`meta_value`='%s');";
			
			$row = $wpdb->get_col(sprintf($sql, $wpdb->postmeta, implode("' OR `meta_value`='", $files)));
			$row && $data = $row;
		}
	}
		
	if (stristr($content, '[gallery') && preg_match_all('/\[gallery\s+ids=(["\']?)([^"\'\]]*)\1\s*\]/is', $content, $gallery))
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

	$data = apply_filters('wp_post_attach_total', $data, $post_id);
	if (!empty($data))
	{
		$data = array_flip(array_flip($data));
		update_post_meta($post_id, '_wp_post_attach_total_', implode(',', $data));
	}
	else
	{
		delete_post_meta($post_id, '_wp_post_attach_total_');
	}

	wp_cache_delete('attach_meda-'.$post_id, 'wp-plus');
}

function get_attached_from_post($post_id, $type = '', $must = false)
{
	$key = sprintf('attach_meda-%d', $post_id);
	if (!$must && false != ($cache = wp_cache_get($key, 'wp-plus')))
	{
		return $cache;
	}

	if (false == ($meta = get_post_meta($post_id, '_wp_post_attach_total_', true)))
	{
		return array();
	}

	$arg = array(
		'include' => $meta,
		'post_type' => 'attachment',
		'post_mime_type' => $type,
		'posts_per_page' => -1,
		'orderby' => 'menu_order',
		'order' => 'ASC'
	);

	if (false == ($data = get_posts($arg)))
	{
		return array();
	}

	return wp_cache_set($key, $data, 'wp-plus', 900);
}