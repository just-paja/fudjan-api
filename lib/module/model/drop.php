<?

$this->req('id');
$this->req('model');

$cname = System\Loader::get_class_from_model($model);
$response = array(
	'message' => 'not-found',
	'status'  => 404,
);

if (class_exists($cname) && is_subclass_of($cname, '\System\Model\Perm')) {
	if ($item = find($cname, $id)) {
		if ($item->can_be(System\Model\Perm::DROP, $request->user)) {
			$item->drop();

			$response['message'] = 'dropped';
			$response['status']  = 200;
		} else {
			$response['message'] = 'denied';
			$response['status']  = 403;
		}
	}
}

$this->partial(null, $response);