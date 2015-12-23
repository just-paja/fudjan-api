<?php

namespace System\Resource
{
	class Schema extends \System\Resource\Json
	{
		public function resolve()
		{
			$this->exists = true;

			try {
				$packages = cfg('resources', 'models', $this->name);
			} catch (\System\Error\Config $e) {
				$this->exists = false;
			}
		}


		public function read()
		{
			$list = cfg('resources', 'models', $this->name);
			$rels = array('collection', 'model');
			$data = array();
			$pack = array();

			foreach ($list as $model) {
				$cname  = \System\Loader::get_class_from_model($model);
				$model  = \System\Loader::get_model_from_class($model);
				$schema = $cname::get_visible_schema($this->request->user);

				foreach ($schema['attrs'] as $attr) {
					if (in_array($attr['type'], $rels)) {
						$rel_cname = \System\Loader::get_class_from_model($attr['model']);
						$rel_model = \System\Loader::get_model_from_class($attr['model']);

						if (!array_key_exists($rel_model, $pack)) {
							$pack[$rel_model] = $rel_cname::get_visible_schema($this->request->user);
						}
					}
				}

				$pack[$model] = $schema;
			}

			foreach ($pack as $name => $def) {
				$data[] = array(
					"name"    => $name,
					"parents" => array('model'),
					"static"  => $def
				);
			}

			$this->content = json_encode(array("data" => $data));
		}
	}
}
