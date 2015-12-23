<?php

namespace Module\Api\Model
{
	class Pack extends \System\Module
	{
		public function run()
		{
			$this->flow->redirect(\System\Resource::get_url('media', 'schema', $this->name));
		}
	}
}
