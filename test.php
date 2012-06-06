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
		'multi'		=> true,		// defaults to false; an ordinal array is given back in the result for this key where values of that are of the type required.
	),
	//'break' => array()	// doesn't look like config
));
$parser->parse(array("create", "-u", "username", "-gAdministrators", "--groups", "Users", "--groups=Backup"));
var_dump($parser->result());
var_dump($parser->getErrors());
