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
						if ($i == 0) { $this->unflaggedHead = array($key=>$def); } else { $this->unflaggedTail = array($key=>$def); }	//TODO: we're almost certainly going to need to do someting to forbid or triage the case where both head and tail are unflagged and multi, because otherwise that is breakingly ambiguous.
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
			if (!isset($def['multi'])) $def['multi'] = FALSE;
			JSAP::validateConfigLine($def);
			$i++;
		}
		// success
		$this->config = $config;
	}
	private static function validateConfigLine($config) {
		if (isset($config['longname']) && !is_string($config['longname']))
			throw new Exception("invalid PSAP config: longname can only be a string.");
		if (isset($config['shortname']) && !is_string($config['shortname']))
			throw new Exception("invalid PSAP config: shortname can only be a string.");
		if (isset($config['shortname']) && strlen($config['shortname']) !== 1)
			throw new Exception("invalid PSAP config: shortname can only be a single character.");
		if (isset($config['description']) && !is_string($config['description']))
			throw new Exception("invalid PSAP config: description can only be a string.");
		if (!is_array($config['type']) && (!is_string($config['type']) || array_search($config['type'], array("string", "int", "num", "bool"))===FALSE ))
			throw new Exception("invalid PSAP config: type can only be one of \"string\", \"int\", \"num\", \"bool\", or an array enumerating valid values.");
		if ($config['required'] !== TRUE && $config['required'] !== FALSE)
			throw new Exception("invalid PSAP config: required can only be a boolean.");
		if (isset($config['default']) && $config['required'])
			throw new Exception("invalid PSAP config: if a parameter is required, why would you try to set a default for it?");
		if (isset($config['default'])) {
			if (!$config['multi'] || !is_array($config['default'])) {
				if (!PSAP::matchesType($config['type'], $config['default']))
					throw new Exception("invalid PSAP config: default value must match the allowed types for that parameter.");
			} else {
				foreach ($config['default'] as $x) if (!PSAP::matchesType($config['type'], $x))
					throw new Exception("invalid PSAP config: default value must match the allowed types for that parameter.");
			}
		}
		if ($config['type'] == "bool" && $config['multi'])
			throw new Exception("invalid PSAP config: accepting multi values for a parameter set as bool type doesn't make sense.");
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
	
	/**
	 * Call this function to perform a parsing; after this function returns, the result() and getErrors() methods will return what we figured out.
	 * 
	 * Repeated calls of this function on the same instance of PSAP will discard earlier results and errors completely and begin fresh, using the same config the PSAP instance was constructed with.
	 * 
	 * @param $argv an array of string arguments to parse.
	 */
	public function parse($argv) {
		$this->result = array();
		$this->errors = array();
		$headDone = ($this->unflaggedHead == NULL);
		$tailDone = ($this->unflaggedTail == NULL);
		
		// normalize input to an ordinal array, because that just annoys me less.
		$argv = array_values($argv);
		$argc = count($argv);
		
		// first figure out how many unflagged args there are continguously on the tail, because deciding that once that up front makes our error messages clearer if there are also incorrect gobs in the middle.
		$nUnfTail = 0;
		for ($i = $argc-1; $i >= 0; $i--, $nUnfTail++)
			if (PSAP::detectFlag($argv[$i]) != PSAP::$TUNFLAG) break;
		
		// k, loop over all the things.
		$gathering = false;
		foreach ($argv as $arg) {
			switch (PSAP::detectFlag($arg)) {
				case PSAP::$TLONG:
					//TODO
					break;
				case PSAP::$TSHORT:
					//TODO;
					break;
				case PSAP::$TUNFLAG:
					if ($gathering !== FALSE) {
						// the last loop found a parameter name but not a value for it, so we expect exactly one blob for that now.
						acceptValue($gathering, $value);
						$gathering = false;	//XXX: not sure what's more valid behavior here, stopping gathering even if the value was unacceptable or keep trying?  choosing what seems like the less runaway of the two options for now.
					} else if (!$headDone) {
						// there is a leading unflagged head in the config, and it hasn't been filled yet, so this belongs there.
						if (PSAP::matchesType($this->unflaggedHead['type'], $arg))
							$this->result[key($this->unflaggedHead)] = $arg;
						else
							$this->errors[] = "an unflagged argument did not match the required type";
					} else {
						
					}
					break;
			}
		}
	}
	private function acceptValue($key, $value) {
		if (!$this->config[$key]['multi'] && isset($this->results[$key]))
			{ $this->errors[] = "multiple values were given for parameter '".$this->getPresentationName($key)."' that doesn't accept repeated use"; return false; }
		if (!PSAP::matchesType($this->config[$key]['type'], $value))
			{ $this->errors[] = "a values given parameter '".$this->getPresentationName($key)."' is not a valid type"; return false; }
		// k, it's valid, put it in results.
		if (!$this->config[$key]['multi'])
			$this->results[$key] = $value;
		else
			$this->results[$key][] = $value;
		return true;
	}
	private function getPresentationName($key) { // doesn't give you a sensible answer for unflagged parameters!
		return (isset($this->config[$key]['longname']) ? $this->config[$key]['longname'] : $this->config[$key]['shortname']);
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
	
	private static function detectFlag($argstr) {
		return (@$str[0]=='-') ? (@$str[1]=='-') ? PSAP::$TLONG : PSAP::$TSHORT : PSAP::$TUNFLAG;
	}
	
	public function result() {
		return $this->result;
	}
	
	public function getErrors() {
		return $this->errors;
	}
}

