<?php
include "markdown.php";

function assert_equal($md, $html) {
	$parsedmd = Markdown\Document::parseComment($md, "post1")->toHTML();
	$pass = $parsedmd == $html;
	echo "<details class=" . ($pass ? 'pass' : 'fail') . ">";
		echo "<summary>" . ($pass ? 'PASS' : 'FAIL') . "</summary>";
		echo "<pre>".htmlspecialchars($md)."</pre>";
		echo "Actual:<pre>".htmlspecialchars($parsedmd)."</pre>";
		echo "Expected:<pre>".htmlspecialchars($html)."</pre>";
	echo "</details>";
}

?>
<style>
.pass > summary { color: green; margin: 0; font-size: 10px;}
.fail > summary { color: white; background: red; padding: 2px; font-weight: bold; }
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
assert_equal("Some <i>inline markup</i>.", "<p>Some &lt;i>inline markup&lt;/i>.");


/* Block tests */
assert_equal("\\1. Not a list", "<p>1. Not a list");
assert_equal("Testing H1\n========", "<p>Testing H1 ========");
assert_equal("Testing H2\n--------", "<p>Testing H2<hr>");
assert_equal("# Testing H1", "<p># Testing H1");
assert_equal("Re #1: foo bar", "<p>Re <a href='#post1-1'>#1</a>: foo bar");