This is yet another Markdown implementation,
but this time written as a state machine rather than a pile of regexes like I normally see.
(It still *uses* regexes to recognize and extract things, because they make it much easier,
but its overall operation is DFA-based.)

When finished, it will have two modes:

1. A regular Markdown parser, implementing Markdown Extra as well
2. A specialized parser for blog comments, which omits several bits of Markdown syntax for safety.

It will also exist in both PHP and JS versions,
because I need both of these.

Current Progress
----------------

The comment parser is in a nearly-complete state.
I implement all markdown elements.
The only thing missing is handling *nested* block-level elements.