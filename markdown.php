<?php
namespace Markdown;

class Document extends Element {

	static function parseDocument($raw) {
		$parser = new Document;
		return $parser->parse($raw, array('replies'));
	}

	static function parseComment($raw, $postid) {
		Reply::$postid = $postid;
		$parser = new Document;
		return $parser->parse($raw, array('headings', 'html'));
	}

	  ///////////////////////////
	 // Element Functionality //
	///////////////////////////

	public $elements = array();

	function __construct($elements = array()) {
		$this->elements = $elements;
	}

	function append($elem) {
		$this->elements[] = $elem;
		return $this;
	}

	function finish() {
		foreach($this->elements as $element) $element->finish();
		$this->finished = true;
		return $this;
	}

	function toHTML() {
		return implode( array_map(function($x) { return $x->toHTML(); }, $this->elements) );
	}

	  /////////////////////////////
	 // Reference Link Tracking //
	/////////////////////////////

	public $linkrefs = array();

	function setLink($ref, $link, $title='') {
		$this->linkrefs[strtolower($ref)] = array('link'=>$link, 'title'=>$title);
		return $this;
	}

	function hasLink($ref) {
		return isset( $this->linkrefs[strtolower($ref)] );
	}

	function getLink($ref) {
		return $this->linkrefs[strtolower($ref)];
	}

	  /////////////
	 // Parsing //
	/////////////

	public $features = array(
		"headings"=>true,
		"replies"=>true,
		"html"=>true
		);

	function excluding() {
		$excluding = array();
		foreach($this->features as $feature=>$on) {
			if(!$on) $excluding[] = $feature;
		}
		return $excluding;
	}

	function parse($md, $exclusions) {
		foreach($exclusions as $exclusion) {
			$this->features[$exclusion] = false;
		}
		if(is_string($md)) {
			$md = explode("\n", $md);
		}
		return $this->documentFromLines( $this->linesFromText($md) );
	}

