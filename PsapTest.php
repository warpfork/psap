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
	private static function setupAlpha() {
		return new Psap(array(
			'opt' => array(
				'longname'	=> "option",
				'shortname'	=> "o",
				'description'	=> "desc",
			),
		));
	}
	
	public function testBasic() {
		$parser = self::setupAlpha();
		$parser->parse(array());
		$parser->result();
		$parser->getErrors();
	}
	
	public function testParseOneShortSeparatedString() {
		$parser = self::setupAlpha();
		$parser->parse(array("-o", "val"));
		$this->assertEquals(array(), $parser->getErrors());
		$this->assertEquals(
			array("opt" => "val"),
			$parser->result()
		);
	}
	
	public function testParseOneShortEqualledString() {
		$parser = self::setupAlpha();
		$parser->parse(array("-o=val"));
		$this->assertEquals(
			array("opt" => "val"),
			$parser->result()
		);
	}
	
	public function testParseOneShortCattedString() {
		$parser = self::setupAlpha();
		$parser->parse(array("-oval"));
		$this->assertEquals(array(), $parser->getErrors());
		$this->assertEquals(
			array("opt" => "val"),
			$parser->result()
		);
	}
	
	public function testParseOneLongSeparatedString() {
		$parser = self::setupAlpha();
		$parser->parse(array("--option", "val"));
		$this->assertEquals(array(), $parser->getErrors());
		$this->assertEquals(
			array("opt" => "val"),
			$parser->result()
		);
	}
	
	public function testParseOneLongEqualledString() {
		$parser = self::setupAlpha();
		$parser->parse(array("--option=val"));
		$this->assertEquals(array(), $parser->getErrors());
		$this->assertEquals(
			array("opt" => "val"),
			$parser->result()
		);
	}
}


