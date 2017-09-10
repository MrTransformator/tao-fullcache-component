<?php

return [
	'events' => [
		'ws.response' => 'Component.FullCache::event_response',
		'cms.insertions' => 'Component.FullCache::event_cms_insertions',
		'templates.build_head' => 'Component.FullCache::event_build_head'
	]
];