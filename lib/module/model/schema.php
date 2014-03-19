<?

$this->req('model');

$cname = System\Loader::get_class_from_model($model);
$response = array(
	'status'  => 404,
	'message' => 'schema-not-found'
);

if (class_exists($cname)) {
	if ($cname::can_user(\System\Model\Perm::VIEW_SCHEMA, $request->user)) {
		$menu = Godmode\Router::get_schemas();

		if (true || in_array($model, $menu)) {
			$attrs = array();
			new $cname();
			$def = \System\Model\Database::get_model_attr_list($cname, false, true);

			foreach ($def as $name) {
				if ($name != \System\Model\Database::get_id_col($cname)) {
					$attr = \System\Model\Database::get_attr($cname, $name);
					$attr['name'] = $name;

					switch ($attr[0])
					{
						case 'bool': $attr['type'] = 'boolean'; break;
						case 'varchar': $attr['type'] = 'string'; break;
						case 'text': $attr['type'] = 'text'; break;
						case 'json': $attr['type'] = 'object'; break;
						case 'belongs_to': $attr['type'] = 'model'; break;
						case 'has_many': $attr['type'] = 'collection'; break;
						default: $attr['type'] = $attr[0];
					}

					if (isset($attr['model'])) {
						$attr['model'] = \System\Loader::get_model_from_class($attr['model']);
					}

					unset($attr[0]);
					$attrs[] = $attr;
				}
			}

			$response['status'] = 200;
			$response['message'] = 'ok';
			$response['data'] = $attrs;
		}
	} else {
		$response['status'] = 403;
		$response['message'] = 'access-denied';
	}
}

$this->partial(null, $response);
