
{{if $count}}
<div id="event-notice" class="birthday-notice fakelink {{$classtoday}}" onclick="openClose('event-wrapper');">{{$event_reminders}} ({{$count}})</div>
<div id="event-wrapper" style="display: none;" >
	<div id="event-title">{{$event_title}}</div>
	<div id="event-title-end"></div>
	{{foreach $events as $event}}
	<div class="event-list" id="event-{{$event.id}}"> <a class="ajax-popupbox" href="events/?id={{$event.id}}">{{$event.title}}</a> - {{$event.date.local.start}} {{if $today}}[{{$today}}]{{/if}}</div>
	{{/foreach}}
</div>
{{/if}}

