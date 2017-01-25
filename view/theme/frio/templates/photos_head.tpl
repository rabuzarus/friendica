
<link rel="stylesheet" href="view/theme/frio/frameworks/lightGallery/css/lightgallery.min.css" type="text/css" media="screen"/>
<link rel="stylesheet" href="view/theme/frio/frameworks/lightGallery/css/lg-fb-comment-box.css" type="text/css" media="screen"/>
<script type="text/javascript" src="view/theme/frio/frameworks/lightGallery/js/lightgallery.js"></script>
<script type="text/javascript" src="view/theme/frio/frameworks/lg-hash/lg-hash.js"></script>
<script type="text/javascript" src="view/theme/frio/frameworks/lg-friendica/lg-friendica.js"></script>
<script type="text/javascript" src="view/theme/frio/frameworks/lg-thumbnail/lg-thumbnail.js"></script>
<script type="text/javascript" src="view/theme/frio/js/photos.js"></script>
<script type="text/javascript">

	var ispublic = "{{$ispublic}}";


	$(document).ready(function() {

		$('#contact_allow, #contact_deny, #group_allow, #group_deny').change(function() {
			var selstr;
			$('#contact_allow option:selected, #contact_deny option:selected, #group_allow option:selected, #group_deny option:selected').each( function() {
				selstr = $(this).text();
				$('#jot-perms-icon').removeClass('unlock').addClass('lock');
				$('#jot-public').hide();
			});
			if(selstr == null) { 
				$('#jot-perms-icon').removeClass('lock').addClass('unlock');
				$('#jot-public').show();
			}

		}).trigger('change');

	});

</script>
