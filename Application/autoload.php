<?php
function autoload() {
	spl_autoload_register(function($className) {
		$pathArr = explode('\\', $className);
		$fileName = array_pop($pathArr);
		$path = '../library';
		foreach ($pathArr as $v) {
			$path .= '/' . $v;
		}
		$path .= '/' . $fileName . '.php';
		if (file_exists($path)) {
			include ($path);
		}
	});
}
autoload();
?>
