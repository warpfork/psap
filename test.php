#!/usr/bin/env php
<?php

require_once "Psap.php";

$parser = new PSAP(array(
	'subcommand' => array(
		'unflagged'	=> true,				// defaults to false; if this is not true either longname or shortname must be set or its an error.
		'description'	=> "the task to perform",
		'type'		=> array("create", "update", "destroy"),
	),
	//'break' => array('unflagged' => true),	// you can't have one of these unless its in the beginning or end
	'username' => array(					// this array key is what will be returned
		'longname'	=> "username",				// defaults to not having one.  if neither longname or shortname is specified, it defaults to an unflagged option (which is only valid in the first or file slot of the array).
		'shortname'	=> "u",					// defaults to not having one
		'description'	=> "the name of the user to act as",	// ends up in the generated usage doc string
		'type'		=> "string",				// default to string (so this line is redundant)
		'required'	=> false,				// defaults to true
		'default'	=> "root",				// if required is true, invalid config; null also invalid config.  must match type or invalid config.
		//'break' => "trap"	// an invalid option name
	),
	'groups' => array(
		'longname'	=> "groups",
		'shortname'	=> "g",
		'description'	=> "the roles this command may act as",
		'required'	=> false,
		'multi'		=> true,		// defaults to false; an ordinal array is given back in the result for this key where values of that are of the type required.  config error if applied to type=bool.
	),
	//'break' => array()	// doesn't look like config
));

echo "\n----------------\n";
$parser->parse(array("create", "-u", "username", "-gAdministrators", "--groups", "Users", "--groups=Backup", "-x"));
var_dump($parser->result());
var_dump($parser->getErrors());

echo "\n----------------\n";
$parser->parse(array("invalid", /* username default to root */ "-g=Administrators",));
var_dump($parser->result());
var_dump($parser->getErrors());

echo "\n----------------\n";
$parser->parse(array("update", "destroy"));
var_dump($parser->result());
var_dump($parser->getErrors());

echo "\n----------------\n";
$parser->parse(array("update", "-uname", "bogus", "-gPoets"));
var_dump($parser->result());
var_dump($parser->getErrors());

echo "\n----------------\n";
$parser = new PSAP(array(
	'subcommand' => array(
		'unflagged'	=> true,
		'description'	=> "the task to perform",
		'type'		=> array("create", "update", "destroy"),
	),
	'sectors' => array(
		'unflagged'	=> true,
		'multi'		=> true,
	),
));
$parser->parse(array("update", "alpha", "beta", "gamma", "delta"));
var_dump($parser->result());
var_dump($parser->getErrors());

/*

ERRATA

* PSAP doesn't support multiple shortflags in a row.  PSAP sees an arg like "-uroot" and parses it as the shortflag "-u" with value "root".  That's a choice you can argue for or against; it's frankly somewhat arbitrary with supporters for each in history.
* you can in fact manage to assign an empty string to an argument.  "-u=" would do it.




*/