<?php
/**
 * API Helper
 *
 * This plugin provides another interface to the API.
 * By default, the API for Blesta doesn't include a way to find out what
 * methods are available.  This does, however.
 *
 * This also provides a simple test to see if you can successfully call
 * API methods.  This is done in models/method_list.php: public function test()
 *
 * It will echo whatever is passed back to it.
 **/
class ApihelperPlugin extends Plugin {
	public function __construct(){
		$this->loadConfig(dirname(__FILE__) . DS . "config.json");
	}
}
?>
