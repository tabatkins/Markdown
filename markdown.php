<?php
namespace Markdown;


function parseComment($text, $postid) {
	return documentFromLines( linesFromText($text), $postid);
}

function linesFromText($md) {
	$lines = array();
	foreach(explode("\n",$md) as $rawline) {
		if(ord($rawline[strlen($rawline) - 1]) == 13) $rawline = substr($rawline,0,-1);
		if(trim($rawline) == '') {
			// blank line
			$lines[] = array('type'=>'blank');
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
		else if(preg_match("/^> (.*)/", $rawline, $matches)) {
			// Blockquote
			$lines[] = array('type'=>'quote', 'text'=>$matches[1], 'raw'=>$rawline);
		}
		else if(preg_match("/^Re #(\d+):/", $rawline, $matches)) {
			// Comment link
			$lines[] = array('type'=>'reply', 'reply-to'=>intval($matches[1]), 'raw'=>$rawline);
		}
		else if(preg_match("/^~~~~(.*)/", $rawline, $matches)) {
			// Explicit code delimiter
			$lines[] = array('type'=>'code', 'data'=>$matches[1], 'raw'=>$rawline);
		}
		else {
			// Normal line of text.
			preg_match("/^(\s*)(.*)/", $rawline, $matches);
			$lines[] = array('type'=>'text', 'text'=>$matches[2], 'spaces'=>strlen($matches[1]), 'raw'=>$rawline);
		}
	}
	return $lines;
}

function documentFromLines($lines, $postid) {
	$state = "start";
	$lines[] = array('eof');
	$doc = new Document;
	$currelem = null;
	for($i = 0; $i < count($lines); $i++) {
		$line = $lines[$i];
		$type = $line['type'];
		$next = $lines[$i+1];
		$nexttype = $next['type'];

		//echo "i: $i <br>Start state: $state <br>Line: "; print_r($line);echo "</a>";

		if($state == "start") {
			$currelem = null;
			if($type == 'blank') {
				// Do nothing.
			} else if($type == 'hr') {
				$doc->append(new Separator);
			} else if($type == 'code') {
				$currelem = new Code;
				$state = 'explicit-code';
			} else if($type == 'text' && $line['spaces'] >= 4) {
				$currelem = new Code(substr($line['raw'], 4));
				$state = 'indented-code';
			} else if($type == 'bulleted') {
				$currelem = new BulletedList($line['text']);
				$state = 'bulleted-list';
			} else if($type == 'numbered') {
				$currelem = new NumberedList($line['text']);
				$state = 'numbered-list';
			} else if($type == 'quote') {
				$currelem = new Quote($lines['text']);
				$state = 'quote';
			} else if($type == 'reply') {
				$doc->append(new Reply($line['reply-to'], $postid));
			} else if($type == 'text') {
				$currelem = new Paragraph($line['text']);
				$state = 'paragraph';
			}
		} else if($state == 'explicit-code') {
			if($type == 'code') {
				$doc->append($currelem->finish());
				$state = 'start';
			} else if($type == 'eof') {
				$i--;
				$doc->append($currelem->finish());
				$state = 'start';
			} else {
				$currelem->append($line['raw']);
			}
		} else if($state == 'indented-code') {
			if($type == 'text' && $line['spaces'] >= 4) {
				$currelem->append(substr($line['raw'], 4));
			} else {
				$i--;
				$doc->append($currelem->finish());
				$state = 'start';
			}
		} else if($state == 'bulleted-list') {
			if($type == 'text') {
				$currelem->append($line['text']);
			} else if($type == 'bulleted') {
				$currelem->newItem($line['text']);
			} else if($type == 'blank' && $nexttype == 'bulleted') {
				$currelem->compact = false;
			} else if($type == 'blank' && $nexttype == 'text' && $next['spaces'] >= 4) {
				$currelem->compact = false;
				$currelem->append('');
			} else {
				$i--;
				$doc->append($currelem->finish());
				$state = 'start';
			}
		} else if($state == 'numbered-list') {
			if($type == 'text') {
				$currelem->append($line['text']);
			} else if($type == 'numbered') {
				$currelem->newItem($line['text']);
			} else if($type == 'blank' && $nexttype == 'numbered') {
				$currelem->compact = false;
			} else if($type == 'blank' && $nexttype == 'text' && $next['spaces'] >= 4) {
				$currelem->compact = false;
				$currelem->append('');
			} else {
				$i--;
				$doc->append($currelem->finish());
				$state = 'start';
			}
		} else if($state == 'quote') {
			if($type == 'quote') {
				$currelem->append($line['text']);
			} else {
				$i--;
				$doc->append($currelem->finish());
				$state = 'start';
			}
		} else if($state == 'paragraph') {
			if($type == 'text') {
				$currelem->append($line['text']);
			} else {
				$i--;
				$doc->append($currelem->finish());
				$state = 'start';
			}
		}
		//echo "<details><summary>Currelem</summary>";print_r($currelem);echo "</a></details>";
		//echo "<details><summary>Doc</summary>";print_r($doc);echo "</a></details>";
		//echo "End state: $state <hr>";
	}
	return $doc;
}

class Element {
	protected $finished = false;
	function finish() {
		$finished = true;
		return $this;
	}
}

class Document extends Element {
	public $elements = array();

	function __construct($elements = array()) {
		$this->elements = $elements;
	}

	function append($elem) {
		$this->elements[] = $elem;
		return $this;
	}

	function toHTML() {
		return implode( array_map(function($x) { return $x->toHTML(); }, $this->elements) );
	}
}

class Separator extends Element {
	function __construct() {}

	function toHTML() {
		return "<hr>";
	}
}

class Reply extends Element {
	public $replyTo;
	public $replyId;

	function __construct($replyTo, $postid) {
		$this->replyTo = intval($replyTo);
		$this->replyId = $postid . '-' . $replyTo;
	}

	function toHTML() {
		return "<p>Re <a href='#" . $this->replyId . "'>#" . $this->replyTo . "</a>:</p>";
	}
}

class Code extends Element {
	public $text;
	protected $lines = array();

	function __construct($firstLine = null) {
		if($firstLine) { $this->lines[] = $firstLine; }
	}

	function append($line) {
		$this->lines[] = $line;
		return $this;
	}

	function finish() {
		$this->finished = true;
		$this->text = htmlspecialchars(implode("\n",$this->lines));
		return $this;
	}

	function toHTML() {
		return "<pre class='code'>" . $this->text . "</pre>";
	}
}

class Paragraph extends Element {
	public $text;
	protected $lines = array();

	function __construct($firstLine = null) {
		if($firstLine) { $this->lines[] = $firstLine; }
	}

	function append($line) {
		$this->lines[] = $line;
		return $this;
	}

	function finish() {
		$finished = true;
		foreach($this->lines as $i => $line) {
			$this->text .= htmlspecialchars($line);
			if(preg_match("/\s{2}$/", $line))
				$this->text .= "<br>";
		}
		return $this;
	}

	function toHTML() {
		return "<p>" . $this->text;
	}
}

class Quote extends Element {
	public $text;
	protected $lines = array();

	function __construct($firstLine = null) {
		if($firstLine) { $this->lines[] = $firstLine; }
	}

	function append($line) {
		$this->lines[] = $line;
		return $this;
	}

	function finish() {
		$finished = true;
		foreach($this->lines as $i => $line) {
			$this->text .= htmlspecialchars($line);
			if(preg_match("/\s{2}$/", $line))
				$this->text .= "<br>";
		}
		return $this;
	}

	function toHTML() {
		return "<blockquote>" . $this->text . "</blockquote>";
	}
}

class MarkdownList extends Element {
	public $items;
	public $compact = true;
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
		$this->finished = true;
		foreach($this->unfinisheditems as $unfinisheditem) {
			$item = '';
			foreach($unfinisheditem as $i=>$line) {
				if($i == 0 && !$compact)
					$item .= "<p>";
				if($line == '')
					$item .= "<p>";
				else {
					$item .= htmlspecialchars($line);
					if(preg_match("/\s{2}$/", $line))
						$item .= "<br>";
				}
			}
			$this->items[] = $item;
		}
		return $this;
	}
}

class BulletedList extends MarkdownList {
	function __construct($firstLine = null) {
		parent::__construct($firstLine);
	}

	function toHTML() {
		$text = "<ul>";
		foreach($this->items as $item) {
			$text .= "<li>" . $item;
		}
		$text .= "</ul>";
		return $text;
	}
}

class NumberedList extends MarkdownList {
	function __construct($firstLine = null) {
		parent::__construct($firstLine);
	}

	function toHTML() {
		$text = "<ol>";
		foreach($this->items as $item) {
			$text .= "<li>" . $item;
		}
		$text .= "</ol>";
		return $text;
	}
}