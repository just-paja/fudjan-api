<?

def($id);
def($new, false);

$this->req('model');

$cname = System\Loader::get_class_from_model($model);
$status = 404;
$message = null;
$data = null;
$meta = null;

if (class_exists($cname) && is_subclass_of($cname, '\System\Model\Perm')) {
	if ($item = $new ? (new $cname()):find($cname, $id)) {
		$data = $request->post();

		foreach ($data as $attr_name=>$val) {
			if ($item->has_attr($attr_name)) {
				$def = \System\Model\Database::get_attr($cname, $attr_name);

				if (preg_match('/^[\{\[].+[\}\]]$/', $val)) {
					$val = \System\Json::decode(html_entity_decode($val));
				}

				if (in_array($def[0], array('file', 'image'))) {
					$helper_cname = '\System\File';

					if ($def[0] == 'image') {
						$helper_cname = '\System\Image';
					}

					if (is_array($val)) {
						if (any($val['method']) && any($val[$val['method']])) {
							$data = $request->post($val[$val['method']]);

							if ($data) {
								$item->$attr_name = $helper_cname::from_tmp($data['tmp_name'], $data['name']);
							}
						}
					}
				} else {
					$item->$attr_name = $val;
				}
			}
		}


		try {
			$item->save();
		} catch (\System\Error $e) {
			$status = 500;
		}

		if ($status == 500) {
			$message = 'Failed to save object';
		} else {
			$status = 200;
		}

		$data = $item->to_object();
	}
} else {
	$message = 'Model not found';
}

$this->json_response($status, $message, $data, $meta);
