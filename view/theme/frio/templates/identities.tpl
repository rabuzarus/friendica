<div class="generic-page-wrapper">

	<form action="identities" method="post" id="register-form">

		<input type="hidden" name="photo" value="{{$photo}}" />
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<h3 class="heading">{{$title}}</h3>

		{{if $registertext != ""}}<div class="error-message">{{$registertext nofilter}}</div>{{/if}}

		{{if $explicit_content}} <p id="register-explicid-content">{{$explicit_content_note}}</p> {{/if}}

		<div id="settings-form">
			<div id="settings-pagetype-desc">{{$h_descadvn}}</div>
			{{$pagetype nofilter}}
		</div>
		<div id="register-name-wrapper" class="form-group">
			<label for="register-name" id="label-register-name" >{{$namelabel}}</label>
			<input type="text" maxlength="60" size="32" name="username" id="register-name" class="form-control" value="{{$username}}">
		</div>
		<div id="register-name-end" ></div>

		<div id="register-nickname-wrapper" class="form-group">
			<label for="register-nickname" id="label-register-nickname" >{{$nicklabel}}</label>
			<input type="text" maxlength="60" size="32" name="nickname" id="register-nickname" class="form-control" value="{{$nickname}}">
			<span class="help-block" id="nickname_tip">{{$nickdesc nofilter}}</span>
		</div>
		<div id="register-nickname-end" ></div>


		{{$publish nofilter}}

		{{include file="field_password.tpl" field=$password}}

		{{if $showtoslink}}
		<p><a href="{{$baseurl}}/tos">{{$tostext}}</a></p>
		{{/if}}

		<div id="register-submit-wrapper" class="pull-right">
			<button type="submit" name="submit" id="register-submit-button" class="btn btn-primary" value="{{$regbutt}}">{{$regbutt}}</button>
		</div>
		<div id="register-submit-end" class="clear"></div>
	</form>
</div>
