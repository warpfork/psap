<?php
/*
 * Copyright 2012 Eric Myhre <http://exultant.us>
 * 
 * This file is part of PSAP.
 * 
 * PSAP is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


class PSAP {
	public function __construct($config) {
		if (!is_array($config) || count($config) < 1)
			throw new Exception("invalid PSAP config: no comprehensible config.");
		// validate
		$i = 0; foreach ($config as $key => &$def) {
			if ($i == 0 || $i == count($config)-1) {	// unflagged are allowed at head and tail; nowhere else.
				if (isset($def['unflagged']) && $def['unflagged'] !== FALSE) {
					if (isset($def['longname']) || isset($def['shortname']))
						throw new Exception("invalid PSAP config: neither longname nor shortname may be specified if parameter is unflagged.");
					else
						if ($i == 0) { $this->unflaggedHead = array($key=>$def); } else { $this->unflaggedTail = array($key=>$def); }	//TODO: we're almost certainly going to need to do someting to forbid or triage the case where both head and tail are unflagged and multi, because otherwise that is breakingly ambiguous.	// actually, maybe we can just ignore it.  if they're both multi, the head is greedier.  that's well defined.
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
			PSAP::validateConfigLine($def);
			$i++;
		}
		// populate lookup tables (and explode if their are collisions)
		foreach ($config as $key => &$def) {
			if (isset($def['shortname']))
				if (isset($this->lookupShort[$def['shortname']]))
					throw new Exception("invalid PSAP config: shortname '".$def['shortname']."' cannot be assigned repeatedly");
				else $this->lookupShort[$def['shortname']] = $key;
			if (isset($def['longname']))
				if (isset($this->lookupLong[$def['longname']]))
					throw new Exception("invalid PSAP config: longname '".$def['longname']."' cannot be assigned repeatedly");
				else $this->lookupLong[$def['longname']] = $key;
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
		if (isset($config['unflagged']) && $config['type']=="bool")
			throw new Exception("invalid PSAP config: type \"bool\" doesn't make sense for unflagged parameters.");
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
	private $lookupShort;
	private $lookupLong;
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
		$headkey = ($this->unflaggedHead == NULL) ? FALSE : key($this->unflaggedHead);
		$tailkey = ($this->unflaggedTail == NULL) ? FALSE : key($this->unflaggedTail);
		
		// normalize input to an ordinal array, because that just annoys me less.
		$argv = array_values($argv);
		$argc = count($argv);
		if ($argc < 1) return;
		
		// first figure out how many unflagged args there are contiguously on the tail, because deciding that once that up front makes our error messages clearer if there are also incorrect gobs in the middle.
		$nUnfTail = 0;
		for ($i = $argc-1; $i >= 0; $i--, $nUnfTail++)
			if (PSAP::detectFlag($argv[$i]) != PSAP::$TUNFLAG) break;
		
		// k, loop over all the things.
		$gathering = false;
		for ($i = 0, $arg = $argv[0]; $i < $argc; $arg = @$argv[++$i]) {
			switch (PSAP::detectFlag($arg)) {
				case PSAP::$TLONG:
					$headkey = FALSE;
					if ($gathering !== FALSE) { $this->acceptValue($gathering, TRUE); $gathering = false; }
					$split = strpos($arg, "=");
					$long = ($split===FALSE) ? substr($arg, 2) : substr($arg, 2, $split-2);
					$key = @$this->lookupLong[$long];
					if (!$key) { $this->errors[] = "unknown long parameter name '".$long."'"; continue; }
					if ($split === FALSE)
						$gathering = $key;
					else
						$this->acceptValue($key, substr($arg, $split+1));
					//TODO
					break;
				case PSAP::$TSHORT:
					$headkey = FALSE;
					if ($gathering !== FALSE) { $this->acceptValue($gathering, TRUE); $gathering = false; }
					$key = @$this->lookupShort[$arg[1]];
					if (!$key) { $this->errors[] = "unknown short parameter name '".$arg[1]."'"; continue; }
					$remainder = substr($arg, 2);
					if ($this->config[$key]['type'] == "bool") {
						$this->acceptValue($key, (strlen($remainder)>0) ? $remainder : TRUE);
					} elseif (strlen($remainder) > 0) {
						if ($arg[2]=='=') $remainder = substr($remainder, 1);
						$this->acceptValue($key, $remainder);
					} else
						$gathering = $key;
					break;
				case PSAP::$TUNFLAG:
					if ($gathering !== FALSE) {
						// the last loop found a parameter name but not a value for it, so we expect exactly one blob for that now.
						$this->acceptValue($gathering, $arg);
						$gathering = false;	//XXX: not sure what's more valid behavior here, stopping gathering even if the value was unacceptable or keep trying?  choosing what seems like the less runaway of the two options for now.
					} else if ($headkey !== FALSE) {
						// there is a leading unflagged head in the config, and it hasn't been filled yet, so this belongs there.
						$this->acceptValue($headkey, $arg);
					} else if ($i > $argc-$nUnfTail) {
						// we're reached the tail of unflagged args.  (also, we could spin through the rest of the argv array right here if we wanted to, because the rest of the control flow in the loop has become fixed at this point.)
						if ($tailkey === FALSE)
							$this->errors[] = ($argc-$i)." trailing values didn't match any parameter and were ignored";
						else
							$this->acceptValue($tailkey, $arg);
					} else {
						// this is just an unexpected chunk of string that's neither a value for a named parameter nor in a place to gather with unflagged values at the head or tail.
						$this->errors[] = "unexpected value not placed as a value to any parameter";
					}
					break;
			}
		}
	}
	private function acceptValue($key, $value) {
		$type = $this->config[$key]['type'];
		if (!$this->config[$key]['multi'] && isset($this->result[$key]))
			{ $this->errors[] = "multiple values were given for ".$this->getPresentationName($key)." parameter that doesn't accept repeated use (value:\"".$value."\")"; return false; }
		if (!PSAP::matchesType($type, $value))
			{ $this->errors[] = "a value given for ".$this->getPresentationName($key)." parameter is not a valid type (value:\"".$value."\")"; return false; }
		// k, it's valid, now cast it...
		if (!is_array($type)) switch ($type) {
			case "int": $value = (int) $value;
			case "num": $value = (float) $value;
			case "bool": $value = TRUE;
			case "string": $value = (string) $value;
		}
		// and put it in results.
		if (!$this->config[$key]['multi'])
			$this->result[$key] = $value;
		else
			$this->result[$key][] = $value;
		return true;
	}
	private function getPresentationName($key) {
		if (isset($this->config[$key]['longname'])) return "'".$this->config[$key]['longname']."'";
		if (isset($this->config[$key]['shortname'])) return "'".$this->config[$key]['shortname']."'";
		if ($key == @key($this->unflaggedTail)) return "trailing unflagged";
		return "unflagged";
	}
	
	private static function matchesType($type, $value) {
		if (is_array($type)) return (array_search($value, $type) !== FALSE);
		switch ($type) {
			case "int": return ((int)$value==$value);	// is_int doesn't do the trick here, since we're certainly getting strings.
			case "num": return is_numeric($value);
			case "bool": return ($value === TRUE);		// the "|| $value === FALSE" clause actually doesn't turn out to make sense, because a bool is true if present and null/unset if not present.
			case "string": return is_string($value);
			default: throw new Exception("this is a bug in PSAP!");
		}
	}
	
	private static function detectFlag($str) {
		return (@$str[0]=='-') ? (@$str[1]=='-') ? ($str!="--"? PSAP::$TLONG : PSAP::$TUNFLAG) : ($str!="-"? PSAP::$TSHORT : PSAP::$TUNFLAG) : PSAP::$TUNFLAG;
	}
	
	public function result() {
		return $this->result;
	}
	
	public function getErrors() {
		return $this->errors;
	}
	
	public function getUsage() {
		generateUsage();
		return $this->usage;
	}
	private $usage;
	private function generateUsage() {
		if (isset($this->usage)) return;
		//TODO
	}
}

