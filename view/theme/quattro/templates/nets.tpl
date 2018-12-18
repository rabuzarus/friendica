<div id="nets-sidebar" class="widget">
	<h3>{{$title}}</h3>
	<div id="nets-desc">{{$desc}}</div>
	
	<ul class="nets-ul">
		<li class="tool {{if $sel_all}}selected{{/if}}"><a href="{{$base}}" class="nets-link nets-all">{{$all}}</a>
		{{foreach $nets as $net}}
			<li class="tool {{if $net.selected}}selected{{/if}}"><a href="{{$base}}?f=&nets={{$net.ref}}" class="nets-link">{{$net.name}}</a></li>
		{{/foreach}}
	</ul>
	
</div>
