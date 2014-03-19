<?

$this->req('id');
$this->req('model');

def($conds, array());
def($opts, array());

$model = System\Loader::get_class_from_model($model);

if ($item = find($model, $id)) {
	$this->json_response(200, $item->to_object());
} else $this->json_response(404, 'Object was not found');
