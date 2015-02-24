<?php
function replace_star()
{
	ob_start();
}

function replace_fonts()
{
	$html = ob_get_contents();
	ob_end_clean();

	echo str_replace('//fonts.googleapis.com/', '//fonts.useso.com/', $html);
}

function check_css($file)
{
	if ($file == 'login')
	{
		replace_star();
	}
}

function get_ssl_avatar($avatar)
{
	$avatar = preg_replace('/.*\/avatar\/(.*)\?s=([\d]+)&.*/','<img src="https://secure.gravatar.com/avatar/$1?s=$2" class="avatar avatar-$2" height="$2" width="$2">',$avatar);
	return $avatar;
}