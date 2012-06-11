<?php
/* 
 * Tests using PHPUnit (http://www.phpunit.de/).
 * 
 * If you don't have or aren't familiar with PHPUnit:
 * Installing the library : http://pub.yourlabs.org/phpunit/installation.html
 * Installing the CLI: `apt-get install phpunit`
 * Using the CLI: http://pub.yourlabs.org/phpunit/textui.html
 */

require_once "Psap.php";

class PsapTest extends PHPUnit_Framework_TestCase {
	public function testBasic() {
		$parser = new Psap(array(
			'opt' => array(
				'longname'	=> "option",
				'shortname'	=> "o",
				'description'	=> "desc",
			),
		));
		$parser->parse(array());
		$parser->result();
		$parser->getErrors();
	}
}


