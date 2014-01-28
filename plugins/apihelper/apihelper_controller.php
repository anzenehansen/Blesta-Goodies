<?php
class ApihelperController extends AppController {
	public function preAction(){
		parent::preAction();

		$this->view->view = "default";
		$this->structure->view = "default";
	}
}
?>
