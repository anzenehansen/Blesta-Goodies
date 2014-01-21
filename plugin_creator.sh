#!/bin/bash

PLUGIN="$1"

while [ "$PLUGIN" == "" ] || [ -e "plugins/$PLUGIN" ]; do
	read -p "Enter plugin name: " PLUGIN
done

echo -n "> Creating plugin structure for $PLUGIN..."
mkdir -p plugins/$PLUGIN/{controllers,models,views,language}

for i in controller model plugin; do
	echo "" > plugins/$PLUGIN/${PLUGIN}_${i}.php
done
touch plugins/$PLUGIN/config.json

echo "<?php
class ${PLUGIN}Plugin extends Plugin {
	public function __construct(){
		\$this->loadConfig(dirname(__FILE__) . DS . \"config.json\");
	}
}
?>" > plugins/$PLUGIN/${PLUGIN}_plugin.php

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
