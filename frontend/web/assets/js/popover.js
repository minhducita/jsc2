var popupover = (function($){
	var defaultConfig  = {
		/*config popup*/
		popupconfig : {
			animation : true,
			container : false,
			content   : "",
			delay     : 0,
			html  	  : false,
			placement : "bottom",
			selector  : false,
			template  : "",
			title	  : "",
			trigger	  : "click",
			viewport  : {selector: "body", padding: 0},
			popup_child:{}
		},
		c_popup:'',
	}
	var popup = {
		Widget : function(options){
			var setting = $.extend({}, defaultConfig, options || {});
			this.set_popup(setting.c_popup,setting.popupconfig,setting.popupconfig.trigger,setting.popupconfig.popup_child);
		},
		set_popup : function(c_popup,config_popup,trigger,config_child) {
			if(!trigger)
				trigger = 'click';
			if(!config_child){
				$(c_popup).popover(config_popup).live(trigger,function(){
					
					popup.button_close_popup(c_popup);
				});
			}else{
				$(c_popup).popover(config_popup).live(trigger,function(){
					$.each(config_child,function(){
						c_popup 	  = this.c_popup;
						config_popup  = this.popupconfig;
						$(c_popup).popover(config_popup).live(trigger,function(){

							popup.button_close_popup(c_popup);
						});
						popup.close_popup_child(c_popup,config_child);
					});
					popup.button_close_popup(c_popup);
				});
			}
			
		},
		close_popup_child : function(c_popup,config_child){
			$(c_popup).click(function(){
				$.each(config_child,function(){
					c_popup_close = this.c_popup;
					if(c_popup != c_popup_close){
						
						$(c_popup_close).popover('hide');  
					}
				});
			});
		},
		button_close_popup:function(c_popup){
			$('.modal-close i').click(function(){
				if(!c_popup){
					c_popup = $(this).parents('.popover').prev().attr('class');
				}
				c_popup = c_popup.replace(' ',".");
				$(c_popup).popover('hide');
			});
		}
	}
	return popup;
})(window.jQuery);