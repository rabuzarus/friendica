/*! lg-friendica - v0.2 - 2017-01-20
* http://sachinchoolur.github.io/lightGallery
* Copyright (c) 2017 rabuzarus; Licensed GPLv3 */

(function (root, factory) {
	if (typeof define === 'function' && define.amd) {
		// AMD. Register as an anonymous module unless amdModuleId is set
		define(['jquery'], function (a0) {
			return (factory(a0));
		});
	} else if (typeof exports === 'object') {
		// Node. Does not work with strict CommonJS, but
		// only CommonJS-like environments that support module.exports,
		// like Node.
		module.exports = factory(require('jquery'));
	} else {
		factory(jQuery);
	}
}(this, function ($) {

	(function() {

		'use strict';

		var defaults = {
			friendica: true,
			counter: false,
		};

		var Friendica = function(element) {

			this.core = $(element).data('lightGallery');
			this.$el = $(element);

			this.core.s = $.extend({}, defaults, this.core.s);
			if (this.core.s.friendica) {
				this.init();
			}

			return this;
		};

		Friendica.prototype.init = function() {
			var _this = this;
			var commentButton = '<span id="lg-comment" class="lg-icon"><i class="fa fa-commenting"></i></span>';

			_this.core.s.initPage = _this.core.s.page;
			_this.core.s.countvar = _this.core.s.start;

			var cache;
			var isopen = false;
	

			this.core.$outer.find('.lg-toolbar').append(commentButton);
			//this.core.$outer.find('.lg-toolbar').append('<span id="lg-album">' + albumname + '</span>');
			$('.lg-toolbar').append('<div id="lg-album"><span id="lg-albumname">' + albumname + '     </span><span id="lg-counter-current">' + albumname + '</span> (<span id="lg-counter-all">' + this.core.s.total + ')</span></div>');

			_this.commentbox();

			$('#lg-comment').on('click.lg', function(){
				if (isopen === true) {
					isopen = false;
				} else {
					isopen = true;
					_this.loadCommentContent();
				}
				_this.core.$outer.toggleClass('lg-comment-active');
			});

			$('lg-bg-overlay, #lg-closecomment').on('click.lg', function(){
				_this.core.$outer.removeClass('lg-comment-active');
			});

			

//			r = function() {
//				var commentURL = null
//				/// @todo hier muss noch ein if hin
//				commentURL = this.s.dynamicEl[index].link + ' #comment-edit-wrapper-' + this.s.dynamicEl[index].id;
//			}

			$("#lg-commentbox").mousemove(function(event) {
				event.stopPropagation();
			});

			$('#lg-bg-overlay, #lg-closecomment').on('click.lg', function() {
				_this.core.$outer.removeClass("lg-comment-active");
			});


			_this.core.$el.on("onBeforeSlide.lg.tm", function() {
				isopen = false;

				var index = _this.core.index;
				var lglength = _this.core.s.dynamicEl.length

				// Load next content from the friendica instance
				// To limit the ressources we don't load the whole
				// album into the gallery. Instead load every single 
				// photo album page into the gallery. If we are at
				// the end of the present gallery content we will look
				// if more pages are available and add it to the gallery
				if (_this.core.index >= (lglength - 1)) {
					_this.core.s.loadcontent = 'next';
					_this.addFurtherContent();
				}



				// Put some core values into the cache, so we can make
				// use of this in other js files
				
				slideCache.id = _this.core.s.dynamicEl[index].id;
				slideCache.link = _this.core.s.dynamicEl[index].link;
				slideCache.index = _this.core.index;

				_this.rewriteSubHtml();

				// Reset the comment sidebar
				$(".lg-commentloader").removeClass("comment_loaded");
				_this.core.$outer.removeClass('lg-comment-active');
				$('.fb-comments .photo-comment-wrapper').remove();
			});

			_this.core.$el.on("onAfterSlide.lg.tm", function(event) {
				//Friendica().done(r())
				isopen = false;

				_this.core.$outer.removeClass('lg-comment-active');
				$(".lg-commentloader").removeClass("comment_loaded");
				$('.fb-comments .photo-comment-wrapper').remove();
			});
			
			_this.core.$el.on("onSlideItemLoad.lg", function(index) {
				var index = _this.core.index;

				if ((_this.core.s.page !== 1) && (index == 0)) {
					_this.core.s.loadcontent = 'previous';
					_this.addFurtherContent();
				}
				$('#lg-counter-current').text(_this.core.s.countvar + index + 1);

			});

			_this.core.$el.on("onBeforeClose.lg", function() {
				// Clear the slideCache on exit
				slideCache = {};
				_this.core.destroy(true);
			});

//			_this.core.$el.on('onAfterSlide.lg.tm', function(event, prevIndex, index) {
//
//				setTimeout(function() { 
//					$('#lg-share-facebook').attr('href', 'https://www.facebook.com/sharer/sharer.php?u=' + (encodeURIComponent(_this.core.$items.eq(index).attr('data-facebook-share-url') || window.location.href)));
//
//					$('#lg-share-twitter').attr('href', 'https://twitter.com/intent/tweet?text=' + _this.core.$items.eq(index).attr('data-tweet-text') + '&url=' + (encodeURIComponent(_this.core.$items.eq(index).attr('data-twitter-share-url') || window.location.href)));
//
//					$('#lg-share-googleplus').attr('href', 'https://plus.google.com/share?url=' + (encodeURIComponent(_this.core.$items.eq(index).attr('data-googleplus-share-url') || window.location.href)));
//
//					$('#lg-share-pinterest').attr('href', 'http://www.pinterest.com/pin/create/button/?url=' + (encodeURIComponent(_this.core.$items.eq(index).attr('data-pinterest-share-url') || window.location.href)) + '&media=' + encodeURIComponent(_this.core.$items.eq(index).attr('href') || _this.core.$items.eq(index).attr('data-src')) + '&description=' + _this.core.$items.eq(index).attr('data-pinterest-text'));
//
//				}, 100);
//			});
		};


		Friendica.prototype.commentbox = function() {
			var _this = this;

			var commentbox = '<div id="lg-commentbox">' +
						'<div class="lg-commentloader">' +
							'<div class="lg-commentloaderspinner"></div>' +
						'</div>' +
						'<div class="fb-commenthead">' + 
							'<button id="lg-closecomment" type="button" class="close" aria-hidden="true">' +
								'&times;' +
							'</button>' +
							//'<i id="lg-closecomment" class="icon-close"></i>' +
						'</div>' +
						'<div class="fb-comments" data-width="100%"></div>' +
					'</div>';

			_this.core.$outer.find('.lg').append(commentbox);
		};

		Friendica.prototype.loadCommentContent = function() {
			var _this = this;
			var commentURL = null;
			var index = this.core.index;

			/// @todo hier muss noch ein if hin
			commentURL = _this.core.s.dynamicEl[index].link + '?mode=none #photo-comment-wrapper-' + _this.core.s.dynamicEl[index].id;
			$('.fb-comments').load(commentURL, function(responseText, textStatus) {
				if (textStatus === 'success' || textStatus === 'notmodified') {
					$('.lg-commentloader').hide();
					$(".lg-commentloader").addClass("comment_loaded")
				}
			});
		};

		/**
		 *  @brief add sub-html into the slide
		 *  @param {Number} index - index of the slide
		 */
		Friendica.prototype.rewriteSubHtml = function() {
			var index = this.core.index;

			if (this.core.s.dynamic) {
//				this.core.s.dynamicEl[index].subHtml = this.core.s.dynamicEl[index].desc;
				var desc = '<div class="username">' + this.core.$items[index].username + '</div>';
				if (this.core.$items[index].desc) {
					desc += '<div class="photo-desc">'+ this.core.$items[index].desc + '</div>';
				}

				this.core.$items[index].subHtml = desc;
			}
		};

		Friendica.prototype.addFurtherContent = function() {
			var query = this.query();

			var url = this.core.s.albumUrl + query;

			postdata = {
					format: 'json'
				};

			var req = $.ajax({
					url: url,
					data: postdata,
					context: this
				});

			req.success(function(data) {
				if (this.core.s.loadcontent == 'next') {
					this.addNextElements(data);
				} else if (this.core.s.loadcontent == 'previous') {
					this.addPrevElements(data);
				}
			});
	
		};

		Friendica.prototype.addNextElements = function(data) {
			if (typeof data.results !== 'undefined' && data.results.length > 0) {
				// Loop through the results and add its elements to
				// the gallery array
				for (var i = 0; i < data.results.length; i++) {
					this.core.$items.push(data.results[i]);
				}

				this.core.s.total = data.total;
				this.core.s.page = data.page;
	//			this.core.s.start = data.start;

				// Append new elements to outer html
				var elementsToAdd = this.core.$items.length - this.core.$outer.find(".lg-inner").find(".lg-item").length;
				while (elementsToAdd > 0) {
					var newSlide = jQuery('<div class="lg-item"></div>');
					this.core.$outer.find(".lg-inner").append(newSlide);
					this.core.$slide = this.core.$outer.find(".lg-item");
					elementsToAdd--;
				}

				// Reset the loading direction
				this.core.s.loadcontent = null;
			}
		};

		Friendica.prototype.addPrevElements = function(data) {
			if (typeof data.results !== 'undefined' && data.results.length > 0) {

				// Loop backwards through the results and add its elements right ahead
				// of the existing gallery elements
				for (var i = data.results.length -1 ; i >= 0; i--) {
					this.core.$items.unshift(data.results[i]);
				}

				var newIndex = this.core.index + data.results.length;

				this.core.index = newIndex;
				this.core.s.total = data.total;
				this.core.s.page = data.page;
//				this.core.s.start = data.start;

				// Append new elements to outer html
				var elementsToAdd = this.core.$items.length - this.core.$outer.find(".lg-inner").find(".lg-item").length;
				while (elementsToAdd > 0) {
					var newSlide = jQuery('<div class="lg-item"></div>');
					this.core.$outer.find(".lg-inner").prepend(newSlide);
					this.core.$slide = this.core.$outer.find(".lg-item");
					elementsToAdd--;
				}

				this.core.s.countvar = data.start;
				// Reset the loading direction
				this.core.s.loadcontent = null;
			}
		};

		Friendica.prototype.query = function() {
			var query;

			if (this.core.s.loadcontent == 'next') {
				query = '?page=' + (this.core.s.page + 1);
			}

			if (this.core.s.loadcontent == 'previous') {
				query = '?page=' + (this.core.s.page - 1);
			}

			return query;
		};

		Friendica.prototype.destroy = function() {

		};

		$.fn.lightGallery.modules.friendica = Friendica;

	})();



}));
