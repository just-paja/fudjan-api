<?

namespace Module\Api\Model
{
	class Detail extends \System\Module
	{
		public function run()
		{
			$this->req('id');
			$this->req('model');

			def($conds, array());
			def($opts, array());

			$response = array(
				'status'  => 404,
				'message' => 'model-not-found'
			);

			$model = System\Loader::get_class_from_model($model);

			if (class_exists($model)) {
				if ($item = $model::find($id)) {
					if ($item->can_be($model::VIEW, $request->user)) {
						$response['status']  = 200;
						$response['message'] = 'ok';
						$response['data']    = $item->to_object();
					} else {
						$response['status'] = 403;
						$response['message'] = 'access-denied';
					}
				} else {
					$response['message'] = 'object-not-found';
				}
			}

			$this->partial(null, $response);
		}
	}
}