	function linesFromText($rawlines) {
		$lines = array();
		foreach($rawlines as $rawline) {
			$rawline = trim($rawline, "\n\r");
			if(trim($rawline) == '') {
				// blank line
				$lines[] = array('type'=>'blank');
			}
			else if($this->features['headings'] && preg_match("/^={3,}\s*$/", $rawline)) {
				// <h1> underline
				$lines[] = array('type'=>'headingunderline', 'level'=>1, 'raw'=>$rawline);
			}
			else if($this->features['headings'] && preg_match("/^-{3,}\s*$/", $rawline)) {
				// <h2> underline
				$lines[] = array('type'=>'headingunderline', 'level'=>2, 'raw'=>$rawline);
			}
			else if($this->features['headings'] && preg_match("/^(#{1,6})\s+(.*)/", $rawline, $matches)) {
				// heading
				$lines[] = array('type'=>'heading', 'text'=>trim($matches[2], " \t#"), 'level'=>strlen($matches[1]), 'raw'=>$rawline);
			}
			else if(preg_match("/^( (\*\s*){3,} | (-\s*){3,} | (_\s*){3,} )$/x", trim($rawline))) {
				// <hr>
				$lines[] = array('type'=>'hr', 'raw'=>$rawline);
			}
			else if(preg_match("/^\d+\.\s+(.+)/", $rawline, $matches)) {
				// Start of a numbered list item
				$lines[] = array('type'=>'numbered', 'text'=>$matches[1], 'raw'=>$rawline);
			}
			else if(preg_match("/^[-*+]\s+(.+)/", $rawline, $matches)) {
				// Start of a bulleted list item
				$lines[] = array('type'=>'bulleted', 'text'=>$matches[1], 'raw'=>$rawline);
			}
			else if($this->features['html'] && preg_match("/^<(hr|img)(\s+\S[^>]*)?>\s*$/", $rawline, $matches)) {
				// HTML block-level start tag
				$lines[] = array('type'=>'html-null', 'tag'=>$matches[1], 'raw'=>$rawline);
			}
			else if($this->features['html'] && preg_match("/^<(address|article|aside|audio|blockquote|canvas|details|dialog|div|dl|figure|footer|form|h1|h2|h3|h4|h5|h6|header|hgroup|hr|iframe|img|map|nav|object|ol|p|pre|script|section|style|table|ul|video)(\s+\S[^>]*)?>$/", $rawline, $matches)) {
				// HTML block-level start tag
				$lines[] = array('type'=>'html-start', 'tag'=>$matches[1], 'raw'=>$rawline);
			}
			else if(preg_match("/^>[ ]?(.*)/", $rawline, $matches)) {
				// Blockquote
				$lines[] = array('type'=>'quote', 'text'=>$matches[1], 'raw'=>$rawline);
			}
			else if(preg_match("/^[ ]{0,3} \[([^\]]+)\] : \s* (\S+) \s* (?| \"([^\"]+)\" | '([^']+)' | \(([^)]+)\) | () ) \s*$/x", $rawline, $matches)) {
				// Link reference
				$lines[] = array('type'=>'ref', 'ref'=>$matches[1], 'link'=>$matches[2], 'title'=>$matches[3], 'raw'=>$rawline);
			}
			else if(preg_match("/^(~{3,})(.*)/", $rawline, $matches)) {
				// Explicit code delimiter
				$lines[] = array('type'=>'code', 'tildas'=>strlen($matches[1]), 'data'=>$matches[2], 'raw'=>$rawline);
			}
			else if($this->features['replies'] && preg_match("/^Re #(\d+):\s*(.*)$/", $rawline, $matches)) {
				// Comment link
				$lines[] = array('type'=>'reply', 'reply-to'=>intval($matches[1]), 'text'=>$matches[2], 'raw'=>$rawline);
			}
			else if(preg_match("/^\|?(\s*:?-+:?\s*\|)+(\s*:?-+:?\s*)?$/", $rawline)) {
				// Table separator
				$lines[] = array('type'=>'table-separator', 'raw'=>$rawline);
			}
			else if(preg_match("/\|/", $rawline)) {
				// Table row
				$lines[] = array('type'=>'table-row', 'raw'=>$rawline);
			}
			else {
				// Normal line of text.
				preg_match("/^(\s*)(.*)/", $rawline, $matches);
				$lines[] = array('type'=>'text', 'text'=>$matches[2], 'spaces'=>strlen($matches[1]), 'raw'=>$rawline);
			}
		}
		return $lines;
	}

