<?php
class MethodList extends ApihelperModel {
    /**
     * Plugins can have API methods as well so we need to load those.
     **/
    private function get_plugins($dir = PLUGINDIR){
        Loader::loadComponents($this, array("Record"));
        
        // Get all plugins installed for this specific company
        $installed_plugins = $this->Record->select("dir")->from("plugins")->where("company_id", "=", Configure::get("Blesta.company_id"))->fetchAll();
        
        $plugins = array();
        
        // As long as we have a plugin directory to open, lets do it!
        if($pdir = opendir($dir)){
            while(($folder = readdir($pdir)) !== false){
                // Ignore ".", ".." and hidden files
                if($folder[0] != "." && is_dir($dir . DS . $folder)){
                    // Switch on if plugin was found as installed or not
                    $found = false;
                    
                    // Loop through each one, if not installed we go to the next plugin
                    foreach($installed_plugins as $installed){
                        if($installed->dir == $folder){
                            $found = true;
                            break;
                        }
                    }
                    
                    if(!$found)
                        continue;
                    
                    // Remove double directory seprators
                    $path = str_replace(DS . DS, DS, $dir . DS . $folder);
                    
                    /**
                     * /lib/loader.php: load($file)
                     * Simply checks to see if $file exists, and if so
                     * includes it.  This is needed.
                     **/
                    Loader::load($path . DS . $folder . "_model.php");
                    
                    $mdir = $path . DS . "models";
                    
                    /**
                     * Not all plugins (i.e.: shared_plugin) has a models
                     * folder.  If it doesn't exist just go to the next
                     * plugin.
                     **/
                    if(!is_dir($mdir))
                        continue;
                    
                    $plugins[$folder] = $this->get_models($mdir);
                    
                    /**
                     * If the plugin doesn't have any available calls,
                     * don't present it to the user.
                     **/
                    if(empty($plugins[$folder]))
                        unset($plugins[$folder]);
                }
            }
            
            closedir($pdir);
        }
        
        return $plugins;
    }
    
    private function get_models($dir = MODELDIR){
        $data = array();
        
        if($handle = opendir($dir)){
            while(false !== ($entry = readdir($handle))){
                if($entry[0] != "."){
                    $data[] = $this->loadcls($dir . DS . $entry);
                }
            }
                
            closedir($handle);
        }
        
        return $data;
    }
    
    public function fetch($type = ""){
        $data = array();
        
        if($type == "models"){
            $data = $this->get_models();
        } else if($type == "plugins"){
            $data = $this->get_plugins();
        } else if($type == "all"){
            $data['models'] = $this->get_models();
            $data['plugins'] = $this->get_plugins();
            $data['self'] = $this->loadcls(__FILE__);
        } else {
            $data = $this->loadcls(__FILE__);
        }
        
        return $data;
    }
    
    private function loadcls($file){
        /**
         * Get all known classes in a file.
         **/
        $classes = $this->get_classes($file);
        
        $methods = array();
        
        /**
         * We have to include the file to get its classes.
         **/
        if(file_exists($file)){
            @include_once($file);
        }
        
        foreach($classes as $cls){            
            $ref = new ReflectionClass($cls);
            
            // Get all of the class' public methods only
            $clsmthds = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
            
            foreach($clsmthds as $mthd){
                /**
                 * We also get inherited methods as well, so we do our best
                 * to ignore those as well as constructor classes.
                 **/
                if($mthd->class == $cls && $mthd->name != "__construct")
                    $methods[$cls][] = $mthd->name;
            }
        }
        
        return $methods;
    }
    
    /**
     * Offers method to test API authentication.
     **/
    public function test($str){
        return $str;
    }
    
    /**
     * Get all classes in file.
     **/
    private function get_classes($fn){
        $classes = array();
        
        $tokens = token_get_all( file_get_contents($fn) );
        $class_token = false;
        foreach ($tokens as $token) {
            if ( !is_array($token) ) continue;
            if ($token[0] == T_CLASS) {
                $class_token = true;
            } else if ($class_token && $token[0] == T_STRING) {
                $classes[] = $token[1];
                $class_token = false;
            }
        }
        
        return $classes;
    }
}

?>