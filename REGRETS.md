
Building this entire piece of code in an "idiomatic php" form is a regret already by day two of looking at what I've done.
Specifically, taking all of the config into the constructor as a big ball of arrays.
It reads well enough when using it, but the implementation is more of a mess than it needs to be.
If it had been done instead more like the original JSAP that I started with as a model (that is, had a Parameter class and use a fluent interface of setters to set its options),
then validity checking could be handled as you were constructing the config, which would be both vastly cleaner implementation code and I think a better pathway for throwing errors
(your stack trace would be meaningful: it would include the very line number on which you called the function that tried to set up an option that is invalid).

