<h1>{{$header}}</h1>

{{if $tabs }}{{include file="common_tabs.tpl"}}{{/if}}

<div class="notif-network-wrapper">
	{{if $notifications}}
		{{foreach $notifications as $notification}}
			{{include file="notifications/notification.tpl" notification=$notification}}
		{{/foreach}}
	{{else}}
		<div class="notification_nocontent">{{$noContent}}</div>
	{{/if}}

	{{$pageg nofilter}}
</div>
