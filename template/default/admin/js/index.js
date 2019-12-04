/*
 * 说明：系统前端全局函数库
 * 作者：范灿杰 邮箱 973432436@qq.com
 * 日期：2014年1月17日
*/
/*cookie写入 by aibhsc*/
function setCookie(name, value) {
	var Days = 30;
	var exp = new Date();
	exp.setTime(exp.getTime() + Days * 86400);
	document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString()+";path=/;";
}
/*cookie写入(完整版) by aibhsc*/
function setCookie1( name, value, expires, path, domain, secure ) {
	var today = new Date();	
	today.setTime( today.getTime() );
	if ( expires ) {
		expires = expires * 1000 * 60 * 60 * 24;	
	}	
	var expires_date = new Date( today.getTime() + (expires) );	
	document.cookie = name+'='+escape( value ) +		
		( ( expires ) ? ';expires='+expires_date.toGMTString() : '' ) +
		//expires.toGMTString()		
		( ( path ) ? ';path=' + path : '' ) +
		( ( domain ) ? ';domain=' + domain : '' ) +
		( ( secure ) ? ';secure' : '' );
}

/*cookie读取 by aibhsc*/
function getCookie(name) {
	var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");
	if (arr = document.cookie.match(reg)) return unescape(arr[2]);
	else return null
}

/*cookie删除 by aibhsc*/
function delCookie(name) {
	var exp = new Date();
	exp.setTime(exp.getTime() - 1);
	var cval = getCookie(name);
	if (cval != null) document.cookie = name + "=" + cval + ";expires=" + exp.toGMTString();
}

/*获取验证码*/
function getCheckCodeImg(id,checkCodeImgURL) {
	$(id).html('<img src="'+checkCodeImgURL+'?" onClick="this.src+=Math.random()" alt="图片看不清？点击重新得到验证码" style=" border:0;padding:0;margin:0;width:88px;height:30px;cursor:pointer;" />')
}

/*校验邮件*/
function isEmail(str) {
	var myReg = /^[-_A-Za-z0-9]+@([_A-Za-z0-9]+\.)+[A-Za-z0-9]{2,3}$/;
	if (myReg.test(str)) return true;
	return false
}

/*AJAX操作*/
function ajax_post(info_id, post_url, post_value) {
	$.ajax({
		async: false,
		cache: false,
		type: 'POST',
		url: post_url,
		data: post_value,
		success: function (return_data) {
			$(info_id).html(return_data)
		}
	})
}

/*显示与隐藏元素*/
function isDisplay(id) {
	if ($(id).css("display") == "none") {
		$(id).css("display", "block")
	} else {
		$(id).css("display", "none")
	}
}

/*激活文本框*/
function setCursor(id, position) {
	var txtFocus = document.getElementById(id);
	if ($.browser.msie) {
		var range = txtFocus.createTextRange();
		range.move("character", position);
		range.select()
	} else {
		txtFocus.setSelectionRange(position, position);
		txtFocus.focus()
	}
}

/*
 *获取URL参数 by aibhsc
*/
function request(paras,url){
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
}

/*修改URL参数 by aibhsc
 * url所要更改参数的网址
 * para_name 参数名称
 * para_value 参数值
*/
function setUrlParam(url,para_name,para_value){
	if(!url){return url;}
	var strNewUrl=new String();
	var strUrl=url;
	if(strUrl.indexOf("?")!=-1){
		strUrl=strUrl.substr(strUrl.indexOf("?")+1);
		if(strUrl.toLowerCase().indexOf(para_name.toLowerCase())==-1){
			strNewUrl=url+"&"+para_name+"="+para_value;
			return strNewUrl;
		}else{
			var aParam=strUrl.split("&");
			for(var i=0;i<aParam.length;i++){
				if(aParam[i].substr(0,aParam[i].indexOf("=")).toLowerCase()==para_name.toLowerCase()){
					aParam[i]= aParam[i].substr(0,aParam[i].indexOf("="))+"="+para_value;
				}
			}
			strNewUrl=url.substr(0,url.indexOf("?")+1)+aParam.join("&");
			return strNewUrl;
		}
	}else{
		strUrl+="?"+para_name+"="+para_value;
		return strUrl
	}
}

