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
			
			var cache;
			var isopen = false;

			this.core.$outer.find('.lg-toolbar').append(commentButton);
			this.core.$outer.find('.lg-toolbar').append('<span id="lg-album">' + albumname + '</span>');

			_this.commentbox();

			$('#lg-comment').on('click.lg', function(){
				if (isopen === true) {
					isopen = false;
				} else {
					isopen = true;
					_this.loadContent();
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

				var lglength = _this.core.s.dynamicEl.length
				if (_this.core.index >= (lglength - 1)) {
					_this.addNextContent();
				}

				// Put some core values into the cache, so we can make
				// use of this in other js files
				var index = _this.core.index;
				slideCache.id = _this.core.s.dynamicEl[index].id;
				slideCache.link = _this.core.s.dynamicEl[index].link;
				slideCache.index = _this.core.index;

				_this.rewriteSubHtml();

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

		Friendica.prototype.loadContent = function() {
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
				this.core.s.dynamicEl[index].subHtml = this.core.s.dynamicEl[index].desc;
				this.core.$items[index].subHtml = this.core.$items[index].desc;
			}
		};

		Friendica.prototype.addNextContent = function() {
			var query = '?page=' + (this.core.s.page + 1);
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
				this.addNextElements(data);
			});
	
		};

		Friendica.prototype.addNextElements = function(data) {
			if (typeof data.results && data.results.length > 0) {
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
			}
		};

		Friendica.prototype.destroy = function() {

		};

		$.fn.lightGallery.modules.friendica = Friendica;

	})();



}));
