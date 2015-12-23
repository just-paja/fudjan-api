<?php

$policy = function($rq, $res) {
	$past_route = function($rq, $name) {
		return \System\Router::get_route_str($rq->host, $name);
	};

	$url_pack = $past_route($rq, 'system_resource');
	$url_pack = str_replace(array('{res_src}', '{res_type}', '{res_path}'), array('media', 'schema', '{name}.'.\System\Resource::get_serial()), $url_pack);

	$urls = array(
		'pack'   => $url_pack,
		'schema' => $past_route($rq, 'api_model_schema'),
		'browse' => $past_route($rq, 'api_model_browse'),
		'create' => $past_route($rq, 'api_model_create'),
		'edit'   => $past_route($rq, 'api_model_object_edit'),
		'drop'   => $past_route($rq, 'api_model_object_drop'),
	);

	$rq->fconfig = array_merge_recursive($rq->fconfig, array(
		'models' => array(
			'url' => $urls
		)
	));

	return true;
};
