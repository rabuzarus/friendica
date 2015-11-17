
{{include file="section_title.tpl"}}

<form action="new_account" method="post" id="new-account-form">

	<input type="hidden" name="photo" value="{{$photo}}" />

	<div id="new-account-desc"><p>{{$new_account_desc}}</p></div>

	<div id="new-account-pagetype-wrapper">
		<h4>{{$pagetype}}</h4>
		<p>{{$pagetype_desc}}<p>
		{{include file="pagetypes.tpl" pagetypes=$pagetypes}}
	</div>

	<div id="new-account-name-wrapper" >
		<label for="new-account-name" id="label-new-account-name" >{{$namelabel}}</label>
		<input type="text" maxlength="60" size="32" name="username" id="new-account-name" value="{{$username|escape:'html'}}" >
		<div id="new-account-name-end"></div>
		<p id= "new-account-name-desc" class="new-account-desc">{{$namedesc}}</p>
	</div>
	<div id="new-account-name-end" ></div>
	

	<div id="new-account-nickname-wrapper" >
		<label for="new-account-nickname" id="label-new-account-nickname" >{{$nicklabel}}</label>
		<input type="text" maxlength="60" size="32" name="nickname" id="new-account-nickname" value="{{$nickname|escape:'html'}}" >
		
		<p id="new-nickname-desc" class="new-account-desc" >{{$nickdesc}}</p>
	</div>
	<div id="new-account-nickname-end" ></div>
	
	<div id ="import-profile">
		<p class="new-account-desc">{{$importt}}</p>
	</div>

	{{$publish}}

	<div id="new-account-submit-wrapper">
		<input type="submit" name="submit" id="new-account-submit-button" value="{{$submit|escape:'html'}}" />
	</div>
	<div id="new-account-submit-end" ></div>

</form>