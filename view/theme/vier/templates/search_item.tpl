

<div class="wall-item-decor">
	{{if $item.star}}<span class="icon star {{$item.isstarred}}" id="starred-{{$item.id}}" title="{{$item.star.starred|escape}}">{{$item.star.starred|escape}}</span>{{/if}}
	{{if $item.lock}}<span class="icon lock fakelink" onclick="lockview(event,{{$item.id}});" title="{{$item.lock|escape}}">{{$item.lock|escape}}</span>{{/if}}
	<img id="like-rotator-{{$item.id}}" class="like-rotator" src="images/rotator.gif" alt="{{$item.wait|escape}}" title="{{$item.wait|escape}}" style="display: none;" />
</div>

<div class="wall-item-container {{$item.indent}} {{$item.shiny}} ">
	<div class="wall-item-item">
		<div class="wall-item-info">
			<div class="contact-photo-wrapper">
				<!-- onmouseover="if (typeof t{{$item.id}} != 'undefined') clearTimeout(t{{$item.id}}); openMenu('wall-item-photo-menu-button-{{$item.id}}')"
				onmouseout="t{{$item.id}}=setTimeout('closeMenu(\'wall-item-photo-menu-button-{{$item.id}}\'); closeMenu(\'wall-item-photo-menu-{{$item.id}}\');',200)"> -->
				<!-- <a href="{{$item.profile_url}}" target="redir" title="{{$item.linktitle|escape}}" class="wall-item-photo-link" id="wall-item-photo-link-{{$item.id}}"></a> -->
					<img src="{{$item.thumb}}" class="contact-photo{{$item.sparkle}}" id="wall-item-photo-{{$item.id}}" alt="{{$item.name|escape}}" />
				<!-- <a rel="#wall-item-photo-menu-{{$item.id}}" class="contact-photo-menu-button icon s16 menu" id="wall-item-photo-menu-button-{{$item.id}}">menu</a> -->
				<ul role="menu" aria-haspopup="true" class="wall-item-menu menu-popup" id="wall-item-photo-menu-{{$item.id}}">
				{{$item.item_photo_menu}}
				</ul>

			</div>
		</div>
		<div class="wall-item-actions-author">
			<a href="{{$item.profile_url}}" target="redir" title="{{$item.linktitle|escape}}" class="wall-item-name-link"><span class="wall-item-name{{$item.sparkle}}">{{$item.name|escape}}</span></a>
			<span class="wall-item-ago">
				{{if $item.plink}}<a class="link" title="{{$item.plink.title|escape}}" href="{{$item.plink.href}}" style="color: #999">{{$item.ago}}</a>{{else}} {{$item.ago}} {{/if}}
				{{if $item.lock}}<span class="fakelink" style="color: #999" onclick="lockview(event,{{$item.id}});">{{$item.lock}}</span> {{/if}}
			</span>
		</div>
		<div class="wall-item-content">
			{{if $item.title}}<h2><a href="{{$item.plink.href}}">{{$item.title}}</a></h2>{{/if}}
			<div class="wall-item-body">{{$item.body}}</div>
		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="wall-item-links">
		</div>
		<div class="wall-item-tags">
		{{if !$item.suppress_tags}}
			{{foreach $item.tags as $tag}}
				<span class="tag">{{$tag}}</span>
			{{/foreach}}
		{{/if}}
		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="">
			<!-- {{if $item.plink}}<a title="{{$item.plink.title|escape}}" href="{{$item.plink.href}}"><i class="icon-link icon-large"></i></a>{{/if}} -->
			{{if $item.conv}}<a href='{{$item.conv.href}}' id='context-{{$item.id}}' title='{{$item.conv.title|escape}}'><i class="icon-link icon-large"></i></a>{{/if}}
		</div>
		<div class="wall-item-actions">

			<div class="wall-item-location">{{$item.location}}&nbsp;</div>

			<div class="wall-item-actions-social">
			{{if $item.star}}
				<a href="#" id="star-{{$item.id}}" onclick="dostar({{$item.id}}); return false;"  class="{{$item.star.classdo}}"  title="{{$item.star.do|escape}}">{{$item.star.do}}</a>
				<a href="#" id="unstar-{{$item.id}}" onclick="dostar({{$item.id}}); return false;"  class="{{$item.star.classundo}}"  title="{{$item.star.undo|escape}}">{{$item.star.undo}}</a>
				<a href="#" id="tagger-{{$item.id}}" onclick="itemTag({{$item.id}}); return false;" class="{{$item.star.classtagger}}" title="{{$item.star.tagger|escape}}">{{$item.star.tagger}}</a>
			{{/if}}

			{{if $item.vote}}
				<a href="#" id="like-{{$item.id}}"{{if $item.responses.like.self}} class="active"{{/if}} title="{{$item.vote.like.0|escape}}" onclick="dolike({{$item.id}},'like'); return false">{{$item.vote.like.1}}</a>
				<a href="#" id="dislike-{{$item.id}}"{{if $item.responses.dislike.self}} class="active"{{/if}} title="{{$item.vote.dislike.0|escape}}" onclick="dolike({{$item.id}},'dislike'); return false">{{$item.vote.dislike.1}}</a>
			{{/if}}

			{{if $item.vote.share}}
				<a href="#" id="share-{{$item.id}}" title="{{$item.vote.share.0|escape}}" onclick="jotShare({{$item.id}}); return false">{{$item.vote.share.1}}</a>
			{{/if}}
			</div>

			<div class="wall-item-actions-tools">

				{{if $item.drop.pagedrop}}
					<input type="checkbox" title="{{$item.drop.select|escape}}" name="itemselected[]" class="item-select" value="{{$item.id}}" />
				{{/if}}
				{{if $item.drop.dropping}}
					<a href="item/drop/{{$item.id}}" onclick="return confirmDelete();" class="icon delete s16" title="{{$item.drop.delete|escape}}">{{$item.drop.delete|escape}}</a>
				{{/if}}
				{{if $item.edpost}}
					<a class="icon edit s16" href="{{$item.edpost.0}}" title="{{$item.edpost.1|escape}}"></a>
				{{/if}}
			</div>

		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="wall-item-links"></div>
		<div class="wall-item-like" id="wall-item-like-{{$item.id}}">{{$item.like}}</div>
		<div class="wall-item-dislike" id="wall-item-dislike-{{$item.id}}">{{$item.dislike}}</div>
	</div>
</div>
