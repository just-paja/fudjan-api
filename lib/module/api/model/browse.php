<?

namespace Module\Api\Model
{
	class Browse extends \System\Module
	{
		public function run()
		{
			def($conds, array());
			def($opts, array());

			$model = $this->req('model');
			$page = $this->req('page');
			$rq = $this->request;

			$response = array(
				'status'  => 200,
				'message' => null,
			);

			$cname = \System\Loader::get_class_from_model($model);
			$status = 200;
			$meta = null;

			$filters = html_entity_decode($rq->get('filters'));
			$sort    = html_entity_decode($rq->get('sort'));
			$joins   = html_entity_decode($rq->get('join'));

			$sort_by = array();

			if ($rq->get('per_page')) {
				$per_page = intval($rq->get('per_page'));
			}

			if (class_exists($cname) && is_subclass_of($cname, '\System\Model\Perm')) {
				if ($cname::can_user($cname::BROWSE, $rq->user)) {
					def($sort, array('created_at desc'));

					$idc = $cname::get_id_col();

					if ($filters) {
						try {
							$filters = \System\Json::decode($filters);
						} catch (\System\Error\Format $e) {
							$response['status'] = 400;
							$response['message'] = 'malformed-filter';
						}
					}

					if ($sort) {
						try {
							$sort = \System\Json::decode($sort);
						} catch (\System\Error\Format $e) {
							$response['status'] = 400;
							$response['message'] = 'malformed-sort';
						}
					}

					if ($joins) {
						try {
							$joins = \System\Json::decode($joins);
						} catch (\System\Error\Format $e) {
							$response['status'] = 400;
							$response['message'] = 'malformed-joins';
						}

						if ($joins) {
							foreach ($joins as $key=>$attr) {
								if (is_string($attr)) {
									$attr = array(
										"attr" => $attr
									);
								}

								if (!isset($attr['attr'])) {
									$response['status'] = 400;
									$response['message'] = 'missing-attr-name';
									$response['attr'] = $attr;
									break;
								}

								if (!isset($attr['as'])) {
									$attr['as'] = $attr['attr'];
								}

								if ($cname::is_rel($attr['attr'])) {
									$joins[$key] = $attr;
								} else {
									$response['status'] = 400;
									$response['message'] = 'not-a-relation';
									$response['attr'] = $attr;
								}
							}
						}
					}

					if ($response['status'] == 200) {
						$query = $cname::get_all($conds, $opts);
						$data = array();

						if (any($sort)) {
							foreach ($sort as $sort_item) {
								if (is_string($sort_item)) {
									$sort_tmp  = explode(' ', $sort_item);
									$sort_item = array('attr' => $sort_tmp[0]);

									if (isset($sort_tmp[1])) {
										$sort_item['mode'] = $sort_tmp[1];
									}
								}

								if (is_array($sort_item)) {
									def($sort_item['mode'], 'asc');

									if (isset($sort_item['attr'])) {
										if ($sort_item['attr'] == 'id') {
											$sort_item['attr'] = $cname::get_id_col($cname);
										}

										$sort_by[] = $sort_item['attr'].' '.$sort_item['mode'];
									} else {
										$response['status'] = 400;
										$response['message'] = 'malformed-sort';
										$response['attr'] = $sort;
										break;
									}
								} else {
									$response['status'] = 400;
									$response['message'] = 'malformed-sort';
									$response['attr'] = $sort_item;
									break;
								}
							}
						} else {
							$sort_by = array('created_at desc');
						}
					}


					if ($response['status'] == 200) {
						if (any($filters)) {
							foreach ($filters as $filter=>$filter_val) {
								if ($filter == 'id') {
									$filter = $idc;
								}

								if (is_array($filter_val)) {
									if (array_keys($filter_val) !== range(0, count($filter_val) - 1)) {
										try {
											$query->add_filter($filter_val);
										} catch(\System\Error\Argument $e) {
											$response['status'] = 400;
											$response['message'] = 'invalid-filter';
											$response['attr'] = array(
												'filter' => $filter,
												'val' => $filter_val
											);
											break;
										}
									} else {
										$query->where_in($filter, $filter_val);
									}
								} else if (is_numeric($filter) && is_string($filter_val)) {
									$response['status'] = 400;
									$response['message'] = 'invalid-filter';
									$response['attr'] = array(
										'filter' => $filter,
										'val' => $filter_val
									);
									break;

								} else {
									if ($cname::attr_exists($filter)) {
										$query->where(array($filter => $filter_val));
									} else if ($cname::has_filter($filter)) {
										$cname::filter($query, $filter, $filter_val);
									} else {
										$response['status'] = 400;
										$response['message'] = 'unknown-attr';
										$response['attr'] = $filter;
										break;
									}
								}
							}
						}
					}


					if ($response['status'] == 200) {
						$query->paginate($per_page, $page)->sort_by(implode(', ', $sort_by));
						$send = true;

						try {
							$items = $query->fetch();
							$count = $query->count();
						} catch (\System\Error\Database $e) {
							$send = false;

							$response['status'] = 400;
							$response['message'] = $e->get_explanation();
						}

						if ($send) {
							$valid = true;

							foreach ($items as $item) {
								$obj = $item->to_object_with_perms($rq->user);

								if ($joins) {
									foreach ($joins as $attr) {
										$allowed = true;
										$attr_name = $attr['attr'];
										$def = $cname::get_attr($attr_name);

										if (in_array($def[0], array($cname::REL_BELONGS_TO, $cname::REL_HAS_ONE))) {
											$rel = $item->$attr_name;

											if ($rel) {
												if (($rel instanceof \System\Model\Perm) && $rel->can_be($cname::BROWSE, $rq->user)) {
													$obj[$attr['as']] = $rel->to_object();
												} else {
													$allowed = false;
												}
											}
										} else if ($def[0] == $cname::REL_HAS_MANY) {
											$rel_cname = $def['model'];

											if ($rel_cname::can_user($rel_cname::BROWSE, $rq->user)) {
												$rel = $item->$attr_name;

												if (isset($attr['filters'])) {
													$rel->add_filters($attr['filters']);
												}

												if (isset($attr['limit'])) {
													$rel->paginate($attr['limit']);
												}

												$rel_data = $rel->fetch();
												$obj[$attr['as']] = array();

												foreach ($rel_data as $rel_obj) {
													if ($rel_obj->can_be($cname::BROWSE, $rq->user)) {
														$obj[$attr['as']][] = $rel_obj->to_object_with_perms($rq->user);
													}
												}
											} else {
												$allowed = false;
											}
										}

										if (!$allowed) {
											$valid = false;
											$response['status'] = 403;
											$response['message'] = 'access-denied';
											$response['errors'] = array(
												array(
													'rel'     => $attr,
													'message' => 'denied'
												)
											);
											break;
										}
									}
								}

								$data[] = $obj;
							}

							if ($valid) {
								$response['total'] = intval($count);
								$response['data'] = $data;
							}
						}
					}
				} else {
					$response['status'] = 403;
					$response['message'] = 'access-denied';
				}
			} else {
				$response['status'] = 404;
				$response['message'] = 'model-not-found';
			}

			$this->partial(null, $response);
		}
	}
}
