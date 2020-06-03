/*
 * midoks 编写的js库，bug很多，后期有时间去http://locutus.io/php/ 对此文件内的php-js方法进行补全 
 */
(function(){
	//申明一个自定义对象
	var PHP = {};
	PHP.returnval=null;//返回值
/**-------------------------------------------
 *											*		
 *				数字处理					*
 *											*
 -------------------------------------------*/
	/**
	 *	@func 绝对值
	 *	@param int math 整数
	 */
	PHP.abs = function(math){return Math.abs(math)};

	/**
	 * @func pi值
	 */
	PHP.pi = Math.PI;

	/**
	 *	@func 对浮点数进行四舍五入
	 *	@param int math 整数
	 */
	PHP.round = function(math){return Math.round(math);};

	/**
	 *	@func 向上取整
	 *	@param int math 整数
	 */
	PHP.ceil = function(math){return Math.ceil(math);};

	/**
	 *	@func 向下取整
	 *	@param int math 整数
	 */
	PHP.floor = function(math){return Math.floor(math);};

	/**
	 *	rand 函数 产生随机数 默认产生0-1 之间的数 [ 0能得到 1不能得到 ]
	 *	@param int min 参数1为最小值
	 *	@param int max 参数2为最大值
	 *	@param int deep 精确度
	 *	在填写参数后,返回填写之间[并包括MAX和MIN]的正整数
	 *	没有填写参数,返回js的随机数
	 */
	PHP.rand = function(min,max,deep){
		if(typeof deep == 'undefined'){
			var deep=3;
		}
		if(arguments.length>1){
			var abs = max-min;//绝对值
			var r = Math.random();//随机数
			var s = abs * r;//随机的总数
			var v = s.toString();//标准比较 以0.5为隔
			var z = Math.pow(10,deep-1)*5;//以0.5为隔[表现的形式]
			if(v.substr(2,deep)>z){
				return min+Math.floor(s)+1;
			}else{
				return min+Math.floor(s);
			}	
		}else{return Math.random();}
	};

	/**
	 *	max 函数 找出最大值
	 *	@param int 整数
	 *	...可以是多个整数
	 *	@return int 最大值
	 */
	PHP.max = function(){
		var i = arguments;
		if(i.length==0){
			return false;
		}else if(i.length==1){
			return i[0];
		}
		var m = new Number;
		for(var p=0;p<i.length-1;p++){
			if(i[p]>i[p+1] && i[p]>m){
				m = i[p];
			}else{
				m = i[p+1]>m ? i[p+1] : m;
			}
		}
		return m;
	};

	/**
	 *	min 函数 找出最小值
	 *	@param int 整数
	 *	...可以是多个整数
	 *	@return int 最小值
	 */
	PHP.min = function(){
		var i = arguments,
			//定义一个临时变量
			m=arguments[0];
		if(i.length==0){
			return false;
		}else if(i.length==1){
			return false;
		}
		for(var p=0;p<i.length-1;p++){
			if(i[p]<i[p+1] && i[p]<m){
				m = i[p];
			}else{
				m = i[p+1]<m ? i[p+1] : m;
			}
		}
		return m;
	};

	//sin cos 等函数
	
	/**
	 *	sin 函数
	 *	@param int x 整数
	 *	return sin 值
	 */
	PHP.sin = function(x){return Math.sin(x);};

	/**
	 *	cos 函数
	 *	@param int x 整数
	 *	return cos 值
	 */
	PHP.cos = function(x){return Math.cos(x);};

	/**
	 *	tan 函数
	 *	@param int x 整数
	 *	return tan 值
	 */
	PHP.tan = function(x){return Math.tan(x);};

	/**
	 *	asin 函数
	 *	@param int x 整数
	 *	return tan 值
	 */
	PHP.asin = function(x){return Math.asin(x);};

	/**
	 *	acos 函数
	 *	@param int x 整数
	 *	return acos 值
	 */
	PHP.acos = function(x){return Math.acos(x);};

	/**
	 *	atan 函数
	 *	@param int x 整数
	 *	return atan 值
	 */
	PHP.atan = function(x){return Math.atan(x);};

	/**
	 *	atan2 函数 两个参数的反正切
	 *	@param number x 整数
	 *  @param number y 整数
	 *	return atan2 值
	 */
	PHP.atan2 = function(x,y){return Math.atan2(x,y);};

	//科学计数
	
	/* E 常量 */
	PHP.E = Math.E;

	/**
	 *	log 函数 自然对数
	 *	@param int float 数字
	 *	return 自然对数
	 */
	PHP.log = function(x){return Math.log(x);};

	/**
	 *	pow 指数表达式
	 *	@param number 基数
	 *	@param number 表达式
	 *	@return number
	 */	
	PHP.pow = function(base,exp){return Math.pow(base,exp);};

	/**
	 *	exp 计算E的指数
	 *	@param number 多少次方
	 *	@return E的x次方
	 */	
	PHP.exp = function(x){return Math.exp(x);};

	/**
	 *	sqrt 开平方根
	 *	@param number arg
	 *	@return number
	 */
	PHP.sqrt = function(x){return Math.sqrt(x);};

/**-------------------------------------------
 *											*
 *				时间函数处理				*
 *											*
 -------------------------------------------*/
	/**
	 *	返回当前的 Unix 时间戳
	 *	精确到秒级
	 */
	var timedel = new Date;//时间函数声明
	PHP.time = function(){
		var t = timedel.getTime().toString();
		return t.substr(0,10);
	};

	/**
	 *	返回当前的 Unix 时间戳 和 微秒数
	 */
	PHP.microtime = function(){
		var t = timedel.getTime().toString();
		return [t.substr(0,10),t.substr(10,3)];
	};
	
	PHP.date = function (format, timestamp) {
	  //  discuss at: http://locutus.io/php/date/
	  // original by: Carlos R. L. Rodrigues (http://www.jsfromhell.com)
	  // original by: gettimeofday
	  //    parts by: Peter-Paul Koch (http://www.quirksmode.org/js/beat.html)
	  // improved by: Kevin van Zonneveld (http://kvz.io)
	  // improved by: MeEtc (http://yass.meetcweb.com)
	  // improved by: Brad Touesnard
	  // improved by: Tim Wiel
	  // improved by: Bryan Elliott
	  // improved by: David Randall
	  // improved by: Theriault (https://github.com/Theriault)
	  // improved by: Theriault (https://github.com/Theriault)
	  // improved by: Brett Zamir (http://brett-zamir.me)
	  // improved by: Theriault (https://github.com/Theriault)
	  // improved by: Thomas Beaucourt (http://www.webapp.fr)
	  // improved by: JT
	  // improved by: Theriault (https://github.com/Theriault)
	  // improved by: Rafał Kukawski (http://blog.kukawski.pl)
	  // improved by: Theriault (https://github.com/Theriault)
	  //    input by: Brett Zamir (http://brett-zamir.me)
	  //    input by: majak
	  //    input by: Alex
	  //    input by: Martin
	  //    input by: Alex Wilson
	  //    input by: Haravikk
	  // bugfixed by: Kevin van Zonneveld (http://kvz.io)
	  // bugfixed by: majak
	  // bugfixed by: Kevin van Zonneveld (http://kvz.io)
	  // bugfixed by: Brett Zamir (http://brett-zamir.me)
	  // bugfixed by: omid (http://locutus.io/php/380:380#comment_137122)
	  // bugfixed by: Chris (http://www.devotis.nl/)
	  //      note 1: Uses global: locutus to store the default timezone
	  //      note 1: Although the function potentially allows timezone info
	  //      note 1: (see notes), it currently does not set
	  //      note 1: per a timezone specified by date_default_timezone_set(). Implementers might use
	  //      note 1: $locutus.currentTimezoneOffset and
	  //      note 1: $locutus.currentTimezoneDST set by that function
	  //      note 1: in order to adjust the dates in this function
	  //      note 1: (or our other date functions!) accordingly
	  //   example 1: date('H:m:s \\m \\i\\s \\m\\o\\n\\t\\h', 1062402400)
	  //   returns 1: '07:09:40 m is month'
	  //   example 2: date('F j, Y, g:i a', 1062462400)
	  //   returns 2: 'September 2, 2003, 12:26 am'
	  //   example 3: date('Y W o', 1062462400)
	  //   returns 3: '2003 36 2003'
	  //   example 4: var $x = date('Y m d', (new Date()).getTime() / 1000)
	  //   example 4: $x = $x + ''
	  //   example 4: var $result = $x.length // 2009 01 09
	  //   returns 4: 10
	  //   example 5: date('W', 1104534000)
	  //   returns 5: '52'
	  //   example 6: date('B t', 1104534000)
	  //   returns 6: '999 31'
	  //   example 7: date('W U', 1293750000.82); // 2010-12-31
	  //   returns 7: '52 1293750000'
	  //   example 8: date('W', 1293836400); // 2011-01-01
	  //   returns 8: '52'
	  //   example 9: date('W Y-m-d', 1293974054); // 2011-01-02
	  //   returns 9: '52 2011-01-02'
	  //        test: skip-1 skip-2 skip-5
	  var jsdate, f
	  // Keep this here (works, but for code commented-out below for file size reasons)
	  // var tal= [];
	  var txtWords = [
	    'Sun', 'Mon', 'Tues', 'Wednes', 'Thurs', 'Fri', 'Satur',
	    'January', 'February', 'March', 'April', 'May', 'June',
	    'July', 'August', 'September', 'October', 'November', 'December'
	  ]
	  // trailing backslash -> (dropped)
	  // a backslash followed by any character (including backslash) -> the character
	  // empty string -> empty string
	  var formatChr = /\\?(.?)/gi
	  var formatChrCb = function (t, s) {
	    return f[t] ? f[t]() : s
	  }
	  var _pad = function (n, c) {
	    n = String(n)
	    while (n.length < c) {
	      n = '0' + n
	    }
	    return n
	  }
	  f = {
	    // Day
	    d: function () {
	      // Day of month w/leading 0; 01..31
	      return _pad(f.j(), 2)
	    },
	    D: function () {
	      // Shorthand day name; Mon...Sun
	      return f.l()
	        .slice(0, 3)
	    },
	    j: function () {
	      // Day of month; 1..31
	      return jsdate.getDate()
	    },
	    l: function () {
	      // Full day name; Monday...Sunday
	      return txtWords[f.w()] + 'day'
	    },
	    N: function () {
	      // ISO-8601 day of week; 1[Mon]..7[Sun]
	      return f.w() || 7
	    },
	    S: function () {
	      // Ordinal suffix for day of month; st, nd, rd, th
	      var j = f.j()
	      var i = j % 10
	      if (i <= 3 && parseInt((j % 100) / 10, 10) === 1) {
	        i = 0
	      }
	      return ['st', 'nd', 'rd'][i - 1] || 'th'
	    },
	    w: function () {
	      // Day of week; 0[Sun]..6[Sat]
	      return jsdate.getDay()
	    },
	    z: function () {
	      // Day of year; 0..365
	      var a = new Date(f.Y(), f.n() - 1, f.j())
	      var b = new Date(f.Y(), 0, 1)
	      return Math.round((a - b) / 864e5)
	    },
	    // Week
	    W: function () {
	      // ISO-8601 week number
	      var a = new Date(f.Y(), f.n() - 1, f.j() - f.N() + 3)
	      var b = new Date(a.getFullYear(), 0, 4)
	      return _pad(1 + Math.round((a - b) / 864e5 / 7), 2)
	    },
	    // Month
	    F: function () {
	      // Full month name; January...December
	      return txtWords[6 + f.n()]
	    },
	    m: function () {
	      // Month w/leading 0; 01...12
	      return _pad(f.n(), 2)
	    },
	    M: function () {
	      // Shorthand month name; Jan...Dec
	      return f.F()
	        .slice(0, 3)
	    },
	    n: function () {
	      // Month; 1...12
	      return jsdate.getMonth() + 1
	    },
	    t: function () {
	      // Days in month; 28...31
	      return (new Date(f.Y(), f.n(), 0))
	        .getDate()
	    },
	    // Year
	    L: function () {
	      // Is leap year?; 0 or 1
	      var j = f.Y()
	      return j % 4 === 0 & j % 100 !== 0 | j % 400 === 0
	    },
	    o: function () {
	      // ISO-8601 year
	      var n = f.n()
	      var W = f.W()
	      var Y = f.Y()
	      return Y + (n === 12 && W < 9 ? 1 : n === 1 && W > 9 ? -1 : 0)
	    },
	    Y: function () {
	      // Full year; e.g. 1980...2010
	      return jsdate.getFullYear()
	    },
	    y: function () {
	      // Last two digits of year; 00...99
	      return f.Y()
	        .toString()
	        .slice(-2)
	    },
	    // Time
	    a: function () {
	      // am or pm
	      return jsdate.getHours() > 11 ? 'pm' : 'am'
	    },
	    A: function () {
	      // AM or PM
	      return f.a()
	        .toUpperCase()
	    },
	    B: function () {
	      // Swatch Internet time; 000..999
	      var H = jsdate.getUTCHours() * 36e2
	      // Hours
	      var i = jsdate.getUTCMinutes() * 60
	      // Minutes
	      // Seconds
	      var s = jsdate.getUTCSeconds()
	      return _pad(Math.floor((H + i + s + 36e2) / 86.4) % 1e3, 3)
	    },
	    g: function () {
	      // 12-Hours; 1..12
	      return f.G() % 12 || 12
	    },
	    G: function () {
	      // 24-Hours; 0..23
	      return jsdate.getHours()
	    },
	    h: function () {
	      // 12-Hours w/leading 0; 01..12
	      return _pad(f.g(), 2)
	    },
	    H: function () {
	      // 24-Hours w/leading 0; 00..23
	      return _pad(f.G(), 2)
	    },
	    i: function () {
	      // Minutes w/leading 0; 00..59
	      return _pad(jsdate.getMinutes(), 2)
	    },
	    s: function () {
	      // Seconds w/leading 0; 00..59
	      return _pad(jsdate.getSeconds(), 2)
	    },
	    u: function () {
	      // Microseconds; 000000-999000
	      return _pad(jsdate.getMilliseconds() * 1000, 6)
	    },
	    // Timezone
	    e: function () {
	      // Timezone identifier; e.g. Atlantic/Azores, ...
	      // The following works, but requires inclusion of the very large
	      // timezone_abbreviations_list() function.
	      /*              return that.date_default_timezone_get();
	       */
	      var msg = 'Not supported (see source code of date() for timezone on how to add support)'
	      throw new Error(msg)
	    },
	    I: function () {
	      // DST observed?; 0 or 1
	      // Compares Jan 1 minus Jan 1 UTC to Jul 1 minus Jul 1 UTC.
	      // If they are not equal, then DST is observed.
	      var a = new Date(f.Y(), 0)
	      // Jan 1
	      var c = Date.UTC(f.Y(), 0)
	      // Jan 1 UTC
	      var b = new Date(f.Y(), 6)
	      // Jul 1
	      // Jul 1 UTC
	      var d = Date.UTC(f.Y(), 6)
	      return ((a - c) !== (b - d)) ? 1 : 0
	    },
	    O: function () {
	      // Difference to GMT in hour format; e.g. +0200
	      var tzo = jsdate.getTimezoneOffset()
	      var a = Math.abs(tzo)
	      return (tzo > 0 ? '-' : '+') + _pad(Math.floor(a / 60) * 100 + a % 60, 4)
	    },
	    P: function () {
	      // Difference to GMT w/colon; e.g. +02:00
	      var O = f.O()
	      return (O.substr(0, 3) + ':' + O.substr(3, 2))
	    },
	    T: function () {
	      // The following works, but requires inclusion of the very
	      // large timezone_abbreviations_list() function.
	      /*              var abbr, i, os, _default;
	      if (!tal.length) {
	        tal = that.timezone_abbreviations_list();
	      }
	      if ($locutus && $locutus.default_timezone) {
	        _default = $locutus.default_timezone;
	        for (abbr in tal) {
	          for (i = 0; i < tal[abbr].length; i++) {
	            if (tal[abbr][i].timezone_id === _default) {
	              return abbr.toUpperCase();
	            }
	          }
	        }
	      }
	      for (abbr in tal) {
	        for (i = 0; i < tal[abbr].length; i++) {
	          os = -jsdate.getTimezoneOffset() * 60;
	          if (tal[abbr][i].offset === os) {
	            return abbr.toUpperCase();
	          }
	        }
	      }
	      */
	      return 'UTC'
	    },
	    Z: function () {
	      // Timezone offset in seconds (-43200...50400)
	      return -jsdate.getTimezoneOffset() * 60
	    },
	    // Full Date/Time
	    c: function () {
	      // ISO-8601 date.
	      return 'Y-m-d\\TH:i:sP'.replace(formatChr, formatChrCb)
	    },
	    r: function () {
	      // RFC 2822
	      return 'D, d M Y H:i:s O'.replace(formatChr, formatChrCb)
	    },
	    U: function () {
	      // Seconds since UNIX epoch
	      return jsdate / 1000 | 0
	    }
	  }
	  var _date = function (format, timestamp) {
	    jsdate = (timestamp === undefined ? new Date() // Not provided
	      : (timestamp instanceof Date) ? new Date(timestamp) // JS Date()
	      : new Date(timestamp * 1000) // UNIX timestamp (auto-convert to int)
	    )
	    return format.replace(formatChr, formatChrCb)
	  }
	  return _date(format, timestamp)
	};

	/**------------------------------------------
	 *											*
	 *				字符串处理					*
	 *											*
	 -------------------------------------------*/
	
	PHP.sprintf = function () {
	  //  discuss at: http://locutus.io/php/sprintf/
	  // original by: Ash Searle (http://hexmen.com/blog/)
	  // improved by: Michael White (http://getsprink.com)
	  // improved by: Jack
	  // improved by: Kevin van Zonneveld (http://kvz.io)
	  // improved by: Kevin van Zonneveld (http://kvz.io)
	  // improved by: Kevin van Zonneveld (http://kvz.io)
	  // improved by: Dj
	  // improved by: Allidylls
	  //    input by: Paulo Freitas
	  //    input by: Brett Zamir (http://brett-zamir.me)
	  // improved by: Rafał Kukawski (http://kukawski.pl)
	  //   example 1: sprintf("%01.2f", 123.1)
	  //   returns 1: '123.10'
	  //   example 2: sprintf("[%10s]", 'monkey')
	  //   returns 2: '[    monkey]'
	  //   example 3: sprintf("[%'#10s]", 'monkey')
	  //   returns 3: '[####monkey]'
	  //   example 4: sprintf("%d", 123456789012345)
	  //   returns 4: '123456789012345'
	  //   example 5: sprintf('%-03s', 'E')
	  //   returns 5: 'E00'
	  //   example 6: sprintf('%+010d', 9)
	  //   returns 6: '+000000009'
	  //   example 7: sprintf('%+0\'@10d', 9)
	  //   returns 7: '@@@@@@@@+9'
	  //   example 8: sprintf('%.f', 3.14)
	  //   returns 8: '3.140000'
	  //   example 9: sprintf('%% %2$d', 1, 2)
	  //   returns 9: '% 2'
	
	  var regex = /%%|%(?:(\d+)\$)?((?:[-+#0 ]|'[\s\S])*)(\d+)?(?:\.(\d*))?([\s\S])/g
	  var args = arguments
	  var i = 0
	  var format = args[i++]
	
	  var _pad = function (str, len, chr, leftJustify) {
	    if (!chr) {
	      chr = ' '
	    }
	    var padding = (str.length >= len) ? '' : new Array(1 + len - str.length >>> 0).join(chr)
	    return leftJustify ? str + padding : padding + str
	  }
	
	  var justify = function (value, prefix, leftJustify, minWidth, padChar) {
	    var diff = minWidth - value.length
	    if (diff > 0) {
	      // when padding with zeros
	      // on the left side
	      // keep sign (+ or -) in front
	      if (!leftJustify && padChar === '0') {
	        value = [
	          value.slice(0, prefix.length),
	          _pad('', diff, '0', true),
	          value.slice(prefix.length)
	        ].join('')
	      } else {
	        value = _pad(value, minWidth, padChar, leftJustify)
	      }
	    }
	    return value
	  }
	
	  var _formatBaseX = function (value, base, leftJustify, minWidth, precision, padChar) {
	    // Note: casts negative numbers to positive ones
	    var number = value >>> 0
	    value = _pad(number.toString(base), precision || 0, '0', false)
	    return justify(value, '', leftJustify, minWidth, padChar)
	  }
	
	  // _formatString()
	  var _formatString = function (value, leftJustify, minWidth, precision, customPadChar) {
	    if (precision !== null && precision !== undefined) {
	      value = value.slice(0, precision)
	    }
	    return justify(value, '', leftJustify, minWidth, customPadChar)
	  }
	
	  // doFormat()
	  var doFormat = function (substring, argIndex, modifiers, minWidth, precision, specifier) {
	    var number, prefix, method, textTransform, value
	
	    if (substring === '%%') {
	      return '%'
	    }
	
	    // parse modifiers
	    var padChar = ' ' // pad with spaces by default
	    var leftJustify = false
	    var positiveNumberPrefix = ''
	    var j, l
	
	    for (j = 0, l = modifiers.length; j < l; j++) {
	      switch (modifiers.charAt(j)) {
	        case ' ':
	        case '0':
	          padChar = modifiers.charAt(j)
	          break
	        case '+':
	          positiveNumberPrefix = '+'
	          break
	        case '-':
	          leftJustify = true
	          break
	        case "'":
	          if (j + 1 < l) {
	            padChar = modifiers.charAt(j + 1)
	            j++
	          }
	          break
	      }
	    }
	
	    if (!minWidth) {
	      minWidth = 0
	    } else {
	      minWidth = +minWidth
	    }
	
	    if (!isFinite(minWidth)) {
	      throw new Error('Width must be finite')
	    }
	
	    if (!precision) {
	      precision = (specifier === 'd') ? 0 : 'fFeE'.indexOf(specifier) > -1 ? 6 : undefined
	    } else {
	      precision = +precision
	    }
	
	    if (argIndex && +argIndex === 0) {
	      throw new Error('Argument number must be greater than zero')
	    }
	
	    if (argIndex && +argIndex >= args.length) {
	      throw new Error('Too few arguments')
	    }
	
	    value = argIndex ? args[+argIndex] : args[i++]
	
	    switch (specifier) {
	      case '%':
	        return '%'
	      case 's':
	        return _formatString(value + '', leftJustify, minWidth, precision, padChar)
	      case 'c':
	        return _formatString(String.fromCharCode(+value), leftJustify, minWidth, precision, padChar)
	      case 'b':
	        return _formatBaseX(value, 2, leftJustify, minWidth, precision, padChar)
	      case 'o':
	        return _formatBaseX(value, 8, leftJustify, minWidth, precision, padChar)
	      case 'x':
	        return _formatBaseX(value, 16, leftJustify, minWidth, precision, padChar)
	      case 'X':
	        return _formatBaseX(value, 16, leftJustify, minWidth, precision, padChar)
	          .toUpperCase()
	      case 'u':
	        return _formatBaseX(value, 10, leftJustify, minWidth, precision, padChar)
	      case 'i':
	      case 'd':
	        number = +value || 0
	        // Plain Math.round doesn't just truncate
	        number = Math.round(number - number % 1)
	        prefix = number < 0 ? '-' : positiveNumberPrefix
	        value = prefix + _pad(String(Math.abs(number)), precision, '0', false)
	
	        if (leftJustify && padChar === '0') {
	          // can't right-pad 0s on integers
	          padChar = ' '
	        }
	        return justify(value, prefix, leftJustify, minWidth, padChar)
	      case 'e':
	      case 'E':
	      case 'f': // @todo: Should handle locales (as per setlocale)
	      case 'F':
	      case 'g':
	      case 'G':
	        number = +value
	        prefix = number < 0 ? '-' : positiveNumberPrefix
	        method = ['toExponential', 'toFixed', 'toPrecision']['efg'.indexOf(specifier.toLowerCase())]
	        textTransform = ['toString', 'toUpperCase']['eEfFgG'.indexOf(specifier) % 2]
	        value = prefix + Math.abs(number)[method](precision)
	        return justify(value, prefix, leftJustify, minWidth, padChar)[textTransform]()
	      default:
	        // unknown specifier, consume that char and return empty
	        return ''
	    }
	  }
	
	  try {
	    return format.replace(regex, doFormat)
	  } catch (err) {
	    return false
	  }
	};

	/**
	 *	把不是字符串类型的,转换成字符串类型
	 *	@param string str 字符串
	 *	@param string
	 */
	function php_str(str){
		if(typeof(str)!='string'){
			return str = str.toString();
		}else{
			return str;
		}
	};
	
	/**
	 * @func 截取字符串
	 * @param string str
	 * @param int start 开始位置
	 * @param int len 截取长度
	 */
	PHP.substr = function(str,start,len){
		var str = php_str(str);
		return str.substr(start,len);
	}

	/**
 	 *	字符串翻转
 	 *	@param string str 字符串
     *	@return string
  	 */
	PHP.strrev = function(str){
		var str = php_str(str);
		var len = str.length-1;
		var t='',i;
		for(i=len;i>=0;i--){t += str[i];}
		return t;
	};

	/**
	 *	字符串长度
	 *	@param string str 字符串
	 *	@return num
	 */
	PHP.strlen = function(str){
		var str = php_str(str);
		return str.length;
	};

	/**
	 *	字符串大写
	 *	@param string str 字符串
	 *	@return string
	 */
	PHP.strtoupper = function(str){
		var str = php_str(str);
		return str.toUpperCase();
	};

	/**
	 *	字符串小写
	 *	@param string str 字符串
	 *	@return string
	 */
	PHP.strtolower = function(str){
		var str = php_str(str);
		return str.toLowerCase();
	};
	
	/**
	 *	str_repeat函数重复一个字符串
	 *	@param string $str 字符串
	 *	@param int $num 重复的次数
	 */
	PHP.str_repeat = function(str,num){
		if(num<0){return '';
		}else{
			/*var t='';
			for(var i=0;i<=num;i++){
				t += str;
			}下面为优化的*/
			var t = new Array();
			for(var i=0;i<num;i++){
				t[i]=str;
			}
			return t.join('');
		}
	};

	/**
	 *	strpos 函数 查找字符串首次出现的位置
	 *	@param string str 字符串
	 *	@param string find 查询的字符
	 *	@return int 返回查找字符串首次出现位置
	 */
	PHP.strpos = PHP.strstr = PHP.strchr = function(str,find){
		return str.indexOf(find);
	}

	/**
	 *	strrpos 函数 查找字符串最后出现的位置
	 *	@param string str 字符串
	 *	@param string find 查询的字符
	 *	@return int 返回查找字符串首次出现位置
	 */
	PHP.strrpos = function(str,find){
		if(find.length>1){
			find = find.substr(find.length-1,1);
		}
		return str.lastIndexOf(find);
	}

	/**
	 *	字符串替换
	 *	@param mixed search 字符串或数组
	 *	@param mixed replace 字符串或数组
	 *	@param mixed subject 字符串或数组
	 *	@param int count 替换的次数   ---暂时未实现
	 *	@return string 返回字符串
	 */
	PHP.str_replace = function(search,replace,subject){
		str = php_str(subject);
		return str.replace(search,replace);
	}

	/**
	 *	将字符串转化为数组
	 *	@param string str 字符串
	 *	@param int num 每一段的长度
	 *	@return array 返回数组
	 */
	PHP.str_split = function(str,num){
		if(typeof num != 'number'){var num=1;}
		var arr = new Array;
		for(var i=0,p=0;i<str.length;i+=num){
			arr[p] = str.substr(i,num);p++;
		}
		return arr;
	}
	
	/**
	 *	@func 随机将字符串打乱
	 *	@param string str 字符串
	 *	@return string 返回打乱的字符串
	 */
	PHP.str_shuffle = function(str){
		var arr = new Array;//创建数组
		arr = str.split('');
		var rd = function(min,max){
			var deep=3;
			var abs = max-min;//绝对值
			var r = Math.random();//随机数
			var s = abs * r;//随机的总数
			var v = s.toString();//标准比较 以0.5为隔
			var z = Math.pow(10,deep-1)*5;//以0.5为隔[表现的形式]
			if(v.substr(2,deep)>z){return min+Math.floor(s)+1;}else{
				return min+Math.floor(s);
			}	
		};var t='';//返回的值,也是临时值
		for(var i=0;i<arr.length;){
			if(arr.length==1){ return t+=arr[0];}
			var rdom = rd(0,arr.length-1);//取随机数
			t +=arr.splice(rdom,1);
		}return t;
	}

	/**
	 * str_pad — 使用另一个字符串填充字符串为指定长度
	 * @param string input 输入的字符串
	 * @param int string 指定长度
	 * @param string pad_str 填充的字符串 默认为空格
	 * @param int type 填充的方式 0:right(默认) 1:left 2:both 
	 * 	如果是选择2,指定的长度减input的长度为奇数,会舍去小数的。
	 * @return string 放回填充后的字符串
	 */
	PHP.str_pad = function(input,length,pad_str,type){
		var str = php_str(input);
		if( typeof length ==='undefined' || length<=str.length){
			return str;
		}else{	
			var len = length-str.length;
			if(len<=0) return str;
			var n = new Array();
			pad_str = typeof pad_str === 'undefined' ? ' ' : pad_str;
			for(var i=0;i<len;i++){n[i] = pad_str;}
			if(typeof type === 'undefined'){
				t = n.join('').substr(0,len);return str + t;
			}
			//所有的参数都填写的情况下
			t = n.join('').substr(0,len);//console.log(t);
			switch(type){
				case 0: return str + t;break;
				case 1: return t+str;break;
				case 2: z = n.join('').substr(0,len/2);return z+str+z;break;
				default:return str;break;
			}
		}
	};

	/**
	 *	trim 函数 取出首尾的空格字符和其它一些字符
	 *	@param string str 字符串
	 *	@param string charlist 首尾要除去的字符
	 *	@return string 返回除去后的字符串
	 */
	PHP.trim = function(str,charlist){
		if(typeof charlist==='undefined'){
			charlist = /^[\s\r\n\t\0\x0B]*(.*[^\s\r\n\t\0\x0B])?[\s\r\n\t\0\x0B]*/ig;
			return str.replace(charlist,"$1");
		}else{
			charlist = '/['+charlist+']*(.*[^'+ charlist +'])?['+charlist+']*/ig';
			charlist = eval(charlist);
			return str.replace(charlist,"$1");
		}
	};

	/**
	 *	ord函数 返回字符串 string 第一个字符的 ASCII 码值
	 *	@param string str 字符
	 *	@return int	 ASCII 码值
	 */
	PHP.ord = function(str){
		var str = php_str(str);
		return str.charCodeAt(0);
	};

	/**
	 * chr函数,返回指定的字符
	 * @param int num 整数	'78,79,9';
	 * @return char 返回字符
	 */
	PHP.chr = function(num){
		var type = typeof num;
		if( type ==='number' ){
			return String.fromCharCode(num);
		}else if(type == 'string'){
			arr = num.split(',');
			for(var i=0;i<arr.length;i++){
				arr[i] = String.fromCharCode(arr[i]);
			}
			return arr.join('');
		}
	};

	/**	
	 *	bin2hex 将二进制数据转换成十六进制表示
	 *	@param mixed str 字符串 | 数字
	 *	@return 返回ASCII字符串,为参数str的十六进制表示.
	 *	note:计算位数问题:php跟服务器环境有关,javascript则是固定64位
	 */
	PHP.bin2hex = function(str){	
		if(typeof str !='number'){
			var t = new Array;
			for(var i=0;i<str.length;i++){
				var e=str.charCodeAt(i);
				t[i] = e.toString(16);
			}
			return t.join('');
		}else{
			return str.toString(16);
		}
	}

	/**
	 *	wordwrap 打断字符串为指定数量的字符
	 *	@param string str 字符串
	 *	@param int width 以多少字符为打断 默认宽度1
	 *	@param string sep 分隔符 默认 '\n'
	 *	@param boolean cut 0 默认 字符串 1 数组
	 *	@return string 字符串
	 */
	PHP.wordwrap = function(str,width,sep,cut){
		var s = str.length;//保存字符的长度
		if(typeof width=='undefined'){width=1;}
		if(typeof sep =='undefined'){sep ='\n';}
		if(typeof cut=='undefined'){cut=0;}	
		if(cut==0){
			var string = '';
			for(var i=0;i<s;i+=width){
				if((i+width)>s){
					string += str.substring(i,i+width);
					return string;
				}
				string += str.substring(i,i+width) + sep;
			}
		}else{
			var string = new Array();
			var t=0;
			for(var i=0;i<s;i+=width){
				if((i+width)>s){
					string [t]= str.substring(i,i+width);
					return string;
				}
				string [t]= str.substring(i,i+width);
				t++;
			}
		}
		return string;
	}

	//关于正则配置
	/**
	 *	preg_match 函数 匹配一次就会停止匹配了。
	 *	@param string match 匹配表达式
	 *	@param string subject 对象
	 *	@return array 返回匹配的数组
	 */
	PHP.preg_match = function(match,subject,arr){
		var t = subject.match(eval(match));
		if(typeof arr =='function'){arr(t);}
		return t;
	}

	/**
	 *	preg_match_all 函数 preg_match 也能实现,主要有人习惯了.
	 *	@param string match 匹配表达式
	 *	@param string subject 对象
	 *	@param array 返回的数据,可操作
	 *	@return array 返回匹配的数组
	 */
	PHP.preg_match_all = function(match,subject,arr){
		t = subject.match(eval('/'+match+'/g'));
		if(typeof arr =='function'){arr(t);}
		return t;
	}

	/**
	 *	preg_replace 执行一个正则表达式的搜索和替换
	 *	@param mixed pattern 正则表达式
	 *	@param mixed replacement 替换
	 *	@param mixed subject 对象
	 *	[@param function c 回调操作] 
	 *	@return 如果subject是数组则返回数组,其他返回字符串
	 */
	PHP.preg_replace = function(pattern,replacement,subject,c){
		pt = eval('/'+pattern+'/g');//正则
		if(typeof subject == 'object'){
			var sarr = new Array;
			for(var i=0;i<subject.length;i++){
				sarr[i] = subject[i].replace(pt,replacement);
			}	
			if(typeof c == 'function'){c(sarr);}
			return sarr;
		}else if(typeof subject == 'string'){
			p = subject.replace(pt,replacement);
			if(typeof c == 'function'){c(p);}
			return p;
		}
		
	}



/**------------------------------------------
 *											*
 *				数组处理					*
 *											*
 -------------------------------------------*/

	/**
	 *	implode函数
	 *	@param separator c 连接符
	 *	返回字符串值，其中包含了连接到一起的数组的所有元素，元素由指定的分隔符分隔开来。
	 *	@param array a 数组
	 *	return string 
	 */
	PHP.implode = function(glue, pieces) {
  //  discuss at: https://locutus.io/php/implode/
  // original by: Kevin van Zonneveld (https://kvz.io)
  // improved by: Waldo Malqui Silva (https://waldo.malqui.info)
  // improved by: Itsacon (https://www.itsacon.net/)
  // bugfixed by: Brett Zamir (https://brett-zamir.me)
  //   example 1: implode(' ', ['Kevin', 'van', 'Zonneveld'])
  //   returns 1: 'Kevin van Zonneveld'
  //   example 2: implode(' ', {first:'Kevin', last: 'van Zonneveld'})
  //   returns 2: 'Kevin van Zonneveld'

  var i = ''
  var retVal = ''
  var tGlue = ''

  if (arguments.length === 1) {
    pieces = glue
    glue = ''
  }

  if (typeof pieces === 'object') {
    if (Object.prototype.toString.call(pieces) === '[object Array]') {
      return pieces.join(glue)
    }
    for (i in pieces) {
      retVal += tGlue + pieces[i]
      tGlue = glue
    }
    return retVal
  }

  return pieces
};

	/**
	 *	explode 函数
	 *  使用一个字符串分割另一个字符串
	 *  @param string separator 分割符
	 *  @param string str 字符串
	 *  @param int limit 分割的次数
	 *  @return array 返回数组
	 */
	PHP.explode = function(delimiter, string, limit) {
  //  discuss at: https://locutus.io/php/explode/
  // original by: Kevin van Zonneveld (https://kvz.io)
  //   example 1: explode(' ', 'Kevin van Zonneveld')
  //   returns 1: [ 'Kevin', 'van', 'Zonneveld' ]

  if (arguments.length < 2 ||
    typeof delimiter === 'undefined' ||
    typeof string === 'undefined') {
    return null
  }
  if (delimiter === '' ||
    delimiter === false ||
    delimiter === null) {
    return false
  }
  if (typeof delimiter === 'function' ||
    typeof delimiter === 'object' ||
    typeof string === 'function' ||
    typeof string === 'object') {
    return {
      0: ''
    }
  }
  if (delimiter === true) {
    delimiter = '1'
  }

  // Here we go...
  delimiter += ''
  string += ''

  var s = string.split(delimiter)

  if (typeof limit === 'undefined') return s

  // Support for limit
  if (limit === 0) limit = 1

  // Positive limit
  if (limit > 0) {
    if (limit >= s.length) {
      return s
    }
    return s
      .slice(0, limit - 1)
      .concat([s.slice(limit - 1)
        .join(delimiter)
      ])
  }

  // Negative limit
  if (-limit >= s.length) {
    return []
  }

  s.splice(s.length + limit)
  return s
};
	/**
	 *	array_reverse 函数
	 *	返回一个单元顺序相反的数组
	 *	@param &array arr
	 */
	PHP.array_reverse = function(arr){
		if(typeof arr =='object' || typeof arr =='array'){
			return arr.reverse();
		}else{
			return arr;
		}
	}
	
	/**
	 *	array_shift 函数
	 *	将数组开头的单元移出数组
	 *	@param &array arr 数组
	 *	@return array 插入的
	 */
	PHP.array_shift = function(arr){
		if(typeof arr =='object' || typeof arr =='array'){
			return arr.shift();	
		}else{
			return arr;
		}
	}

	/**
	 * array_unshift 函数
	 * 将数组开头插入一个或对个单元
	 * @param &array arr 数组
	 * @param array carr 插入单元
	 * @return array 返回插入的单元
	 */
	PHP.array_unshift = function(arr,carr){
		if(arguments.length>2){//如果大于2,说明多个元素压入
			var r = new Array;
			for(var i=1;i<arguments.length;i++){	
				r[i-1] = arguments[i];
			}
		}
		if(typeof arr =='object' || typeof arr == 'array'){
			if(typeof r =='object' || typeof r == 'array'){
				for(var i=0;i<r.length;i++){
					if(i==(r.length-1)){	
						return arr.unshift(r[i]);
					}
					arr.unshift(r[i]);				
				}
				return arr;
			}
			return arr.unshift(carr);
		}else{
			return arr;
		}
	
	}

	/**
	 * array_pop 函数 
	 * 将最后一个元素弹出
	 * @param array arr 数组
	 * @return 返回弹出的元素
	 */
	PHP.array_pop = function(arr){
		if(typeof arr =='object' || typeof arr =='array'){
			return arr.pop();
		}else{
			return arr;
		}
	};

	/** 
	 * array_push 函数
	 * 将一个或对个值,压入末尾
	 * @param array arr 数组
	 * @param mixed e 一个或对个元素
	 * @return 返回压入后的元素
	 */
	PHP.array_push = function(arr,e){
		if(arguments.length>2){//如果大于2,说明多个元素压入
			var r = new Array;
			for(var i=1;i<arguments.length;i++){	
				r[i-1] = arguments[i];
			}
		}
		if(typeof arr =='object' || typeof arr == 'array'){
			if(typeof r =='object' || typeof r == 'array'){
				for(var i=0;i<r.length;i++){
					if(i==(r.length-1)){	
						return arr.push(r[i]);
					}
					arr.push(r[i]);				
				}
				return arr;
			}
			return arr.push(e);
		}else{
			return arr;
		}
	}

	/**
	 *	array_merge  合并一个或多个数组
	 *	如果不是数组类型 不返回任何值
	 *	@param array 第一个数组
	 *	@param array 第二个数据
	 *	@return array 返回的数据
	 */
	PHP.array_merge = function(){
		var arr = new Array;
		var timer = 0;
		for(var i=0;i<arguments.length;++i){
			for(var j=0;j<arguments[i].length;++j){
				arr[timer] = arguments[i][j];
				++timer;
			}
		}
		return arr;
	};
	
PHP.array_column = function (input, ColumnKey, IndexKey = null) { // eslint-disable-line camelcase
  //   discuss at: https://locutus.io/php/array_column/
  //   original by: Enzo Dañobeytía
  //   example 1: array_column([{name: 'Alex', value: 1}, {name: 'Elvis', value: 2}, {name: 'Michael', value: 3}], 'name')
  //   returns 1: {0: "Alex", 1: "Elvis", 2: "Michael"}
  //   example 2: array_column({0: {name: 'Alex', value: 1}, 1: {name: 'Elvis', value: 2}, 2: {name: 'Michael', value: 3}}, 'name')
  //   returns 2: {0: "Alex", 1: "Elvis", 2: "Michael"}
  //   example 3: array_column([{name: 'Alex', value: 1}, {name: 'Elvis', value: 2}, {name: 'Michael', value: 3}], 'name', 'value')
  //   returns 3: {1: "Alex", 2: "Elvis", 3: "Michael"}
  //   example 4: array_column([{name: 'Alex', value: 1}, {name: 'Elvis', value: 2}, {name: 'Michael', value: 3}], null, 'value')
  //   returns 4: {1: {name: 'Alex', value: 1}, 2: {name: 'Elvis', value: 2}, 3: {name: 'Michael', value: 3}}

  if (input !== null && (typeof input === 'object' || Array.isArray(input))) {
    var newarray = []
    if (typeof input === 'object') {
      let temparray = []
      for (let key of Object.keys(input)) {
        temparray.push(input[key])
      }
      input = temparray
    }
    if (Array.isArray(input)) {
      for (let key of input.keys()) {
        if (IndexKey && input[key][IndexKey]) {
          if (ColumnKey) {
            newarray[input[key][IndexKey]] = input[key][ColumnKey]
          } else {
            newarray[input[key][IndexKey]] = input[key]
          }
        } else {
          if (ColumnKey) {
            newarray.push(input[key][ColumnKey])
          } else {
            newarray.push(input[key])
          }
        }
      }
    }
    return Object.assign({}, newarray)
  }
};

PHP.array_filter = function (arr, func) { // eslint-disable-line camelcase
  //  discuss at: https://locutus.io/php/array_filter/
  // original by: Brett Zamir (https://brett-zamir.me)
  //    input by: max4ever
  // improved by: Brett Zamir (https://brett-zamir.me)
  //      note 1: Takes a function as an argument, not a function's name
  //   example 1: var odd = function (num) {return (num & 1);}
  //   example 1: array_filter({"a": 1, "b": 2, "c": 3, "d": 4, "e": 5}, odd)
  //   returns 1: {"a": 1, "c": 3, "e": 5}
  //   example 2: var even = function (num) {return (!(num & 1));}
  //   example 2: array_filter([6, 7, 8, 9, 10, 11, 12], even)
  //   returns 2: [ 6, , 8, , 10, , 12 ]
  //   example 3: array_filter({"a": 1, "b": false, "c": -1, "d": 0, "e": null, "f":'', "g":undefined})
  //   returns 3: {"a":1, "c":-1}

  var retObj = {}
  var k

  func = func || function (v) {
    return v
  }

  // @todo: Issue #73
  if (Object.prototype.toString.call(arr) === '[object Array]') {
    retObj = []
  }

  for (k in arr) {
    if (func(arr[k])) {
      retObj[k] = arr[k]
    }
  }

  return retObj
};

PHP.array_values = function (input) { // eslint-disable-line camelcase
  //  discuss at: https://locutus.io/php/array_values/
  // original by: Kevin van Zonneveld (https://kvz.io)
  // improved by: Brett Zamir (https://brett-zamir.me)
  //   example 1: array_values( {firstname: 'Kevin', surname: 'van Zonneveld'} )
  //   returns 1: [ 'Kevin', 'van Zonneveld' ]

  var tmpArr = []
  var key = ''

  for (key in input) {
    tmpArr[tmpArr.length] = input[key]
  }

  return tmpArr
};
	
	PHP.in_array = function (needle, haystack, argStrict) { // eslint-disable-line camelcase
	  //  discuss at: http://locutus.io/php/in_array/
	  // original by: Kevin van Zonneveld (http://kvz.io)
	  // improved by: vlado houba
	  // improved by: Jonas Sciangula Street (Joni2Back)
	  //    input by: Billy
	  // bugfixed by: Brett Zamir (http://brett-zamir.me)
	  //   example 1: in_array('van', ['Kevin', 'van', 'Zonneveld'])
	  //   returns 1: true
	  //   example 2: in_array('vlado', {0: 'Kevin', vlado: 'van', 1: 'Zonneveld'})
	  //   returns 2: false
	  //   example 3: in_array(1, ['1', '2', '3'])
	  //   example 3: in_array(1, ['1', '2', '3'], false)
	  //   returns 3: true
	  //   returns 3: true
	  //   example 4: in_array(1, ['1', '2', '3'], true)
	  //   returns 4: false
	  var key = ''
	  var strict = !!argStrict
	  // we prevent the double check (strict && arr[key] === ndl) || (!strict && arr[key] === ndl)
	  // in just one for, in order to improve the performance
	  // deciding wich type of comparation will do before walk array
	  if (strict) {
	    for (key in haystack) {
	      if (haystack[key] === needle) {
	        return true
	      }
	    }
	  } else {
	    for (key in haystack) {
	      if (haystack[key] == needle) { // eslint-disable-line eqeqeq
	        return true
	      }
	    }
	  }
	  return false
	};

	/**
	 *	为了简化操作
	 *	就设置一个函数来控制cookie
	 *  函数cookie
	 *  @param string key key值
	 *  @param string value value值
	 *  @param num timeout 过期时间 默认1分钟,可不填 以秒为单位
	 */
	 PHP.cookie = function(){
		var e = new Array();
		var timeout = 600000;//60秒
		var today = new Date();
		for(var i=0;i<arguments.length;i++){
			e[i] = arguments[i]; 
		}	
		switch(e.length){
			case 1:
				var s = e[0] + '=';
				begin = document.cookie.indexOf(s);
				if(s!=-1){
					begin +=s.length;
					end = document.cookie.indexOf(';',begin);
					if(end!=-1){
						len = end - begin;
						return document.cookie.substr(begin, len)!='undefined'?document.cookie.substr(begin, len):'';
					}
				}break;
			case 2:	
				today.setTime(today.getTime() + timeout);
				return document.cookie = e[0]+'='+e[1]+';expires='+(today.toLocaleString());break;
			case 3:
				today.setTime(today.getTime() + e[2]*1000);
				return document.cookie = e[0]+'='+e[1]+';expires='+(today.toLocaleString());break;
			default:
				return false;break;	
		}
	};

/**-----------------------------------
 *									*	
 *			文件的操作				*
 *									*
 -----------------------------------*/
//	NOTE:在JS的文件的操作，并不是真正的文件操作。
//	而是使用ajax，获取网页的数据，并进行操作。
	
   /**
	*  借鉴:高洛峰 Ajax3.0
	*  为文件获取文件过去,操作ajax对象。
	*  @param string type HTML、XML和JSON,默认HTML,传值是不区分大小写
	*  @param boolean bool  true表示异步传输，false表示同步传输
	*/
	function php_ajax(type,bool){
		var xhr = {};
		/*默认返回 html type 返回的类型为 */
		if(typeof(type)=='undefined'){
			xhr.type='HTML';
		}else{
			xhr.type=type.toUpperCase();
		}
		/*默认传输的方式 true 是异步 false 是同步 */
		if(typeof(bool)=='undefined'){
			xhr.async=true;
		}else{
			xhr.async=bool;
		}
		xhr.url = '';//网站地址
		xhr.send = '';//POST请求服务器地址? & 格式url;
		xhr.result=null;

		xhr.createXHR = function(){
			try{//判断浏览器是否原生态支持
				request = new XMLHttpRequest();
				if(request.overrideMimeType){
					request.overrideMimeType('text/html');
				}
			}catch(e){
				var v = ['Microsoft.XMLHTTP', 'MSXML.XMLHTTP', 'Microsoft.XMLHTTP',
					'Msxml2.XMLHTTP.7.0', 'Msxml2.XMLHTTP.6.0', 'Msxml2.XMLHTTP.5.0',
						'Msxml2.XMLHTTP.4.0', 'MSXML2.XMLHTTP.3.0', 'MSXML2.XMLHTTP'];
				for(var i=0;i<v.length;i++){
					try{
						request = new ActiveXObject(v[i]); 
						if(request){return request;}
					}catch(e){continue;
					
					}
				}
			}
			return request;
		}
		//换个名字
		xhr.XHR = xhr.createXHR();
		//进程控制
		xhr.processHandle = function(){
			if( xhr.XHR.readyState ==4 && xhr.XHR.status==200){
				if(xhr.type=='HTML'){
					xhr.result(xhr.XHR.responseText);
					return xhr.XHR.responseText;
				}else if(xhr.type=='JSON'){
					xhr.result(eval('('+xhr.XHR.responseText+')'));
					return eval('('+xhr.XHR.responseText+')');
				}else{
					xhr.result(xhr.XHR.responseXML);
					return xhr.XHR.responseXML;
				}
			}	
		};

		/**
		 *	get 获值
		 *	@param string url web文件
		 *	@param mixed result 数据操作
		 */
		xhr.get = function(url,result){
			//添加回调函数
			var name ='PHPjs';
			var r = name + '_' + Math.random().toString().substr(2);//随机

			xhr.url = url+'&'+name+'='+r;
			
			if(result!=null){
				xhr.XHR.onreadystatechange = xhr.processHandle;
				xhr.result = result;
			}
			if(window.XMLHttpRequest){
				xhr.XHR.open('GET',xhr.url,xhr.async);
				xhr.XHR.send(null);
			}else{
				xhr.XHR.open('GET',xhr.url,xhr.async);
				xhr.XHR.send();
			}
			//window[r] = result;
		};

		/**
		 *	get 获值
		 *	@param string url web文件
		 *	@paramn mixed send 传向服务端的值
		 *	@param mixed result 数据操作
		 */
		xhr.post = function(url,send,result){
			xhr.url = url;
			/* 分解过去的值 */
			if(typeof(send) == 'object'){
				var str = '';
				for(var pro in send){
					str +=pro +'='+send[pro]+'&';
				}
				xhr.send = str.substr(0,str.length-1);
			}else{
				xhr.send = send;
			}
			if(result!=null){
				xhr.XHR.onreadystatechange = xhr.processHandle;
				xhr.result = result;
			}
			xhr.XHR.open('POST',url,xhr.async);
			xhr.XHR.setRequestHeader('request-type','ajax');//设置请求类型(是否是ajax请求)
			xhr.XHR.setRequestHeader('Content-type','application/x-www-form-urlencoded');//设置格式
			xhr.XHR.send(xhr.send);
		}
		return xhr;//返回
	};

	/**
	 *	判断是否是方法并把第二参数的数据给它
	 *	@param function func 方法
	 *	@param mixd data 数据
	 *	@return 没有返回值
	 */
	function is_function(func,data){if(typeof func == 'function'){func(data);};};

	/**
	 *	创建标签
	 *	@param string elm 标签名
	 *	@return bom对象
	 */
	function create_elm(elm){
		var f;
		try{f = document.createElement(elm);//非IE下
		}catch(e){f = document.createElement(eval('<'+elm+'></'+elm+'>'));//IE下 
		}
		return f;
	};
	//因为ajax不能跨域操作的,而想的解决方法
	/**
	 *	@param string url 网站的地址
	 *	@return string 返回DOM
	 */
	function create_iframe(url){
		var f = create_elm('iframe');;
	    f.style.display='none';
		f.src=url;
		f.id='PHPjs';
		//在网页要先显示
		document.body.appendChild(f);
		return document.getElementById('PHPjs');
		//在网页上,不显示[试过,不行]
		return f;
	};

	/**
	 *	@param string url 网站的地址
	 *	@param mixed data 数据控制
	 *	@param boolean show 是否显示
	 */
	function get_iframe(url,data,show){
		var sign = create_iframe(url);
		console.log(sign);
		//加载完后,触发
		sign.onload = function(){
			var end =  sign.contentDocument;//console.log(sign.contentDocument.getElementById('title'));
			is_function(data,end);
			//console.log(end);
			if(show){document.body.removeChild(sign);}
			return end;
		}	
	};

	/**
	 *	创建script头方式
	 *	@param string url 网站地址
	 *	@return 返回标签对象
	 */
	function create_script(url){
		var f = create_elm('script');
		f.type = 'text/javascript';
		f.src=url;
		document.head.appendChild(f);
		return f;
	}
	/**
	 *	获取数据
	 *	@param string url 网站地址
	 *	@param mixed data 回调函数
	 *	@return 无返回值
	 */
	function get_script(url,data){
		//对url处理
		var c = url.split('?');
		var p = c[1].split('&');
		var name ='PHPjs';
		var r = name + '_' + Math.random().toString().substr(2);//随机
		//组装url
		var zurl = c[0] + '?' + p.join('&') + '&' + name + '=' + r;
		var f = create_script(zurl);
		window[r] = data;
		//获得数据后,回收资源
		f.onload = function(){
			try{delete window[r];} catch(e){}//删除全局变量
			document.head.removeChild(f);
		}
	}

   /**
	* file_get_contents | file 函数 获取的网页的数据
	* @param string url 网站的地址
	* @param mixed data 对返回的值,操作
	* @param boolean show iframe是显示在页面上 默认不显示[false]。也就意为没有返回值了。[true],有返回值
	* @return mied 获取网页的数据
	* bug:如过为IP地址会有错误显示
	*/
	PHP.file_get_contents = PHP.file = function(url,data,show){
		var localDomain = document.domain;//当前域
		if(typeof show == 'undefined'){show=false;}
		//三种选择
		//1 同域的情况下
		var matchDomain = /(http:|https:)?\/\/(\w*\.\w*\.\w{2,3}[^\/])\//;//提出传来的域名
		var fromDomain = matchDomain.exec(url);
		if(!fromDomain){/* 直接调用本域下文件的 */
			return php_ajax('HTML',true).get(url,data);
		}
		if(localDomain == fromDomain[2]){//同域中调用文件
			return php_ajax('HTML',true).get(url,data);
		}else if((function(localDomain,fromDomain){
			var MRole = /(.*\.)?(.*\.\w{2,3})/,//匹配域名的规则
			match=false;
			var murl = MRole.exec(fromDomain[2]);
			var lurl = MRole.exec(localDomain);
			//console.log(murl);
			if(null == lurl){return false;}//对本地检查
			if(murl[2]==lurl[2]){return true;}
			return match;
		})(localDomain,fromDomain)){
		//2 在与子域的情况下[使用iframe解决] 需要的把iframe网页里设置成 例如:phpjs.com 才能使用
		//使用getElementById(),getElementByTagName();设置
			var MRole = /(.*\.)?(.*\.\w{2,3})/;//匹配域名的规则
			lurl = MRole.exec(localDomain);
			console.log('test:'+lurl[2]);
			document.domain = lurl[2];//console.log(lurl);
			//var data = get_iframe(url,data,show); //iframe架构
			var data = get_script(url,data);//script头部
			return data;
		}else{
		//3 获取其他域的情况下[使用iframe解决] 需要的把iframe网页里设置成 例如:phpjs.com 才能使用
		// 使用其他域的时候,意味你要是脚本语言如PHP,JSP,...请在头消息中设置是text/javascript这样才能更好使用
		// 使用方式与jquery json 一样
			var data = get_script(url,data);
			return data;
		}
	}
/**----------------------------------
 *				json处理			*
 -----------------------------------*/
	
	PHP.json_encode = function (mixedVal) {
	  // eslint-disable-line camelcase
	  //       discuss at: http://phpjs.org/functions/json_encode/
	  //      original by: Public Domain (http://www.json.org/json2.js)
	  // reimplemented by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	  //      improved by: Michael White
	  //         input by: felix
	  //      bugfixed by: Brett Zamir (http://brett-zamir.me)
	  //        example 1: json_encode('Kevin')
	  //        returns 1: '"Kevin"'
	
	  /*
	    http://www.JSON.org/json2.js
	    2008-11-19
	    Public Domain.
	    NO WARRANTY EXPRESSED OR IMPLIED. USE AT YOUR OWN RISK.
	    See http://www.JSON.org/js.html
	  */
	
	  var $global = (typeof window !== 'undefined' ? window : global)
	  $global.$locutus = $global.$locutus || {}
	  var $locutus = $global.$locutus
	  $locutus.php = $locutus.php || {}
	
	  var json = $global.JSON
	  var retVal
	  try {
	    if (typeof json === 'object' && typeof json.stringify === 'function') {
	      // Errors will not be caught here if our own equivalent to resource
	      retVal = json.stringify(mixedVal)
	      if (retVal === undefined) {
	        throw new SyntaxError('json_encode')
	      }
	      return retVal
	    }
	
	    var value = mixedVal
	
	    var quote = function (string) {
	      var escapeChars = [
	        '\u0000-\u001f',
	        '\u007f-\u009f',
	        '\u00ad',
	        '\u0600-\u0604',
	        '\u070f',
	        '\u17b4',
	        '\u17b5',
	        '\u200c-\u200f',
	        '\u2028-\u202f',
	        '\u2060-\u206f',
	        '\ufeff',
	        '\ufff0-\uffff'
	      ].join('')
	      var escapable = new RegExp('[\\"' + escapeChars + ']', 'g')
	      var meta = {
	        // table of character substitutions
	        '\b': '\\b',
	        '\t': '\\t',
	        '\n': '\\n',
	        '\f': '\\f',
	        '\r': '\\r',
	        '"': '\\"',
	        '\\': '\\\\'
	      }
	
	      escapable.lastIndex = 0
	      return escapable.test(string) ? '"' + string.replace(escapable, function (a) {
	        var c = meta[a]
	        return typeof c === 'string' ? c : '\\u' + ('0000' + a.charCodeAt(0)
	          .toString(16))
	          .slice(-4)
	      }) + '"' : '"' + string + '"'
	    }
	
	    var _str = function (key, holder) {
	      var gap = ''
	      var indent = '    '
	      // The loop counter.
	      var i = 0
	      // The member key.
	      var k = ''
	      // The member value.
	      var v = ''
	      var length = 0
	      var mind = gap
	      var partial = []
	      var value = holder[key]
	
	      // If the value has a toJSON method, call it to obtain a replacement value.
	      if (value && typeof value === 'object' && typeof value.toJSON === 'function') {
	        value = value.toJSON(key)
	      }
	
	      // What happens next depends on the value's type.
	      switch (typeof value) {
	        case 'string':
	          return quote(value)
	
	        case 'number':
	          // JSON numbers must be finite. Encode non-finite numbers as null.
	          return isFinite(value) ? String(value) : 'null'
	
	        case 'boolean':
	        case 'null':
	          // If the value is a boolean or null, convert it to a string. Note:
	          // typeof null does not produce 'null'. The case is included here in
	          // the remote chance that this gets fixed someday.
	          return String(value)
	
	        case 'object':
	          // If the type is 'object', we might be dealing with an object or an array or
	          // null.
	          // Due to a specification blunder in ECMAScript, typeof null is 'object',
	          // so watch out for that case.
	          if (!value) {
	            return 'null'
	          }
	
	          // Make an array to hold the partial results of stringifying this object value.
	          gap += indent
	          partial = []
	
	          // Is the value an array?
	          if (Object.prototype.toString.apply(value) === '[object Array]') {
	            // The value is an array. Stringify every element. Use null as a placeholder
	            // for non-JSON values.
	            length = value.length
	            for (i = 0; i < length; i += 1) {
	              partial[i] = _str(i, value) || 'null'
	            }
	
	            // Join all of the elements together, separated with commas, and wrap them in
	            // brackets.
	            v = partial.length === 0 ? '[]' : gap
	              ? '[\n' + gap + partial.join(',\n' + gap) + '\n' + mind + ']'
	              : '[' + partial.join(',') + ']'
	            gap = mind
	            return v
	          }
	
	          // Iterate through all of the keys in the object.
	          for (k in value) {
	            if (Object.hasOwnProperty.call(value, k)) {
	              v = _str(k, value)
	              if (v) {
	                partial.push(quote(k) + (gap ? ': ' : ':') + v)
	              }
	            }
	          }
	
	          // Join all of the member texts together, separated with commas,
	          // and wrap them in braces.
	          v = partial.length === 0 ? '{}' : gap
	            ? '{\n' + gap + partial.join(',\n' + gap) + '\n' + mind + '}'
	            : '{' + partial.join(',') + '}'
	          gap = mind
	          return v
	        case 'undefined':
	        case 'function':
	        default:
	          throw new SyntaxError('json_encode')
	      }
	    }
	
	    // Make a fake root object containing our value under the key of ''.
	    // Return the result of stringifying the value.
	    return _str('', {
	      '': value
	    })
	  } catch (err) {
	    // @todo: ensure error handling above throws a SyntaxError in all cases where it could
	    // (i.e., when the JSON global is not available and there is an error)
	    if (!(err instanceof SyntaxError)) {
	      throw new Error('Unexpected error type in json_encode()')
	    }
	    // usable by json_last_error()
	    $locutus.php.last_error_json = 4
	    return null
	  }
	}

	/**
	 *	json解密
	 *	@param string str 字符串
	 *	@return 返回对象数据
	 */
	PHP.json_decode = function (strJson) { // eslint-disable-line camelcase
  //       discuss at: http://phpjs.org/functions/json_decode/
  //      original by: Public Domain (http://www.json.org/json2.js)
  // reimplemented by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  //      improved by: T.J. Leahy
  //      improved by: Michael White
  //           note 1: If node or the browser does not offer JSON.parse,
  //           note 1: this function falls backslash
  //           note 1: to its own implementation using eval, and hence should be considered unsafe
  //        example 1: json_decode('[ 1 ]')
  //        returns 1: [1]

  /*
    http://www.JSON.org/json2.js
    2008-11-19
    Public Domain.
    NO WARRANTY EXPRESSED OR IMPLIED. USE AT YOUR OWN RISK.
    See http://www.JSON.org/js.html
  */

  var $global = (typeof window !== 'undefined' ? window : global)
  $global.$locutus = $global.$locutus || {}
  var $locutus = $global.$locutus
  $locutus.php = $locutus.php || {}

  var json = $global.JSON
  if (typeof json === 'object' && typeof json.parse === 'function') {
    try {
      return json.parse(strJson)
    } catch (err) {
      if (!(err instanceof SyntaxError)) {
        throw new Error('Unexpected error type in json_decode()')
      }

      // usable by json_last_error()
      $locutus.php.last_error_json = 4
      return null
    }
  }

  var chars = [
    '\u0000',
    '\u00ad',
    '\u0600-\u0604',
    '\u070f',
    '\u17b4',
    '\u17b5',
    '\u200c-\u200f',
    '\u2028-\u202f',
    '\u2060-\u206f',
    '\ufeff',
    '\ufff0-\uffff'
  ].join('')
  var cx = new RegExp('[' + chars + ']', 'g')
  var j
  var text = strJson

  // Parsing happens in four stages. In the first stage, we replace certain
  // Unicode characters with escape sequences. JavaScript handles many characters
  // incorrectly, either silently deleting them, or treating them as line endings.
  cx.lastIndex = 0
  if (cx.test(text)) {
    text = text.replace(cx, function (a) {
      return '\\u' + ('0000' + a.charCodeAt(0)
        .toString(16))
        .slice(-4)
    })
  }

  // In the second stage, we run the text against regular expressions that look
  // for non-JSON patterns. We are especially concerned with '()' and 'new'
  // because they can cause invocation, and '=' because it can cause mutation.
  // But just to be safe, we want to reject all unexpected forms.
  // We split the second stage into 4 regexp operations in order to work around
  // crippling inefficiencies in IE's and Safari's regexp engines. First we
  // replace the JSON backslash pairs with '@' (a non-JSON character). Second, we
  // replace all simple value tokens with ']' characters. Third, we delete all
  // open brackets that follow a colon or comma or that begin the text. Finally,
  // we look to see that the remaining characters are only whitespace or ']' or
  // ',' or ':' or '{' or '}'. If that is so, then the text is safe for eval.

  var m = (/^[\],:{}\s]*$/)
    .test(text.replace(/\\(?:["\\/bfnrt]|u[0-9a-fA-F]{4})/g, '@')
    .replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+-]?\d+)?/g, ']')
    .replace(/(?:^|:|,)(?:\s*\[)+/g, ''))

  if (m) {
    // In the third stage we use the eval function to compile the text into a
    // JavaScript structure. The '{' operator is subject to a syntactic ambiguity
    // in JavaScript: it can begin a block or an object literal. We wrap the text
    // in parens to eliminate the ambiguity.
    j = eval('(' + text + ')') // eslint-disable-line no-eval
    return j
  }

  // usable by json_last_error()
  $locutus.php.last_error_json = 4
  return null
};
/**----------------------------------
 *				URL编码				*
 *			各种的加密和编码		*
 -----------------------------------*/
PHP.basename = function (direct) {
	if (!direct) return '';

	if (direct.indexOf('/') !== -1) {
		var result = direct.split('/');
		var a = result.pop();
		while (a ==='') {
			a = result.pop();
		}
		return a || '';
	} else {
		return direct;
	}
};
	/**
	 *	@func 解析 URL，返回其组成部分
	 *  @ret array(
			[scheme] => http		//链接协议
			[host] => hostname		//主机名
			[user] => username		//用户名
			[pass] => password		//密码
			[path] => /path			//路径
			[query] => arg=value	//?后面的值
			[fragment] => anchor	//#后面的值

		);
	 */
	PHP.parse_url = function(str, component) { // eslint-disable-line camelcase
  //       discuss at: http://locutus.io/php/parse_url/
  //      original by: Steven Levithan (http://blog.stevenlevithan.com)
  // reimplemented by: Brett Zamir (http://brett-zamir.me)
  //         input by: Lorenzo Pisani
  //         input by: Tony
  //      improved by: Brett Zamir (http://brett-zamir.me)
  //           note 1: original by http://stevenlevithan.com/demo/parseuri/js/assets/parseuri.js
  //           note 1: blog post at http://blog.stevenlevithan.com/archives/parseuri
  //           note 1: demo at http://stevenlevithan.com/demo/parseuri/js/assets/parseuri.js
  //           note 1: Does not replace invalid characters with '_' as in PHP,
  //           note 1: nor does it return false with
  //           note 1: a seriously malformed URL.
  //           note 1: Besides function name, is essentially the same as parseUri as
  //           note 1: well as our allowing
  //           note 1: an extra slash after the scheme/protocol (to allow file:/// as in PHP)
  //        example 1: parse_url('http://user:pass@host/path?a=v#a')
  //        returns 1: {scheme: 'http', host: 'host', user: 'user', pass: 'pass', path: '/path', query: 'a=v', fragment: 'a'}
  //        example 2: parse_url('http://en.wikipedia.org/wiki/%22@%22_%28album%29')
  //        returns 2: {scheme: 'http', host: 'en.wikipedia.org', path: '/wiki/%22@%22_%28album%29'}
  //        example 3: parse_url('https://host.domain.tld/a@b.c/folder')
  //        returns 3: {scheme: 'https', host: 'host.domain.tld', path: '/a@b.c/folder'}
  //        example 4: parse_url('https://gooduser:secretpassword@www.example.com/a@b.c/folder?foo=bar')
  //        returns 4: { scheme: 'https', host: 'www.example.com', path: '/a@b.c/folder', query: 'foo=bar', user: 'gooduser', pass: 'secretpassword' }

  var query

  var mode = (typeof require !== 'undefined' ? require('../info/ini_get')('locutus.parse_url.mode') : undefined) || 'php'

  var key = [
    'source',
    'scheme',
    'authority',
    'userInfo',
    'user',
    'pass',
    'host',
    'port',
    'relative',
    'path',
    'directory',
    'file',
    'query',
    'fragment'
  ]

  // For loose we added one optional slash to post-scheme to catch file:/// (should restrict this)
  var parser = {
    php: new RegExp([
      '(?:([^:\\/?#]+):)?',
      '(?:\\/\\/()(?:(?:()(?:([^:@\\/]*):?([^:@\\/]*))?@)?([^:\\/?#]*)(?::(\\d*))?))?',
      '()',
      '(?:(()(?:(?:[^?#\\/]*\\/)*)()(?:[^?#]*))(?:\\?([^#]*))?(?:#(.*))?)'
    ].join('')),
    strict: new RegExp([
      '(?:([^:\\/?#]+):)?',
      '(?:\\/\\/((?:(([^:@\\/]*):?([^:@\\/]*))?@)?([^:\\/?#]*)(?::(\\d*))?))?',
      '((((?:[^?#\\/]*\\/)*)([^?#]*))(?:\\?([^#]*))?(?:#(.*))?)'
    ].join('')),
    loose: new RegExp([
      '(?:(?![^:@]+:[^:@\\/]*@)([^:\\/?#.]+):)?',
      '(?:\\/\\/\\/?)?',
      '((?:(([^:@\\/]*):?([^:@\\/]*))?@)?([^:\\/?#]*)(?::(\\d*))?)',
      '(((\\/(?:[^?#](?![^?#\\/]*\\.[^?#\\/.]+(?:[?#]|$)))*\\/?)?([^?#\\/]*))',
      '(?:\\?([^#]*))?(?:#(.*))?)'
    ].join(''))
  }

  var m = parser[mode].exec(str)
  var uri = {}
  var i = 14

  while (i--) {
    if (m[i]) {
      uri[key[i]] = m[i]
    }
  }

  if (component) {
    return uri[component.replace('PHP_URL_', '').toLowerCase()]
  }

  if (mode !== 'php') {
    var name = (typeof require !== 'undefined' ? require('../info/ini_get')('locutus.parse_url.queryKey') : undefined) || 'queryKey'
    parser = /(?:^|&)([^&=]*)=?([^&]*)/g
    uri[name] = {}
    query = uri[key[12]] || ''
    query.replace(parser, function ($0, $1, $2) {
      if ($1) {
        uri[name][$1] = $2
      }
    })
  }

  delete uri.source
  return uri
};
	
	
	/**
	 *  
	 *	@func base64_encode 加密
	 *	@param string str 字符串
	 *  @return 返回加密后的字符串
	 */
	PHP.base64_encode = function(stringToEncode) { // eslint-disable-line camelcase
	  //  discuss at: http://locutus.io/php/base64_encode/
	  // original by: Tyler Akins (http://rumkin.com)
	  // improved by: Bayron Guevara
	  // improved by: Thunder.m
	  // improved by: Kevin van Zonneveld (http://kvz.io)
	  // improved by: Kevin van Zonneveld (http://kvz.io)
	  // improved by: Rafał Kukawski (http://blog.kukawski.pl)
	  // bugfixed by: Pellentesque Malesuada
	  // improved by: Indigo744
	  //   example 1: base64_encode('Kevin van Zonneveld')
	  //   returns 1: 'S2V2aW4gdmFuIFpvbm5ldmVsZA=='
	  //   example 2: base64_encode('a')
	  //   returns 2: 'YQ=='
	  //   example 3: base64_encode('✓ à la mode')
	  //   returns 3: '4pyTIMOgIGxhIG1vZGU='
	  // encodeUTF8string()
	  // Internal function to encode properly UTF8 string
	  // Adapted from Solution #1 at https://developer.mozilla.org/en-US/docs/Web/API/WindowBase64/Base64_encoding_and_decoding
	  var encodeUTF8string = function (str) {
	    // first we use encodeURIComponent to get percent-encoded UTF-8,
	    // then we convert the percent encodings into raw bytes which
	    // can be fed into the base64 encoding algorithm.
	    return encodeURIComponent(str).replace(/%([0-9A-F]{2})/g,
	      function toSolidBytes (match, p1) {
	        return String.fromCharCode('0x' + p1)
	      })
	  }
	  if (typeof window !== 'undefined') {
	    if (typeof window.btoa !== 'undefined') {
	      return window.btoa(encodeUTF8string(stringToEncode))
	    }
	  } else {
	    return new Buffer(stringToEncode).toString('base64')
	  }
	  var b64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/='
	  var o1
	  var o2
	  var o3
	  var h1
	  var h2
	  var h3
	  var h4
	  var bits
	  var i = 0
	  var ac = 0
	  var enc = ''
	  var tmpArr = []
	  if (!stringToEncode) {
	    return stringToEncode
	  }
	  stringToEncode = encodeUTF8string(stringToEncode)
	  do {
	    // pack three octets into four hexets
	    o1 = stringToEncode.charCodeAt(i++)
	    o2 = stringToEncode.charCodeAt(i++)
	    o3 = stringToEncode.charCodeAt(i++)
	    bits = o1 << 16 | o2 << 8 | o3
	    h1 = bits >> 18 & 0x3f
	    h2 = bits >> 12 & 0x3f
	    h3 = bits >> 6 & 0x3f
	    h4 = bits & 0x3f
	    // use hexets to index into b64, and append result to encoded string
	    tmpArr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4)
	  } while (i < stringToEncode.length)
	  enc = tmpArr.join('')
	  var r = stringToEncode.length % 3
	  return (r ? enc.slice(0, r - 3) : enc) + '==='.slice(r || 3)
	}

	/**
	 * @bug:不能处理中文字
	 * @func base64 解密
	 * @param string str 解密字符串
	 * @return 返回解密后的字符串
	 */
	PHP.base64_decode = function(encodedData) { // eslint-disable-line camelcase
	  //  discuss at: http://locutus.io/php/base64_decode/
	  // original by: Tyler Akins (http://rumkin.com)
	  // improved by: Thunder.m
	  // improved by: Kevin van Zonneveld (http://kvz.io)
	  // improved by: Kevin van Zonneveld (http://kvz.io)
	  //    input by: Aman Gupta
	  //    input by: Brett Zamir (http://brett-zamir.me)
	  // bugfixed by: Onno Marsman (https://twitter.com/onnomarsman)
	  // bugfixed by: Pellentesque Malesuada
	  // bugfixed by: Kevin van Zonneveld (http://kvz.io)
	  // improved by: Indigo744
	  //   example 1: base64_decode('S2V2aW4gdmFuIFpvbm5ldmVsZA==')
	  //   returns 1: 'Kevin van Zonneveld'
	  //   example 2: base64_decode('YQ==')
	  //   returns 2: 'a'
	  //   example 3: base64_decode('4pyTIMOgIGxhIG1vZGU=')
	  //   returns 3: '✓ à la mode'
	  // decodeUTF8string()
	  // Internal function to decode properly UTF8 string
	  // Adapted from Solution #1 at https://developer.mozilla.org/en-US/docs/Web/API/WindowBase64/Base64_encoding_and_decoding
	  var decodeUTF8string = function (str) {
	    // Going backwards: from bytestream, to percent-encoding, to original string.
	    return decodeURIComponent(str.split('').map(function (c) {
	      return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2)
	    }).join(''))
	  }
	  if (typeof window !== 'undefined') {
	    if (typeof window.atob !== 'undefined') {
	      return decodeUTF8string(window.atob(encodedData))
	    }
	  } else {
	    return new Buffer(encodedData, 'base64').toString('utf-8')
	  }
	  var b64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/='
	  var o1
	  var o2
	  var o3
	  var h1
	  var h2
	  var h3
	  var h4
	  var bits
	  var i = 0
	  var ac = 0
	  var dec = ''
	  var tmpArr = []
	  if (!encodedData) {
	    return encodedData
	  }
	  encodedData += ''
	  do {
	    // unpack four hexets into three octets using index points in b64
	    h1 = b64.indexOf(encodedData.charAt(i++))
	    h2 = b64.indexOf(encodedData.charAt(i++))
	    h3 = b64.indexOf(encodedData.charAt(i++))
	    h4 = b64.indexOf(encodedData.charAt(i++))
	    bits = h1 << 18 | h2 << 12 | h3 << 6 | h4
	    o1 = bits >> 16 & 0xff
	    o2 = bits >> 8 & 0xff
	    o3 = bits & 0xff
	    if (h3 === 64) {
	      tmpArr[ac++] = String.fromCharCode(o1)
	    } else if (h4 === 64) {
	      tmpArr[ac++] = String.fromCharCode(o1, o2)
	    } else {
	      tmpArr[ac++] = String.fromCharCode(o1, o2, o3)
	    }
	  } while (i < encodedData.length)
	  dec = tmpArr.join('')
	  return decodeUTF8string(dec.replace(/\0+$/, ''))
	}

	/**
	 *  @url http://www.blogjava.net/hadeslee/archive/2007/11/16/160544.html 参考地址
	 *	@func urlencode 编码
	 *	@param string url URL地址
	 *	@return 返回编码后地址
	 */
	PHP.urlencode = function(url){
		if(url==null || url==''){return '';}
		var NewUrl = '';
		function toupper(str){return str.toString(16).toUpperCase();}
		for(var i=0, icode, len=url.length; i<len; i++){
			icode = url.charCodeAt(i);//转化成对应的ASCII码值
			if(icode<0x0f){
				NewUrl += '%0' + icode.toString(16).toUpperCase();
			}else if(icode<0x80){
				if(icode==0x20){
					NewUrl += '+';//空格
				}else if((icode>=0x30 && icode <=0x39) || (icode>=0x41 && icode <=0x5A) || (icode>=0x61 && icode <=0x7A)){//数字和字母区间
					NewUrl += url.charAt(i);//charAt 返回索引位置;
				}else{
					NewUrl += '%' + toupper(icode);//符号的区间
				}
			}else if(icode<0x7ff){
				NewUrl += '%' + toupper(0xC0 + (icode>>6));
				NewUrl += '%' + toupper(0x80 + icode%0x40);
			}else{
				//中文encodeURI()编码
				NewUrl += '%' + toupper(0xE0 + (icode>>12));
				NewUrl += '%' + toupper(0x80 + (icode>>6)%0x40);
				NewUrl += '%' + toupper(0x80 + icode%0x40);
			}
		}
		return NewUrl;
	}

	/**
	 *  @time 2013-1-31 解决urlencode中文编码
	 *  @func urldecode 解码
	 *	@param string url URL编码后地址
	 *	@return 返回解码后地址
	 */
	PHP.urldecode = function(url){
		var NewUrl = '';
		var len = url.length;
		var hanzi = '';//接受汉字
		for(var i=0,icode;i<len;i++){
			icode = url.charCodeAt(i);//转化成对应的ASCII码值
			if(url[i] == '+'){//遇到+,返回空
				NewUrl += ' ';
			}else if((icode>=0x30 && icode <=0x39) || (icode>=0x41 && icode <=0x5A) || (icode>=0x61 && icode <=0x7A)){
				NewUrl += url[i];//字母和数字不变
			}else if(url[i] == '%'){
				var t ="0x" + url.substr(i+1,2);
				if(eval(t)>127){//不在ascii内,为汉字
					//中文encodeURI()解码
					hanzi = url.substr(i,9);
					var hword = decodeURI(hanzi);
					NewUrl += hword;
					i+=8;
				}else{
					NewUrl += String.fromCharCode(t);//特殊符号
					i+=2;
				}
		
			}else{}
		}
		return NewUrl;
	}
/**-----------------------------------
 * @func MD5加密处理
 * @param s 输入的字符串
 * @return MD5加密后的字符串
 * @source:http://pajhome.org.uk/crypt/md5/md5.html
 ------------------------------------*/
	PHP.md5 = function(s){
		var hexcase = 0;//十六进制格式输出。 0 - 小写1 - 大写
		var b64pad = '';//基于64位填充字符。 "="严格符合RFC标准

		/* 编码为utf-8 , 假设input的值为utf-16 */
		function str16tostr8(input){
			var output = '';
			var i = -1;
			var x, y;
			//console.log(input);
			while(++i < input.length){
				x = input.charCodeAt(i);/* 解码utf-16 */
				y = i + 1 < input.length ? input.charCodeAt(i+1) : 0;
				if(0xD800 <= x && x <= 0xDBFF && 0xDC00 <= y && y <= 0xDFFF){
					x = 0x10000 + ((x & 0x03FF) << 10) + (y & 0x03FF);
					i++;
				}
				/* 编码为utf8的 */
				if(x <= 0x7F)
					output += String.fromCharCode(x);
				else if(x <= 0x7FF)
					output += String.fromCharCode(0xC0 | ((x >>> 6 ) & 0x1F),
												  0x80 | ( x         & 0x3F));
				else if(x <= 0xFFFF)
					output += String.fromCharCode(0xE0 | ((x >>> 12) & 0x0F),
												  0x80 | ((x >>> 6 ) & 0x3F),
												  0x80 | ( x         & 0x3F));
				else if(x <= 0x1FFFFF)
					output += String.fromCharCode(0xF0 | ((x >>> 18) & 0x07),
												  0x80 | ((x >>> 12) & 0x3F),
												  0x80 | ((x >>> 6 ) & 0x3F),
												  0x80 | ( x         & 0x3F));
			}
			//console.log(output);
			return output;
		}

		/**
		 *	把原始字符串转化为16为字符串
		 *	@param string input 输入值
		 */
		function rstr2hex(input){
			try { 
				hexcase
			}catch(e){ 
				hexcase=0; 
			}
			var hex_tab = hexcase ? "0123456789ABCDEF" : "0123456789abcdef";
			var output = "";
			var x;
			for(var i = 0; i < input.length; i++){
				x = input.charCodeAt(i);
				output += hex_tab.charAt((x >>> 4) & 0x0F)
						  +hex_tab.charAt( x       & 0x0F);
			}
			return output;
		}
		

		/**
		 *	添加整数,封装为2^32形式.
		 *	这里采用16位内部操作解决js解析器错误.
		 */
		function safe_add(x, y){
			var lsw = ( x & 0xFFFF) + ( y & 0xFFFF );//限制在32位
			var msw = ( x >> 16) + (y >> 16) + ( lsw >> 16);//缩小2^16
			return (msw << 16) | (lsw & 0xFFFF);
		}

		/**
		 *	原数据转化一个数组的低位字符
		 *	字符大于255 && 高字节忽略
		 *	@param string input 接收的字符串
		 *	@return array
		 */
		function rstr2binl(input){
			var output = Array( input.length >> 2);
			for(var i = 0; i < output.length; i++){
				output[i] = 0;
			}
			for(var i = 0; i<input.length*8;i += 8){
				output[i>>5] |= (input.charCodeAt(i / 8) & 0xFF) << (i%32);
			}
			return output;
		}

		/**
		 *	把一个低位字节转化字符串
		 */
		function binl2rstr(input){
			var output = "";
			for(var i = 0; i < input.length * 32; i += 8)
				output += String.fromCharCode((input[i>>5] >>> (i % 32)) & 0xFF);
			return output;
		}

		/**
		 *	位转为一个32位数字的左边
		 */
		function bit_rol(num, cnt){
			//console.log(num >>> (32-cnt), (num << cnt));
			return (num << cnt) | (num >>> (32-cnt));
		}

		//这些方法实现四种基本运算的算法使用。
		function md5_cmn(q, a, b, x, s, t){
			return safe_add(bit_rol(safe_add(safe_add(a, q), safe_add(x, t)), s), b);
		}

		function md5_ff(a, b, c, d, x, s, t){
			return md5_cmn((b & c) | ((~b) & d), a, b, x, s, t);
		}

		function md5_gg(a, b, c, d, x, s, t){
			return md5_cmn((b & d) | (c & (~d)), a, b, x, s, t);
		}

		function md5_hh(a, b, c, d, x, s, t){
			return md5_cmn(b ^ c ^ d, a, b, x, s, t);
		}

		function md5_ii(a, b, c, d, x, s, t){
			return md5_cmn(c ^ (b | (~d)), a, b, x, s, t);
		}
		
		/**
		 *	低位字节计算数组的MD5,和一位长度
		 */
		function binl_md5(x, len){
			/* 填充 */
			x[len >> 5] |= 0x80 << ((len) % 32);
			x[(((len + 64) >>> 9) << 4) + 14] = len;

			var a =  1732584193;
			var b = -271733879;
			var c = -1732584194;
			var d =  271733878;

			for( var i = 0; i < x.length; i += 16){
				var olda = a;
				var oldb = b;
				var oldc = c;
				var oldd = d;
				//第一次
				a = md5_ff(a, b, c, d, x[i+ 0], 7 , -680876936);
				d = md5_ff(d, a, b, c, x[i+ 1], 12, -389564586);
				c = md5_ff(c, d, a, b, x[i+ 2], 17,  606105819);
				b = md5_ff(b, c, d, a, x[i+ 3], 22, -1044525330);
				a = md5_ff(a, b, c, d, x[i+ 4], 7 , -176418897);
				d = md5_ff(d, a, b, c, x[i+ 5], 12,  1200080426);
				c = md5_ff(c, d, a, b, x[i+ 6], 17, -1473231341);
				b = md5_ff(b, c, d, a, x[i+ 7], 22, -45705983);
				a = md5_ff(a, b, c, d, x[i+ 8], 7 ,  1770035416);
				d = md5_ff(d, a, b, c, x[i+ 9], 12, -1958414417);
				c = md5_ff(c, d, a, b, x[i+10], 17, -42063);
				b = md5_ff(b, c, d, a, x[i+11], 22, -1990404162);
				a = md5_ff(a, b, c, d, x[i+12], 7 ,  1804603682);
				d = md5_ff(d, a, b, c, x[i+13], 12, -40341101);
				c = md5_ff(c, d, a, b, x[i+14], 17, -1502002290);
				b = md5_ff(b, c, d, a, x[i+15], 22,  1236535329);
				//第二次
				a = md5_gg(a, b, c, d, x[i+ 1], 5 , -165796510);
				d = md5_gg(d, a, b, c, x[i+ 6], 9 , -1069501632);
				c = md5_gg(c, d, a, b, x[i+11], 14,  643717713);
				b = md5_gg(b, c, d, a, x[i+ 0], 20, -373897302);
				a = md5_gg(a, b, c, d, x[i+ 5], 5 , -701558691);
				d = md5_gg(d, a, b, c, x[i+10], 9 ,  38016083);
				c = md5_gg(c, d, a, b, x[i+15], 14, -660478335);
				b = md5_gg(b, c, d, a, x[i+ 4], 20, -405537848);
				a = md5_gg(a, b, c, d, x[i+ 9], 5 ,  568446438);
				d = md5_gg(d, a, b, c, x[i+14], 9 , -1019803690);
				c = md5_gg(c, d, a, b, x[i+ 3], 14, -187363961);
				b = md5_gg(b, c, d, a, x[i+ 8], 20,  1163531501);
				a = md5_gg(a, b, c, d, x[i+13], 5 , -1444681467);
				d = md5_gg(d, a, b, c, x[i+ 2], 9 , -51403784);
				c = md5_gg(c, d, a, b, x[i+ 7], 14,  1735328473);
				b = md5_gg(b, c, d, a, x[i+12], 20, -1926607734);
				//第三次
				a = md5_hh(a, b, c, d, x[i+ 5], 4 , -378558);
				d = md5_hh(d, a, b, c, x[i+ 8], 11, -2022574463);
				c = md5_hh(c, d, a, b, x[i+11], 16,  1839030562);
				b = md5_hh(b, c, d, a, x[i+14], 23, -35309556);
				a = md5_hh(a, b, c, d, x[i+ 1], 4 , -1530992060);
				d = md5_hh(d, a, b, c, x[i+ 4], 11,  1272893353);
				c = md5_hh(c, d, a, b, x[i+ 7], 16, -155497632);
				b = md5_hh(b, c, d, a, x[i+10], 23, -1094730640);
				a = md5_hh(a, b, c, d, x[i+13], 4 ,  681279174);
				d = md5_hh(d, a, b, c, x[i+ 0], 11, -358537222);
				c = md5_hh(c, d, a, b, x[i+ 3], 16, -722521979);
				b = md5_hh(b, c, d, a, x[i+ 6], 23,  76029189);
				a = md5_hh(a, b, c, d, x[i+ 9], 4 , -640364487);
				d = md5_hh(d, a, b, c, x[i+12], 11, -421815835);
				c = md5_hh(c, d, a, b, x[i+15], 16,  530742520);
				b = md5_hh(b, c, d, a, x[i+ 2], 23, -995338651);
				//第四次
				a = md5_ii(a, b, c, d, x[i+ 0], 6 , -198630844);
				d = md5_ii(d, a, b, c, x[i+ 7], 10,  1126891415);
				c = md5_ii(c, d, a, b, x[i+14], 15, -1416354905);
				b = md5_ii(b, c, d, a, x[i+ 5], 21, -57434055);
				a = md5_ii(a, b, c, d, x[i+12], 6 ,  1700485571);
				d = md5_ii(d, a, b, c, x[i+ 3], 10, -1894986606);
				c = md5_ii(c, d, a, b, x[i+10], 15, -1051523);
				b = md5_ii(b, c, d, a, x[i+ 1], 21, -2054922799);
				a = md5_ii(a, b, c, d, x[i+ 8], 6 ,  1873313359);
				d = md5_ii(d, a, b, c, x[i+15], 10, -30611744);
				c = md5_ii(c, d, a, b, x[i+ 6], 15, -1560198380);
				b = md5_ii(b, c, d, a, x[i+13], 21,  1309151649);
				a = md5_ii(a, b, c, d, x[i+ 4], 6 , -145523070);
				d = md5_ii(d, a, b, c, x[i+11], 10, -1120210379);
				c = md5_ii(c, d, a, b, x[i+ 2], 15,  718787259);
				b = md5_ii(b, c, d, a, x[i+ 9], 21, -343485551);
				//安全转码
				a = safe_add(a, olda);
				b = safe_add(b, oldb);
				c = safe_add(c, oldc);
				d = safe_add(d, oldd);
			}
			return Array(a, b, c, d);
		}
		
		/**
		 *	计算原始字符串的MD5值
		 */
		function rstr_md5(s){
			return binl2rstr(binl_md5(rstr2binl(s), s.length * 8));
		}

		/**
		 *	计算MD5的值
		 */
		function hex_md5(s){
			return rstr2hex(rstr_md5(str16tostr8(s)));	
		}

		/* 测试 */
		function hex_md5_test(s){
			console.log('md5加密测试:'+hex_md5(s));
		}//hex_md5_test(s);
		
		//转化为base64编码格式
		function rstr2b64(input){	
			try { 
				b64pad 
			} catch(e) { 
				b64pad=''; 
			}
			var tab = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
			var output = "";
			var len = input.length;
			for(var i = 0; i < len; i += 3){
				var triplet = (input.charCodeAt(i) << 16)
							| (i + 1 < len ? input.charCodeAt(i+1) << 8 : 0)
							| (i + 2 < len ? input.charCodeAt(i+2)      : 0);
				for(var j = 0; j < 4; j++){
					if(i * 8 + j * 6 > input.length * 8) output += b64pad;
					else output += tab.charAt((triplet >>> 6*(3-j)) & 0x3F);
				}
			}
			return output;
		}

		/**
		 *	64MD5 + base64加密
		 */
		function b64_md5(s){ 
			return rstr2b64(rstr_md5(str16tostr8(s))); 
		}
		//console.log('base64格式:'+b64_md5(s));
		
		/**
		 *	原始字符串转化为任意编码格式
		 *	@param string input 输入的值
		 *	@param string encoding 编码格式
		 */
		function rstr2any(input, encoding){
			var divisor = encoding.length;
			var i, j, q, x, quotient;

			/* Convert to an array of 16-bit big-endian values, forming the dividend */
			var dividend = Array(Math.ceil(input.length / 2));
			for(i = 0; i < dividend.length; i++){
				dividend[i] = (input.charCodeAt(i * 2) << 8) | input.charCodeAt(i * 2 + 1);
			}
			/*
			 * Repeatedly perform a long division. The binary array forms the dividend,
			 * the length of the encoding is the divisor. Once computed, the quotient
			 * forms the dividend for the next step. All remainders are stored for later
			 * use.
			 */
			var full_length = Math.ceil(input.length * 8 /
							  (Math.log(encoding.length) / Math.log(2)));
			var remainders = Array(full_length);
			for(j = 0; j < full_length; j++){
				quotient = Array();
				x = 0;
				for(i = 0; i < dividend.length; i++){
					x = (x << 16) + dividend[i];
					q = Math.floor(x / divisor);
					x -= q * divisor;
					if(quotient.length > 0 || q > 0)
						quotient[quotient.length] = q;
					}
					remainders[j] = x;
					dividend = quotient;
				}
				/* Convert the remainders to the output string */
				var output = '';
				for(i = remainders.length - 1; i >= 0; i--)
					output += encoding.charAt(remainders[i]);

				return output;
			}

			function any_md5(s, e){ 
				return rstr2any(rstr_md5(str16tostr8(s)), e); 
			}
			//转化为任意编码
			//console.log("任意编码格式:"+any_md5(s,'asdfasdfsdf'));
		return hex_md5(s);
	};
	
/**
 * A JavaScript implementation of the Secure Hash Algorithm, SHA-1, as defined
 * in FIPS 180-1
 * Version 2.2 Copyright Paul Johnston 2000 - 2009.
 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * Distributed under the BSD License
 * See http://pajhome.org.uk/crypt/md5 for details.
 */
/**
 *	@func sha1函数
 *	@param string s 字符串
 *	@ret 返回sha1值
 */
	PHP.sha1 = function (s){
		/**
		 *	返回sha1函数值
		 */
		return hex_sha1(s);
	 
		/*
		 * Configurable variables. You may need to tweak these to be compatible with
		 * the server-side, but the defaults work in most cases.
		 */
		var hexcase = 0;  /* hex output format. 0 - lowercase; 1 - uppercase        */
		var b64pad  = ""; /* base-64 pad character. "=" for strict RFC compliance   */

		/*
		 * These are the functions you'll usually want to call
		 * They take string arguments and return either hex or base-64 encoded strings
		 */
		function hex_sha1(s)    { return rstr2hex(rstr_sha1(str2rstr_utf8(s))); }
		function b64_sha1(s)    { return rstr2b64(rstr_sha1(str2rstr_utf8(s))); }
		function any_sha1(s, e) { return rstr2any(rstr_sha1(str2rstr_utf8(s)), e); }
		function hex_hmac_sha1(k, d)
		  { return rstr2hex(rstr_hmac_sha1(str2rstr_utf8(k), str2rstr_utf8(d))); }
		function b64_hmac_sha1(k, d)
		  { return rstr2b64(rstr_hmac_sha1(str2rstr_utf8(k), str2rstr_utf8(d))); }
		function any_hmac_sha1(k, d, e)
		  { return rstr2any(rstr_hmac_sha1(str2rstr_utf8(k), str2rstr_utf8(d)), e); }

		/*
		 * Perform a simple self-test to see if the VM is working
		 */
		function sha1_vm_test()
		{
		  return hex_sha1("abc").toLowerCase() == "a9993e364706816aba3e25717850c26c9cd0d89d";
		}

		/*
		 * Calculate the SHA1 of a raw string
		 */
		function rstr_sha1(s)
		{
		  return binb2rstr(binb_sha1(rstr2binb(s), s.length * 8));
		}

		/*
		 * Calculate the HMAC-SHA1 of a key and some data (raw strings)
		 */
		function rstr_hmac_sha1(key, data)
		{
		  var bkey = rstr2binb(key);
		  if(bkey.length > 16) bkey = binb_sha1(bkey, key.length * 8);

		  var ipad = Array(16), opad = Array(16);
		  for(var i = 0; i < 16; i++)
		  {
			ipad[i] = bkey[i] ^ 0x36363636;
			opad[i] = bkey[i] ^ 0x5C5C5C5C;
		  }

		  var hash = binb_sha1(ipad.concat(rstr2binb(data)), 512 + data.length * 8);
		  return binb2rstr(binb_sha1(opad.concat(hash), 512 + 160));
		}

		/*
		 * Convert a raw string to a hex string
		 */
		function rstr2hex(input)
		{
		  try { hexcase } catch(e) { hexcase=0; }
		  var hex_tab = hexcase ? "0123456789ABCDEF" : "0123456789abcdef";
		  var output = "";
		  var x;
		  for(var i = 0; i < input.length; i++)
		  {
			x = input.charCodeAt(i);
			output += hex_tab.charAt((x >>> 4) & 0x0F)
				   +  hex_tab.charAt( x        & 0x0F);
		  }
		  return output;
		}

		/*
		 * Convert a raw string to a base-64 string
		 */
		function rstr2b64(input)
		{
		  try { b64pad } catch(e) { b64pad=''; }
		  var tab = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
		  var output = "";
		  var len = input.length;
		  for(var i = 0; i < len; i += 3)
		  {
			var triplet = (input.charCodeAt(i) << 16)
						| (i + 1 < len ? input.charCodeAt(i+1) << 8 : 0)
						| (i + 2 < len ? input.charCodeAt(i+2)      : 0);
			for(var j = 0; j < 4; j++)
			{
			  if(i * 8 + j * 6 > input.length * 8) output += b64pad;
			  else output += tab.charAt((triplet >>> 6*(3-j)) & 0x3F);
			}
		  }
		  return output;
		}

		/*
		 * Convert a raw string to an arbitrary string encoding
		 */
		function rstr2any(input, encoding)
		{
		  var divisor = encoding.length;
		  var remainders = Array();
		  var i, q, x, quotient;

		  /* Convert to an array of 16-bit big-endian values, forming the dividend */
		  var dividend = Array(Math.ceil(input.length / 2));
		  for(i = 0; i < dividend.length; i++)
		  {
			dividend[i] = (input.charCodeAt(i * 2) << 8) | input.charCodeAt(i * 2 + 1);
		  }

		  /*
		   * Repeatedly perform a long division. The binary array forms the dividend,
		   * the length of the encoding is the divisor. Once computed, the quotient
		   * forms the dividend for the next step. We stop when the dividend is zero.
		   * All remainders are stored for later use.
		   */
		  while(dividend.length > 0)
		  {
			quotient = Array();
			x = 0;
			for(i = 0; i < dividend.length; i++)
			{
			  x = (x << 16) + dividend[i];
			  q = Math.floor(x / divisor);
			  x -= q * divisor;
			  if(quotient.length > 0 || q > 0)
				quotient[quotient.length] = q;
			}
			remainders[remainders.length] = x;
			dividend = quotient;
		  }

		  /* Convert the remainders to the output string */
		  var output = "";
		  for(i = remainders.length - 1; i >= 0; i--)
			output += encoding.charAt(remainders[i]);

		  /* Append leading zero equivalents */
		  var full_length = Math.ceil(input.length * 8 /
											(Math.log(encoding.length) / Math.log(2)))
		  for(i = output.length; i < full_length; i++)
			output = encoding[0] + output;

		  return output;
		}

		/*
		 * Encode a string as utf-8.
		 * For efficiency, this assumes the input is valid utf-16.
		 */
		function str2rstr_utf8(input)
		{
		  var output = "";
		  var i = -1;
		  var x, y;

		  while(++i < input.length)
		  {
			/* Decode utf-16 surrogate pairs */
			x = input.charCodeAt(i);
			y = i + 1 < input.length ? input.charCodeAt(i + 1) : 0;
			if(0xD800 <= x && x <= 0xDBFF && 0xDC00 <= y && y <= 0xDFFF)
			{
			  x = 0x10000 + ((x & 0x03FF) << 10) + (y & 0x03FF);
			  i++;
			}

			/* Encode output as utf-8 */
			if(x <= 0x7F)
			  output += String.fromCharCode(x);
			else if(x <= 0x7FF)
			  output += String.fromCharCode(0xC0 | ((x >>> 6 ) & 0x1F),
											0x80 | ( x         & 0x3F));
			else if(x <= 0xFFFF)
			  output += String.fromCharCode(0xE0 | ((x >>> 12) & 0x0F),
											0x80 | ((x >>> 6 ) & 0x3F),
											0x80 | ( x         & 0x3F));
			else if(x <= 0x1FFFFF)
			  output += String.fromCharCode(0xF0 | ((x >>> 18) & 0x07),
											0x80 | ((x >>> 12) & 0x3F),
											0x80 | ((x >>> 6 ) & 0x3F),
											0x80 | ( x         & 0x3F));
		  }
		  return output;
		}

		/*
		 * Encode a string as utf-16
		 */
		function str2rstr_utf16le(input)
		{
		  var output = "";
		  for(var i = 0; i < input.length; i++)
			output += String.fromCharCode( input.charCodeAt(i)        & 0xFF,
										  (input.charCodeAt(i) >>> 8) & 0xFF);
		  return output;
		}

		function str2rstr_utf16be(input)
		{
		  var output = "";
		  for(var i = 0; i < input.length; i++)
			output += String.fromCharCode((input.charCodeAt(i) >>> 8) & 0xFF,
										   input.charCodeAt(i)        & 0xFF);
		  return output;
		}

		/*
		 * Convert a raw string to an array of big-endian words
		 * Characters >255 have their high-byte silently ignored.
		 */
		function rstr2binb(input)
		{
		  var output = Array(input.length >> 2);
		  for(var i = 0; i < output.length; i++)
			output[i] = 0;
		  for(var i = 0; i < input.length * 8; i += 8)
			output[i>>5] |= (input.charCodeAt(i / 8) & 0xFF) << (24 - i % 32);
		  return output;
		}

		/*
		 * Convert an array of big-endian words to a string
		 */
		function binb2rstr(input)
		{
		  var output = "";
		  for(var i = 0; i < input.length * 32; i += 8)
			output += String.fromCharCode((input[i>>5] >>> (24 - i % 32)) & 0xFF);
		  return output;
		}

		/*
		 * Calculate the SHA-1 of an array of big-endian words, and a bit length
		 */
		function binb_sha1(x, len)
		{
		  /* append padding */
		  x[len >> 5] |= 0x80 << (24 - len % 32);
		  x[((len + 64 >> 9) << 4) + 15] = len;

		  var w = Array(80);
		  var a =  1732584193;
		  var b = -271733879;
		  var c = -1732584194;
		  var d =  271733878;
		  var e = -1009589776;

		  for(var i = 0; i < x.length; i += 16)
		  {
			var olda = a;
			var oldb = b;
			var oldc = c;
			var oldd = d;
			var olde = e;

			for(var j = 0; j < 80; j++)
			{
			  if(j < 16) w[j] = x[i + j];
			  else w[j] = bit_rol(w[j-3] ^ w[j-8] ^ w[j-14] ^ w[j-16], 1);
			  var t = safe_add(safe_add(bit_rol(a, 5), sha1_ft(j, b, c, d)),
							   safe_add(safe_add(e, w[j]), sha1_kt(j)));
			  e = d;
			  d = c;
			  c = bit_rol(b, 30);
			  b = a;
			  a = t;
			}

			a = safe_add(a, olda);
			b = safe_add(b, oldb);
			c = safe_add(c, oldc);
			d = safe_add(d, oldd);
			e = safe_add(e, olde);
		  }
		  return Array(a, b, c, d, e);

		}

		/*
		 * Perform the appropriate triplet combination function for the current
		 * iteration
		 */
		function sha1_ft(t, b, c, d)
		{
		  if(t < 20) return (b & c) | ((~b) & d);
		  if(t < 40) return b ^ c ^ d;
		  if(t < 60) return (b & c) | (b & d) | (c & d);
		  return b ^ c ^ d;
		}

		/*
		 * Determine the appropriate additive constant for the current iteration
		 */
		function sha1_kt(t)
		{
		  return (t < 20) ?  1518500249 : (t < 40) ?  1859775393 :
				 (t < 60) ? -1894007588 : -899497514;
		}

		/*
		 * Add integers, wrapping at 2^32. This uses 16-bit operations internally
		 * to work around bugs in some JS interpreters.
		 */
		function safe_add(x, y)
		{
		  var lsw = (x & 0xFFFF) + (y & 0xFFFF);
		  var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
		  return (msw << 16) | (lsw & 0xFFFF);
		}

		/*
		 * Bitwise rotate a 32-bit number to the left.
		 */
		function bit_rol(num, cnt)
		{
		  return (num << cnt) | (num >>> (32 - cnt));
		}
	}

	/**
	 *	@func lzw压缩/解压数据算法
	 *	@param string s 压缩/解压/字符串
	 *	@reuturn binary string 返回压缩/解压二进制字符串
	 */
	PHP.lzw = function(){

		/**
		 *	@func  以unicode码编写
		 *	@param string str 要编码的字符串
		 *	@return 返回编码后的数据
		 */
		function encode(str){//仅支持unicode码
			var result = '';
			for(var n=0; n<str.length; n++){
				var c = str.charCodeAt(n);
				if(c < 128){//ASCII码(二进制7内处理)
					result += String.fromCharCode(c);
				}else if(c>127 && c<2048){//(二进制7~12之间处理)
					result += String.fromCharCode( (c >> 6) | 192 );//192 (11000000)
					result += String.fromCharCode( (c & 63) | 128 );//128 (10000000)
				}else{//(二进制12~20之间处理)
					result += String.fromCharCode( (c>>12) | 224 ); //224 (11100000)
					result += String.fromCharCode( ((c >> 6) & 63) | 128);
					result += String.fromCharCode( (c & 63) | 128);
				}
			}
			return result;
		}

		/**
		 *	@func 解压
		 *	@param string str 要解压的字符串
		 *	@return string result 返回解压后的字符串
		 */
		function decode(str){
			var result = '';
			var i = 0;
			var c1 = 0;
			var c2 = 0;
			var c3 = 0;
			while(i < str.length){
				c1 = str.charCodeAt(i);
				if(c1 < 128){////ASCII码(二进制7内处理)
					result += String.fromCharCode(c1);
					i++;
				}else if(c1 >191 && c1 < 224){//(二进制7~12之间处理)
					c2 = str.charCodeAt(i+1);
					result += String.fromCharCode(((c1 & 31) << 6) | (c2 & 63));
					i+=2;
				}else{//(二进制12~20之间处理)
					c2 = str.charCodeAt(i + 1);
					c3 = str.charCodeAt(i + 2);
					result += String.fromCharCode(((c1 & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
					i += 3;
				}
			}
			return result;
		}


		/**
		 * @func lzw 压缩算法
		 * @param string str 要压缩的字符串
		 * @return string 返回压缩的字符串 
		 */
		this.encode = function(str){
			var str = encode(str);//转码
			var dic = new Array();//基础字典
			var chars = 256;//字符长度
			for(var i=0; i<chars; i++){
				dic[String(i)] = i;
			}
			//console.log(dic);
			var splited = new Array();//分割
			splited = str.split('');//分割字符串
			var buffer = new Array();//缓存数值
			var xstr = '';
			var result = new String('');//结果
			var size = splited.length;//长度
			for(var i=0; i<=size; i++){
				current = new String(splited[i]);
				xstr = (buffer.length == 0) ?
					   String(current.charCodeAt(0)) : (buffer.join('-')
					   + '-' + String(current.charCodeAt(0)));
				//console.log(xstr);
				if(dic[xstr] !== undefined){
					buffer.push(current.charCodeAt(0));
				}else{
					result += String.fromCharCode(dic[buffer.join('-')]);
					dic[xstr] = chars;
					chars++;
					buffer = new Array();
					buffer.push(current.charCodeAt(0));
				}//console.log('1' + result);
			}
			return result;		
		}

		/**
		 *	@func lzw解压
		 *	@param string str 要解压的字符串
		 *	@return string 返回解压后的字符串
		 */
		this.decode = function(str){
			var dic = new Array();//基础字典
			var chars = 256;//字符长度
			for(var i=0; i<chars; i++){
				dic[i] = String.fromCharCode(i);
			}
			//默认设置
			var original = new String(str);
			var splited = original.split('');//拆分
			var buffer = new String('');//缓存值
			var result = new String('');//结果值
			var chain = new String('');
			for(var i=0; i<splited.length; i++){
				var code = original.charCodeAt(i);
				var current = dic[code];
				//console.log(code);
				if(buffer == ''){
					buffer = current;
					result += current;
				}else{
					if(code <= 255){
						result += current;
						chain = buffer + current;
						dic[chars] = chain;
						chars++;
						buffer = current;
					}else{
						chain = dic[code];
						if(chain == null){
							chain = buffer + buffer.slice(0, 1);
						}
						result += chain;
						dic[chars] = buffer + chain.slice(0, 1);
						chars++;
						buffer = chain;
					}
				}
			}	
			result = decode(result);
			return result;
		}
		return this;
	}


/**-----------------------------------
 *				杂项处理			*
 -----------------------------------*/
	/**
	 *	sleep函数
	 *	@param func callback 休眠后要执行的函数
	 *	@param int time 要休眠的时间[毫秒][大概的范围内]
	 *	@return 无返回值
	 */
	PHP.sleep = function(callback,time){
		setTimeout(callback,time);
	};

	/**
	 *	eval函数 js自带的
	 *	@param mixed c 要执行的代码
	 */
	PHP.eval = function(c){return eval(c);};
	
	/**
	 *	get_mac函数 | 仅在IE下有效
	 *	return string MAC地址 | 返回false
	 */
	PHP.get_mac = function(){
		return false;
		var browserName=navigator.userAgent.toLowerCase();
    	if(/msie/i.test(browserName) && !/opera/.test(browserName)){
        	//return 'IE';
		}else{
			return false;
		}
		if(objObject.IPEnabled != null && objObject.IPEnabled != "undefined" && objObject.IPEnabled == true) {   
             if(objObject.MACAddress != null && objObject.MACAddress != "undefined" && objObject.DNSServerSearchOrder!=null)   
                 MACAddr = objObject.MACAddress;   
             if(objObject.IPEnabled && objObject.IPAddress(0) != null && objObject.IPAddress(0) != "undefined" && objObject.DNSServerSearchOrder!=null)   
                 IPAddr = objObject.IPAddress(0);   
             if(objObject.DNSHostName != null && objObject.DNSHostName != "undefined")   
                 sDNSName = objObject.DNSHostName;   
         }
		 return MACAddr;
	}
	
	//打印函数
	//PHP.print

/*----------------------------------
 *								   *
 *			各种判断			   *
 *								   *
 *---------------------------------*/



/**----------------------------------
 *									*
 *			当前的运行环境			*
 *									*
 ----------------------------------*/
	//返回当前运行的欢迎
	PHP.$_ENV = function(){
		var env = window.navigator;
		return env;
	};


    //$_GET全局变量
    //PHP.$_GET;
    (function(){
        var result = {};
        var url = window.location.search;
        var len = url.length;
        console.log(url);
        if(url != ''){
            url = url.substr(1,len-1);
            var urls = url.split('&');
            for(var i in urls){
                var tmp_i = urls[i];
                var tmp_is = tmp_i.split('=');
                if(tmp_is.length == 2){
                    result[tmp_is[0]] = tmp_is[1];
                }
            }
        }
        PHP.$_GET = result;
    })();
    


	//查看浏览器上的插件
	PHP.$_Plugin = function(){
		
	};

	//浏览器语言版本
	PHP.language = (navigator.browserLanguage || navigator.language).toLowerCase();

	//运行的平台
	PHP.platform = window.navigator.platform;

	//浏览器名字
	var browserName = function(){
		var browserName=navigator.userAgent.toLowerCase();
    	if(/msie/i.test(browserName) && !/opera/.test(browserName)){
        	return 'IE';
		}else if(/firefox/i.test(browserName)){
			return 'Firefox';
		}else if(/chrome/i.test(browserName) && /webkit/i.test(browserName) && /mozilla/i.test(browserName)){
			return 'Chrome';
		}else if(/opera/i.test(browserName)){
			return 'Opera';
		}else if(/webkit/i.test(browserName) &&!(/chrome/i.test(browserName) && /webkit/i.test(browserName) && /mozilla/i.test(browserName))){
			return 'Safari';
		}else{
			return 'unknown';
		}
	}
	PHP.browserName = browserName();
	
	//@func 浏览器的内核版本
	var browser = {
    	versions:function(){
        	var u = navigator.userAgent, app = navigator.appVersion;
        	return {//移动终端浏览器版本信息
            	trident: u.indexOf('Trident') > -1, //IE内核 
            	presto: u.indexOf('Presto') > -1, //opera内核
            	webkit: u.indexOf('AppleWebKit') > -1, //苹果、谷歌内核
            	gecko: u.indexOf('Gecko') > -1 && u.indexOf('KHTML') == -1, //火狐内核
            	mobile: !!u.match(/AppleWebKit.*Mobile.*/)||!!u.match(/AppleWebKit/), //是否为移动终端
        		ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), //ios终端
            	android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1, //android终端或者uc浏览器
            	iPhone: u.indexOf('iPhone') > -1, //是否为iPhone或者QQHD浏览器
            	iPad: u.indexOf('iPad') > -1, //是否iPad
            	webApp: u.indexOf('Safari') == -1 //是否web应该程序，没有头部与底部
        	};
    	}()	
	};
	PHP.kernel = browser;

	/**
	 * @func 浏览器运行的环境
	 * @return 返回一个数据
	 * ah:浏览者的屏幕高度
	 * aw:浏览者的屏幕宽度
	 * wh:屏幕的可用高度
	 * ww:屏幕的可用高度
	 * h:浏览器可用高度
	 * w:浏览器可用宽度
	 */
	PHP.screen = function(){
		var s = screen;
		var w = window;
		//console.log(s);
		//console.log(window.outerHeight);
		return [s.availHeight,s.availWidth,
			   w.outerHeight,w.outerWidth,
			   s.height,s.width];
	}

	//cookie是否支持;
	PHP.is_cookie = window.navigator.cookieEnabled;
	
	//java是否支持;
	PHP.is_java = window.navigator.javaEnabled();


/*-----------------------------------*
 *									 *
 *				测试方法			 *
 *									 *
 *-----------------------------------*/
	
	/**
	 *	@function 方法基准测试函数
	 *	@param callback 回调函数
	 *	@param times	循环次数
	 */
	
	PHP.benchmark = function(callback, times){
		if(typeof callback != 'function'){//回调函数判断
			if(this.browserName != 'IE')
				console.log('not function');
			else
				alert('not function');
		}
		if(typeof times != 'number'){
			if(this.browserName != 'IE')
				console.log('not number');
			else
				alert('not number');
		}
		var start = (new Date()).getTime();
		//循环执行
		for(var i=0; i<times; i++){
			callback();	
		}
		var end = (new Date()).getTime();
		var ResultStr = 'benchmark: time:'+(end-start)+'ms, times:'+times+' times;'+"\r\n";
		ResultStr += (end-start)/times+ 'ms/times'+"\r\n";
		if(this.browserName != 'IE')
			console.log(ResultStr);
		else
			return ResultStr;
	}


/*-----------------------------------*
 *									 *
 *			 JS DOM 方法		     *
 *			 						 *
 ------------------------------------*/

	/**
	 *	@func 监听事件
	 *	比较通用型的事件监听
 	 *	兼容IE5.0+IE6.0和 火狐
 	 *	@param mixed elm  如 document.getElementById('body');
 	 *	@param string evType 事件的类型
 	 *	@param function fn 执行的方法
 	 *	@param boolean useCapture 是否捕捉 一般为false
	 */
	PHP.listen = function(elm, evType, fn, useCapture){
		console.log(elm, evType, fn);
		if(elm.addEventListener){
			elm.addEventListener(evType, fn, useCapture);
			return true;
		}else if(elm.attachEvent){
			var r = elm.attachEvent('on'+evType, fn);
		}else{
			elm['on'+evType] = fn;
		}
		return this;
	};

	/**
	 *	@func 页面加载完后执行任务
	 *	@param function fn 执行的方法
	 */
	PHP.onload = function(fn){
		if(typeof fn == 'function'){
			window.onload = fn;	
		}
	};

	/**
	 *	@func 让PHP关键字成为全局函数
	 *	在window的环境中,引用本JS文件后,可以使用本类中的所有方法
	 */
	window.PHP=PHP;
})();
