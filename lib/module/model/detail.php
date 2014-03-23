<?

$this->req('id');
$this->req('model');

def($conds, array());
def($opts, array());

$response = array(
	'status'  => 404,
	'message' => 'model-not-found'
);

$model = System\Loader::get_class_from_model($model);

if (class_exists($model)) {
	if ($item = find($model, $id)) {
		if ($item->can_be(\System\Model\Perm::VIEW, $request->user)) {
			$response['status']  = 200;
			$response['message'] = 'ok';
			$response['data']    = $item->to_object();
		} else {
			$response['status'] = 403;
			$response['message'] = 'access-denied';
		}
	} else {
		$response['message'] = 'object-not-found';
	}
}

$this->partial(null, $response);
