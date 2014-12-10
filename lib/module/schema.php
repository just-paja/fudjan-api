<?

$package  = $this->req('package');
$packages = cfg('resources', 'models');
$page     = PHP_INT_MAX;
$per_page = PHP_INT_MAX;

$res = array(
	'status'  => 404,
	'message' => 'api-schema-not-found'
);

if (isset($packages[$package])) {
	$data = array();
	$pack = array();
	$list = $packages[$package];
	$rels = array('collection', 'model');

	foreach ($list as $model) {
		$cname  = \System\Loader::get_class_from_model($model);
		$model  = \System\Loader::get_model_from_class($model);
		$schema = $cname::get_visible_schema($request->user);

		foreach ($schema as $attr) {
			if (in_array($attr['type'], $rels)) {
				$rel_cname = \System\Loader::get_class_from_model($attr['model']);
				$rel_model = \System\Loader::get_model_from_class($attr['model']);

				if (!array_key_exists($rel_model, $pack)) {
					$pack[$rel_model] = $rel_cname::get_visible_schema($request->user);
				}
			}
		}

		$pack[$model] = $schema;
	}

	foreach ($pack as $name => $def) {
		$data[] = array(
			"name"    => $name,
			"parents" => array('model'),
			"static"  => array(
				"attrs" => $def
			)
		);
	}

	$res = array(
		'status' => 200,
		'data'   => $data
	);
}

try {
	$debug = \System\Settings::get('dev', 'debug', 'backend');
} catch(\System\Error $e) {
	$debug = true;
}

if (!$debug) {
	$max_age = \System\Settings::get('cache', 'resource', 'max-age');

	$response->header('Pragma', 'public,max-age='.$max_age);
	$response->header('Cache-Control', 'public');
	$response->header('Expires', date(\DateTime::RFC1123, time() + $max_age + rand(0,60)));
	$response->header('Age', '0');
}

$this->partial(null, $res);
