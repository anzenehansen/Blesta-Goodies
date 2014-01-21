<?php
class DatabaseManagerPlugin extends Plugin {
	public function __construct(){
		$this->loadConfig(dirname(__FILE__) . DS . "config.json");
	}
}
?>
