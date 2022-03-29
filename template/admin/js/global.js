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
    if( jQuery('select[data-default]').length>0 ){
    	jQuery('select[data-default]').each(function(k,v){
    		jQuery(this).find('option[value="'+jQuery(this).attr('data-default')+'"]').attr('selected','selected');
    	});
    }
});

function del_data_imgs( o ){
	var that = $(o),
		name = that.parents('[data-imgs]').attr('data-imgs'),
		img_src = that.parents('[data-imgs] .imgs').find('img').attr('src'),
		input_obj = $('input[data-file="'+ name +'"]'),
		input_val = input_obj.val(),
		input_vals = input_val.split(',');
	if( input_vals ){
		for( let k in input_vals ){
			if( input_vals[k].indexOf( img_src )>-1 || img_src.indexOf( input_vals[k] )>-1 ){
				input_vals[k] = null;
				that.parents('[data-imgs] .imgs').remove();
				break;
			}
		}
		input_vals = PHP.array_unique( PHP.array_filter( input_vals, function( val ){ return val&&val.length >0; } ) );
		input_val = PHP.implode( ',', input_vals );
		input_obj.val( input_val );
	}
}

function admin_login(d){
	d = typeof(d)=='object'?d:JSON.parse(d);
	if( d.status=='yes' ){
		window.location.href = 'index.html';
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
			( typeof(_callback)=='function' ) ? _callback(d) : null;
			if( d.data && d.data.upfiles ){
				var input_obj = jQuery( 'input[data-file="'+name+'"]' ),
				old = input_obj.val();
				jQuery( 'input[data-file="'+name+'"]' ).val( ( old ? (old + ',') :'' ) + PHP.implode(',', d.data.upfiles) );
				if( jQuery('div[data-imgs="'+name+'"]').length ){
					jQuery.each(d.data.upfiles, function(k,v){
						jQuery('div[data-imgs="'+name+'"]').append('<div class="imgs"><img src="'+v+'" width="100" height="100"><div class="del-img" onclick="del_data_imgs(this)">删除</div></div>');
					});
				}
			}
		},
		error: function(data, status, e){alert(e);}
	});
	return false;
}
