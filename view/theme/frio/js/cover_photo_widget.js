
/**
 * @file view/theme/frio/js/cover_photo_widget.js
 * @brief This file contains the js code for the cover_photo_widget.tpl
 */

var coverSlid = false;

$(document).ready(function() {
	// Handle the cover photo on profile pages (parallax effect)
	if ($("#nav-cover #cover-photo").length) {
		var navCoverElm = $("#nav-cover");
		var secondNavElm = $("#topbar-second");

		navCoverElm.removeClass("hidden");
		$("main").css("margin-top", "0");


		var scrollTop = $(window).scrollTop();
		var offset = navCoverElm.offset().top;
		var coverHeight = navCoverElm.outerHeight(true);

		var coverPos = offset - scrollTop + coverHeight;

		// We add the css for the second navbar according to the position
		// of the cover-photo. If it isn't visible in the window we need
		// a fixed second navbar. If the cover photo is visible the second
		// navbar needs to be scrollable
		if (coverPos >= 50) {
			secondNavElm.addClass("nav-scrollable");
			$("main").css("padding-top", "40px");
			coverSlid = false;
		} else {
			$("main").css("padding-top", "90px");
			coverSlid = true;
		}

		// Slide the cover up if it is clicked
		$(document).on('click', slideUpCover);

		// Add a ScrollSpy to the cover element
		$("#nav-cover").scrollspy({
			min: navCoverElm.position().top - 50, //
			max: navCoverElm.position().top + navCoverElm.height() - 50,
			mode: 'vertical',
			onEnter: function onLeave(element) {
//				$("#nav-cover").removeClass("hidden");
				secondNavElm.addClass("nav-scrollable");
				$("main").css("padding-top", "40px");
//				$("main").addClass("no-margin");
//				$("main").css("margin-top", "0");
				coverSlid = false;
				
			},
			onLeave: function(element) {
//				$("#nav-cover").addClass("hidden");
				secondNavElm.removeClass("nav-scrollable");
				$("main").css("padding-top", "90px");
//				$("main").removeClass("no-margin");
//				$("main").css("margin-top", "90px");
				coverSlid = true;
			}
		});

		// Initialize the prallax effect
		var rellax = new Rellax('.rellax');
	}
});

$(window).scroll(function () {
	if ($(window).scrollTop() < ($('#cover-photo').height() - 50)) {
		if (coverSlid) {
			$(window).scrollTop(Math.ceil($('#cover-photo').height()) - 50);
			setTimeout(function(){ coverVis = true; }, 1000);
		}
	}
});

function slideUpCover() {
	var secondNavPos = $("#topbar-second").position().top;
	$('html, body').animate({scrollTop: secondNavPos - 50 + 'px'});
}
