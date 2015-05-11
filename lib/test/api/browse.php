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


		public function test_decode_part()
		{
			$obj = new \Module\Api\Model\Browse(array(
				"page" => 0,
				"request" => new \System\Http\Request(array(
					"get" => array(
						'foo' => '',
						'arr' => '[]',
						'str' => 'str',
					)
				))
			));

			// undefined, should pass as null
			$this->assertNull($obj->request_decode_part('bar'));

			// defined empty, should pass as null
			$this->assertNull($obj->request_decode_part('foo'));

			// defined string, should fail
			try {
				$ex = null;
				$obj->request_decode_part('str');
			} catch (\System\Error\Format $e) {
				$ex = $e;
			}

			$this->assertTrue($ex !== null);
		}

	}
}