	function documentFromLines($lines, $postid='') {
		$state = "start";
		$lines[] = array('type'=>'eof');
		$lines[] = array('type'=>'eof');
		$currelem = null;
		for($i = 0; $i < count($lines); $i++) {
			$line = $lines[$i];
			$type = $line['type'];
			$nextline = $lines[$i+1];
			$nexttype = $nextline['type'];

			//echo "<pre>";print_r($lines);print_r($line);print_r($nextline);echo "</pre>";

			if($state == "start") {
				$currelem = null;
				if($type == 'blank') {
					// Do nothing.
				} else if($type == 'eof') {
					break;
				} else if($type == 'hr' || ($type == 'headingunderline' && $line['level'] == 2)) {
					$currelem = new Separator;
					$currelem->doc = $this;
					$this->append($currelem);
				} else if($type == 'heading') {
					$currelem = new Heading($line['level'], $line['text']);
					$currelem->doc = $this;
					$this->append($currelem);
				} else if($type == 'code') {
					$currelem = new Code;
					$tildas = $line['tildas'];
					$currelem->doc = $this;
					$state = 'explicit-code';
				} else if($type == 'text' && $line['spaces'] >= 4) {
					$currelem = new Code(substr($line['raw'], 4));
					$currelem->doc = $this;
					$state = 'indented-code';
				} else if($type == 'ref') {
					$this->setLink($line['ref'], $line['link'], $line['title']);
				} else if($type == 'bulleted') {
					$currelem = new BulletedList($line['text']);
					$currelem->doc = $this;
					$state = 'bulleted-list';
				} else if($type == 'numbered') {
					$currelem = new NumberedList($line['text']);
					$currelem->doc = $this;
					$state = 'numbered-list';
				} else if($type == 'quote') {
					$currelem = new Quote($line['text']);
					$currelem->doc = $this;
					$state = 'quote';
				} else if($type == 'reply') {
					$currelem = new Reply($line['reply-to'], $line['text']);
					$currelem->doc = $this;
					$state = 'paragraph';
				} else if($type == 'html-null') {
					$currelem = new HTML($line['tag'], $line['raw']);
					$currelem->doc = $this;
					$this->append($currelem);
				} else if($type == 'html-start') {
					$currelem = new HTML($line['tag'], $line['raw']);
					$currelem->doc = $this;
					$state = 'html';
				} else if($type == 'text' && $nexttype == 'headingunderline') {
					$currelem = new Heading($nextline['level'], $line['text']);
					$currelem->doc = $this;
					$this->append($currelem);
					$i++;
				} else if($type == 'table-row' && $nexttype == 'table-separator' && $lines[$i+2]['type'] == 'table-row') {
					$currelem = new Table($line, $nextline, $lines[$i+2]);
					$currelem->doc = $this;
					$i += 2;
					$state = 'table';
				} else if($type == 'text') {
					$currelem = new Paragraph($line['text']);
					$currelem->doc = $this;
					$state = 'paragraph';
				} else {
					// lines that weren't caught as anything else
					$currelem = new Paragraph($line['raw']);
					$currelem->doc = $this;
					$state = 'paragraph';
				}
			} else if($state == 'explicit-code') {
				if($type == 'code' && $line['tildas'] == $tildas) {
					$this->append($currelem);
					$state = 'start';
				} else if($type == 'eof') {
					$i--;
					$this->append($currelem);
					$state = 'start';
				} else {
					$currelem->append($line['raw']);
				}
			} else if($state == 'indented-code') {
				if($type == 'text' && $line['spaces'] >= 4) {
					$currelem->append(substr($line['raw'], 4));
				} else {
					$i--;
					$this->append($currelem);
					$state = 'start';
				}
			} else if($state == 'bulleted-list') {
				if($type == 'text') {
					$currelem->append($line['text']);
				} else if($type == 'bulleted') {
					$currelem->newItem($line['text']);
				} else if($type == 'blank' && $nexttype == 'bulleted') {
					// Do nothing
				} else if($type == 'blank' && $nexttype == 'text' && $nextline['spaces'] >= 4) {
					$currelem->append('');
				} else {
					$i--;
					$this->append($currelem);
					$state = 'start';
				}
			} else if($state == 'numbered-list') {
				if($type == 'text') {
					$currelem->append($line['text']);
				} else if($type == 'numbered') {
					$currelem->newItem($line['text']);
				} else if($type == 'blank' && $nexttype == 'numbered') {
					// Do nothing
				} else if($type == 'blank' && $nexttype == 'text' && $nextline['spaces'] >= 4) {
					$currelem->append('');
				} else {
					$i--;
					$this->append($currelem);
					$state = 'start';
				}
			} else if($state == 'quote') {
				if($type == 'quote') {
					$currelem->append($line['text']);
				} else {
					$i--;
					$this->append($currelem);
					$state = 'start';
				}
			} else if($state == 'html') {
				if($line['raw'] == "</".$currelem->tag.">") {
					$currelem->append($line['raw']);
					$this->append($currelem);
					$state = 'start';
				} else if($type == 'eof') {
					$i--;
					$this->append($currelem);
					$state = 'start';
				} else {
					$currelem->append($line['raw']);
				}
			} else if($state == 'table') {
				if($type == 'table-row') {
					$currelem->append($line['raw']);
				} else {
					$i--;
					$this->append($currelem);
					$state = 'start';
				}
			} else if($state == 'paragraph') {
				if($type == 'text') {
					$currelem->append($line['text']);
				} else {
					$i--;
					$this->append($currelem);
					$state = 'start';
				}
			}
		}
		return $this->finish();
	}

