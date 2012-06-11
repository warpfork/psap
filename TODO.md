
* write proper tests using PHPUnit or something instead of being quite so flagrantly whimsical about it all
* unflagged option support
 * you could support multiple unflagged in a row as long as they're, uh, in a row.  would be better than multi?  but you can just use multi.  oshit, what if you want different types?  like, confined subcommand, then optionally one int.  we should support that.
* it's quite annoying that having by default required=true, so if you set a default, you have to be verbose and specify required=false manually.  fix that.
* you really should have that docname thinger option when you get around to generating usage strings
* allow specifying nonstandard and zero wrap on getUsage.
* parse error reporting
 * add an exception throwing mode to parse.  and yes as stateful.  and not in the constructor because that would be less self-documenting.  and possibly on by default, since for example if you have a program where the subcommand wasn't even specified, you really just wanna yell about that briefly right away, not give people a wall of error text most of which isn't even relevant to them yet.
 * add a simple success($treatWarningsAsFailure=true) predicate
 * add a simple getErrorString() method 
 * actually, maybe catering to the common case would be better served by wrapping both of those last two items into a dieIfUnsuccessful method.  that could then also make sure the exit code is done correctly.
* keys in the results array should be null if an argument wasn't provided.  that's a helluva lot nicer than having to prefix all your $results checks with @.  and it means that if you fuck up and are checking an arg name that's a typo instead of one that's really known to the parser, that SHOULD give you a warning.
* type lambdas
 * well, support them at all, first of all.
 * perhaps a demo using --groups=GROUP1,GROUP2,GROUPN and a type lambda that parses it?  and then ship that lambda.  make another file+class called PsapSmartTypes and huck them in there.
 * filename and dir lamdas that check existence and error if not readable (writable?) would also probably be widely appreciated.
