<?php
class Main extends DatabaseManagerController {

        /**
         * Pre-action
         */
        public function preAction() {
                parent::preAction();

                $this->redirect();
        }
}
?>