	function parseInlines($raw) {
		/*	Inlines can be split into "atomic" and "nestable".
			Atomics are things like `code` and [text](link), which can't contain any other markup inside of themselves.
			Nestables are things like *italics* and **bold**, because they can have more markup inside.
			You have to deal with atomics first, because otherwise you'll screw up and get a nestable ending inside of an atomic.
			So, I first remove the atomics, replacing each occurrence with a NUL U+0000 character.
			(I strip out literal nulls from the original text,
			because the HTML parser will just replace them anyway.)
			Then I process the nestable ones,
			and then restore the atomics.
		*/

		list($text, $subs) = $this->removeAtomics($raw);

		$text = $this->processNestables($text);

		$text = $this->restoreAtomics($text, $subs);

		return $text;
	}

	function processNestables($raw) {
		$text = '';
		for($i = 0; $i < strlen($raw); $i++) {
			$char = $raw[$i];

			if( $char == '*' && preg_match('/^\*{3}([\w\00](.*?[\w\00])?)\*{3}[^*\w\00]/', substr($raw, $i), $matches) ) {
				// Bolditalic with *
				$text .= '<b><i>' . $this->processNestables($matches[1]) . '</i></b>';
				$i += strlen($matches[1]) + 5;
			} else if( $char == '_' && preg_match('/^_{3}([\a-zA-Z0-9\00](.*?[\a-zA-Z0-9\00])?)_{3}[^\w\00]/', substr($raw, $i), $matches) ) {
				// Bolditalic with _
				$text .= '<b><i>' . $this->processNestables($matches[1]) . '</i></b>';
				$i += strlen($matches[1]) + 5;
			} else if( $char == '*' && preg_match('/^\*{2}([\w\00](.*?[\w\00])?)\*{2}[^*\w\00]/', substr($raw, $i), $matches) ) {
				// Bold with *
				$text .= '<b>' . $this->processNestables($matches[1]) . '</b>';
				$i += strlen($matches[1]) + 3;
			} else if( $char == '_' && preg_match('/^_{2}([\a-zA-Z0-9\00](.*?[\a-zA-Z0-9\00])?)_{2}[^\w\00]/', substr($raw, $i), $matches) ) {
				// Bold with _
				$text .= '<b>' . $this->processNestables($matches[1]) . '</b>';
				$i += strlen($matches[1]) + 3;
			} else if( $char == '*' && preg_match('/^\*([\w\00](.*?[\w\00])?)\*[^*\w\00]/', substr($raw, $i), $matches) ) {
				// Italic with *
				$text .= '<i>' . $this->processNestables($matches[1]) . '</i>';
				$i += strlen($matches[1]) + 1;
			} else if( $char == '_' && preg_match('/^_([\a-zA-Z0-9\00](.*?[\a-zA-Z0-9\00])?)_[^\w\00]/', substr($raw, $i), $matches) ) {
				// Italic with _
				$text .= '<i>' . $this->processNestables($matches[1]) . '</i>';
				$i += strlen($matches[1]) + 1;
			} else {
				$text .= $char;
			}
		}
		return $text;
	}

