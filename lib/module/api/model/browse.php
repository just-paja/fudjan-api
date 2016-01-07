<?php

namespace Module\Api\Model
{
	class Browse extends \System\Module
	{
		protected $status = 200;
		protected $joins = array();
		protected $query;
		protected $count;
		protected $sort = array();
		protected $filters = array();
		protected $items = array();
		protected $result = array();


		public function request_decode()
		{
			$this->cname = \System\Loader::get_class_from_model($this->req('model'));
			$this->request_decode_pagination();

			$this->filters = $this->request_decode_part('filters');
			$this->sort    = $this->request_decode_part('sort');
			$this->joins   = $this->request_decode_part('join');

			if (!is_array($this->joins)) {
				$this->joins = array();
			}
		}


		public function request_decode_pagination()
		{
			$this->page = intval($this->req('page'));
			$pp = $this->request->get('per_page');

			if ($pp) {
				$this->per_page = intval($pp);
			}
		}


		public function request_decode_part($part_name)
		{
			$part = html_entity_decode($this->request->get($part_name));

			if ($part) {
				return \System\Json::decode($part);
			}

			return null;
		}


		public function request_parse_joins($joins_in)
		{
			$cname = $this->cname;
			$joins = array();

			if (!is_array($joins_in)) {
				throw new \System\Error\Format('malformed-joins', 'joins-must-be-array');
			}

			foreach ($joins_in as $key=>$attr) {
				if (is_string($attr)) {
					$attr = array(
						"attr" => $attr
					);
				}

				if (!isset($attr['attr'])) {
					$this->status = 400;
					throw new \System\Error\Format('missing-attr-name', $attr);
				}

				if (!isset($attr['as'])) {
					$attr['as'] = $attr['attr'];
				}

				if ($cname::is_rel($attr['attr'])) {
					$joins[$key] = $attr;
				} else {
					$this->status = 400;
					throw new \System\Error\Format('not-a-relation', $attr);
				}
			}

			return $joins;
		}


		public function request_parse_sort($sort)
		{
			$cname = $this->cname;

			if (empty($sort)) {
				return $this->sort = array('created_at desc');
			}

			foreach ($sort as $sort_item) {
				if (is_string($sort_item)) {
					$sort_tmp  = explode(' ', $sort_item);
					$sort_item = array('attr' => $sort_tmp[0]);

					if (isset($sort_tmp[1])) {
						$sort_item['mode'] = $sort_tmp[1];
					}
				}

				if (!is_array($sort_item)) {
					$this->status = 400;
					throw new \System\Error\Format('malformed-sort', 'must-be-array', $sort_item);
				}

				if (empty($sort_item['attr'])) {
					$this->status = 400;
					throw new \System\Error\Format('malformed-sort', 'missing-key-attr', $sort);
				}

				def($sort_item['mode'], 'asc');

				if ($sort_item['attr'] == 'id') {
					$sort_item['attr'] = $cname::get_id_col();
				}

				$sort_by[] = $sort_item['attr'].' '.$sort_item['mode'];
			}

			return $this->sort = $sort_by;
		}


		public function request_parse_filters($filters)
		{
			if (empty($filters)) {
				return $this->filters = array();
			}

			foreach ($filters as $filter=>$filter_val) {
				if (is_numeric($filter) && is_string($filter_val)) {
					$this->status = 400;
					throw new \System\Error\Format('invalid-filter', 'must-be-numeric', $filter, $filter_val);
				}
			}
		}


		public function request_parse()
		{
			$this->joins = $this->request_parse_joins($this->joins);
			$this->request_parse_sort($this->sort);
			$this->request_parse_filters($this->filters);
		}


		public function request_verify()
		{
			$cname = $this->cname;

			if (!class_exists($cname)) {
				$this->status = 404;
				throw new \System\Error\Argument('bad-model-choice', 'unknown-model', $this->model);
			}

			if (!is_subclass_of($cname, '\System\Model\Perm') || !$cname::can_user($cname::BROWSE, $this->request->user)) {
				$this->status = 403;
				throw new \System\Error\Argument('bad-model-choice', 'not-allowed', $this->model);
			}
		}


