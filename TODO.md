
* write proper tests using PHPUnit or something instead of being quite so flagrantly whimsical about it all
* unflagged option support
 * you could support multiple unflagged in a row as long as they're, uh, in a row.  would be better than multi?  but you can just use multi.  oshit, what if you want different types?  like, confined subcommand, then optionally one int.  we should support that.
* you really should have that docname thinger option when you get around to generating usage strings
* allow specifying nonstandard and zero wrap on getUsage.
* parse error reporting
 * add a simple success($treatWarningsAsFailure=true) predicate
 * add a simple getErrorString() method 
 * actually, maybe catering to the common case would be better served by wrapping both of those last two items into a dieIfUnsuccessful method.  that could then also make sure the exit code is done correctly.
* type lambdas
 * well, support them at all, first of all.
 * perhaps a demo using --groups=GROUP1,GROUP2,GROUPN and a type lambda that parses it?  and then ship that lambda.  make another file+class called PsapSmartTypes and huck them in there.
 * filename and dir lamdas that check existence and error if not readable (writable?) would also probably be widely appreciated.
