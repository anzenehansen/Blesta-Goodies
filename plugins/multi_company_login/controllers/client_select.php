<?php
class ClientSelect extends MultiCompanyLoginController {
    public function preAction(){
        parent::preAction();
	
	Loader::loadComponents($this, array("Session", "Record"));
	
        // Restore structure view location of the client portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);
	
	Language::loadLang("client_select", null, PLUGINDIR . "multi_company_login" . DS . "language" . DS);
    }

    public function index(){
	if(!empty($this->post)){
	    $data = $this->post['company'];
	    
	    $parts = explode(",", $data);
	    $cid = $parts[0];
	    $uid = $parts[1];
	    
	    $this->Session->write("blesta_company_id", $cid);
	    $this->Session->write("mcl_new_id", $cid);
	    $this->Session->write("blesta_client_id", $uid);
	    Configure::set("Blesta.company_id", $cid);
	    $this->redirect($this->base_uri);
	} else{
	    $data = unserialize($this->Session->read("mcl_company_data"));
	}
	
	$this->set("data", $data);
    }
}
