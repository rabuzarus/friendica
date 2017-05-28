
/**
 * @file view/theme/frio/js/cover_photo_widget.js
 * @brief This file contains the js code for the cover_photo_widget.tpl
 */

var coverSlid = false;

$(document).ready(function() {
	// Handle the cover photo on profile pages (we show the cover photo only
	// on screens that have a minimal width of 768px)
	if ($("#cover-photo").length && window.matchMedia("(min-width: 768px)").matches) {
		var navCoverElm = $("#nav-cover");
		var secondNavElm = $("#topbar-second");

		// When the page does load, the cover is hidden (This was done
		// to prevent glitches which would result of the fact that 
		// important css is only added through this js file.
		// Now we remove the hidden class to make the cover visible
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
		$(document).on("click", slideUpCover);

		// Add a ScrollSpy to the cover element
		$("#nav-cover").scrollspy({
			// Specify the scrollspy area.
			min: navCoverElm.position().top - 50, // - 50px is the heigh of the first topbar
			max: navCoverElm.position().top + navCoverElm.height() - 50,
			mode: 'vertical',
			onEnter: function (element) {
				secondNavElm.addClass("nav-scrollable");
				$("main").css("padding-top", "40px");
				coverSlid = false;

			},
			onLeave: function(element) {
				secondNavElm.removeClass("nav-scrollable");
				$("main").css("padding-top", "90px");
				coverSlid = true;
			}
		});

		// Initialize the prallax effect
		var rellax = new Rellax(".rellax");
	}
});

$(window).scroll(function () {
	if ($('#cover-photo').length && window.matchMedia("(min-width: 768px)").matches && $(window).scrollTop() < ($("#nav-cover").height() - 50)) {
		if (coverSlid) {
			$(window).scrollTop(Math.ceil($('#nav-cover').height()) - 50);
			setTimeout(function(){coverVis = true;}, 1000);
		}
	}
});
$(window).resize(function () {
		// If the screen width becomes smaller than 768px we remove the cover
		if ($("#cover-photo").length && window.matchMedia("(max-width: 767px)").matches) {
			$(window).unbind("scroll.scrollspy");
			$('#nav-cover').remove();
			$("#topbar-second").removeClass("nav-scrollable");
			$("main").css("margin-top", "90px");
			coverSlid = false;
		}
});

function slideUpCover() {
	var secondNavPos = $("#topbar-second").position().top;
	$("html, body").animate({scrollTop: secondNavPos - 50 + "px"});
}
