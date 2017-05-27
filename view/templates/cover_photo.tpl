
<div id="cover-photo-content" class="generic-page-wrapper">

	{{include file="section_title.tpl"}}

	<form enctype="multipart/form-data" action="cover_photo" method="post">
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

		<div id="cover-photo-upload-wrapper">

			<label id="cover-photo-upload-label" class="form-label" for="cover-photo-upload">{{$lbl_upfile}}</label>
			<input name="userfile" class="form-input" type="file" id="cover-photo-upload" size="48" />
			<div class="clearfix"></div>

			<div id="cover-photo-submit-wrapper" class="pull-right">
				<button type="submit" name="submit" id="cover-photo-submit" class="btn btn-primary" value="{{$submit|escape:'html'}}">{{$submit|escape:'html'}}</button>
			</div>
			<div class="clearfix"></div>
		</div>
	</form>

	<div id="cover-photo-link-select-wrapper">
		{{$select}}
	</div>
</div>
