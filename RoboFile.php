<?php

include '.tk/RoboFileBase.php';

class RoboFile extends RoboFileBase {

	public function directoriesStructure() {
		return array( 'assets', 'includes', 'languages', 'templates' );
	}

	public function fileStructure() {
		return array( 'loader.php', 'composer.json', 'license.txt', 'readme.txt' );
	}

	public function cleanDirectories() {
		return array( 'assets', 'includes/resources/freemius', 'vendor-scope' );
	}

	public function pluginMainFile() {
		return 'BuddyForms';
	}

	public function pluginFreemiusId() {
		return 391;
	}
}