(function(window, angular, undefined) {
'use strict';
    angular.module('app.jsCore', [])
        .directive('navMenu', function() {
            return {
                restrict: 'AC',
                link: function(scope, element, attrs) {
                    var handleSidenarAndContentHeight = function () {
                        var content = $('.page-content');
                        var sidebar = $('.page-sidebar');

                        if (!content.attr("data-height")) {
                            content.attr("data-height", content.height());
                        }

                        if (sidebar.height() > content.height()) {
                            content.css("min-height", sidebar.height() + 120);
                        } else {
                            content.css("min-height", content.attr("data-height"));
                        }
                    };
					element.children('li').click(function(e){
						e.stopPropagation();
						if (jQuery(this).hasClass('active') !== false) {
                            return;
                        }
                        if (jQuery(this).children('a').next().hasClass('sub-menu') === false) {
                            return;
                        }
                        var parent = $(this).parent();
                        parent.children('li.open').children('.sub-menu').slideUp(200);
                        parent.children('li').removeClass('open').removeClass('active');

                        var sub = jQuery(this).children('a').next();
                        jQuery('.arrow', jQuery(this).children('a')).addClass("open");
                        jQuery(this).addClass('active').addClass("open");
                        sub.slideDown(200, function () {
                            handleSidenarAndContentHeight();
                        });
                        e.preventDefault();
						
					});
					$('html').click(function(e){
						element.children('li').removeClass('active');
						element.children('li').removeClass('open');
						element.children('li').find('input[type="text"]').val('');
						var sub = element.children('li').children('a').next();						
						sub.slideUp(200, function () {
                            handleSidenarAndContentHeight();
                        });
					})
                    /*  // Nav Menu hover
						element.children('li').hover(function(e) {
                        if (jQuery(this).hasClass('active') !== false) {
                            return;
                        }
                        if (jQuery(this).children('a').next().hasClass('sub-menu') === false) {
                            return;
                        }
                        var parent = $(this).parent();
                        parent.children('li.open').children('.sub-menu').slideUp(200);
                        parent.children('li').removeClass('open').removeClass('active');

                        var sub = jQuery(this).children('a').next();
                        jQuery('.arrow', jQuery(this).children('a')).addClass("open");
                        jQuery(this).addClass('active').addClass("open");
                        sub.slideDown(200, function () {
                            handleSidenarAndContentHeight();
                        });
                        e.preventDefault();
                    }, function(e) {
                        var sub = jQuery(this).children('a').next();
                        jQuery('.arrow', jQuery(this).children('a')).removeClass("open");
                        jQuery(this).removeClass('open').removeClass('active');
                        jQuery(this).find('input[type="text"]').val('');
                        jQuery(this).find('textarea').val('');
                        sub.slideUp(200, function () {
                            handleSidenarAndContentHeight();
                        });
                        e.preventDefault();
                    });*/
                }
            }
        })
		.directive('showHidePopup',function()
		{
			return{
				restrict:'A',
				link:function(scope,element,attrs){
					var $popupShow = attrs.popupShow;
					var $popupHide = attrs.popupHide;
					element.on('click',function(){
						$("."+$popupShow).show();
						$("."+$popupHide).hide();
					});
				}
			}
		})
        .directive('popPup', ['$modal', '$popover', '$tooltip', 'PATH', '$timeout', function(a, b, c, d, f) {
            return {
                restrict: 'AC',
                link: function(scope, element, attrs) {
                    var popupPath = d.widgets + 'popup/';
                    var openType = angular.isDefined(attrs.openType) ? attrs.openType : null;
                    var options = {
                        templateUrl: popupPath + attrs.templateUrl,
                        show: false,
                        scope: scope
                    };
                    switch (openType) {
                        case 'dialog':
                            var dialog =  a(options);
                            element.on('click', function() {
								//$('.modal-close i').trigger('click');
                                dialog.$promise.then(function(){
									dialog.show();
									$('.closeModal').click(function(){
										popover.hide();
										$("body").removeClass("modal-open").removeClass("modal-with-am-fade").removeAttr("ng-style");
										$(".modal-dialog").hide("normal",function(){
											$(".modal-backdrop").hide();
											$(this).remove();
										});
										
									});
									
								});
                            });
                        break;
                        case 'popover':
                            var popover = b(element, angular.extend(options, {
                                html: true,
                                container:'body',
                                placement: 'bottom',
                                trigger: 'manual',
                                autoClose: true,

                            }));
                            element.on('click', function(e) {
								$('.modal-close').trigger('click');
								var contentPopup = $(".popover").html();
								if(angular.isUndefined(contentPopup)) {
									if(element.attr("class").indexOf("card-menu") != -1){
										f(function(){
										  popover.show();
										}, 700);
										
									}else {
										popover.show();
									}
								} 
								else {
									popover.hide();
								};
								
								/*** Disable show Detail card when click @MINHQUYEN ****/
								if(element.attr("class").indexOf("card-menu") != -1){
									e.stopPropagation();
								};
                            });
                           
						break; 
						case 'popovercalendar':
                            var popover = b(element, angular.extend(options, {
                                html: true,
                                container:'body',
                                placement: 'bottom',
                                trigger: 'manual',
                                autoClose: true,
                            }));
                            element.on('click', function(e) {
								popover.show();	
                            });
						break;
						case 'popoverinput':
							var popover = b(element, angular.extend(options, {
									html: 1,
									container:'body',
									placement: 'bottom',
									trigger: 'manual',
									autoSelect:true,
							}));
                            element.on('click', function(e) {
								var contentPopup = $(".popover.search").html();
								if(angular.isUndefined(contentPopup)) {
									$('body').trigger('click');
									popover.show();
								}								
								e.stopPropagation();
								$(".popover").on('click',function(e){e.stopPropagation()});
								$(".header-search").on('click',function(e){e.stopPropagation()});
								_.defer(function() {
									element.focus();
								})
                            });
							
							$(document.body).on('click', function() {
								popover.hide();
							});
                            break;
                        case 'tool-tip':
                            //$tooltip(element, {title: 'My Title'});
                            break;
                        default:
                            break;
                    }
                }
            }
        }])
        .directive('checkItemChecklist',function(){
            return{
                restrict:'A',
                link:function(scope,element,attrs) {
                    element.on("click",function() {
                        var modelCheck = element.closest('.modal-check');
						var checkItemsCount = modelCheck.find('.list-checklist input[type="checkbox"]').length || 0;
                        var checkItemsCheckedCount = modelCheck.find('.list-checklist input[type="checkbox"]').filter(':checked').length || 0;
                        var percen_itemchecked = checkItemsCount != 0 ? (checkItemsCheckedCount * 100) / checkItemsCount : 0;
                        modelCheck.find('.checklist-progress-bar-current').css('width', percen_itemchecked + "%");
                        modelCheck.find('.checklist-progress-percentage').html(Math.ceil(percen_itemchecked) + "%");
                    });
                }
            }
        })
		.directive('showMenuRight',function(){
			return{
				restrict:'A',
				link:function(scope,element,attrs){
					var menuShow = attrs.menuShow;
					element.on("click",function(){
						jQuery('.board-wrapper').addClass('is-show-menu');
						jQuery('.show_menu_right').hide();
						$(".is-show-menu .menu-right-btnclose").on("click",function(){
							jQuery('.board-wrapper').removeClass('is-show-menu');
							jQuery('.show_menu_right').show();
							if(menuShow)
								jQuery('.selection-list .'+menuShow).trigger('click');
						});
						return false
					});
				}
			}
		})
		.directive('dropdown',function(){
			return{
				restrict:"A",
				link:function(scope,element,attrs){
					var showclass = attrs.showClass; 
					element.on("click",function(e){
						$(".modal-close").trigger("click");
						$(".modal-close i").trigger("click");
						if( $(showclass).css("display") === 'block'){
							$(showclass).hide();
						}else{
							$(showclass).show();
						}
						e.stopPropagation();
						$(showclass).on("click",function(e){
							e.stopPropagation();
						});
						return false;
					});
					$(document).on("click",function(e){
						$(showclass).hide();
					})
				}
			}
		})
		.directive('popupOver',function($http,$compile,$modal){
			return {
				restrict:"A",
				link:function(scope,element,attrs){
					var typePopup 	  	= (attrs.typePopup)?attrs.typePopup:"board";
					var positionShow 	= attrs.positionShow;
					var linkroot  	  	= "/app-clients/widgets/popup/"+typePopup+"/";
					var default_popup   = attrs.popupPage;
					scope.templatepopup = {};
					var popover_content = $('.popover-contents').html();
					if(typeof(popover_content) == 'undefined'){
						if(jQuery.isEmptyObject(scope.templatepopup[default_popup])){
							$http.get(linkroot+default_popup+".tpl.html").success(function(respone){
								var temp = $compile(respone)(scope);
								scope.templatepopup[default_popup] = temp;
								element.html(temp);
							});
						}
					}
					var starttop = 0;
					var positionPopover = function($height,$top){
						$top = parseInt($top.replace("px",""));
						var win_height = $(window).outerHeight();
						if(($height + $top) > win_height){
							starttop = $(".popover").css("top");
							var div_height = win_height - $height;
							$(".popover").css("top",div_height - 50);
						}else if(starttop != 0){
							$(".popover").css("top",starttop);
						}
					}
					scope.getPopupover = function($linkshow){
						if(jQuery.isEmptyObject(scope.templatepopup[$linkshow])){
							$http.get(linkroot+$linkshow+".tpl.html").success(function(respone){
								var temp = respone;
								scope.templatepopup[$linkshow] = temp;
								element.html($compile(scope.templatepopup[$linkshow])(scope));
								positionPopover($('.popover').outerHeight(),$('.popover').css("top"),element);
							});
						}else{
							var temp = $compile(scope.templatepopup[$linkshow])(scope);
							element.html(temp);
							positionPopover($('.popover').outerHeight(),$('.popover').css("top"),element);
						}
						$(".menu-right-btnclose").click(function(){
							$(".header").trigger("click")
						});
					}
				}
			}
			
		})
		.directive('menuRight',function($http,$compile)
		{
			return {
				restrict:"A",
				link:function(scope, element, attrs) 
				{
					var linkroot = "/app-clients/widgets/menu-right/"; // link root all page popup of menu right
					scope.urlMenuright = linkroot+"default.html"; // link include page default
					scope.template = {'default':'default'}; // template default;
					
					var menuright = {
						"changeBackground":	linkroot+"change-background.html",
						"filterCards"	  :	linkroot+"filter-cards.html",
						"powerUps"		  :	linkroot+"power-ups.html",
						"stickers"		  :	linkroot+"stickers.html",
						"more"			  :	linkroot+"more.html",
						"archivedItems"	  :	linkroot+"more/archived-items.html",
						"labels"          :	linkroot+"more/labels.html",
						"setting"		  :	linkroot+"more/setting.html",
						"default"		  :	linkroot+"/default.html",
						"undefined"		  :	linkroot+"/default.html",
						'activity'		  : linkroot+"/activity.html",
					}; // link load all page popup 
					
					$('.backdefault').hide();// hide arrow back;
					scope.getMenuRight = function(menuType,$titleMenu,$link_back) { // event click change template popup 
						$titleMenu = ($titleMenu)?$titleMenu:"Menu";
						$('.menu-right-header > h4').html($titleMenu); // edit title menu right
						if(menuType == 'undefined' || menuType=="default") {
							//hide arrow back;
							$('.backdefault').hide(); // hide arrow back;
						} else {
							// set link arrow back and  show arrow back
							$('.backdefault').show();
							if($link_back) {
								scope.urlBackPage = $link_back;
							}else{
								scope.urlBackPage = 'default';
							}
						}
						if (!angular.isDefined(scope.template[menuType]) && menuType != "default" &&  menuType != "undefined") {
							// load template popup menu right when scope.template[menuType] empty
							$http.get(menuright[menuType]).success(function(respone) {
								scope.template[menuType] = menuType;
								$('.contentPopup').hide();
								var contentPopup  = "<div class='contentPopup' id='popup"+scope.template[menuType]+"'>"+respone+"</div>";
								$('#showMenuright').append($compile(contentPopup)(scope));
							});
						} else {
							// show template is choose.
							$('.contentPopup').hide();
							$('#popup'+scope.template[menuType]).show();
						}
					}
				}
			}
		})
		.directive('showStickers',function(){
			return{
				restrict:"C",
				link:function(scope,element,attrs) {
					$(document).ready(function(){
						var $num_stick = 12;
						var $link_root = "/assets/img/stickers/";
						element.html('');
						for(var $i=1;$i <= $num_stick;$i++){
							element.append("<div class='sticker-select'><img class='sticker-select-image' src='"+$link_root+"/stick"+$i+".png'></div>");
						}
					});
				}
			}
		})
		.directive('showNote',function(){
			return{
				restrict:"C",
				link:function(scope,element,attrs){	
					$('.btn-close-note,.color-note-text').click(function(){
						if($(".color-note-full").is(":visible")){
							$(".color-note-full").hide();
							$(".color-note-text").show();
							$('.color-note').css("background","none");
						}else{
							$(".color-note-full").show();
							$(".color-note-text").hide();
							$('.color-note').css("background","");
						}
					});

				}
			}
		})
		.directive('textareaNewLine',function(){
			return{
				restrict:"A",
				link:function(scope, element,attrs) {
					$(document).ready(function() {
						var s = scope.card.desc;
						if(s) {
							scope.card.desc = s.split("<br/>").join("\n");
							scope.desc  = s.split("\n").join("<br/>");
						}
						
						var valTextarea = element.val();
						//element.val(valTextarea.split("<br/>").join("\n"));
					});
					element.keypress(function(e) {
						if (event.which == 13) {
						  event.preventDefault();
						  var s = $(this).val();
						  if(s) {
							 $(this).val(s+ "\n");
							 scope.desc = s.split("\n").join("<br/>");
						  }
						}
					});
					element.keyup(function() {
						 var s = $(this).val();
						 if(s) {
							scope.desc = s.split("\n").join("<br/>"); 
						 }
					})
				}
			}
		})
		.directive('showArchived',function(){
			return {
				restrict:"C",
				link:function(scope,element,attrs){
					element.click(function(){
						if($(".archive_content_full").is(":visible")){
							$(".archive_content_full").hide();
							$(".archive_content_list").show();
							$(".archive-controls-switch").html("Switch to cards");
						}else{
							$(".archive_content_full").show();
							$(".archive_content_list").hide();
							$(".archive-controls-switch").html("Switch to lists");
						}
					});
				}
			}
		}).directive('changeForm',function(){
			return {
				restrict:"C",
				link:function(scope,element,attrs){
					var classShow = attrs.classShow;
					element.click(function(){
						$(".change-form").parents("form").hide();
						$("form."+classShow).show();
						scope.SignupErrors ={};
						scope.Errors ={};
					});
				}
			}
		}).directive("selectColorLabel",function(){
			return{
				restrict:"C",
				link:function(scope,element,attrs){
					var typeSelect = attrs.typeSelect;
					var	selectedLabel = attrs.selectedLabel;
					element.on("click",function(){
						if(typeSelect){ // selected multi;
							if($(this).find("span.icon-sm").is(":visible")){
								$(this).find("span.icon-sm").remove();
							}else{
								$(this).append("<span class='icon-sm'></span>");
							}
						}else{ //selected one
							$(this).parent().find("span").html("");
							$(this).html("<span class='icon-sm'></span>");
						}
					});
				}
			}
		}).directive('body',function(){
			return{
				restrict:"E",
				link:function(scope,element,attrs){
					var bodyMenuleft = element.attr("class");					
					if(bodyMenuleft.indexOf("body-menu-left") > -1){
						$(window).resize(function(){
							if($(this).width() <= 1000){
								element.addClass("body-menu-left-mobile");
								element.removeClass("body-menu-left");
							}else{
								element.addClass("body-menu-left");
								element.removeClass("body-menu-left-mobile");
							}
						});
					}
				}
			}
		}).directive('showAddList',function() {
			return {
				restrict:'A',
				link:function(scope,element,attrs) {
					var $listid = attrs.listid;
					element.on('click',function(){
						var parentAdd = $("#boardfix").find("#boardList"+$listid);
						parentAdd.find(".list-box-footer a").trigger("click");
						$(".popover").find('.modal-close').trigger("click");
					});
				}
			}
		}).directive('scrollPopover',function() {
			return {
				restrict:'A',
				link:function(scope,element,attrs) {
					_.defer(function(){
						$('.popup-notifi-content').css("height",'auto');
						var heightWindow   = $(window).height()-120;
						var heightPopup	   = $('.popup-notifi-content').height();
						if(heightPopup > heightWindow) {
							$('.popup-notifi-content').css("height",heightWindow-40);
						}
					})
				}
			}
		}).directive('menuProfile', function() {
			return {
				restrict:'C',
				link: function(scope, element, attrs) {
					element.find('li').click(function() {
						$('body').trigger('click');
					});
				}
			}
		}) .directive('scrollbarchange',[ '$rootScope', function(a) {
			return {
				restrict: "C",
				link : function (scope, element, attrs) {
					_.defer(function(){
						element.perfectScrollbar({
							suppressScrollX: true,
							
						});
					});
					
					$(window).resize(function() {
						element.perfectScrollbar('update');
					});
					
					$('div.list-column').resize(function () {
						element.perfectScrollbar('update');
						alert('resize');
					})
					
					$('#board .list-box .list-box-content').resize(function() {
						element.perfectScrollbar('update');
					});
				}
			}
		}]).directive('searchHover',['$rootScope' ,function(a){
			return {
				restrict:"C",
				link : function(scope, element, attrs) {
					element.hover(function() {
						$(this).closest('.search-result-card').addClass('search-result-card-highlight-card');
						$(this).closest('.content').addClass('search-result-card-highlight-section');
					}).mouseout( function() {
						$(this).closest('.search-result-card').removeClass('search-result-card-highlight-card');
						$(this).closest('.content').removeClass('search-result-card-highlight-section');
					});
				}
			}
		}]);
})(window, window.angular);