	function removeAtomics($raw) {
		$text = '';
		$nul = chr(0);
		$subs = array();
		for($i = 0; $i < strlen($raw); $i++) {
			$char = $raw[$i];

			if( $char == '`' && preg_match('/^`\s?([^`]+?)\s?`/', substr($raw, $i), $matches) ) {
				// Code literal: `code here`
				$text .= $nul;
				$subs[] = '<code>' . html($matches[1]) . '</code>';
				$i += strlen($matches[0]) - 1;
			} else if( $char == '`' && preg_match('/^``\s?(.+?)\s?``/', substr($raw, $i), $matches) ) {
				// Double-ticked code literal: `code here`
				$text .= $nul;
				$subs[] = '<code>' . html($matches[1]) . '</code>';
				$i += strlen($matches[0]) - 1;
			} else if( $char == '!' && preg_match('/^! \[ ([^\]]+) \] \( ([^\s)]+)(\s+[^)]*|) \)/x', substr($raw, $i), $matches) ) {
				// Image: ![alt](link title)
				$text .= $nul;
				$subs[] = '<img src="' . attr($matches[2]) . '" alt="' . attr($matches[1]) . '" title="' . attr($matches[3]) . '">';
				$i += strlen($matches[0]) - 1;
			} else if( $char == '!' && preg_match('/^! \[ ([^\]]+) \]\[ ([^\]]+) \]/x', substr($raw, $i), $matches) && $this->hasLink($matches[2]) ) {
				// Referenced image: ![alt][ref]
				$ref = $this->getLink($matches[2]);
				$text .= $nul;
				$subs[] = '<img src="' . attr($ref['link']) . '" alt="' . attr($matches[1]) . '" title="' . attr($ref['title']) . '">';
				$i += strlen($matches[0]) - 1;
			} else if( $char == '[' && preg_match('/^\[ ([^\]]+) \] \( ([^\s)]+)(\s+[^)]* |) \)/x', substr($raw, $i), $matches) ) {
				// Link: [text](link title)
				$text .= $nul;
				$subs[] = '<a href="' . attr($matches[2]) . '" title="' . attr($matches[3]) . '">' . html($matches[1]) . '</a>';
				$i += strlen($matches[0]) - 1;
			} else if( $char == '[' && preg_match('/^\[ ([^\]]+) \]\[ ([^\]]+) \]/x', substr($raw, $i), $matches) && $this->hasLink($matches[2]) ) {
				// Reference Link: [text][ref]
				$ref = $this->getLink($matches[2]);
				$text .= $nul;
				$subs[] = '<a href="' . attr($ref['link']) . '" title="' . attr($ref['title']) . '">' . html($matches[1]) . '</a>';
				$i += strlen($matches[0]) - 1;
			} else if( $char == '[' && preg_match('/^\[ ([^\]]+) \]\[\]/x', substr($raw, $i), $matches) && $this->hasLink($matches[1]) ) {
				// Implicit Reference Link: [ref][]
				$ref = $this->getLink($matches[1]);
				$text .= $nul;
				$subs[] = '<a href="' . attr($ref['link']) . '" title="' . attr($ref['title']) . '">' . html($matches[1]) . '</a>';
				$i += strlen($matches[0]) - 1;
			} else if( $this->features['html'] && $char == '<' && preg_match('/^<\/?\w+(\s+\S[^>]*)?>/', substr($raw, $i), $matches) ) {
				// HTML tag
				$text .= $nul;
				$subs[] = $matches[0];
				$i += strlen($matches[0]) - 1;
			} else if( $char == '<' && preg_match('/^<(\w+:[^>]+)>/', substr($raw, $i), $matches) ) {
				// Literal link: <link>
				$text .= $nul;
				$subs[] = '<a href="' . attr($matches[1]) . '">' . html($matches[1]) . '</a>';
				$i += strlen($matches[0]) - 1;
			} else if( $char == '<' && preg_match('/^<(\S+@\S+)>/', substr($raw, $i), $matches) ) {
				// Literal email address: <link>
				$text .= $nul;
				$subs[] = '<a href="mailto:' . attr($matches[1]) . '">' . html($matches[1]) . '</a>';
				$i += strlen($matches[0]) - 1;
			} else if($char == '<') {
				// All other occurences of <
				$text .= '&lt;';
			} else if($char === '&' && preg_match('/^& ( [a-zA-Z]+ | \#[0-9]+ | \#x[0-9a-fA-F]+ ) ;/x', substr($raw, $i), $matches) ) {
				// Character reference: &copy;, etc.
				$text .= $matches[0];
				$i += strlen($matches[0]) - 1;
			} else if($char == '&') {
				// All other occurences of &
				$text .= '&amp;';
			} else if( $char == '\\' && $i == 0 && preg_match('/\d/', $raw[1]) ) {
				// Escaped digit at the start of a line (to suppress it being interpreted as a numbered list)
				$text .= $raw[1];
				$i++;
			} else if( $char == '\\' && preg_match('/^\\\\([\\\\`*_{}[\]()#+.!-])' . '/', substr($raw, $i), $matches) ) {
				// Character escape
				$text .= $nul;
				$subs[] = $matches[0];
				$i++;
			} else if( $char == chr(0) ) {
				// Literal nul in the original text.
				// Do nothing.
			} else {
				$text .= $char;
			}
		}

		return array($text, $subs);
	}

