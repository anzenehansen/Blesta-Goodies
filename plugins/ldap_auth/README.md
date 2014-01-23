LDAP Authentication Plugin
===========================

Since Blesta has yet to implement this (or even allowing custom authentication
methods), I decided to do it myself.  Turns out most of the work is easy.

There's a few (small) changes that need to be done to the core code:

*/install_dir/components/events/default/events_users_callback.php*

```php
/* Add this function */
        public static function pre_auth(EventObject $event) {
                return parent::triggerPluginEvent($event);
        }
```

*/install_dir/app/models/users.php*

```php
/* Add this function */
        public function pre_auth($username, array $vars, $type="any"){
                $this->Events->register("Users.pre_auth", array("EventsUsersCall
back", "pre_auth"));
                $res = $this->Events->trigger(new EventObject("Users.pre_auth", $vars));

                return 1;
        }
```

```php
/* Modify public function auth(...) like so */
        public function auth($username, array $vars, $type="any") {
                if (!isset($vars['username']))
                        $vars['username'] = $username;

                $ret = $this->pre_auth($username, $vars, $type);

                if(!$ret){
                        return false;
                }

                ....
                
                $user = $this->Record->fetch();

                if(($ret == 2) && ($user)){
                        $vars['user'] = $user;
                        return true;
                }

                $authorized = false;
                
                ...
        }
```
    
What this does is create a separate method to hook an event into (pre_auth)
that takes the same arguments as auth().

pre_auth is a simple method that will a value based on the
authentication's result.

pre_auth Return Values
-----------------------
Given the code of Blesta I made this very simple:

* **0** - Authentication failure
* **1** - Success, but continue processing MySQL tables as well
* **2** - Success, don't bother checking MySQL tables

Why Do This?
-------------
My company uses OpenLDAP to authenticate users for our services.  We didn't want
them to have to remember yet another password to our system.  Blesta's
developers, however, have not had the chance to implement something like this.

So, I did it for them.  Its not that difficult and feel it opens up
a lot more avenues for them.

Table Structure/Options
------------------------
This plugin supports multiple companies, so if you have different OU's and such
for each ocmpany you don't have to worry about that.

Table structure is as follows:

* id - Unique ID for each company's LDAP
* baseDN - Root/base DN for all requests for company (i.e.: dc=example,dc=com)
* staffDN - DN for staff/admins (i.e.: cn=%u,ou=Staff,o=IT)
* clientDN - Same as staffDN but for clients
* company_id - The company ID to use this for
* host - The LDAP server
* port - Port for host connection
* ssl - Use TLS/SSL with connection?

To Do
------
The following still needs to be done:

* Hook into Users.create event so user is added to LDAP server
* Create event for user deletion to delete LDAP record (?)
* Allow editing of LDAP entries
* Allow only choosing companies that don't have an entry in table yet