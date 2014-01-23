<?php
class LdapAuthPlugin extends Plugin {
	public function __construct(){
		$this->loadConfig(dirname(__FILE__) . DS . "config.json");
		
		Loader::loadComponents($this, array("Input", "Record"));
	}
	
	// Lets tell Blesta what events we want to process
	public function getEvents(){
		return array(
			array(
				"event" => "Users.pre_auth",
				"callback" => array("this", "auth")
			)
		);
	}
	
	public function auth($event){
		// Get the params passed via /app/models/users.php->auth()
		$params = $event->getParams();
		
		// Get the company ID based on the HTTP headers (Host)
		$cid = $this->Record->select("companies.id")->from("companies")->where("hostname", "=", $_SERVER['HTTP_HOST'])->fetch()->id;
		
		// Get the user ID based on the username (if the user exists)
		$user_id = $this->Record->select("id")->from("users")->where("username", "=", $params['username'])->fetch();
		
		if(!$user_id)
			return 0;
		
		$user_id = $user_id->id;
		
		// Check if we're authenticating a staff member or not
		$is_admin = $this->Record->select("id")->from("staff")->where("user_id", "=", $user_id)->numResults();
		
		// Get the LDAP info specific to this company
		$ldap_info = $this->Record->select()->from("ldap_auth_settings")->where("company_id", "=", $cid)->fetch();
		
		// Get the DN based on if user is admin or not
		$dn = ($is_admin == 1) ? $lap_info->staffdn : $ldap_info->clientdn;
		
		// Formulate the RDN to authenticate the user
		$ldap_rdn = str_replace("%u", $params['username'], $dn) . "," . $ldap_info->basedn;
		
		// Try to connect to the LDAP server
		$ds = ldap_connect($ldap_info->hostname, $ldap_info->port);
		
		$ret = 0;
		
		if($ds){
			// Enable TLS?
			if($ldap_info->ssl)
				ldap_start_tls($ds);
			
			// Attempt to bind to the LDAP server using the specific information
			if(ldap_bind($ds, $ldap_rdn, $params['password']))
				$ret = 2;
				
			ldap_close($ds);
		}
		
		return $ret;
	}
	
	// Install process for plugin
	public function install($plugin_id){
		// If we can't use ldap_* functions error out
		if(!function_exists("ldap_search")){
			$this->Input->setErrors(
				array(
					"ldap" => array(
						"invalid" => "PHP-LDAP not installed or found."
					)
				)
			);
			
			return;
		}
		
		// Create the table to store our data
		$this->Record->
			setField("id", array("type" => "int", "size" => 10, "unsigned" => true, "auto_increment" => true))->
			setField("baseDN", array("type" => "varchar", "size" => 255))->
			setField("staffDN", array("type" => "varchar", "size" => 255))->
			setField("clientDN", array("type" => "varchar", "size" => 255))->
			setField("company_id", array("type" => "int", "size" => "10", "unsigned" => true))->
			setField("host", array("type" => "varchar", "size" => 255))->
			setField("port", array("type" => "int", "size" => 10, "unsigned" => true))->
			setField("ssl", array("type" => "int", "size" => 1, "unsinged" => true))->
			setKey(array("id"), "primary")->create("ldap_auth_settings");
	}
	
	// Happns when a person clicks the "Uninstall" button :()
	public function uninstall($plugin_id, $last_instance){
		$this->Record->drop("ldap_auth_settings");
	}
}
?>
