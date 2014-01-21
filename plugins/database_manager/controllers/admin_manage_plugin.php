<?php
class AdminManagePlugin extends AppController {
    // Basic function to call before any views are rendered
    private function init(){
        $this->view->setView(null, "DatabaseManager.default");
        
        // We need access to the database and generic class
        Loader::loadComponents($this, array("Record", "Input"));
        
        // Get the plugin's ID for links
        $this->plugin_id = (isset($this->get[0]) ? $this->get[0] : null);
        
        // Store it in every call
        $this->vars = array('plugin_id' => $this->plugin_id);
    }
    
    // Called by default (i.e.: /)
    public function index(){
        $this->init();
        
        // As far as I know Blesta doesn't support the SHOW query be default
        $this->vars['res'] = $this->Record->query("SHOW TABLES")->fetchAll();
        
        return $this->partial("admin_manage_plugin", $this->vars);
    }
    
    // Perform a backup (copied mostly from app/controllers/admin_system_backup.php)
    public function backup(){
        $this->init();
        
        $this->uses(array("backup"));
        
        if(!isset($this->Backup)){
            $this->vars['errors'] = $this->setMessage("error", "Backup component not loaded!", true, null, false);
            return $this->partial("admin_manage_plugin", $this->vars);
        }
        
        $this->Backup->download();
        
        if(($errors = $this->Backup->errors())){
            $this->vars['errors'] = $this->setMessage("error", $errors, true, null, false);
            return $this->partial("admin_manage_plugin", $this->vars);
        }
    }

    public function restore(){
        $this->init();
        
        // If we have an uploaded file and it's (hopefully) legit...
        if(!empty($this->files) && !empty($this->files['file'])){
            Loader::loadComponents($this, array("Upload", "SettingsCollection"));
            
            // Get the company's temporary upload directory
            $temp = $this->SettingsCollection->fetchSetting(null, Configure::get("Blesta.company_id"), "u
ploads_dir");
            $upload_path = $temp['value'] . Configure::get("Blesta.company_id") . DS . "download_files" .
 DS;
 
            // Should we overwrite and/or purge the database stuff?
            $overwrite = ($this->post['overwrite'] == "on") ? true : false;
            $purge = ($this->post['purge'] == "on") ? true : false;
            
            $this->Upload->setFiles($this->files);
            $this->Upload->setUploadPath($upload_path);
            $fn = $this->files['file']['name'];
            
            if(!($errors = $this->Upload->errors())){
                $this->Upload->writeFile("file", $overwrite, $fn);
                $data = $this->Upload->getUploadData();
                
                if(isset($data['file']['full_path'])){
                    $this->rebuild($data['file']['full_path'], $purge);
                }
                
                $this->vars['errors'] = $this->Upload->errors();
            }
            
            if($errors){
                $this->Input->setErrors($errors);
                @unlink($upload_path . $fn);
                
                $this->vars['errors'] = $this->setMessage("error", $errors, true, null, false);
            }
        }
        
        return $this->partial("admin_restore", $this->vars);
    }
    
    // Deletes all data from tables that are unessential to Blesta
    public function purge(){
        // Specific tables that need to be undeleted in order for
        // rebuild to be successful
        $exclude = array(
            "sessions", "companies", "company_settings", "settings",
            "plugin_events", "plugins", "staff_links", "staff_group",
            "staff_groups", "staff_group_notices", "email_groups",
            "themes", "staff", "users", "staff_settings",
            "permission_groups", "permissions", "acl_acl", "acl_aco",
            "acl_aro", "log_users", "plugin_actions", "languages"
        );
        
        $this->init();
        
        $tables = $this->Record->query("SHOW TABLES")->fetchAll();
        
        foreach($tables as $index=>$tbl){
            if(!in_array($tbl->tables_in_blesta, $exclude))
                $this->Record->truncate($tbl->tables_in_blesta);
        }
    }
    
    // Recreate database
    public function rebuild($file="", $purge=false){
        Loader::loadComponents($this, array("SettingsCollection"));
	$temp = $this->SettingsCollection->fetchSystemSetting(null, "temp_dir");
	
        $this->init();
        
        if($file == ""){
            $file = $this->buildDump($temp);
        }
        
        if($purge)
            $this->purge();
        
        $db_info = Configure::get("Database.profile");
	exec("mysql --host=" . escapeshellarg($db_info['host']) . " --user=" . escapeshellarg($db_info['user']) .
				" --password=" . escapeshellarg($db_info['pass']) . " " . escapeshellarg($db_info['database']) . " < " .
				escapeshellarg($file));
        
        unlink($file);
        
        $this->vars['errors'] = $this->setMessage("error", $file, true, null, false);
        
        //return $this->partial("admin_manage_plugin", $this->vars);
        $this->redirect($this->base_uri . "settings/company/plugins/manage/" . $this->plugin_id . "/");
    }
    
    private function buildDump($temp) {
	$db_info = Configure::get("Database.profile");
	
	Loader::loadComponents($this, array("Input"));
	$temp_dir = (isset($temp['value']) ? $temp['value'] : null);
	
	$this->Input->setRules($this->getRules());
        
	$vars = array(
		'temp_dir' => $temp_dir,
		'db_info' => $db_info
	);
		
	if ($this->Input->validates($vars)) {
	    // ISO 8601
	    $file = $db_info['database'] . "_" . date("Y-m-d\THis\Z");
	    $file_name = $temp_dir . $file . ".sql";
			
	    exec("mysqldump --host=" . escapeshellarg($db_info['host']) . " --user=" . escapeshellarg($db_info['user']) .
		    " --password=" . escapeshellarg($db_info['pass']) . " " . escapeshellarg($db_info['database']) . " > " .
		    escapeshellarg($file_name));
			
	    // GZip the file if possible
	    if (function_exists("gzwrite")) {
		$chunk_size = 4096;
		$compress_file_name = $file_name . ".gz";
		// Compress as much as possible
		$gz = gzopen($compress_file_name, "wb9");
		$fh = fopen($file_name, 'rb');
				
                // Read from the original and write in chunks to preserve memory
		while (!feof($fh)) {
		    $data = fread($fh, $chunk_size);
		    if ($data)
			gzwrite($gz, $data);
		}
		unset($data);
		$compressed = gzclose($gz);
				
		// Remove the original data file
		if ($compressed) {
		    unlink($file_name);
		    return $compress_file_name;
		}
	    }
			
	    return $file_name;
	}
    }
    
            private function getRules() {
                $rules = array(
                        'temp_dir' => array(
                                'writable' => array(
                                        'rule' => "is_writable",
                                        'message' => "Temporary directory is not writable!"
                                )
                        ),
                        'db_info[driver]' => array(
                                'support' => array(
                                        'rule' => array("compares", "==", "mysql"),
                                        'message' => "Currently only MySQL is supported!"
                                )
                        )
                );

                return $rules;
        }
}
?>