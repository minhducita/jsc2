$(window).scroll(function(){
	var popover = $(".popover");
	var popover_offset = $('.popover').offset();
	if(popover && popover_offset){
		var scrolltop = $(document).scrollTop()+30;
		var scrollBottom = $(window).scrollTop() + $(window).height() ;
		var popupTop = popover_offset.top;
		var popupBottom = popover_offset.top + popover.height();
		if(scrolltop > popupTop){
			popover.css("top",scrolltop);
		}else if(scrollBottom < popupBottom){
			popover.css("top",scrollBottom - popover.height()-20);
		}
	}
});
$(document).on('click',function(){
	var popover = $(".popover");
	var popover_offset = popover.offset();
	if(popover && popover_offset){
		var scrolltop = $(document).scrollTop();
		var scrollBottom = $(window).scrollTop() + $(window).height();
		var popupBottom = popover_offset.top + popover.height();
		if(scrollBottom < popupBottom){
			$('.popover').css("top",scrollBottom - popover.height()-20);
		}
	}
});

$( window ).resize(function() {
	$('.popup-notifi-content').css("height",'auto');
	if($(".popover.notification").html()) {
		var heightWindow   = $(window).height()-120;
		var popupTop 	   = $(".popover.notification").offset().top;
		var heightPopup	   = $('.popup-notifi-content').height();
		if(heightPopup > heightWindow) {
			$('.popup-notifi-content').css("height",heightWindow-40);
		}
	}
});

$(document).ready(function(){
	$(".search-result-card").hover(function(){
		$("this").addClass("search-result-card-highlight-card");
		$("search-result-card").addClass("search-result-card-highlight-section");                
	});
});