<div class="generic-page-wrapper">
	{{* Include the title template for the settings title. *}}
	{{include file="section_title.tpl" title=$title }}

	<a href="{{$new_lnk}}">{{$new_lbl}}</a>

	{{if $micropro}}
	{{foreach $micropro as $m}}
		{{$m nofilter}}
	{{/foreach}}
	{{/if}}
</div>