/*
 * 获取滚动条位置 by aibhsc
 * 使用示例：
	var obj = ScollPostion();
	alert(obj.top);
*/
function  ScollPostion() {
	var t, l, w, h;
	if (document.documentElement && document.documentElement.scrollTop) {
		t = document.documentElement.scrollTop;
		l = document.documentElement.scrollLeft;
		w = document.documentElement.scrollWidth;
		h = document.documentElement.scrollHeight;
	}else if(document.body) {
		t = document.body.scrollTop;
		l = document.body.scrollLeft;
		w = document.body.scrollWidth;
		h = document.body.scrollHeight;
	}
	return { top: t, left: l, width: w, height: h };
}

/*获取当前页URL*/
function GetUrl() {
	var aParams = document.location.search.substr(1).split("&");
	var url = document.location.href.replace(document.location.search.substr(0), "");
	var reqstr = "";
	var argumentslen = arguments.length;
	var argumentstr = "&";
	if (argumentslen > 0) {
		for (var i = 0; i < argumentslen; i++) {
			argumentstr += arguments[i].toString() + "&"
		}
	}
	if (aParams.length > 0) {
		for (i = 0; i < aParams.length; i++) {
			var aParam = aParams[i].split("=");
			if (aParam[0] != "" && argumentstr.indexOf("&" + aParam[0] + "&") < 0) {
				reqstr += aParam[0] + "=" + aParam[1] + "&"
			}
		}
	}
	url = (reqstr.lastIndexOf("&") > 0) ? url + "?" + reqstr.substring(0, reqstr.length - 1) : url + "?go=1";
	return url
}

function getType(obj) {
	return typeof (obj)
}

function str2time(str){	//将日期字符串转换为时间戳
	if(!str || isNaN(str)){return 0;}
	str = str.replace(/-/g,'/'); // 将-替换成/，因为下面这个构造函数只支持/分隔的日期字符串
	var date = new Date(str); // 构造一个日期型数据，值为传入的字符串
	return str2int(((date.getTime())/1000));
}

function str2int(str) {
	if(!str){return 0;}
	var num = parseInt(str);
	return num;
}

function str2float(str){
	if(!str){return 0;}
	return parseFloat(str);
}

function ignoreSpaces(string) {
	var temp = "";
	string = '' + string;
	splitstring = string.split(" ");
	for (i = 0; i < splitstring.length; i++) {
		temp += splitstring[i]
	}
	return temp
}

function formatUrl(str) {
	return encodeURIComponent(ignoreSpaces(str))
}

function formatHtml(str) {
	return encodeURIComponent(str)
}

/*输入统计*/
function countSize(id1, id2, num) {
	$(id1).keyup(function () {
		var content_len = $(id1).val().length;
		var in_len = num - content_len;
		if (in_len >= 0) {
			$(id2).html('您还可以输入' + in_len + '字');
			$(id1).css("border", "")
		} else {
			$(id2).html('您还可以输入' + in_len + '字');
			$(id1).css("border", "1px solid #ff0000")
		}
	})
}

/*字符过滤*/
function strReplace(_keywords, _body) {
	var strs = new Array();
	strs = _keywords.split(" ");
	var m = strs.length;
	for (i = 0; i < m; i++) {
		_body = _body.replace(eval("/" + strs[i] + "/gi"), "")
	}
	return _body
}

/*
	获取select中的所有item，并且组装所有的值为一个字符串，值与值之间用逗号隔开
	@param objSelectId 目标select组件id
	@return select中所有item的值，值与值之间用逗号隔开
*/
function getAllItemValuesByString(objSelectId) {
	var selectItemsValuesStr = "";
	var objSelect = document.getElementById(objSelectId);
	if (null != objSelect && typeof(objSelect) != "undefined") {
		var length = objSelect.options.length;
		for(var i = 0; i < length; i = i + 1) {
			if(objSelect.options[i].selected){
				if (0 == i) {
					selectItemsValuesStr = objSelect.options[i].value;
				} else {
					selectItemsValuesStr = selectItemsValuesStr + "," + objSelect.options[i].value;
				}
			}
		}
	}
	if(selectItemsValuesStr.substring(0,1)==','){
		selectItemsValuesStr = selectItemsValuesStr.substring(1);
	}
	return selectItemsValuesStr;
}


