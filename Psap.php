<?php
/*
 * Copyright 2012 Eric Myhre <http://exultant.us>
 * 
 * This file is part of PSAP <https://github.com/heavenlyhash/psap/>.
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
			throw new PsapConfigError("no comprehensible config.");
		// validate
		$i = 0; foreach ($config as $key => &$def) {
			if ($i == 0 || $i == count($config)-1) {	// unflagged are allowed at head and tail; nowhere else.
				if (isset($def['unflagged']) && $def['unflagged'] !== FALSE) {
					if (isset($def['longname']) || isset($def['shortname']))
						throw new PsapConfigError("neither longname nor shortname may be specified if parameter is unflagged.");
					else
						if ($i == 0) { $this->unflaggedHead = array($key=>$def); } else { $this->unflaggedTail = array($key=>$def); }
				} else
					if (!isset($def['longname']) && !isset($def['shortname']))
						throw new PsapConfigError("either longname or shortname must be specified unless a parameter is unflagged.");
			} else {
				if (isset($def['unflagged']) && $def['unflagged'] !== FALSE)
					throw new PsapConfigError("unflagged parameters are only allowed at the beginning or end.");
				if (!isset($def['longname']) && !isset($def['shortname']))
					throw new PsapConfigError("either longname or shortname must be specified unless a parameter is unflagged.");
			}
			if (!isset($def['type'])) $def['type'] = "string";
			if (!isset($def['multi'])) $def['multi'] = FALSE;
			if ($def['type']=="bool")
				if (isset($def['default'])) throw new PsapConfigError("type \"bool\" parameters always default to false.  you may not specify a default.");
				else $def['default'] = FALSE;
			PSAP::validateConfigLine($def);
			$i++;
		}
		// populate lookup tables (and explode if there are collisions)
		foreach ($config as $key => &$def) {
			if (isset($def['shortname']))
				if (isset($this->lookupShort[$def['shortname']]))
					throw new PsapConfigError("shortname '".$def['shortname']."' cannot be assigned repeatedly");
				else $this->lookupShort[$def['shortname']] = $key;
			if (isset($def['longname']))
				if (isset($this->lookupLong[$def['longname']]))
					throw new PsapConfigError("longname '".$def['longname']."' cannot be assigned repeatedly");
				else $this->lookupLong[$def['longname']] = $key;
		}
		// success
		$this->config = $config;
		$this->throwParseError = true;
		$this->throwParseWarn = true;
	}
	private static function validateConfigLine($config) {
		if (isset($config['longname']) && !is_string($config['longname']))
			throw new PsapConfigError("longname can only be a string.");
		if (isset($config['shortname']) && !is_string($config['shortname']))
			throw new PsapConfigError("shortname can only be a string.");
		if (isset($config['shortname']) && strlen($config['shortname']) !== 1)
			throw new PsapConfigError("shortname can only be a single character.");
		if (isset($config['description']) && !is_string($config['description']))
			throw new PsapConfigError("description can only be a string.");
		if (!is_array($config['type']) && (!is_string($config['type']) || array_search($config['type'], array("string", "int", "num", "bool"))===FALSE ))
			throw new PsapConfigError("type can only be one of \"string\", \"int\", \"num\", \"bool\", or an array enumerating valid values.");
		if (isset($config['unflagged']) && $config['type']=="bool")
			throw new PsapConfigError("type \"bool\" doesn't make sense for unflagged parameters.");
		if (isset($config['default']) && $config['default'] !== NULL) {
			if (!$config['multi'] || !is_array($config['default'])) {
				if (!PSAP::matchesType($config['type'], $config['default']))
					throw new PsapConfigError("default value must match the allowed types for that parameter.");
			} else {
				foreach ($config['default'] as $x) if (!PSAP::matchesType($config['type'], $x))
					throw new PsapConfigError("default value must match the allowed types for that parameter.");
			}
		}
		if ($config['type'] == "bool" && $config['multi'])
			throw new PsapConfigError("accepting multi values for a parameter set as bool type doesn't make sense.");
		foreach (array('unflagged', 'longname', 'shortname', 'description', 'type', 'default', 'multi') as $x) unset($config[$x]);
		if (!empty($config)) throw new PsapConfigError("unrecognized parameter definition option \"".key($config)."\"");
	}
	public function configureThrowOnParseError($bool) {
		$this->throwParseError = ($bool == TRUE);
	}
	public function configureThrowOnParseWarn($bool) {
		$this->throwParseWarn = ($bool == TRUE);
	}
	
	private $throwParseError;
	private $throwParseWarn;
	var $config;
	private $lookupShort;
	private $lookupLong;
	private $unflaggedHead;
	private $unflaggedTail;
	private static $TUNFLAG = 1;
	private static $TSHORT = 2;
	private static $TLONG = 3;
	private $result;
	private $problems;
	
	/**
	 * Call this function to perform a parsing; after this function returns, the result() and getProblems() methods will return what we figured out.
	 * 
	 * Repeated calls of this function on the same instance of PSAP will discard earlier results and errors completely and begin fresh, using the same config the PSAP instance was constructed with.
	 * 
	 * @param $argv an array of string arguments to parse.
	 */
	public function parse($argv) {
		$this->result = array();
		$this->problems = array();
		$headkey = ($this->unflaggedHead == NULL) ? FALSE : key($this->unflaggedHead);
		$tailkey = ($this->unflaggedTail == NULL) ? FALSE : key($this->unflaggedTail);
		
		// normalize input to an ordinal array, because that just annoys me less.
		$argv = array_values($argv);
		$argc = count($argv);
		
		// first figure out how many unflagged args there are contiguously on the tail, because deciding that once that up front makes our error messages clearer if there are also incorrect gobs in the middle.
		$nUnfTail = 0;
		for ($i = $argc-1; $i >= 0; $i--, $nUnfTail++)
			if (PSAP::detectFlag($argv[$i]) != PSAP::$TUNFLAG) break;
		
		// k, loop over all the things.
		$gathering = false;
		for ($i = 0; $i < $argc; $i++) {
			$arg = $argv[$i];
			switch (PSAP::detectFlag($arg)) {
				case PSAP::$TLONG:
					$headkey = FALSE;
					if ($gathering !== FALSE) { $this->acceptValue($gathering, TRUE); $gathering = false; }
					$split = strpos($arg, "=");
					$long = ($split===FALSE) ? substr($arg, 2) : substr($arg, 2, $split-2);
					$key = @$this->lookupLong[$long];
					if (!$key) { $this->acceptParseProblem(new PsapParseWarn("unknown long parameter name '".$long."'")); continue; }
					if ($split === FALSE)
						$gathering = $key;
					else
						$this->acceptValue($key, substr($arg, $split+1));
					break;
				case PSAP::$TSHORT:
					$headkey = FALSE;
					if ($gathering !== FALSE) { $this->acceptValue($gathering, TRUE); $gathering = false; }
					$key = @$this->lookupShort[$arg[1]];
					if (!$key) { $this->acceptParseProblem(new PsapParseWarn("unknown short parameter name '".$arg[1]."'")); continue; }
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
						$gathering = false;
					} else if ($headkey !== FALSE) {
						// there is a leading unflagged head in the config, and it hasn't been filled yet, so this belongs there.
						if ($this->acceptValue($headkey, $arg) && !$this->config[$headkey]['multi']) $headkey = FALSE;
					} else if ($i > $argc-$nUnfTail) {
						// we're reached the tail of unflagged args.  (also, we could spin through the rest of the argv array right here if we wanted to, because the rest of the control flow in the loop has become fixed at this point.)
						if ($tailkey === FALSE)
							{ $this->acceptParseProblem(new PsapParseWarn(($argc-$i)." trailing values didn't match any parameter and were ignored")); break 2; }
						else
							$this->acceptValue($tailkey, $arg); /* we don't do the same thing with setting tailkey to false as we do with headkey because getting messages about multiple values ignores is actually reasonable here. */
					} else {
						// this is just an unexpected chunk of string that's neither a value for a named parameter nor in a place to gather with unflagged values at the head or tail.
						acceptParseProblem(new PsapParseWarn("unexpected value not placed as a value to any parameter"));
					}
					break;
			}
		}
		
		// apply default values for anything that wasn't picked up from args (including null in the case of nonrequired parameters that don't have default values).
		foreach ($this->config as $key => &$def)
			if (!isset($this->result[$key]) && array_key_exists('default',$def))
				$this->result[$key] = $def['default'];
		
		// assert that all parameters have a value and rack up errors if they don't.
		foreach ($this->config as $key => &$def)
			if (!array_key_exists($key, $this->result))
				$this->acceptParseProblem(new PsapParseError("a value is required for ".$this->getPresentationName($key)." parameter but none was provided!"));
	}
	private function acceptValue($key, $value) {
		$type = $this->config[$key]['type'];
		if (!$this->config[$key]['multi'] && isset($this->result[$key]))
			{ $this->acceptParseProblem(new PsapParseWarn("multiple values were given for ".$this->getPresentationName($key)." parameter that doesn't accept repeated use (value:\"".$value."\"); value ignored")); return false; }
		if (!PSAP::matchesType($type, $value))
			{ $this->acceptParseProblem(new PsapParseError("a value given for ".$this->getPresentationName($key)." parameter is not a valid type (value:\"".$value."\")")); return false; }
		// k, it's valid, now cast it...
		if (!is_array($type)) switch ($type) {
			case "int": $value = (int) $value; break;
			case "num": $value = (float) $value; break;
			case "bool": $value = TRUE; break;
			case "string": $value = (string) $value; break;
		}
		// and put it in results.
		if (!$this->config[$key]['multi'])
			$this->result[$key] = $value;
		else
			$this->result[$key][] = $value;
		return true;
	}
	private function acceptParseProblem(RuntimeException $problem) {
		if ($problem instanceof PsapParseError && $this->throwParseError) throw $problem;
		if ($problem instanceof PsapParseWarn  && $this->throwParseWarn)  throw $problem;
		$this->problems[] = $problem;
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
			case "int": return is_numeric($value) && ((int)$value==$value);	// is_int doesn't do the trick here, since we're certainly getting strings.  and you need the is_numeric check in addition to the casting part because casting "asdf" to int will give you a zero.
			case "num": return is_numeric($value);
			case "bool": return ($value === TRUE || $value === FALSE);
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
	
	public function getProblems() {
		return $this->problems;
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

class PsapConfigError extends ErrorException {}
class PsapParseError extends RuntimeException {}
class PsapParseWarn extends RuntimeException {}

