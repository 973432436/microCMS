var ecms = {};

// 校验邮件
ecms.isEmail = function (str) {
	return str.match(/^[-_A-Z0-9]+@([_A-Z0-9]+\.)+[A-Z0-9]{2,3}$/i);
};

// 获取URL参数
ecms.request = function (paras,url){
	if(!url){var url = location.href;}
	var paraString = url.substring(url.indexOf("?") + 1, url.length).split("&");
	var paraObj = {};
	for (i = 0; j = paraString[i]; i++) {
		paraObj[j.substring(0, j.indexOf("=")).toLowerCase()] = j.substring(j.indexOf("=") + 1, j.length)
	}
	var returnValue = decodeURIComponent(paraObj[paras.toLowerCase()]);
	if (typeof (returnValue) == "undefined") {
		return null
	} else {
		return returnValue
	}
};

// AJAX请求
ecms._ajax = function (post_url, post_value, method, refunction, async){
	jQuery.ajax({
		async: async,
		cache: false,
		type: method,
		url: post_url,
		data: post_value,
		success: function(d){
			d = jQuery.parseJSON(d);
			refunction(d);
		}
	});
};

// ajax通用回调处理 die(json_encode(array('status'=>'yes','message'=>'信息提交成功','data'=>$data,'url'=>'')));
ecms.callback_ajax = function (data){
	try{
		var obj = typeof(data)!='object'?jQuery.parseJSON(data):data;
		if(obj.message && obj.message!=''){
			alert(obj.message);
		}
		if(obj.status && obj.status=='yes' && obj.url && obj.url!=''){
			window.location.href=obj.url;
		}
	}catch(e){
		alert(e);
	}
};

// AJAX 提交表单
ecms.ajaxForm = function (id,refunction,async){
	var ajax_url = jQuery(id).attr("action");
	var ajax_type = jQuery(id).attr('method');
	var ajax_data = jQuery(id).serialize();
	async = async===false?false:true;
	ecms._ajax(ajax_url, ajax_data, ajax_type, refunction, async);
};

ecms.onload = function (func){
  var oldonload = window.onload;
  if (typeof window.onload != 'function') {
    window.onload = func;
  } else {
    window.onload = function() {
      oldonload();
      func();
    }
  }
};