	function restoreAtomics($raw, $subs) {
		$text = '';
		$nul = chr(0);
		for($i = 0; $i < strlen($raw); $i++) {
			$char = $raw[$i];
			if( $char == $nul ) {
				$text .= array_shift($subs);
			} else {
				$text .= $char;
			}
		}
		return $text;
	}

}



function attr($text) {
	return str_replace('"', '&quot;', $text);
}
function html($text) {
	return htmlspecialchars($text);
}




class Element {
	protected $finished = false;
	public $doc;

	function finish() {
		$this->finished = true;
		return $this;
	}

	function __toString() {
		return $this->toHTML();
	}
}

class Separator extends Element {
	function __construct() {}

	function toHTML() {
		return "<hr>";
	}
}

class Heading extends Element {
	public $text;
	public $id = '';
	public $level;

	function __construct($level, $text) {
		$this->level = $level;
		if(preg_match("/(.*?)\{#([\w-]+)\}\s*/", $text, $matches)) {
			$this->text = $matches[1];
			$this->id = $matches[2];
		} else {
			$this->text = $text;
		}
	}

	function finish() {
		$this->text = $this->doc->parseInlines($this->text);
		return parent::finish();
	}

	function toHTML() {
		$text = "<h" . $this->level;
		if($this->id) {
			$text .= " id='" . $this->id . "'";
		}
		$text .= ">" . $this->text . "</h" . $this->level . ">";
		return $text;
	}
}

class Code extends Element {
	protected $lines = array();

	function __construct($firstLine = null) {
		if($firstLine) { $this->lines[] = $firstLine; }
	}

	function append($line) {
		$this->lines[] = $line;
		return $this;
	}

	function finish() {
		$this->text = html(implode("\n",$this->lines));
		return parent::finish();
	}

	function toHTML() {
		return "<pre class='code'>" . $this->text . "</pre>";
	}
}

class HTML extends Element {
	public $tag;
	protected $lines = array();

	function __construct($tag, $firstLine=null) {
		$this->tag = $tag;
		if($firstLine) { $this->lines[] = $firstLine; }
	}

	function append($line) {
		$this->lines[] = $line;
		return $this;
	}

	function finish() {
		$this->text = implode("\n",$this->lines);
		return parent::finish();
	}

	function toHTML() {
		return $this->text;
	}
}

class Paragraph extends Element {
	protected $lines = array();

	function __construct($firstLine = null) {
		if($firstLine) { $this->lines[] = $firstLine; }
	}

	function append($line) {
		$this->lines[] = $line;
		return $this;
	}

	function finish() {
		foreach($this->lines as $i=>$line) {
			$this->text .= $this->doc->parseInlines($line);
			if( $i != count($this->lines) - 1 )
				$this->text .= ' ';
			if(preg_match("/\s{2}$/", $line))
				$this->text .= "<br>";
		}
		return parent::finish();
	}

	function toHTML() {
		return "<p>" . $this->text;
	}
}

class Reply extends Paragraph {
	public $raw;
	public $replyTo;
	protected $lines = array();
	public static $postid = '';

	function __construct($replyTo, $firstLine = null) {
		$this->replyTo = $replyTo;
		if($firstLine) { $this->lines[] = $firstLine; }
	}

	function finish() {
		if( is_int($this->replyTo) ) {
			$this->text .= "Re <a href='#" . self::$postid . '-' . $this->replyTo . "'>#" . $this->replyTo . "</a>: ";
		}
		return parent::finish();
	}
}

class Quote extends Element {
	public $raw;
	public $contents;
	protected $lines = array();

