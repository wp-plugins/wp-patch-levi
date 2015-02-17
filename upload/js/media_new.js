;jQuery(function($) {
	$('.max-upload-size').each(function(){
		var $this = $(this);
		if (!$this[0].back){
			$this[0].back = $this.text();
		}

		if ($('.html-uploader').length){
			$this.text($('#maxsize-tips').html());
		}

		$('.upload-flash-bypass a, .upload-html-bypass a').click(function (){
			$('.max-upload-size').trigger('tips');
			$this.text($this.text().indexOf('切换') < 0 ? $('#maxsize-tips').html() : $this[0].back);
		});
	});
});