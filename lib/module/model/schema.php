<?

$page     = 0;
$per_page = 1;

$this->req('model');

$cname = System\Loader::get_class_from_model($model);
$exists = class_exists($cname) && is_subclass_of($cname, '\System\Model\Perm');

$send = array(
	'status'  => 404,
	'message' => 'schema-not-found'
);


if ($exists) {
	try {
		$schema = $cname::get_visible_schema($request->user);
	} catch(\System\Error\AccessDenied $e) {
		$send['status'] = 403;
		$send['message'] = 'access-denied';
	}

	if ($schema) {
		$send['status'] = 200;
		$send['message'] = 'ok';
		$send['data'] = $schema;
	}
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

$this->partial(null, $send);
