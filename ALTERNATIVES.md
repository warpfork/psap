
### contrast to getopt in php core:
* PSAP handles defaults.
* PSAP handles unlabeled parameters.
* PSAP is just plain more ledgible.  seriously.
* PSAP handles usage generation.
* PSAP makes assertions about types.
* PSAP allows lambdas for complex types.	// TODO
* PSAP gives you a heads up about args that weren't expected.

### contrast to Pharse (https://github.com/chrisallenlane/Pharse):
(And yes, this is kind of a random one to pick out, but it did come up early in my search for existing solutions for some reason, and I did refine my approach by looking at it.)
* PSAP is a good bit more object oriented rather than being overtly static.
* PSAP doesn't screw up if you have hyphens in your key name.
* PSAP differenciates between longname and the key name you use to reference internally (which is important if you want to accept parameters that have a shortname but not a longname).
* PSAP doesn't lump things into one string in one of the intermediate parse steps thereby ignoring all argument grouping specified by the caller (!)
* PSAP doesn't consider a string of unlabeled words to all be parts of the most recently named parameter; it warns you about the oddness instead.
* PSAP generates prettier usage (specifies defaults, aligns better, denotes optional versus required, just generally looks more at home with other unix util patterns).
* PSAP defines types precisely, so we don't have to resort to the ridiculous tactic of inserting [keyname]_given as a boolean into the results set; null/unset in the results set means the argument wasn't given, end of story.
* PSAP will accumulate a report of all errors encountered, instead of complaining about one missing required argument at a time and exiting.

### contrast to Pear Console_CommandLine:
* ...actually Pear Console_CommandLine appears to be excellent from what I see of it at a glance.
* Pear Console_CommandLine just has a bajillion classes and a whole bunch of files, and I don't see why this should be any more complex than being able to fit in a single file.
* PSAP doesn't allow you to dick up the "-" and "--" prefixes.  long is long and short is short and neither need to waste time specifying that redundantly nor you are not allowed to mess with it.
* PSAP defaults are put in our generated help/usage string programmatically; you don't need to do it manually.
* PSAP doesn't have anything like Pear Console_CommandLine's 'action' option.  that is better handled by our 'type' option and a consistent understanding of types (for example, StoreTrue is a waste of breath, because in what world would TRUE not be a valid thing to put in the results array if there's no value matching the parameter on the command line?  it's not like you can slip a php value of TRUE in as a string; "true" and TRUE are different.)
* PSAP doesn't have anything like Pear Console_CommandLine's 'list' option, because seriously, what the heck is that for?
* PSAP doesn't coopt "help" and "version" and make them into magic words your application can't control.  (You should definitely define "help" to do something reasonable, but PSAP won't insist that it knows what you want.)
* PSAP doesn't support stuff like loading the args parser from an xml string.  because why would you want that?  your application logic needs to interact with the args results in ways that is tightly bound to the names you give the arguments anyway; there's no conceivable world in which you could be able to call and use the parser object without also being perfectly able to configure it in-program.
* PSAP doesn't assume that an arg of "-" means "glom stdin into a string for me".  if you want that, it's an application logic issue.  (besides, most utilities that accept streams for input do so for reasons like wanting to be able to operate as the stream progresses rather than blocking, or because the information is just big, or etc.)
* PSAP has the 'type' option performing the equivalent role of Pear Console_CommandLine's 'choices' option, because enumerating valid values is more specific than giving a type, so there's no reason you could ever possibly want to do both except to waste your breath.

### other general departures of PSAP from the average:
* I just like PSAP's handling of unflagged options better than any of the other systems I've looked at.
** you can put them at the beginning and end; not in the middle because that's confusing and almost never makes sense.
** "subcommands" don't have explicit support because that's not a complicated concept and belongs in the application logic, not the args parser.  but it's extremely clear to implement a subcommand pattern using unflagged options on the front.

