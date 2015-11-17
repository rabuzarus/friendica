
{{foreach $pagetypes as $types}}
<h4>{{$types.0}}</h4>
	<div class="page-type-list-wrapper">
		{{foreach $types.1 as $pagetype}}
		{{include file="field_radio.tpl" field=$pagetype}}
		{{/foreach}}
	</div>
{{/foreach}}