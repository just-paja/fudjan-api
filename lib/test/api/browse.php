<?

namespace Test\Api
{
	class Browse extends \PHPUnit_Framework_TestCase
	{
		public function test_decode_pagination()
		{
			$obj = new \Module\Api\Model\Browse(array(
				"page" => 0,
				"request" => new \System\Http\Request()
			));

			$obj->request_decode_pagination();

			$this->assertEquals($obj->page, 0);
			$this->assertEquals($obj->per_page, null);

			$obj->page = 1;
			$obj->request->get = array("per_page" => 5);

			$obj->request_decode_pagination();

			$this->assertEquals($obj->page, 1);
			$this->assertEquals($obj->per_page, 5);

			$obj->request->get = array("per_page" => 'asd');

			$obj->request_decode_pagination();
			$this->assertEquals($obj->per_page, 0);
		}

	}
}
