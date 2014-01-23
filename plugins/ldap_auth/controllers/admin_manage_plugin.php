<?php
class AdminManagePlugin extends AppController {
   
    // Basic function to call before any views are rendered
    private function init(){
        $this->view->setView(null, "ldap_auth.default");
        
        // We need access to the database and generic class
        Loader::loadComponents($this, array("Record", "Input"));
        $this->uses(array("companies"));
        
        // Get the plugin's ID for links
        $this->plugin_id = (isset($this->get[0]) ? $this->get[0] : null);
        
        // Store it in every call
        $this->vars = array('plugin_id' => $this->plugin_id);
    }
    
    // Called by default (i.e.: /)
    public function index(){
        Loader::loadComponents($this, array("Session"));
        
        $this->init();
        
        if($this->post){
            $post = $this->post;
            
            if(empty($post['basedn']) || empty($post['staffdn']) || empty($post['clientdn'])){
                $this->vars['errors'] = $this->setMessage("error", "One or more fields were left empty.", true, null, false);
            } else{
                $this->Record->insert("ldap_auth_settings",
                    array(
                        "baseDN" => $post['basedn'],
                        "staffDN" => $post['staffdn'],
                        "clientDN" => $post['clientdn'],
                        "company_id" => $post['company'],
                        "host" => $post['host'],
                        "port" => $post['port'],
                        "ssl" => ($post['ssl'] == "on") ? 1 : 0
                    )
                );
                
                if($this->Record->lastInsertId() > 0){
                    $this->vars['errors'] = $this->setMessage("message", "Successfully added LDAP information.", true, null, false);
                }
            }
        }
        
        // Only let the staff member add LDAP connections to companies they have access to
        $c = $this->Companies->getAllAvailable($this->Session->read("blesta_staff_id"));
        
        $companies = array();
        
        foreach($c as $comp){
            $companies[$comp->id] = $comp->name . " (" . $comp->hostname .")";
        }
        
        $this->vars['companies'] = $companies;
        
        return $this->partial("admin_manage_plugin", $this->vars);
    }
}
?>