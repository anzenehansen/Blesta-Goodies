<?php
class MultiCompanyLoginModel extends AppModel {
    public function GetUserCompanies($uid){
    /*    if(!isset($this->Record))
            Load::loadComponents($this, array("Record"));
        
        $email = $this->Record->select("contacts.email AS email")->
        from("contacts")->
        leftJoin("clients", "clients.user_id", "=", $uid)->
        where("contacts.client_id", "=", "clients.id")->fetch()->email;
        
        $companies = $this->Record->select(
            array("companies.id", "companies.name", "companies.hostname")
        )->from("companies")->
        leftJoin("client_groups", "client_groups.company_id", "=", "companies.id")->
        leftJoin("clients", "clients.client_group_id", "=", "client_groups.id")->
        leftJoin("users", "users.id", "=", "clients.user_id")->
        leftJoin("contacts", "contacts.client_id", "=", "clients.id")->
        where("contacts.email", "=", $email)->
        where("clients.status", "=", "active")->fetchAll();
        
        return array($email, $companies);*/
    }
}

?>
