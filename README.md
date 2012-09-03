This is yet another Markdown implementation,
but this time written as a state machine rather than a pile of regexes like I normally see.
(It still *uses* regexes to recognize and extract things, because they vastly simplify things,
but its overall operation is DFA-based.)

This approach has the wonderful benefit of making the parser **easy to modify**.
You can trivially add new features of your own,
and turn them on or off in different parsing modes.

When finished, it will have two modes:

1. A regular Markdown parser, implementing Markdown Extra as well
2. A specialized parser for blog comments, which omits several bits of Markdown syntax for safety.

It will also exist in both PHP and JS versions,
because I need both of these.

Current Progress
----------------

Both modes of the parser are now complete for regular Markdown!

The "document" parser implements all of regular Markdown,
plus the `~~~~` code blocks from Markdown Extra.
(I'll add more of Markdown Extra soon.)

The "comment" parser is the same,
but it disallows headings and HTML (block or inline).
Additionally, it introduces a new block type - the "reply".
A line like "Re #2: more text" starts a reply,
and it's otherwise identical to a paragraph.
Replies automatically link up to the referenced comment.

Use
---

To use, just include the `markdown.php` file,
then call the static functions `Document::parseDocument(str)` or `Document::parseComment(str,postid)`.
You'll get back a Document object, which will stringify into the appropriate HTML.
(Or you can poke around in it yourself to manually fix things up.)

You may wish to customize the way in which the Reply class links to replies,
if your own blog uses a syntax other than `#<postid>-<commentnumber>`.