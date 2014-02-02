<?php
class MultiCompanyLoginPlugin extends Plugin {
	public function __construct(){
		$this->loadConfig(dirname(__FILE__) . DS . "config.json");
		
		Language::loadLang("multi_company_login_plugin", null, PLUGINDIR . "multi_company_login" . DS . "language" . DS);
	}
	
	public function getEvents(){
		return array(
			array(
				"event" => "Users.login",
				"callback" => array("this", "company_sessions")
			)
		);
	}
	
	public function getActions(){
		return array(
			/** Generates the link found on the client's home **/
			array(
				'action' => "nav_primary_client",
				'uri' => "plugin/multi_company_login/client_select/",
				'name' => Language::_("MultiCompanyLoginPlugin.name", true)
			)
		);
	}
	
	/**
	 * Triggered when a user logs in.
	 *
	 * Gets all of the companies said user is a part of.
	 **/
	public function company_sessions($event){
		$params = $event->getParams();
		
		$user_id = $params['user_id'];
		
		Loader::loadModels($this, array("MultiCompanyLogin.CompanySelect"));
		$data = $this->CompanySelect->FetchByUserID($user_id);
		$email = $data[0];
		$companies = $data[1];
		Loader::loadComponents($this, array("Session"));
		$this->Session->write("mcl_company_data", serialize($companies));
		$this->Session->write("mcl_email", $email);
	}
}
?>
