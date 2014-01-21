<?php
class DatabaseManagerController extends AppController {
	public function preAction(){
		parent::preAction();

		// Require login
		// $this->requireLogin();

		$this->uses(array("Backup"));
	
		$this->view->view = "default";
		$this->orig_structure_view = $this->structure->view;
		$this->structure->view = "default";
	}
}
?>