	function __construct($firstLine = null) {
		if($firstLine) { $this->lines[] = $firstLine; }
	}

	function append($line) {
		$this->lines[] = $line;
		return $this;
	}

	function finish() {
		$excluding = $this->doc->excluding();
		$this->contents = new Document();
		$this->contents->parse($this->lines, $excluding);
		return parent::finish();
	}

	function toHTML() {
		return "<blockquote>" . $this->contents->toHTML() . "</blockquote>";
	}
}

class MarkdownList extends Element {
	public $items;
	protected $unfinisheditems = array();

	function __construct($firstLine = null) {
		if($firstLine) {
			$this->unfinisheditems[] = array($firstLine);
		}
	}

	function append($line) {
		if(count($this->unfinisheditems) == 0) $unfinisheditems[0] = array();
		$this->unfinisheditems[count($this->unfinisheditems) - 1][] = $line;
		return $this;
	}

	function newItem($line) {
		$this->unfinisheditems[] = array();
		return $this->append($line);
	}

	function finish() {
		$excluding = $this->doc->excluding();
		$compact = true;
		foreach($this->unfinisheditems as $unfinisheditem) {
			$doc = new Document();
			$doc->parse($unfinisheditem, $excluding);
			if( count($doc->elements) > 1 || !($doc->elements[0] instanceof Paragraph) )
				$compact = false;
			$this->items[] = $doc;
		}
		$this->compact = $compact;
		return parent::finish();
	}
}

class BulletedList extends MarkdownList {
	function __construct($firstLine = null) {
		parent::__construct($firstLine);
	}

	function toHTML() {
		$raw = "<ul>";
		foreach($this->items as $item) {
			if($this->compact) {
				$raw .= "<li>" . $item->elements[0]->text;
			} else {
				$raw .= "<li>" . $item->toHTML();
			}
		}
		$raw .= "</ul>";
		return $raw;
	}
}

class NumberedList extends MarkdownList {
	function __construct($firstLine = null) {
		parent::__construct($firstLine);
	}

	function toHTML() {
		$raw = "<ol>";
		foreach($this->items as $item) {
			if($this->compact) {
				$raw .= "<li>" . $item->elements[0]->text;
			} else {
				$raw .= "<li>" . $item->toHTML();
			}
		}
		$raw .= "</ol>";
		return $raw;
	}
}

class Table extends Element {
	public $header = array();
	public $alignments = array();
	public $rows = array();
	function __construct($header, $separator, $firstline) {
		$this->header = explode("|", trim($header['raw'], "| \t"));
		$separatorCells = explode("|", trim($separator['raw'], "| \t"));
		$this->rows[0] = explode("|", trim($firstline['raw'], "| \t"));

		foreach($separatorCells as $cell) {
			$cell = trim($cell);
			if($cell[0] == ':' && $cell[strlen($cell)-1] == ':') {
				$this->alignments[] = "center";
			} else if($cell[0] == ':') {
				$this->alignments[] = "left";
			} else if($cell[strlen($cell)-1] == ':') {
				$this->alignments[] = "right";
			} else {
				$this->alignments[] = "";
			}
		}
	}

	function append($row) {
		$this->rows[] = explode("|", trim($row, "| \t"));
		return $this;
	}

	function finish() {
		foreach($this->header as &$th) {
			$th = $this->doc->parseInlines(trim($th));
		}
		foreach($this->rows as &$row) {
			foreach($row as &$td) {
				$td = $this->doc->parseInlines(trim($td));
			}
		}
		return parent::finish();
	}

	function toHTML() {
		$text = "<table><thead><tr>";
		foreach($this->header as $i=>$th) {
			$text .= "<th align='" . $this->alignments[$i] . "'>" . $th;
		}
		$text .= "<tbody>";
		foreach($this->rows as $row) {
			$text .= "<tr>";
			foreach($row as $i=>$td) {
				$text .= "<td align='" . $this->alignments[$i] . "'>" . $td;
			}
		}
		$text .= "</table>";
		return $text;
	}
}
