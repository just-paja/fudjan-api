<?

$policy = function($rq, $res) {
	$past_route = function($rq, $name) {
		$r = \System\Router::get_route($rq->host, $name);
		return $r[3];
	};

	$urls = array(
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
