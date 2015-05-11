<?

namespace Test\Api\Browse
{
	class Dummy extends \System\Model\Perm
	{
		static protected $attrs = array(
			"test_join" => array(
				"type"  => 'has_many',
				"model" => 'Test\Api\Browse\Dummy'
			)
		);
	}
}


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


		public function test_parse_joins()
		{
			$obj = new \Module\Api\Model\Browse(array(
				"page" => 0,
				"model" => 'Test.Api.Browse.Dummy',
				"cname" => '\Test\Api\Browse\Dummy',
				"request" => new \System\Http\Request()
			));

			try {
				$ex = null;
				$obj->request_parse_joins(null);
			} catch (\System\Error\Format $ex) {
			}

			$this->assertTrue($ex != null);
			$joins = $obj->request_parse_joins(array('test_join'));

			$this->assertTrue(is_array($joins));
			$this->assertEquals(count($joins), 1);
			$this->assertTrue(array_key_exists(0, $joins));
			$this->assertTrue(array_key_exists('attr', $joins[0]));
			$this->assertTrue(array_key_exists('as', $joins[0]));
			$this->assertEquals($joins[0]['attr'], 'test_join');
			$this->assertEquals($joins[0]['as'], 'test_join');
		}

	}
}
