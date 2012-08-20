This is yet another Markdown implementation,
but this time written as a state machine rather than a pile of regexes like I normally see.

When finished, it will have two modes:

1. A regular Markdown parser, implementing Markdown Extra as well
2. A specialized parser for blog comments, which omits several bits of Markdown syntax for safety.

It will also exist in both PHP and JS versions,
because I need both of these.

Right now, all I've got is a working but incomplete version of the comment parser,
which is good enough to use on my blog.
It doesn't yet handle the inline markdown elements,
and doesn't do nesting or blockquotes.

The code is also in a generally sorry state right now, 
but that'll resolve itself as I work on it more.