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
	
	/** @expectedException PsapParseError
	 */
	public function testFailParseMissingRequired() {
		$parser = self::setupAlpha();
		$parser->parse(array());
	}
	
	private static function setupTypeInt() {
		return new Psap(array(
			'opt' => array(
				'longname'	=> "option",
				'shortname'	=> "o",
				'type'		=> "int",
			),
		));
	}
	
	public function testParseOneInt() {
		$parser = self::setupTypeInt();
		$parser->parse(array("-o1"));
		$this->assertSame(
			array("opt" => 1),
			$parser->result()
		);
	}
	
	/** @expectedException PsapParseError
	 */
	public function testFailParseIntTypeWrongString() {
		$parser = self::setupTypeInt();
		$parser->parse(array("-ostr"));
	}
	
	/** @expectedException PsapParseError
	 */
	public function testFailParseIntTypeWrongNum() {
		$parser = self::setupTypeInt();
		$parser->parse(array("-o12.3"));
	}
	
	private static function setupTypeBool() {
		return new Psap(array(
			'opt' => array(
				'longname'	=> "option",
				'shortname'	=> "o",
				'type'		=> "bool",
			),
		));
	}
	
	public function testParseOneBool() {
		$parser = self::setupTypeBool();
		$parser->parse(array("-o"));
		$this->assertSame(
			array("opt" => true),
			$parser->result()
		);
	}
	
	/** @expectedException PsapParseError
	 */
	public function testFailParseBoolTypeWrongString() {
		$parser = self::setupTypeBool();
		$parser->parse(array("-ostr"));
	}
	
	/** @expectedException PsapConfigError
	 */
	public function testFailConfigInvalidOption() {
		new Psap(array(
			'opt' => array(
				'longname'	=> "option",
				'shortname'	=> "o",
				'break'		=> "trap",
			),
		));
	}
	
	/** @expectedException PsapConfigError
	 */
	public function testFailConfigUnnamedParameter() {
		new Psap(array(
			'opt' => array(
				'type'		=> "bool",
			),
		));
	}
	
	/** @expectedException PsapConfigError
	 */
	public function testFailConfigInvalidType() {
		new Psap(array(
			'opt' => array(
				'shortname'	=> "o",
				'type'		=> "wat",
			),
		));
	}
	
	/** @expectedException PsapConfigError
	 */
	public function testFailConfigMistypedDefault() {
		new Psap(array(
			'opt' => array(
				'shortname'	=> "o",
				'default'	=> 12,
			),
		));
	}
	
	private static function setupBeta() {
		return new Psap(array(
			'username' => array(
				'longname'	=> "username",
				'shortname'	=> "u",
				'description'	=> "the name of the user to act as",
				'default'	=> "root",
			),
			'groups' => array(
				'longname'	=> "groups",
				'shortname'	=> "g",
				'description'	=> "the roles this command may act as",
				'default'	=> null,
				'multi'		=> true,
			),
			'test' => array(
				'longname'	=> "test",
				'shortname'	=> "T",
				'description'	=> "if running in test mode, normal output messages occur, but no actual actions will be performed.  a dry run, in other words.",
				'type'		=> "bool",
			),
		));
	}
	
	public function testParseDefaults() {
		$parser = self::setupBeta();
		$parser->configureThrowOnParseError(false);
		$parser->configureThrowOnParseWarn(false);
		$parser->parse(array());
		$this->assertSame(
			array(
				"username"	=> "root",
				"groups"	=> null,
				"test"		=> false,
			), $parser->result()
		);
		//$this->assertSame(array(), $parser->getErrors());
	}
	
	public function testParseDefaultOverriding() {
		$parser = self::setupBeta();
		$parser->configureThrowOnParseError(false);
		$parser->configureThrowOnParseWarn(false);
		$parser->parse(array("-uhash"));
		$this->assertSame(
			array(
				"username"	=> "hash",
				"groups"	=> null,
				"test"		=> false,
			), $parser->result()
		);
		//$this->assertSame(array(), $parser->getErrors());
	}
}


