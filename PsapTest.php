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
		$this->assertSame(array(), $parser->getErrors());
		$this->assertSame(
			array("opt" => "val"),
			$parser->result()
		);
	}
	
	public function testParseOneShortEqualledString() {
		$parser = self::setupAlpha();
		$parser->parse(array("-o=val"));
		$this->assertSame(
			array("opt" => "val"),
			$parser->result()
		);
	}
	
	public function testParseOneShortCattedString() {
		$parser = self::setupAlpha();
		$parser->parse(array("-oval"));
		$this->assertSame(array(), $parser->getErrors());
		$this->assertSame(
			array("opt" => "val"),
			$parser->result()
		);
	}
	
	public function testParseOneLongSeparatedString() {
		$parser = self::setupAlpha();
		$parser->parse(array("--option", "val"));
		$this->assertSame(array(), $parser->getErrors());
		$this->assertSame(
			array("opt" => "val"),
			$parser->result()
		);
	}
	
	public function testParseOneLongEqualledString() {
		$parser = self::setupAlpha();
		$parser->parse(array("--option=val"));
		$this->assertSame(array(), $parser->getErrors());
		$this->assertSame(
			array("opt" => "val"),
			$parser->result()
		);
	}
	
	/** @expectedException PsapParseWarn
	 */
	public function testFailParseOneShortStringTwice() {
		$parser = self::setupAlpha();
		$parser->parse(array("-oval", "-o=splode"));
	}
	
	/** @expectedException PsapParseWarn
	 */
	public function testFailParseOneLongStringTwice() {
		$parser = self::setupAlpha();
		$parser->parse(array("--option", "val", "--option=splode"));
	}
	
	/** @expectedException PsapParseWarn
	 */
	public function testFailParseOneMixedlenStringTwice() {
		$parser = self::setupAlpha();
		$parser->parse(array("-o", "val", "--option=splode"));
	}
	
	private static function setupBeta() {
		return new Psap(array(
			'opt' => array(
				'longname'	=> "option",
				'shortname'	=> "o",
				'type'		=> "int",
			),
		));
	}
	
	public function testParseOneInt() {
		$parser = self::setupBeta();
		$parser->parse(array("-o1"));
		$this->assertSame(
			array("opt" => 1),
			$parser->result()
		);
	}
	
	/** @expectedException PsapParseError
	 */
	public function testFailParseTypeWrong() {
		$parser = self::setupBeta();
		$parser->parse(array("-ostr"));
	}
}


