var Index = function () {
    return {
        initLayerSlider: function () {
            $('#layerslider').layerSlider({
                skin : 'fullwidth',
                thumbnailNavigation : 'hover',
                hoverPrevNext : false,
                responsive : false,
                responsiveUnder : 960,
                sublayerContainer : 960
            });
        }
    };
}();
var App = function () {

     // IE mode
    var isRTL = false;
    var isIE8 = false;
    var isIE9 = false;
    var isIE10 = false;

    var responsive = true;

    var responsiveHandlers = [];

    var handleInit = function() {

        if ($('body').css('direction') === 'rtl') {
            isRTL = true;
        }

        isIE8 = !! navigator.userAgent.match(/MSIE 8.0/);
        isIE9 = !! navigator.userAgent.match(/MSIE 9.0/);
        isIE10 = !! navigator.userAgent.match(/MSIE 10.0/);
        
        if (isIE10) {
            jQuery('html').addClass('ie10'); // detect IE10 version
        }
    }

    // runs callback functions set by App.addResponsiveHandler().
    var runResponsiveHandlers = function () {
        // reinitialize other subscribed elements
        for (var i in responsiveHandlers) {
            var each = responsiveHandlers[i];
            each.call();
        }
    }

    // handle the layout reinitialization on window resize
    var handleResponsiveOnResize = function () {
        var resize;
        if (isIE8) {
            var currheight;
            $(window).resize(function () {
                if (currheight == document.documentElement.clientHeight) {
                    return; //quite event since only body resized not window.
                }
                if (resize) {
                    clearTimeout(resize);
                }
                resize = setTimeout(function () {
                    runResponsiveHandlers();
                }, 50); // wait 50ms until window resize finishes.                
                currheight = document.documentElement.clientHeight; // store last body client height
            });
        } else {
            $(window).resize(function () {
                if (resize) {
                    clearTimeout(resize);
                }
                resize = setTimeout(function () {
                    runResponsiveHandlers();
                }, 50); // wait 50ms until window resize finishes.
            });
        }
    }

    var handleIEFixes = function() {
        //fix html5 placeholder attribute for ie7 & ie8
        if (isIE8 || isIE9) { // ie8 & ie9
            // this is html5 placeholder fix for inputs, inputs with placeholder-no-fix class will be skipped(e.g: we need this for password fields)
            jQuery('input[placeholder]:not(.placeholder-no-fix), textarea[placeholder]:not(.placeholder-no-fix)').each(function () {

                var input = jQuery(this);

                if (input.val() == '' && input.attr("placeholder") != '') {
                    input.addClass("placeholder").val(input.attr('placeholder'));
                }

                input.focus(function () {
                    if (input.val() == input.attr('placeholder')) {
                        input.val('');
                    }
                });

                input.blur(function () {
                    if (input.val() == '' || input.val() == input.attr('placeholder')) {
                        input.val(input.attr('placeholder'));
                    }
                });
            });
        }
    }

    // Handles scrollable contents using jQuery SlimScroll plugin.
    var handleScrollers = function () {
        $('.scroller').each(function () {
            var height;
            if ($(this).attr("data-height")) {
                height = $(this).attr("data-height");
            } else {
                height = $(this).css('height');
            }
            $(this).slimScroll({
                allowPageScroll: true, // allow page scroll when the element scroll is ended
                size: '7px',
                color: ($(this).attr("data-handle-color")  ? $(this).attr("data-handle-color") : '#bbb'),
                railColor: ($(this).attr("data-rail-color")  ? $(this).attr("data-rail-color") : '#eaeaea'),
                position: isRTL ? 'left' : 'right',
                height: height,
                alwaysVisible: ($(this).attr("data-always-visible") == "1" ? true : false),
                railVisible: ($(this).attr("data-rail-visible") == "1" ? true : false),
                disableFadeOut: true
            });
        });
    }

    var handleSearch = function() {    
        $('.search-btn').click(function () {            
            if($('.search-btn').hasClass('show-search-icon')){
                if ($(window).width()>767) {
                    $('.search-box').fadeOut(300);
                } else {
                    $('.search-box').fadeOut(0);
                }
                $('.search-btn').removeClass('show-search-icon');
            } else {
                if ($(window).width()>767) {
                    $('.search-box').fadeIn(300);
                } else {
                    $('.search-box').fadeIn(0);
                }
                $('.search-btn').addClass('show-search-icon');
            } 
        }); 
    }

    var handleMenu = function() {
        $(".header .navbar-toggle").click(function () {
            if ($(".header .navbar-collapse").hasClass("open")) {
                $(".header .navbar-collapse").slideDown(300)
                .removeClass("open");
            } else {             
                $(".header .navbar-collapse").slideDown(300)
                .addClass("open");
            }
        });
    }

    var handleSidebarMenu = function () {
        $(".sidebar .dropdown a").click(function () {
            if ($(this).hasClass("collapsed") == false) {
                $(this).addClass("collapsed");
                $(this).siblings(".dropdown-menu").slideDown(300);
            } else {
                $(this).removeClass("collapsed");
                $(this).siblings(".dropdown-menu").slideUp(300);
            }
        });
    }

    function handleDifInits() { 
        $(".header .navbar-toggle span:nth-child(2)").addClass("short-icon-bar");
        $(".header .navbar-toggle span:nth-child(4)").addClass("short-icon-bar");
    }

    function handleUniform() {
        if (!jQuery().uniform) {
            return;
        }
        var test = $("input[type=checkbox]:not(.toggle), input[type=radio]:not(.toggle, .star)");
        if (test.size() > 0) {
            test.each(function () {
                    if ($(this).parents(".checker").size() == 0) {
                        $(this).show();
                        $(this).uniform();
                    }
                });
        }
    }

    var handleFancybox = function () {
        jQuery(".fancybox-fast-view").fancybox();

        if (!jQuery.fancybox) {
            return;
        }

        if (jQuery(".fancybox-button").size() > 0) {            
            jQuery(".fancybox-button").fancybox({
                groupAttr: 'data-rel',
                prevEffect: 'none',
                nextEffect: 'none',
                closeBtn: true,
                helpers: {
                    title: {
                        type: 'inside'
                    }
                }
            });

            $('.fancybox-video').fancybox({
                type: 'iframe'
            });
        }
    }

    // Handles Bootstrap Accordions.
    var handleAccordions = function () {
       
        jQuery('body').on('shown.bs.collapse', '.accordion.scrollable', function (e) {
            App.scrollTo($(e.target), -100);
        });
        
    }

    // Handles Bootstrap Tabs.
    var handleTabs = function () {
        // fix content height on tab click
        $('body').on('shown.bs.tab', '.nav.nav-tabs', function () {
            handleSidebarAndContentHeight();
        });

        //activate tab if tab id provided in the URL
        if (location.hash) {
            var tabid = location.hash.substr(1);
            $('a[href="#' + tabid + '"]').click();
        }
    }
	
    return {
        init: function () {
            // init core variables
            handleInit();
            handleResponsiveOnResize();
            handleIEFixes();
            handleSearch();
            handleFancybox();
            handleDifInits();
            handleSidebarMenu();
            handleAccordions();
            handleMenu();
            handleScrollers();

            this.addResponsiveHandler(function(){ 
                App.initBxSlider(true);
            });
        },

        initUniform: function (els) {
            if (els) {
                jQuery(els).each(function () {
                        if ($(this).parents(".checker").size() == 0) {
                            $(this).show();
                            $(this).uniform();
                        }
                    });
            } else {
                handleUniform();
            }
        },

        initTouchspin: function () {
            $(".product-quantity .form-control").TouchSpin({
                buttondown_class: "btn quantity-down",
                buttonup_class: "btn quantity-up"
            });
            $(".quantity-down").html("<i class='fa fa-angle-down'></i>");
            $(".quantity-up").html("<i class='fa fa-angle-up'></i>");
        },

        initBxSlider: function (reload) {
            $('.bxslider').each(function(){
                var width = $(window).width();

                var slides; 
                var slideMargin = parseInt($(this).attr("data-slide-margin"));
                var slideContainerWidth = $(this).closest('.bxslider-wrapper').width();
                var slideWidth;

                if (width <= 480) {
                    slides = $(this).attr("data-slides-phone");
                } else if (width > 480 && width <= 992) {
                    slides = $(this).attr("data-slides-tablet");
                } else {
                    slides = $(this).attr("data-slides-desktop");
                }

                slides = parseInt(slides);

                slideWidth = slideContainerWidth / slides;


                if (reload === true) {
                    if (!$(this).data("bxslider")) {
                        return;
                    }
                    $(this).data("bxslider").reloadSlider({
                        minSlides: slides,
                        maxSlides: slides,
                        slideWidth: slideWidth,
                        slideMargin: slideMargin,
                        moveSlides:5,
                        responsive:true
                    });
                } else {
                    //alert(2);
                    var slider = $(this).bxSlider({
                        minSlides: slides,
                        maxSlides: slides,
                        slideWidth: slideWidth,
                        slideMargin: slideMargin,   
                        moveSlides:5,
                        responsive:true
                    });
                    $(this).data("bxslider", slider);
                }
            });       
        },

        initImageZoom: function (callback) {
            $('.product-main-image').zoom({"url": $('.product-main-image img').attr('data-BigImgSrc'), "callback":callback});
        },

        initSliderRange: function () {
            $( "#slider-range" ).slider({
              range: true,
              min: 0,
              max: 500,
              values: [ 50, 250 ],
              slide: function( event, ui ) {
                $( "#amount" ).val( "$" + ui.values[ 0 ] + " - $" + ui.values[ 1 ] );
              }
            });
            $( "#amount" ).val( "$" + $( "#slider-range" ).slider( "values", 0 ) +
            " - $" + $( "#slider-range" ).slider( "values", 1 ) );
        },

        // wrapper function to scroll(focus) to an element
        scrollTo: function (el, offeset) {
            var pos = (el && el.size() > 0) ? el.offset().top : 0;
            if (el) {
                if ($('body').hasClass('page-header-fixed')) {
                    pos = pos - $('.header').height(); 
                }            
                pos = pos + (offeset ? offeset : -1 * el.height());
            }

            jQuery('html,body').animate({
                scrollTop: pos
            }, 'slow');
        },

        //public function to add callback a function which will be called on window resize
        addResponsiveHandler: function (func) {
            responsiveHandlers.push(func);
        },

        scrollTop: function () {
            App.scrollTo();
        },

        gridOption1: function () {
            $(function(){
                $('.grid-v1').mixitup();
            });    
        }

    };
}();
/**
 * 页面数据处理
 */
