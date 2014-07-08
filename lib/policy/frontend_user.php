<?

$policy = function($rq, $res) {
	$rq->fconfig = array_merge_recursive($rq->fconfig, array(
		'user' => $rq->user->to_object()
	));

	return true;
};