		public function query_build()
		{
			$cname = $this->cname;

			$this->query = $cname::get_all();

			$this->query_build_joins();
			$this->query_build_filters();
			$this->query_build_pagination();
			$this->query_build_sort();
		}


		public function query_build_joins()
		{
			$cname = $this->cname;

			foreach ($this->joins as $j) {
				$attr = $cname::get_attr($j['attr']);

				if ($attr['type'] == 'has_many' && any($attr['is_bilinear'])) {
					$join_alias = 't_'.$j['attr'];
					$table_name = $cname::get_bilinear_table_name($attr);
					$rel_model = $attr['model'];
					$using = $cname::get_id_col();

					$this->query->join($table_name, "USING(".$using.")", $join_alias);
				}
			}
		}


		public function query_build_filters()
		{
			$cname = $this->cname;
			$idc = $cname::get_id_col();

			foreach ($this->filters as $filter=>$filter_val) {
				if ($filter == 'id') {
					$filter = $idc;
				}

				if (is_array($filter_val)) {
					if (array_keys($filter_val) !== range(0, count($filter_val) - 1)) {
						$this->query->add_filter($filter_val);
					} else {
						$this->query->where_in($filter, $filter_val);
					}
				} else {
					if ($cname::has_attr($filter)) {
						$this->query->where(array($filter => $filter_val));
					} else if ($cname::has_filter($filter)) {
						$cname::filter($query, $filter, $filter_val);
					} else {
						$this->status = 400;
						throw new \System\Error\Format('invalid-filter', 'unknown-attr', $filter);
					}
				}
			}
		}


		public function query_build_pagination()
		{
			$this->query->paginate($this->per_page, $this->page);
		}


		public function query_build_sort()
		{
			$this->query->sort_by(implode(',', $this->sort));
		}


		public function query_fetch()
		{
			$this->items = $this->query->fetch();
			$this->count = intval($this->query->count());
		}


		public function result_filter()
		{
			$this->result = array();

			foreach ($this->items as $item) {
				$obj = $item->to_object_with_perms($this->request->user);
				$this->result_filter_joins($item, $obj);
				$this->result[] = $obj;
			}
		}


		public function result_filter_joins($item, &$obj)
		{
			$cname = $this->cname;

			foreach ($this->joins as $attr) {
				$attr_name = $attr['attr'];
				$def = $cname::get_attr($attr_name);

				if (in_array($def['type'], array($cname::REL_BELONGS_TO, $cname::REL_HAS_ONE))) {
					$rel = $item->$attr_name;

					if ($rel) {
						if (!($rel instanceof \System\Model\Perm) || !$rel->can_be($cname::BROWSE, $this->request->user)) {
							$this->status = 403;
							throw new \System\Error\Argument('denied-access-to-relation', $attr_name);
						}

						$obj[$attr['as']] = $rel->to_object_with_perms($this->request->user);
					}
				} else if ($def['type'] == $cname::REL_HAS_MANY) {
					$rel_cname = $def['model'];

					if (!$rel_cname::can_user($rel_cname::BROWSE, $this->request->user)) {
						$this->status = 403;
						throw new \System\Error\Argument('denied-access-to-relation', $attr_name);
					}

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
						if ($rel_obj->can_be($cname::BROWSE, $this->request->user)) {
							$obj[$attr['as']][] = $rel_obj->to_object_with_perms($this->request->user);
						}
					}
				}
			}
		}


		public function run()
		{
			$this->request_decode();
			$this->request_parse();
			$this->request_verify();

			$this->query_build();
			$this->query_fetch();

			$this->result_filter();

			$response['status'] = $this->status;
			$response['total']  = $this->count;
			$response['data']   = $this->result;

			$this->partial(null, $response);
		}
	}
}
