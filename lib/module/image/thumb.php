<?

$name = $request->get('name');
$size = $request->get('size');

$dirs = array('/var/files', '/share/pixmaps');

if ($name && $size) {
	$name = str_replace('../', '', $name);
	$path = null;
	$size = explode('x', $size);

	def($size[0], null);
	def($size[1], null);

	foreach ($dirs as $dir) {
		$help = substr($dir, 1);

		if (strpos($name, $help) === 0) {
			$name = str_replace($help, '', $name);
		}

		$path = \System\Composer::resolve($dir.'/'.$name);

		if ($path) {
			break;
		}
	}

	if ($path) {
		$im  = \System\Image::from_path($path);
		$url = $im->thumb($size[0], $size[1]);

		$response->redirect($url, \System\Settings::get('dev', 'debug', 'backend') ?
			\System\Http\Response::TEMPORARY_REDIRECT:
			\System\Http\Response::MOVED_PERMANENTLY
		);
	} else throw new \System\Error\NotFound();
} else throw new \System\Error\NotFound();
