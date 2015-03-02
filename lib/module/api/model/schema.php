<?


namespace Module\Api\Model
{
	class Schema extends \System\Module
	{
		public function run()
		{
			$rq = $this->request;
			$res = $this->response;

			$page     = 0;
			$per_page = 1;

			$model = $this->req('model');
			$cname = \System\Loader::get_class_from_model($model);
			$exists = class_exists($cname) && is_subclass_of($cname, '\System\Model\Perm');

			$send = array(
				'status'  => 404,
				'message' => 'schema-not-found'
			);


			if ($exists) {
				try {
					$schema = $cname::get_visible_schema($rq->user);
				} catch(\System\Error\AccessDenied $e) {
					$send['status'] = 403;
					$send['message'] = 'access-denied';
				}

				if ($schema) {
					$send['status'] = 200;
					$send['message'] = 'ok';
					$send['data'] = $schema;
				}
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