jQuery(document).ready(function(){
	if(typeof(pageScript)!='undefined' && pageScript){
		try{
			for(var k in pageScript){
				if( typeof(apps[pageScript[k]])=='function' ){
					apps[pageScript[k]]();
				}
			}
		}catch(e){
			console.log(e);
		}
	}
});
var apps = {};
apps['product-other-images'] = function(){
	jQuery('div.product-other-images a').bind('hover click', function(){
		jQuery('div.product-other-images a').removeClass('active');
		jQuery(this).addClass('active');
		jQuery('div.product-main-image img').attr('src', jQuery(this).find('img:eq(0)').attr('src')).attr('data-BigImgSrc', jQuery(this).find('img:eq(0)').attr('src'));
	});
	jQuery('div.product-other-images a').bind('mouseleave', function(){
		App.initImageZoom(function(){while(jQuery('img.zoomImg').length>1){jQuery('img.zoomImg:first').remove();}});
	});
};
apps['product_pop_up'] = function (o){
	var product_data = JSON.parse(jQuery(o).attr('product-data'));
	console.log(product_data);
	jQuery('#product-pop-up div.product-main-image img').attr('src', product_data['image'][0]).attr('alt', product_data['title']);
	jQuery('#product-pop-up div.product-other-images').html('');
	for(var k in product_data['image']){
		jQuery('#product-pop-up div.product-other-images').append('<a href="javascript:void(0);"><img alt="'+product_data['title']+'" src="'+product_data['image'][k]+'"></a>');
	}
	jQuery('#product-pop-up div.product-other-images a:eq(0)').addClass('active');
	jQuery('#product-pop-up h1.product-title').html(product_data['title']);
	jQuery('#product-pop-up .price strong .money').html(product_data['price']);
	jQuery('#product-pop-up .price em .money').html(product_data['price']);
	jQuery('#product-pop-up .description p').html(product_data['description']);
	jQuery('#product-pop-up a.more-details').attr('href',product_data['product_url']);
	apps['product-other-images']();
	App.initImageZoom(function(){while(jQuery('img.zoomImg').length>1){jQuery('img.zoomImg:first').remove();}});
};
apps['member-login'] = function(o){
	var email = jQuery('#email').val();
	var captcha = jQuery('#captcha').val();
	if( email && ecms.isEmail(email) && captcha ){
		ecms.ajaxForm(o, function(d){
			if( typeof(d.status)!='undefined' && d.status=='yes' ){
				window.location.href = 'member-account.html';
			}else{
				alert('Login failed, please try again.');
			}
		}, false);
	}
};
apps['member-exit'] = function(){
	ecms._ajax(location.href, 'act=member_exit&ajax=yes', 'GET', function(d){
		window.location.href = 'index.html';
	}, false);
};
apps['get-mail-captcha'] = function(email){
	if( email && ecms.isEmail(email) ){
		ecms._ajax(location.href, 'act=mail_captcha&ajax=yes&email='+email, 'GET', ecms.callback_ajax, false);
	}
};
apps['member-msg-submit'] = function(d){
	if( typeof(d.status)!='undefined' ){
		if( d.status=='yes' ){
			alert('success');
			window.location.reload();
		}else{
			alert('fail, please check your input...');
		}
	}
};
apps['member-msg-edit'] = function(msg_id){
	var em = jQuery('.data-list-'+msg_id);
	var o = JSON.parse(em.attr('data-json'));
	if( em.length>0 ){
		jQuery('input[name="act"]').val("msg_save");
		jQuery('input[name="msg_id"]').val(msg_id);
		jQuery('input[name="title"]').attr('readonly', 'readonly').val(o.title);
		jQuery('textarea[name="content"]').val(o.content);
		jQuery('input[name="id"]').val('0');
	}
};
apps['member-msg-reply'] = function(msg_id){
	var em = jQuery('.data-list-'+msg_id);
	var o = JSON.parse(em.attr('data-json'));
	if( em.length>0 ){
		jQuery('input[name="act"]').val("msg_reply_save");
		jQuery('input[name="msg_id"]').val(msg_id);
		jQuery('input[name="title"]').attr('readonly', 'readonly').val(o.title);
		jQuery('textarea[name="content"]').val('');
		jQuery('input[name="id"]').val('0');
	}
};
apps['member-msg-reset'] = function(){
	jQuery('input[name="act"]').val('');
	jQuery('input[name="msg_id"]').val('0');
	jQuery('input[name="title"]').removeAttr('readonly').val('');
	jQuery('textarea[name="content"]').val('');
	jQuery('input[name="id"]').val('0');
};
apps['cart-render'] = function(cart_data){
	if( cart_data ){
		jQuery('.cart-info-count').html(cart_data.numbers+' 件');
		jQuery('.cart-info-value').html('$'+cart_data.money);
		jQuery('ul.cart-content-list').html(js_template(jQuery('script.cart-content-list').html(), {'cart':cart_data}));
		jQuery('ul.cart-content-list').css({"height":(cart_data['numbers']*65<250?cart_data['numbers']*65:250)+'px'});
		jQuery('div.slimScrollDiv').css({"height":(cart_data['numbers']*65<250?cart_data['numbers']*65:250)+'px'});
		jQuery('#cart-pagedata').html(js_template(jQuery('#cart-pagedata-tpl').html(), cart_data));
		setTimeout(function(){
			App.initTouchspin();
			apps['cart-pagedata']();
		}, 10);
	}
};
apps['cart-load'] = function(){
	ecms._ajax(location.href, 'act=cart&ajax=yes', 'POST', function(d){
		apps['cart-render'](d.data);
	}, true);
};
apps['cart-add'] = function(product_id, numbers, exts){
	ecms._ajax(location.href, 'act=cart&ajax=yes&product_id='+product_id+'&numbers='+numbers+'&exts='+exts, 'POST', function(d){
		apps['cart-render'](d.data);
	}, true);
};
apps['cart-update'] = function(cart_id, numbers){
	ecms._ajax(location.href, 'act=cart&ajax=yes&cart_id='+cart_id+'&numbers='+numbers, 'POST', function(d){
		apps['cart-render'](d.data);
	}, false);
};
apps['cart-del'] = function(cart_id){
	ecms._ajax(location.href, 'act=cart&ajax=yes&cart_id='+cart_id, 'POST', function(d){
		apps['cart-render'](d.data);
	}, true);
};
apps['cart-pagedata'] = function(){
	jQuery('.product-quantity .quantity-up,.product-quantity .quantity-down').click(function(){
		var numbers = parseInt(jQuery(this).parents('.product-quantity').find('input.product-quantity-val').val());
		if( numbers>0 ){
			apps['cart-update'](
				jQuery(this).parents('.product-quantity').find('input.product-quantity-val').attr('data-id'),
				jQuery(this).parents('.product-quantity').find('input.product-quantity-val').val()
			);
		}
	});
	jQuery('input.product-quantity-val').bind('blur', function(){
		var numbers = parseInt(jQuery(this).parents('.product-quantity').find('input.product-quantity-val').val());
		if( numbers>0 && numbers!=jQuery(this).attr('data-old-numbers') ){
			apps['cart-update'](
				jQuery(this).parents('.product-quantity').find('input.product-quantity-val').attr('data-id'),
				jQuery(this).parents('.product-quantity').find('input.product-quantity-val').val()
			);
		}
	});
};
apps['orders-add'] = function(d){
	if( typeof(d.data)!='undefined' ){
		if( typeof(d.data.orders_id)!='undefined' && d.data.orders_id>0 ){
			window.top.location.href = 'shopping-checkout.html';
			return true;
		}
	}
	alert('提交失败');
	return false;
};
apps['orders-checkout'] = function(d){
	if( typeof(d.status)!='undefined' && d.status=='yes' ){
		if( typeof(d.data.payment_url)!='undefined' && d.data.payment_url ){
			window.top.location.href = d.data.payment_url;
			return true;
		}
	}
	alert('提交失败');
	return false;
};
apps['address_edit'] = function(id){
	ecms._ajax(location.href, 'act=address_info&id='+id,'GET', function(d){
		if( typeof(d.data.id)!='undefined' && d.data.id==id ){
			jQuery('input[name="id"]').val(d.data['id']);
			jQuery('input[name="postalCode"]').val(d.data['postalCode']);
			jQuery('input[name="phone"]').val(d.data['phone']);
			jQuery('input[name="username"]').val(d.data['username']);
			//jQuery('select[name="countryCode"][value="'+d.data['countryCode']+'"]').prop('selected', 'selected');
			jQuery('select[name="countryCode"]').val(d.data['countryCode']);
			jQuery('input[name="state"]').val(d.data['state']);
			jQuery('input[name="city"]').val(d.data['city']);
			jQuery('input[name="line1"]').val(d.data['line1']);
			jQuery('input[name="line2"]').val(d.data['line2']);
			jQuery('select[name="is_default"]').val(d.data['is_default']);
		}
	}, false);
};
apps['address_edit_callback'] = function(d){
	if( typeof(d.message)!='undefined' && d.message.length>0 ){
		alert(d.message);
	}
	if( typeof(d.status)!='undefined' && d.status=='yes' ){
		window.top.location.href = location.href;
	}
};
