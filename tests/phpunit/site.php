<?php
/**
 * @file
 * Configure file for site configuration.
 */

use CL\Site\Site;

return function(Site $site) {
	$standard = require __DIR__ . '/../../../site/tests/phpunit/site.php';
	$standard($site);


};
