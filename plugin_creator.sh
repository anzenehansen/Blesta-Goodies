#!/bin/bash

# Get name of plugin as an argument or prompt user for it
PLUGIN="$1"

while [ "$PLUGIN" == "" ] || [ -e "plugins/$PLUGIN" ]; do
	read -p "Enter plugin name: " PLUGIN
done

# Create folder structure of the plugin
echo -n "> Creating plugin structure for $PLUGIN..."
mkdir -p plugins/$PLUGIN/{controllers,models,views,language}

# Create empty necessary files (touch wouldn't work for me)
for i in controller model plugin; do
	echo "" > plugins/$PLUGIN/${PLUGIN}_${i}.php
done

# Empty config file
touch plugins/$PLUGIN/config.json

# Base plugin class that gets loaded into Blesta
echo "<?php
class ${PLUGIN}Plugin extends Plugin {
	public function __construct(){
		\$this->loadConfig(dirname(__FILE__) . DS . \"config.json\");
	}
}
?>" > plugins/$PLUGIN/${PLUGIN}_plugin.php

# Controller plugin class that handles rendering files and displaying it to the user
echo "<?php
class ${PLUGIN}Controller extends AppController {
	public function preAction(){
		parent::preAction();

		\$this->view->view = \"default\";
		\$this->structure->view = \"default\";
	}
}
?>" > plugins/$PLUGIN/${PLUGIN}_controller.php

echo "done."

exit 0
