<h3>{{$title}}</h3>

<form action="new_account" method="post" id="new_account-form">

	<input type="hidden" name="photo" value="{{$photo}}" />

	{{if $registertext != ""}}<div class="error-message">{{$registertext}} </div>{{/if}}

	<div id="register-name-wrapper" >
		<label for="register-name" id="label-register-name" >{{$namelabel}}</label>
		<input type="text" maxlength="60" size="32" name="username" id="register-name" value="{{$username|escape:'html'}}" >
	</div>
	<div id="register-name-end" ></div>

	<div id="register-email-end" ></div>

	<p id="register-nickname-desc" >{{$nickdesc}}</p>

	<div id="register-nickname-wrapper" >
		<label for="register-nickname" id="label-register-nickname" >{{$nicklabel}}</label>
		<input type="text" maxlength="60" size="32" name="nickname" id="register-nickname" value="{{$nickname|escape:'html'}}" ><div id="register-sitename">@{{$sitename}}</div>
	</div>
	<div id="register-nickname-end" ></div>

	{{$publish}}

	<div id="register-submit-wrapper">
		<input type="submit" name="submit" id="register-submit-button" value="{{$submit|escape:'html'}}" />
	</div>
	<div id="register-submit-end" ></div>

<h3>{{$importh}}</h3>
	<div id ="import-profile">
		<a href="uimport">{{$importt}}</a>
	</div>
</form>