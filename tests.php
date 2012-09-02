<?php
include "markdown.php";
include "commentmarkdown.php";

function assert_equal($md, $html) {
	$parsedmd = Markdown\parse($md, "post1")->toHTML();
	if($parsedmd == $html) {
		echo "<p class=pass>PASS";
	} else {
		echo "<p class=fail>FAIL<pre>".htmlspecialchars($md)."</pre>Actual:<pre>".htmlspecialchars($parsedmd)."</pre>Expected:<pre>".htmlspecialchars($html)."</pre>";
	}
}

?>
<style>
.pass { color: green; margin: 0; font-size: 10px;}
.fail { color: white; background: red; padding: 2px; font-weight: bold; }
pre { background: rgba(0,0,0,.2); }
</style>
<?php

/* Inline tests */
assert_equal("Testing *italics*.", "<p>Testing <i>italics</i>.");
assert_equal("Testing *multi-word italics*.", "<p>Testing <i>multi-word italics</i>.");
assert_equal("Testing *multi-word 2*3 italics*.", "<p>Testing <i>multi-word 2*3 italics</i>.");
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
assert_equal("Testing <not a link>.", "<p>Testing &lt;not a link>.");
assert_equal("Testing a stray < bracket.", "<p>Testing a stray &lt; bracket.");
assert_equal("Testing [links](http://www.example.com).", '<p>Testing <a href="http://www.example.com" title="">links</a>.');
assert_equal("Testing [ref links][ref1].\n [ref1]: http://example.com ", '<p>Testing <a href="http://example.com" title="">ref links</a>.');
assert_equal("Testing [ref links][ref2].\n [ref2]: http://example.com \"a title\"", '<p>Testing <a href="http://example.com" title="a title">ref links</a>.');
assert_equal("Testing [ref links][ref3].\n [ref3]: http://example.com 'a title'", '<p>Testing <a href="http://example.com" title="a title">ref links</a>.');
assert_equal("Testing [ref links][ref4].\n [ref4]: http://example.com (a title)", '<p>Testing <a href="http://example.com" title="a title">ref links</a>.');
assert_equal("Testing [ref links][].\n [Ref Links]: http://example.com (a title)", '<p>Testing <a href="http://example.com" title="a title">ref links</a>.');
assert_equal("Testing ![images](http://xanthir.com/pony).", '<p>Testing <img src="http://xanthir.com/pony" alt="images" title="">.');
assert_equal("Testing ![ref images][ref5].\n [ref5]: http://xanthir.com/pony ", '<p>Testing <img src="http://xanthir.com/pony" alt="ref images" title="">.');
assert_equal("Testing ![ref images][ref6].\n [ref6]: http://xanthir.com/pony \"a title\"", '<p>Testing <img src="http://xanthir.com/pony" alt="ref images" title="a title">.');
assert_equal("Testing ![ref images][ref7].\n [ref7]: http://xanthir.com/pony 'a title'", '<p>Testing <img src="http://xanthir.com/pony" alt="ref images" title="a title">.');
assert_equal("Testing ![ref images][ref8].\n [ref8]: http://xanthir.com/pony (a title)", '<p>Testing <img src="http://xanthir.com/pony" alt="ref images" title="a title">.');


/* Block tests */
assert_equal("Testing H1\n========", "<h1>Testing H1</h1>");
assert_equal("Testing H2\n--------", "<h2>Testing H2</h2>");
assert_equal("# Testing H1", "<h1>Testing H1</h1>");
assert_equal("# Testing H1  ", "<h1>Testing H1</h1>");
assert_equal("#\t\tTesting H1", "<h1>Testing H1</h1>");
assert_equal("# Testing H1 ####", "<h1>Testing H1</h1>");
assert_equal("###### Testing H6", "<h6>Testing H6</h6>");
assert_equal("####### Testing H7", "<p>####### Testing H7");
assert_equal(" # Testing headings", "<p># Testing headings");
assert_equal("Testing headings\n ======", "<p>Testing headings ======");
assert_equal("\\1. Not a list", "<p>1. Not a list");