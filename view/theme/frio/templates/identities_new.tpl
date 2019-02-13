<div class="generic-page-wrapper">

	<form action="identities{{$form_action}}" method="post" id="register-form">

		<input type="hidden" name="photo" value="{{$photo}}" />
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		{{if $account_type}}<input type="hidden" name="account-type" value="{{$account_type}}">{{/if}}
		{{if $page_flags}}<input type="hidden" name="page-flags" value="{{$page_flags}}">{{/if}}
		{{if $username}}<input type="hidden" name="username" value="{{$username}}">{{/if}}
		{{if $nickname}}<input type="hidden" name="nickname" value="{{$nickname}}">{{/if}}
		{{if $netpublish}}<input type="hidden" name="profile_publish_reg" value="{{$netpublish}}"{{/if}}

		<h3 class="heading">{{$title}}</h3>

		{{if $registertext != ""}}<div class="error-message">{{$registertext nofilter}}</div>{{/if}}

		{{if $explicit_content}} <p id="register-explicid-content">{{$explicit_content_note}}</p> {{/if}}

		{{if $desc}}
		<div id="settings-pagetype-desc">{{$desc}}</div>
		{{/if}}

		{{if $pagetype}}
		<div id="settings-form">
			{{$pagetype nofilter}}
		</div>
		{{/if}}

		{{if $namelabel}}
		<div id="register-name-wrapper" class="form-group">
			<label for="register-name" id="label-register-name" >{{$namelabel}}</label>
			<input type="text" maxlength="60" size="32" name="username" id="register-name" class="form-control" value="{{$username}}">
		</div>
		<div id="register-name-end" ></div>
		{{/if}}

		{{if $nicklabel}}
		<div id="register-nickname-wrapper" class="form-group">
			<label for="register-nickname" id="label-register-nickname" >{{$nicklabel}}</label>
			<input type="text" maxlength="60" size="32" name="nickname" id="register-nickname" class="form-control" value="{{$nickname}}">
			<span class="help-block" id="nickname_tip">{{$nickdesc nofilter}}</span>
		</div>
		<div id="register-nickname-end" ></div>
		{{/if}}

		{{if $publish}}
		{{$publish nofilter}}
		{{/if}}

		{{if $password}}
		{{include file="field_password.tpl" field=$password}}
		{{/if}}
		{{if $showtoslink}}
		<p><a href="{{$baseurl}}/tos">{{$tostext}}</a></p>
		{{/if}}
		



		<div id="register-submit-wrapper" class="pull-right">
			<button type="submit" name="submit" id="register-submit-button" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
		</div>
		<div id="register-submit-end" class="clear"></div>
	</form>
</div>