/*JQUERY AJAX请求 by aibhsc
 * 参数：①请求地址 ②参数 ③提交方式 ④返回值处理函数 ⑤是否异步请求
*/
function _ajax(post_url, post_value, method, refunction, async){
	async = true;//启用动画进度，需要强制异步请求
	_loadingContent('block');
	$.ajax({
		async: async,
		cache: false,
		type: method,
		url: post_url,
		data: post_value,
		success: function(d){
			refunction(d);
			_loadingContent('none');
		}
	});
}

/**
 * 功能：ajax通用回调处理
 * 数据格式：
 * 		die(json_encode(array('status'=>'yes','message'=>'信息提交成功','data'=>$data,'url'=>'')));
 */
function callback_ajax(obj){
	try{
		obj = typeof(obj)=='string'?jQuery.parseJSON(obj):obj;
		if(obj.message && obj.message!=''){
			alert(obj.message);
		}
		if(obj.status && obj.status=='yes' && obj.url && obj.url!=''){
			window.location.href=obj.url;
		}
	}catch(e){
		alert(e);
	}
}

/*AJAX 提交表单 by aibhsc
 * 参数：①表单ID	②返回值处理函数		③是否异步，默认为异步
*/
function ajaxForm(id,refunction,async){
	var ajax_url = $(id).attr("action");
	var ajax_type = $(id).attr('method');
	var ajax_data = $(id).serialize();
	if(async===false){
		async = false;
	}else{
		async = true;
	}
	_ajax(ajax_url, ajax_data, ajax_type, refunction, async);
}

/*判断变量是否存在
 * by aibhsc
 * */
function isset(e){
	if(!e){
		return false;
	}else{
		return true;
	}
}

/*将百分数转换为小数
 * by 范灿杰
 */
function percent2float(number,m){
	if(!number){return 0;}
	if(!m){m=3;}
	if(number.indexOf('%')>0){
		number = (parseFloat(number)/100).toFixed(m);
	}
	return number;
}

/*判断字符串2在字符串1中是否存在
 * by 范灿杰
 */
function strstr(str1,str2){
	if(!isset(str1)){
		return false;
	}else{
		if(str1.indexOf(str2)>0){
			return true;
		}else{
			return false;
		}
	}
	return false;
}


function focusClearInput(e){	//对象获取焦点时候清空该对象默认内容
	$(e).click(function(){//用户点击时候自动清空内容
		$(this).val("");
	});
	$(e).focus(function(){
		if(this.defaultValue==$(this).val()){
			$(this).val("");
		}
	}).blur(function(){
		if($(this).val()==""){
			$(this).css("color", "#FF0000");
			$(this).val(this.defaultValue);
		}else{
			$(this).css("color", "#000000");
		}
	});
}

/*输出调试信息*/
function debugLog(e){
	if(e){
		alert(e);
	}
}

/*获取当前页面的文件名*/
function getPageName(){
	var strUrl=window.location.pathname;
	var arrUrl=strUrl.split("/");
	var strPage=arrUrl[arrUrl.length-1];
	return strPage;
}

/*设置导航条当前页面为激活状态*/
function setActivePage(thisPageName){
	$("#nav ul li").removeClass("active");
	if(!thisPageName){
		$("#nav ul li:first").addClass("active");
	}else{
		$("#nav ul li[index='"+thisPageName+"']").addClass("active");
	}
}

