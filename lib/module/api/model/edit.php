<?php


namespace Module\Api\Model
{
	class Edit extends \System\Module
	{
		public function run()
		{
			$id = $this->id;
			$new = $this->new;

			def($id);
			def($new, false);

			$model = $this->req('model');
			$rq    = $this->request;
			$cname = \System\Loader::get_class_from_model($model);
			$response = array(
				'message' => 'not-found',
				'status'  => 404,
			);

			if (class_exists($cname) && is_subclass_of($cname, '\System\Model\Perm')) {
				if ($item = $new ? (new $cname()):$cname::find($id)) {
					$data = $rq->post();

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
										$data = $rq->post($val[$val['method']]);

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
							} else if ($def['type'] == 'time') {
								$date = \DateTime::createFromFormat('H:i:s', $val);

								if ($date) {
									$tz = new \DateTimeZone(\System\Settings::get('locales', 'timezone'));
									$date->setTimeZone($tz);
								} else {
									$date = null;
								}

								$item->$attr_name = $date;
							} else if ($def['type'] == 'date') {
								$date = \DateTime::createFromFormat('Y-m-d', $val);

								if ($date) {
									$tz = new \DateTimeZone(\System\Settings::get('locales', 'timezone'));
									$date->setTimeZone($tz);
								} else {
									$date = null;
								}

								$item->$attr_name = $date;
							} else if ($def['type'] == 'datetime') {
								$date = \DateTime::createFromFormat('Y-m-d\TH:i:sO', $val);

								if ($date) {
									$tz = new \DateTimeZone(\System\Settings::get('locales', 'timezone'));
									$date->setTimeZone($tz);
								} else {
									$date = null;
								}

								$item->$attr_name = $date;
							} else {
								$item->$attr_name = $val;
							}
						}
					}

					$item->request = $rq;

					if ($item::has_attr('author') && $rq->user) {
						$item->author = $rq->user;
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
		}
	}
}
