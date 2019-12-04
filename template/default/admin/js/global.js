jQuery(function() {
    // 下拉菜单
    if ($('.dropMenu').length && $('.dropMenu').length > 0) {
        $('.dropMenu').hover(function() {
            $(this).addClass('active');
        },
        function() {
            $(this).removeClass('active');
        });
    }
    // 标签切换
    jQuery('a[data-tab*="#"]').click(function(){
    	jQuery('a[data-tab*="#"]').each(function(){
    		jQuery(this).removeClass('selected');
    		jQuery(jQuery(this).attr('data-tab')).hide();
    	});
    	jQuery(this).addClass('selected');
    	jQuery(jQuery(this).attr('data-tab')).show();
    });
    setNavActive();
});

function admin_login(d){
	d = typeof(d)=='object'?d:JSON.parse(d);
	callback_ajax(d);
	if( d.status=='yes' ){
		window.location.href = '/admin/index.html';
	}
}

function setNavActive(k){
	k = k?k:PHP.parse_url(location.href).path.replace('/admin/', '');
	jQuery('#menu li.cur').removeClass('cur');
	jQuery('#menu li a[href*="'+k+'"]').parent('li').addClass('cur');
}

function delete_row(table,data_id){
	if( confirm('Are you sure to delete?') ){
		_ajax(location.href, 'act=admin/delete_row&id='+data_id+'&table='+table, 'GET', function(d){
			d = typeof(d)=='object'?d:JSON.parse(d);
			callback_ajax(d);
			if( d.status=='yes' ){
				jQuery('tr[data-id="'+data_id+'"]').remove();
			}
		}, true);
	}
}

function slide_edit(id){
	var slide_data = PHP.json_decode(PHP.base64_decode(jQuery('tr[data-id="'+id+'"]').attr('data')));
	console.log(slide_data);
	jQuery('form.slide_info input[name="form[name]"]').val(slide_data.name);
	jQuery('form.slide_info input[name="form[url]"]').val(slide_data.url);
	jQuery('form.slide_info input[name="form[category]"]').val(slide_data.category);
	jQuery('form.slide_info input[name="form[sort]"]').val(slide_data.sort);
	jQuery('form.slide_info textarea[name="form[notes]"]').val(slide_data.notes);
	jQuery('form.slide_info input[name="form[id]"]').val(slide_data.id);
	jQuery('form.slide_info input[name="form[img]"]').val(slide_data.img);
}

function ajaxFileUpload(id_name,post_url,post_jsondata,_callback,file_ext){
	var ths = jQuery(id_name);
	var name = ths.attr('name');
	file_ext = (file_ext||'gif|jpg|jpeg|png').toLowerCase();
	if (ths.val().length <= 0) {
		alert("请选择需要上传的文件");
		return false;
	}else if( !(new RegExp('\.('+file_ext+')$')).test(ths.val().toLowerCase()) ){
		alert("图片类型必须是."+file_ext+"中的一种");
		ths.val("");
		return false;
	}
	jQuery.ajaxFileUpload({
		url: post_url,
		type: 'post',
		data: post_jsondata,
		secureuri: false,
		fileElementId: id_name,
		dataType: 'text',
		success: function(d){
			d = (typeof(d)=='object')?d:JSON.parse(d);
			(typeof(_callback)=='function')?_callback(d):null;
			if( d.data && d.data.upfiles ){
				jQuery('input[data-file="'+name+'"]').val(PHP.implode(',', d.data.upfiles));
				if( jQuery('div[data-imgs="form_file"]').length ){
					jQuery('div[data-imgs="form_file"]').html('');
					jQuery.each(d.data.upfiles, function(k,v){
						jQuery('div[data-imgs="form_file"]').append('<img src="'+v+'" width="100" height="100">');
					});
				}
			}
		},
		error: function(data, status, e){alert(e);}
	});
	return false;
}