/******** JS 兼容补丁 开始 ********/
//让IE兼容forEach方法
//Array.forEach implementation for IE support..
//https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Array/forEach
if (!Array.prototype.forEach) {
	  Array.prototype.forEach = function(callback, thisArg) {
			  var T, k;
			  if (this == null) {
					  throw new TypeError(" this is null or not defined");
			  }
			  var O = Object(this);
			  var len = O.length >>> 0; // Hack to convert O.length to a UInt32
			  if ({}.toString.call(callback) != "[object Function]") {
					  throw new TypeError(callback + " is not a function");
			  }
			  if (thisArg) {
					  T = thisArg;
			  }
			  k = 0;
			  while (k < len) {
					  var kValue;
					  if (k in O) {
							  kValue = O[k];
							  callback.call(T, kValue, k, O);
					  }
					  k++;
			  }
	};
}
/******** JS 兼容补丁 结束 ********/

/*页面初始化*/
function doc_init(){
  var _clientWidth = document.body.clientWidth; //网页可见区域宽
  var _clientHeight = document.body.clientHeight; //网页可见区域高
  var _offsetWidth = document.body.offsetWidth; //网页可见区域宽(包括边线的宽)
  var _offsetHeight = document.body.offsetHeight; //网页可见区域高(包括边线的高)
  var _scrollWidth = document.body.scrollWidth; //网页正文全文宽
  var _scrollHeight = document.body.scrollHeight; //网页正文全文高
  var _scrollTop = document.body.scrollTop; //网页被卷去的高
  var _scrollLeft = document.body.scrollLeft; //网页被卷去的左
  var _screenTop = window.screenTop; //网页正文部分上
  var _screenLeft = window.screenLeft; //网页正文部分左
  var _screenHeight = window.screen.height; //屏幕分辨率的高
  var _screenWidth = window.screen.width; //屏幕分辨率的宽
  var _availHeight = window.screen.availHeight; //屏幕可用工作区高度
  var _availWidth = window.screen.availWidth; //屏幕可用工作区宽度
  var _clientHeight1 = document.documentElement.clientHeight;
  var _clientWidth1 = document.documentElement.clientWidth;
}

//点击弹出内容，方便复制。
function copyToClipboard(notice,text) {
  window.prompt(notice, text);
}
//checkbox全选操作
function checkAll(checkboxName){
	var code_Values = checkboxName?document.getElementsByName(checkboxName):document.getElementsByTagName("input");
	for(i = 0;i < code_Values.length;i++){
		if(code_Values[i].type == "checkbox"){
			code_Values[i].checked = true;
		}
	}
}

//checkbox取消全选
function uncheckAll(checkboxName){
	var code_Values = checkboxName?document.getElementsByName(checkboxName):document.getElementsByTagName("input");
	for(i = 0;i < code_Values.length;i++){
		if(code_Values[i].type == "checkbox"){
			code_Values[i].checked = false;
		}
	}
}

/**
 * 根据checkbox名称获取所有的值并采用逗号分隔
 */
function checkbox(checkboxName){
	var str=checkboxName?document.getElementsByName(checkboxName):document.getElementsByTagName("input");
	var objarray=str.length;
	var chestr="";
	for (i=0;i<objarray;i++){
		if(str[i].checked == true){
			chestr+= i==0?str[i].value:","+str[i].value;
		}
	}
	chestr = chestr.substr(0, 1)==','?chestr.slice(1):chestr;
	chestr = chestr.substr(-1)==','?chestr.substring(0, chestr.length - 1):chestr;
	return chestr;
}


/**
 * 统计checkbox已选定的数量
 */
function checkbox_count(checkboxName){
	var str=checkboxName?document.getElementsByName(checkboxName):document.getElementsByTagName("input");
	var count = 0;
	var objarray=str.length;
	for (i=0;i<objarray;i++){
		if(str[i].checked == true){
			count++;
		}
	}
	return count;
}


/*
 * 获取date前一天的时间
 * 使用示例：
 * var yestoday = getYestoday(new Date());
 */
