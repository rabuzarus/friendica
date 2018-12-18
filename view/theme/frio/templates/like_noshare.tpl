
<div class="wall-item-actions" id="wall-item-like-buttons-{{$id}}">
	<button type="button" class="btn-link button-likes" id="like-{{$id}}" title="{{$likethis}}" onclick="dolike({{$id}},'like'); return false;" data-toggle="button">
		<i class="faded-icon page-action fa fa-thumbs-up" aria-hidden="true"></i>
	</button>
	{{if $nolike}}
	<span class="icon-padding"> </span>
	<button type="button" class="btn-link button-likes" id="dislike-{{$id}}" title="{{$nolike}}" onclick="dolike({{$id}},'dislike'); return false;" data-toggle="button">
		<i class="faded-icon page-action fa fa-thumbs-down" aria-hidden="true"></i>
	</button>
	{{/if}}
	<img id="like-rotator-{{$id}}" class="like-rotator" src="images/rotator.gif" alt="{{$wait|escape}}" title="{{$wait|escape}}" style="display: none;" />
</div>
