<?

namespace Module\Api\Model
{
	class Pack extends \System\Module
	{
		public function run()
		{
			$rq  = $this->request;
			$res = $this->response;
			$package  = $this->req('name');
			$packages = cfg('resources', 'models');
			$page     = PHP_INT_MAX;
			$per_page = PHP_INT_MAX;

			$send = array(
				'status'  => 404,
				'message' => 'api-schema-not-found'
			);

			if (isset($packages[$package])) {
				$data = array();
				$pack = array();
				$list = $packages[$package];
				$rels = array('collection', 'model');

				foreach ($list as $model) {
					$cname  = \System\Loader::get_class_from_model($model);
					$model  = \System\Loader::get_model_from_class($model);
					$schema = $cname::get_visible_schema($rq->user);

					foreach ($schema['attrs'] as $attr) {
						if (in_array($attr['type'], $rels)) {
							$rel_cname = \System\Loader::get_class_from_model($attr['model']);
							$rel_model = \System\Loader::get_model_from_class($attr['model']);

							if (!array_key_exists($rel_model, $pack)) {
								$pack[$rel_model] = $rel_cname::get_visible_schema($rq->user);
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

				$send = array(
					'status' => 200,
					'data'   => $data
				);
			}

			try {
				$debug = \System\Settings::get('dev', 'debug', 'backend');
			} catch(\System\Error $e) {
				$debug = true;
			}

			if (!$debug) {
				$max_age = \System\Settings::get('cache', 'resource', 'max-age');

				$res->header('Pragma', 'public,max-age='.$max_age);
				$res->header('Cache-Control', 'public');
				$res->header('Expires', date(\DateTime::RFC1123, time() + $max_age + rand(0,60)));
				$res->header('Age', '0');
			}

			$this->partial(null, $send);
		}
	}
}
