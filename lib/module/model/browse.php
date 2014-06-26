<?

def($conds, array());
def($opts, array());

$this->req('model');

$response = array(
	'status'  => 200,
	'message' => null,
);

$cname = System\Loader::get_class_from_model($model);
$status = 200;
$meta = null;

$filters = html_entity_decode($request->get('filters'));
$sort = html_entity_decode($request->get('sort'));
$sort_by = array();

if ($request->get('per_page')) {
	$per_page = intval($request->get('per_page'));
}


if (class_exists($cname) && is_subclass_of($cname, '\System\Model\Perm')) {
	if ($cname::can_user(\System\Model\Perm::BROWSE, $request->user)) {
		def($sort, array('created_at desc'));

		$idc = \System\Model\Database::get_id_col($cname);

		if ($filters) {
			try {
				$filters = \System\Json::decode($filters);
			} catch (\System\Error\Format $e) {
				$response['status'] = 400;
				$response['message'] = 'malformed-filter';
			}
		}

		if ($sort) {
			try {
				$sort = \System\Json::decode($sort);
			} catch (\System\Error\Format $e) {
				$response['status'] = 400;
				$response['message'] = 'malformed-sort';
			}
		}

		if ($response['status'] == 200) {
			$query = get_all($cname, $conds, $opts);
			$data = array();

			if (any($sort)) {
				foreach ($sort as $sort_item) {
					def($sort_item['mode'], 'asc');
					$sort_by[] = $sort_item['attr'].' '.$sort_item['mode'];
				}
			} else {
				$sort_by = array('created_at desc');
			}

			if (any($filters)) {
				foreach ($filters as $filter=>$filter_val) {
					if ($filter == 'id') {
						$filter = $idc;
					}

					if (is_array($filter_val)) {
						if (array_keys($filter_val) !== range(0, count($filter_val) - 1)) {
							$query->add_filter($filter_val);
						} else {
							$query->where_in($filter, $filter_val);
						}
					} else {
						if (\System\Model\Database::attr_exists($cname, $filter)) {
							$query->where(array($filter => $filter_val));
						} else if ($cname::has_filter($filter)) {
							$cname::filter($query, $filter, $filter_val);
						} else throw new \System\Error\Argument('Model does not have attr', $filter);
					}
				}
			}

			$query->paginate($per_page, $page)->sort_by(implode(', ', $sort_by));
			$send = true;

			try {
				$items = $query->fetch();
				$count = $query->count();
			} catch (\System\Error\Database $e) {
				$send = false;

				$response['status'] = 400;
				$response['message'] = $e->get_explanation();
			}

			if ($send) {
				foreach ($items as $item) {
					$data[] = $item->to_object();
				}

				$response['total'] = intval($count);
				$response['data'] = $data;
			}
		}
	} else {
		$response['status'] = 403;
		$response['message'] = 'access-denied';
	}
} else {
	$response['status'] = 404;
	$response['message'] = 'model-not-found';
}

$this->partial(null, $response);
