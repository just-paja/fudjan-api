<?

def($id);
def($new, false);

$this->req('model');

$cname = System\Loader::get_class_from_model($model);
$response = array(
	'message' => 'not-found',
	'status'  => 404,
);

if (class_exists($cname) && is_subclass_of($cname, '\System\Model\Perm')) {
	if ($item = $new ? (new $cname()):$cname::find($id)) {
		$data = $request->post();

		foreach ($data as $attr_name=>$val) {
			if ($item::has_attr($attr_name)) {
				$def = $cname::get_attr($attr_name);

				if (is_string($val)) {
					if (preg_match('/^[\{\[].*[\}\]]$/', $val)) {
						$val = \System\Json::decode(html_entity_decode($val));
					}
				}

				if (in_array($def['type'], array('file', 'image'))) {
					$helper_cname = '\System\File';

					if ($def['type'] == 'image') {
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
				} else if ($def['type'] == 'password') {
					$item->$attr_name = hash_passwd($val);
				} else if ($def['type'] == 'bool') {
					if ($val == 'false') {
						$val = false;
					}

					$item->$attr_name = $val;
				} else if ($def['type'] == 'datetime') {
					$tz = new \DateTimeZone(\System\Settings::get('locales', 'timezone'));
					$item->$attr_name = \DateTime::createFromFormat('Y-m-d\TH:i:sO', $val);
					$item->$attr_name->setTimeZone($tz);
				} else {
					$item->$attr_name = $val;
				}
			}
		}

		$item->request = $request;

		if ($item::has_attr('author') && $request->user) {
			$item->author = $request->user;
		}

		try {
			$item->save();
		} catch (\System\Error $e) {
			$response['status'] = 500;
			$response['message'] = $e->get_explanation();
		}

		if ($response['status'] != 500) {
			$response['message'] = $new ? 'created':'saved';
			$response['status'] = 200;
		}

		$response['data'] = $item->to_object();
	}
}

$this->partial(null, $response);
