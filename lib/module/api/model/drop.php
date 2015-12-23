<?php

namespace Module\Api\Model
{
	class Drop extends \System\Module
	{
		public function run()
		{
			$rq = $this->request;
			$id = $this->req('id');
			$model = $this->req('model');

			$cname = \System\Loader::get_class_from_model($model);
			$response = array(
				'message' => 'not-found',
				'status'  => 404,
			);

			if (class_exists($cname) && is_subclass_of($cname, '\System\Model\Perm')) {
				if ($item = $cname::find($id)) {
					if ($item->can_be($cname::DROP, $rq->user)) {
						$item->drop();

						$response['message'] = 'dropped';
						$response['status']  = 200;
					} else {
						$response['message'] = 'denied';
						$response['status']  = 403;
					}
				}
			}

			$this->partial(null, $response);
		}
	}
}
