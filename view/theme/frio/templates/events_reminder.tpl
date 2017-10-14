
{{if $count}}
<div id="event-reminder" class="panel">
	<div class="panel-body">
	<div id="event-notice" class="birthday-notice fakelink {{$classtoday}}" onclick="openClose('event-wrapper');">
		<span id="event-reminder-heading">{{$event_title}}</span>
		<span id="event-reminder-counter"> ({{$count}})</span>
	</div>
	<div id="event-wrapper" style="display: none;" >
		{{foreach $events as $event}}
		<div class="event-list {{if $event.today}}event-today{{/if}}" id="event-{{$event.id}}" onclick="addToModal(baseurl +  '/events?id=' + {{$event.id}}); false;">
			<div class="event-reminder-title">{{$event.title}}</div>
			<div class="event-reminder-date">{{$event.date_short}}</div>
		</div>
		{{/foreach}}
	</div>
	</div>
</div>
{{/if}}

