<?php
class CompanySelect extends MultiCompanyLoginModel {
    /**
     * FetchByUserID()
     * @params: $uid - The user ID to get details of.
     *
     * @return: array():
     *  [0] - Email of user ID
     *  [1] - Companies that email is attached to
     **/
    public function FetchByUserID($uid){
        Loader::loadComponents($this, array("Record", "Session"));
        
        $email = $this->Record->select("contacts.email")->from("contacts")->
            leftJoin("clients", "clients.id", "=", "contacts.client_id", false)->
            where("clients.user_id", "=", $uid)->fetch()->email;
        
        /**
         * The reason why we don't place a "WHERE company.id != Blesta.company_id
         * is due to the fact it makes it impossible to switch back to the original profile.
         *
         * This method is only called once at login so to circumvent this we
         * simply limit it when we generate the dropdown.
         **/
        $companies = $this->Record->select(
            array("companies.id", "companies.name", "companies.hostname", "clients.id" => "user_id")
        )->from("companies")->
        leftJoin("client_groups", "client_groups.company_id", "=", "companies.id", false)->
        leftJoin("clients", "clients.client_group_id", "=", "client_groups.id", false)->
        leftJoin("users", "users.id", "=", "clients.user_id", false)->
        leftJoin("contacts", "contacts.client_id", "=", "clients.id", false)->
        where("contacts.email", "=", $email)->
        where("clients.status", "=", "active")->fetchAll();
        
        return array($email, $companies);
    }
}

?>