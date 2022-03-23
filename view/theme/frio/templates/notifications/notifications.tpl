<script type="text/javascript" src="../../frameworks/jquery-color/jquery.color.js?v={{$smarty.const.FRIENDICA_VERSION}}"></script>
<script type="text/javascript" src="../../js/mod_notifications.js?v={{$smarty.const.FRIENDICA_VERSION}}"></script>

<div class="generic-page-wrapper">
	{{include file="section_title.tpl" title=$l10n.title}}

	{{if $tabs }}{{include file="common_tabs.tpl"}}{{/if}}

	<div class="notif-network-wrapper">
		{{* The notifications *}}
		{{if $notifications}}
		<ul class="notif-network-list media-list">
		{{foreach $notifications as $notification}}
			{{include file="notifications/notification.tpl" notification=$notification}}
		{{/foreach}}
		</ul>
        {{else}}
		<div class="notification_nocontent">{{$l10n.noContent}}</div>
		{{/if}}
	</div>

	{{* The pager *}}
	{{$pager nofilter}}
</div>
