
var albumCache = {};
var slideCache = {};

$(document).ready(function() {
	loadingPage = false;
	// Initialize justifiedGallery
	justifyPhotos();

//	$('.photo-view-wrapper').lightGallery();

	$('.photo-gallery a').on('click', function(e) {
		e.preventDefault();

		var slideID = $(this).attr('data-slide');

		if (typeof albumname === 'undefined') {
			albumname = $(this).children('img').attr('alt');
		}


		var url = window.location.href; 
		var parts = parseUrl(url);

		// get a clean url which points to the album
		// we use this url in lightGallery to load further content
		var cleanurl = '';
		if (typeof parts.scheme != 'undefined') {
			cleanurl += parts.scheme + '://';
		}
		if (typeof parts.host != 'undefined') {
			cleanurl += parts.host;
		}
		if (typeof parts.path != 'undefined') {
			cleanurl += parts.path;
		}

		getPhotosByAlbumAddress(url, function(data){
			$(this).lightGallery({
				index: parseInt(slideID, 10),
				dynamic: true,
				dynamicEl: data.results,
				total: data.total,
				page: data.page,
				itemspage: data.items_page,
				start: data.start,
				albumUrl: cleanurl,
				friendica: true,
				preload: 0,
				closable: false,
				thumbnail: false,
				showThumbByDefault: false,
				enableDrag: false,
				loop: false,
				counter: false
			});
		});
	});

});

function justifyPhotos() {
	justifiedGalleryActive = true;
	$('.photo-gallery').justifiedGallery({
		margins: 3,
		border: 0,
		sizeRangeSuffixes: {
			'lt100': '-2',
			'lt240': '-2',
			'lt320': '-2',
			'lt500': '',
			'lt640': '-1',
			'lt1024': '-0'
		}
	}).on('jg.complete', function(e){ justifiedGalleryActive = false; });
}

function justifyPhotosAjax() {
	justifiedGalleryActive = true;
	$('.photo-gallery').justifiedGallery('norewind').on('jg.complete', function(e){ justifiedGalleryActive = false; });
}

//function getPhotosByAlbmuname(album, callback) {
//	albumhex = bin2hex(album);
//	url = baseurl + '/photos/trebor/album/' + albumhex;
//
//	postdata = {
//		format: 'json'
//	};
//
//	// If we have this album already in the cache take this one
//	if(albumname in albumCache) {
//		setTimeout(function() { callback(albumCache[albumname]); } , 1);
//		return;
//	}
//
//	$.ajax({
//		url: url,
//		data: postdata,
//		success: function(data, textStatus) {
//			// Store data in the cache
//			albumCache[albumname] = data;
//			callback(data);
//		}
//	}).fail(function () {callback([]); });
//}

function getPhotosByAlbumAddress(url, callback) {

	postdata = {
		format: 'json'
	};

	// If we have this album already in the cache take this one
// uncommented - we need a new cache
//	if(albumname in albumCache) {
//		setTimeout(function() { callback(albumCache[albumname]); } , 1);
//		return;
//	}

	$.ajax({
		url: url,
		data: postdata,
		success: function(data, textStatus) {
			// Store data in the cache
//			albumCache[albumname] = data;
			callback(data);
		}
	}).fail(function () {callback([]); });
}


// the function does exist in main.js but we need to overwrite it
function post_comment(id) {
		unpause();
		commentBusy = true;
		$('body').css('cursor', 'wait');
		$("#comment-preview-inp-" + id).val("0");
		$.post(
			"item",
			$("#comment-edit-form-" + id).serialize(),
			function(data) {
				if(data.success) {
					$("#comment-edit-wrapper-" + id).hide();
					$("#comment-edit-text-" + id).val('');
					var tarea = document.getElementById("comment-edit-text-" + id);
					if(tarea)
						commentClose(tarea,id);
					if(timer) clearTimeout(timer);
					timer = setTimeout(NavUpdate,10);
					force_update = true;

					$('.fb-comments .photo-comment-wrapper').remove();
					var commentURL = slideCache.link + '?mode=none #photo-comment-wrapper-' + slideCache.id;
					$('.fb-comments').load(commentURL);
					$('body').css('cursor', 'auto');
				}
			},
			"json"
		);
		return false;
	}

function Bin2Hex(n){
	if(!checkBin(n)) {
		return 0;
	}
	return parseInt(n,2).toString(16);
}

function checkBin(n){return/^[01]{1,64}$/.test(n)}

function bin2hex (s) {
  // From: http://phpjs.org/functions
  // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   bugfixed by: Onno Marsman
  // +   bugfixed by: Linuxworld
  // +   improved by: ntoniazzi (http://phpjs.org/functions/bin2hex:361#comment_177616)
  // *     example 1: bin2hex('Kev');
  // *     returns 1: '4b6576'
  // *     example 2: bin2hex(String.fromCharCode(0x00));
  // *     returns 2: '00'

  var i, l, o = "", n;

  s += "";

  for (i = 0, l = s.length; i < l; i++) {
    n = s.charCodeAt(i).toString(16)
    o += n.length < 2 ? "0" + n : n;
  }

  return o;
}