function getYestoday(date){	
	var yesterday_milliseconds=date.getTime()-1000*60*60*24;	 
	var yesterday = new Date();	 
		yesterday.setTime(yesterday_milliseconds);	 
	  
	var strYear = yesterday.getFullYear();  
	var strDay = yesterday.getDate();  
	var strMonth = yesterday.getMonth()+1;
	if(strMonth<10)  
	{  
		strMonth="0"+strMonth;  
	}  
	datastr = strYear+"-"+strMonth+"-"+strDay;
	return datastr;
}

//获得上个月在date这一天的日期
function getLastMonthYestdy(date){
	var daysInMonth = new Array([0],[31],[28],[31],[30],[31],[30],[31],[31],[30],[31],[30],[31]);
	var strYear = date.getFullYear();  
	var strDay = date.getDate();  
	var strMonth = date.getMonth()+1;
	if(strYear%4 == 0 && strYear%100 != 0){
		daysInMonth[2] = 29;
	}
	if(strMonth - 1 == 0){
		strYear -= 1;
		strMonth = 12;
	}else{
		strMonth -= 1;
	}
	strDay = daysInMonth[strMonth] >= strDay ? strDay : daysInMonth[strMonth];
	if(strMonth<10){
		strMonth="0"+strMonth;  
	}
	if(strDay<10){
		strDay="0"+strDay;  
	}
	datastr = strYear+"-"+strMonth+"-"+strDay;
	return datastr;
}

//获得上一年在date这一天的日期
function getLastYearYestdy(date){
	var strYear = date.getFullYear() - 1;  
	var strDay = date.getDate();  
	var strMonth = date.getMonth()+1;
	if(strMonth<10){
		strMonth="0"+strMonth;
	}
	if(strDay<10){
		strDay="0"+strDay;  
	}
	datastr = strYear+"-"+strMonth+"-"+strDay;
	return datastr;
}

function _loadingContent(display,tips){
	var img = 'images/loading1.gif';
	img = img.replace(eval('/@@/g'), '//');
	//tips = !tips?('<img src="/static/images/loading1.gif">'):tips;
	tips = !tips?('<img src="'+img+'">'):tips;
	if(jQuery('#_loadingContent').length > 0){
		jQuery('#_loadingContent').remove();
	}
	jQuery('body').append('<div id="_loadingContent" style="display:block;position:fixed;z-index:999999999;top:0px;left:0px;width:100%;height:100%;background:#ccc;filter:alpha(opacity=70);-moz-opacity:0.7;-khtml-opacity: 0.7;opacity: 0.7;padding:300px 0 0 0;text-align:center;">'+(display=='none'?'':tips)+'</div>');
	jQuery('#_loadingContent').css({'display':display});
	console.log(display);
}

//str:在这个字符串里面找    substr:要找的字符或字符串
function countSubstr(str,substr){
           var count;
           var reg="/"+substr+"/gi";    //查找时忽略大小写
           reg=eval(reg);
           if(str.match(reg)==null){
                   count=0;
           }else{
                   count=str.match(reg).length + 1;
           }
           return count;

           //返回找到的次数
}

pad_zero = function() {
	var tbl = [];
	return function(num, n) {
		var len = n-num.toString().length;
		if (len <= 0) return num;
		if (!tbl[len]) tbl[len] = (new Array(len+1)).join('0');
		return tbl[len] + num;
	}
}();

function formatSeconds(seconds) {
    var theTime = parseInt(seconds);// 秒
    var theTime1 = 0;// 分
    var theTime2 = 0;// 小时
    if(theTime > 60) {
        theTime1 = parseInt(theTime/60);
        theTime = parseInt(theTime%60);
		if(theTime1 > 60) {
			theTime2 = parseInt(theTime1/60);
			theTime1 = parseInt(theTime1%60);
		}
    }
	var result = ""+pad_zero(parseInt(theTime),2)+"";
	result = ""+pad_zero(parseInt(theTime1),2)+":"+result;
	result = ""+pad_zero(parseInt(theTime2),2)+":"+result;
    return result;
}


jQuery(document).ready(function(){
	//window.setInterval(documentComplete, 1000);
});

//页面所有资源加载完成，执行操作
function documentComplete(){
	if(document.readyState=='complete'){
		_loadingContent('none');
	}
}