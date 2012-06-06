<?php

class PSAP {
	public function __construct($config) {
		if (!is_array($config) || count($config) < 1)
			throw new Exception("invalid PSAP config: no comprehensible config.");
		// validate
		$i = 0; foreach ($config as $key => $def) {
			if ($i == 0 || $i == count($config)-1) {	// unflagged are allowed at head and tail; nowhere else.
				if (isset($def['unflagged']) && $def['unflagged'] !== FALSE) {
					if (isset($def['longname']) || isset($def['shortname']))
						throw new Exception("invalid PSAP config: neither longname nor shortname may be specified if parameter is unflagged.");
					else
						if ($i == 0) { $this->unflaggedHead = array($key=>$def); } else { $this->unflaggedTail = array($key=>$def); }
				} else
					if (!isset($def['longname']) && !isset($def['shortname']))
						throw new Exception("invalid PSAP config: either longname or shortname must be specified unless a parameter is unflagged.");
			} else {
				if (isset($def['unflagged']) && $def['unflagged'] !== FALSE)
					throw new Exception("invalid PSAP config: unflagged parameters are only allowed at the beginning or end.");
				if (!isset($def['longname']) && !isset($def['shortname']))
					throw new Exception("invalid PSAP config: either longname or shortname must be specified unless a parameter is unflagged.");
			}
			if (!isset($def['type'])) $def['type'] = "string";
			if (!isset($def['required'])) $def['required'] = TRUE;
			if (!isset($def['multi'])) $def['multi'] = TRUE;
			$this->validateConfigLine($def);
		}
		// success
		$this->config = $config;
	}
	private function validateConfigLine($config) {
		if (isset($config['longname']) && !is_string($config['longname']))
			throw new Exception("invalid PSAP config: longname can only be a string.");
		if (isset($config['shortname']) && !is_string($config['shortname']))
			throw new Exception("invalid PSAP config: shortname can only be a string.");
		if (isset($config['shortname']) && strlen($config['shortname']) !== 1)
			throw new Exception("invalid PSAP config: shortname can only be a single character.");
		if (isset($config['description']) && !is_string($config['description']))
			throw new Exception("invalid PSAP config: description can only be a string.");
		if (!is_array($config['type']) && (!is_string($config['type']) || array_search($config['type'], array("string", "int", "num", "bool"))===FALSE ))	//XXX: others?  "array"?
			throw new Exception("invalid PSAP config: type can only be one of \"string\", \"int\", \"num\", \"bool\", or an array enumerating valid values.");
		if ($config['required'] !== TRUE && $config['required'] !== FALSE)
			throw new Exception("invalid PSAP config: required can only be a boolean.");
		if (isset($config['default']) && $config['required'])
			throw new Exception("invalid PSAP config: if a parameter is required, why would you try to set a default for it?");
		if (isset($config['default']) && !PSAP::matchesType($config['type'], $config['default']))
			throw new Exception("invalid PSAP config: default value must match the allowed types for that parameter.");
		foreach (array('unflagged', 'longname', 'shortname', 'description', 'type', 'required', 'default', 'multi') as $x) unset($config[$x]);
		if (!empty($config)) throw new Exception("invalid PSAP config: unrecognized parameter definition option \"".key($config)."\"");
	}
	
	private $config;
	private $unflaggedHead;
	private $unflaggedTail;
	private static $TUNFLAG = 1;
	private static $TSHORT = 2;
	private static $TLONG = 3;
	private $result;
	private $errors;
	
	public function parse($argv) {
		$this->result = array();
		$this->errors = array();
		$headDone = ($this->unflaggedHead == NULL);
		$tailDone = ($this->unflaggedTail == NULL);
		
		foreach ($argv as $arg) {
			if (substr($arg, 0, 2) == "--") {
				// it wants to be a long arg
			
			} elseif (substr($arg, 0, 2) == "--") {
				// it wants to be a short arg
			
			} else {
				// it's just a blob
				if ($headDone) {
					if (PSAP::matchesType($this->unflaggedHead['type'], $arg))
						$this->result[key($this->unflaggedHead)] = $arg;
					else
						$this->errors[] = "an unflagged argument did not match the required type";
				} else {
					// run forward a bit and see if there are more long or short args after this... no, no, do that backwards from the end one time up front.
				}
			}
		}
	}
	
	private static function matchesType($type, $value) {
		if (is_array($type)) return (array_search($value, $type) !== FALSE);
		switch ($type) {
			case "int": return is_int($value);
			case "num": return is_numeric($value);
			case "bool": return ($value === TRUE || $value === FALSE);
			case "string": return is_string($value);
			default: throw new Exception("this is a bug in PSAP!");
		}
	}
	
	public function result() {
		return $this->result;
	}
	
	public function getErrors() {
		return $this->errors;
	}
}

