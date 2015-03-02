<?

$policy = function($rq, $res) {
	$past_route = function($rq, $name) {
		return \System\Router::get_route_str($rq->host, $name);
	};

	$urls = array(
		'pack'   => $past_route($rq, 'api_schema'),
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
