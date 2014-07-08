<?

$this->req('model');

$cname = System\Loader::get_class_from_model($model);
$exists = class_exists($cname) && is_subclass_of($cname, '\System\Model\Perm');

$response = array(
	'status'  => 404,
	'message' => 'schema-not-found'
);

$rel_attrs = array(
	\System\Model\Database::REL_BELONGS_TO,
	\System\Model\Database::REL_HAS_MANY
);

if ($exists) {
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

					if (in_array($attr[0], $rel_attrs)) {
						$rel_cname = $attr['model'];
						$is_subclass = is_subclass_of($rel_cname, '\System\Model\Perm');

						if (!($is_subclass && $rel_cname::can_user(\System\Model\Perm::VIEW_SCHEMA, $request->user))) {
							$attr = null;
							continue;
						}
					}

					switch ($attr[0])
					{
						case 'bool': $attr['type'] = 'boolean'; break;
						case 'varchar': $attr['type'] = 'string'; break;
						case 'json': $attr['type'] = 'object'; break;
						case \System\Model\Database::REL_BELONGS_TO: $attr['type'] = 'model'; break;
						case \System\Model\Database::REL_HAS_MANY: $attr['type'] = 'collection'; break;
						default: $attr['type'] = $attr[0];
					}

					if (isset($attr['model'])) {
						$attr['model'] = \System\Loader::get_model_from_class($rel_cname);
					}

					if (isset($attr['options'])) {
						if (isset($attr['options'][0]) && $attr['options'][0] == 'callback') {
							$opts = $attr['options'];
							array_shift($opts);
							$opts = call_user_func($opts);
						} else {
							$opts = $attr['options'];
						}

						$attr['options'] = array();

						foreach ($opts as $opt_value=>$opt_name) {
							$attr['options'][] = array(
								'name'  => $opt_name,
								'value' => $opt_value,
							);
						}
					}

					// Word 'default' is keyword in some browsers, so pwf-models use 'def' instead
					if (isset($attr['default'])) {
						$attr['def'] = $attr['default'];
						unset($attr['default']);
					}

					if (is_array($attr)) {
						unset($attr[0]);
						$attrs[] = $attr;
					}
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
