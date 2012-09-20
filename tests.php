<?php
include "markdown.php";

function assert_equal($md, $html) {
	$parsedmd = Markdown\Document::parseDocument($md)->toHTML();
	$pass = $parsedmd == $html;
	echo "<details class=" . ($pass ? 'pass' : 'fail') . ">";
		echo "<summary>" . ($pass ? 'PASS' : 'FAIL') . "</summary>";
		echo "<pre>".htmlspecialchars($md)."</pre>";
		echo "Expected/Actual:<pre>".htmlspecialchars($html)."</pre><pre>".htmlspecialchars($parsedmd)."</pre>";
		if(!$pass) {
			$doc = new Markdown\Document;
			echo "<details><summary>Lines</summary><pre>" . htmlspecialchars(print_r($doc->linesFromText(explode("\n", $md)), true)) . "</pre></details>";
			echo "<details><summary>Doc</summary><pre>" . htmlspecialchars(print_r(Markdown\Document::parseDocument($md), true)) . "</pre></details>";
		}
	echo "</details>";
}

?>
<style>
.pass > summary { color: green; margin: 0; font-size: 10px;}
.fail > summary { color: white; background: red; padding: 2px; font-weight: bold; }
ins { background: #dfd; color: #080; text-decoration: none; }
pre { background: rgba(0,0,0,.2); margin-bottom: 0; }
pre + pre { margin-top: 0; }
</style>
<?php

/* Inline tests */
assert_equal("Testing *italics*.", "<p>Testing <i>italics</i>.");
assert_equal("Testing *multi-word italics*.", "<p>Testing <i>multi-word italics</i>.");
assert_equal("Testing *multi-word 2*3 italics*.", "<p>Testing <i>multi-word 2*3 italics</i>.");
assert_equal("Testing *more* than *one* italics.", "<p>Testing <i>more</i> than <i>one</i> italics.");
assert_equal("Testing `literal *text*`.", "<p>Testing <code>literal *text*</code>.");
assert_equal("Testing ``double ` literal`` text.", "<p>Testing <code>double ` literal</code> text.");
assert_equal("Testing `` literal `` spaces.", "<p>Testing <code>literal</code> spaces.");
assert_equal("Testing ```` text.", "<p>Testing ```` text.");
assert_equal("Testing ````` text.", "<p>Testing <code>`</code> text.");
assert_equal("Testing an & character.", "<p>Testing an &amp; character.");
assert_equal("Testing an &copy; reference.", "<p>Testing an &copy; reference.");
assert_equal("Testing **bold**.", "<p>Testing <b>bold</b>.");
assert_equal("Testing ***bold italic***.", "<p>Testing <b><i>bold italic</i></b>.");
assert_equal("Testing *some **nested** text* here.", "<p>Testing <i>some <b>nested</b> text</i> here.");
assert_equal("Testing *some **misnested text* here**.", "<p>Testing <i>some **misnested text</i> here**.");
assert_equal("Testing *italics `into* literal`.", "<p>Testing *italics <code>into* literal</code>.");
assert_equal("Testing *italics `across` literal*.", "<p>Testing <i>italics <code>across</code> literal</i>.");
assert_equal("Testing **`bolded literals`**.", "<p>Testing <b><code>bolded literals</code></b>.");
assert_equal("Testing <http://www.example.com>.", '<p>Testing <a href="http://www.example.com">http://www.example.com</a>.');
assert_equal("Testing <fakeemail@example.com>.", '<p>Testing <a href="mailto:fakeemail@example.com">fakeemail@example.com</a>.');
assert_equal("Testing a stray < bracket.", "<p>Testing a stray &lt; bracket.");
assert_equal("Testing [links](http://www.example.com).", '<p>Testing <a href="http://www.example.com" title="">links</a>.');
assert_equal("Testing [ref links][ref1].\n [ref1]: http://example.com ", '<p>Testing <a href="http://example.com" title="">ref links</a>.');
assert_equal("Testing [ref links][ref2].\n [ref2]: http://example.com \"a title\"", '<p>Testing <a href="http://example.com" title="a title">ref links</a>.');
assert_equal("Testing [ref links][ref3].\n [ref3]: http://example.com 'a title'", '<p>Testing <a href="http://example.com" title="a title">ref links</a>.');
assert_equal("Testing [ref links][ref4].\n [ref4]: http://example.com (a title)", '<p>Testing <a href="http://example.com" title="a title">ref links</a>.');
assert_equal("Testing [unreffed links][ref 4].", '<p>Testing [unreffed links][ref 4].');
assert_equal("Testing [ref links][].\n [Ref Links]: http://example.com (a title)", '<p>Testing <a href="http://example.com" title="a title">ref links</a>.');
assert_equal("Testing ![images](http://xanthir.com/pony).", '<p>Testing <img src="http://xanthir.com/pony" alt="images" title="">.');
assert_equal("Testing ![ref images][ref5].\n [ref5]: http://xanthir.com/pony ", '<p>Testing <img src="http://xanthir.com/pony" alt="ref images" title="">.');
assert_equal("Testing ![ref images][ref6].\n [ref6]: http://xanthir.com/pony \"a title\"", '<p>Testing <img src="http://xanthir.com/pony" alt="ref images" title="a title">.');
assert_equal("Testing ![ref images][ref7].\n [ref7]: http://xanthir.com/pony 'a title'", '<p>Testing <img src="http://xanthir.com/pony" alt="ref images" title="a title">.');
assert_equal("Testing ![ref images][ref8].\n [ref8]: http://xanthir.com/pony (a title)", '<p>Testing <img src="http://xanthir.com/pony" alt="ref images" title="a title">.');
assert_equal("Testing <not a link>.", "<p>Testing <not a link>.");
assert_equal("Some <i>inline markup</i>.", "<p>Some <i>inline markup</i>.");
assert_equal("Slightly <strong id='foo'>more</strong> complicated markup.", "<p>Slightly <strong id='foo'>more</strong> complicated markup.");


/* Block tests */
assert_equal("Testing H1\n========", "<h1>Testing H1</h1>");
assert_equal("Testing H2\n--------", "<h2>Testing H2</h2>");
assert_equal("# Testing H1", "<h1>Testing H1</h1>");
assert_equal("# Testing H1  ", "<h1>Testing H1</h1>");
assert_equal("#\t\tTesting H1", "<h1>Testing H1</h1>");
assert_equal("# Testing H1 ####", "<h1>Testing H1</h1>");
assert_equal("###### Testing H6", "<h6>Testing H6</h6>");
assert_equal("####### Testing H7", "<p>####### Testing H7");
assert_equal("Testing IDs{#foo}\n=======", "<h1 id='foo'>Testing IDs</h1>");
assert_equal("Testing IDs {#foo} \n=======", "<h1 id='foo'>Testing IDs </h1>");
assert_equal("# Testing IDs {#foo} #", "<h1 id='foo'>Testing IDs </h1>");
assert_equal(" # Testing headings", "<p># Testing headings");
assert_equal("# Testing IDs #\n=======", "<h1>Testing IDs</h1><p>=======");
assert_equal("=======", "<p>=======");
assert_equal("Testing headings\n ======", "<p>Testing headings ======");
assert_equal("\\1. Not a list", "<p>1. Not a list");
assert_equal("Re #1: foo bar", "<p>Re #1: foo bar");

assert_equal("> foo\n> bar", "<blockquote><p>foo bar</blockquote>");
assert_equal(">foo\n>bar\n>\n>baz", "<blockquote><p>foo bar<p>baz</blockquote>");
assert_equal(">foo\n>1. bar\n> bar\n> 2. bar \n>\n>baz\noutside", "<blockquote><p>foo<ol><li>bar bar<li>bar </ol><p>baz</blockquote><p>outside");
assert_equal(">foo\n>>bar\n>foo", "<blockquote><p>foo<blockquote><p>bar</blockquote><p>foo</blockquote>");
assert_equal(">foo\n> >bar\n>foo", "<blockquote><p>foo<blockquote><p>bar</blockquote><p>foo</blockquote>");

assert_equal("1. foo", "<ol><li>foo</ol>");
assert_equal("1. foo\n2. bar", "<ol><li>foo<li>bar</ol>");
assert_equal("1. foo\n\n    foo\n2. bar", "<ol><li><p>foo<p>foo<li><p>bar</ol>");
assert_equal("1. 1. foo\n    2. foo\n2. bar", "<ol><li><ol><li>foo<li>foo</ol><li><p>bar</ol>");

assert_equal("foo\n<div>\n bar\n1. bar\n </div>\n</div>\nfoo", "<p>foo<div>\n bar\n1. bar\n </div>\n</div><p>foo");

assert_equal("~~~\n*foo*\n~~~", "<pre class='code'>*foo*</pre>");
assert_equal("~~~~~\n*foo*\n~~~~~", "<pre class='code'>*foo*</pre>");
assert_equal("~~~\n~~~~\n~~~", "<pre class='code'>~~~~</pre>");

assert_equal("foo | bar\n--- | ---\n*foo* cell | bar cell", "<table><thead><tr><th align=''>foo<th align=''>bar<tbody><tr><td align=''><i>foo</i> cell<td align=''>bar cell</table>");