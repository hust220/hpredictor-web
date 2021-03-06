<?php 
/**
 * 
 * @package MediaWiki
 * @subpackage Experimental
 */

/** */
require_once ('Parser.php');

/**
 * This should one day become the XML->(X)HTML parser
 * Based on work by Jan Hidders and Magnus Manske
 * To use, set
 *    $wgUseXMLparser = true ;
 *    $wgEnableParserCache = false ;
 *    $wgWiki2xml to the path and executable of the command line version (cli)
 * in LocalSettings.php
 * @package MediaWiki
 * @subpackage Experimental
 */

/**
 * the base class for an element
 * @package MediaWiki
 * @subpackage Experimental
 */
class element {
	var $name = '';
	var $attrs = array ();
	var $children = array ();

	/**
	* This finds the ATTRS element and returns the ATTR sub-children as a single string
	*/
	function getSourceAttrs() {
		$ret = '';
		foreach ($this->children as $child) {
			if (!is_string($child) AND $child->name == 'ATTRS') {
				$ret = $child->makeXHTML($parser);
			}
		}
		return $ret;
	}

	/**
	 * This collects the ATTR thingies for getSourceAttrs()
	 */
	function getTheseAttrs() {
		$ret = array ();
		foreach ($this->children as $child) {
			if (!is_string($child) AND $child->name == 'ATTR') {
				$ret[] = $child->attrs["NAME"]."='".$child->children[0]."'";
			}
		}
		return implode(' ', $ret);
	}

	function fixLinkTails(& $parser, $key) {
		$k2 = $key +1;
		if (!isset ($this->children[$k2]))
			return;
		if (!is_string($this->children[$k2]))
			return;
		if (is_string($this->children[$key]))
			return;
		if ($this->children[$key]->name != "LINK")
			return;

		$n = $this->children[$k2];
		$s = '';
		while ($n != '' AND (($n[0] >= 'a' AND $n[0] <= 'z') OR $n[0] == 'ä' OR $n[0] == 'ö' OR $n[0] == 'ü' OR $n[0] == 'ß')) {
			$s .= $n[0];
			$n = substr($n, 1);
		}
		$this->children[$k2] = $n;

		if (count($this->children[$key]->children) > 1) {
			$kl = array_keys($this->children[$key]->children);
			$kl = array_pop($kl);
			$this->children[$key]->children[$kl]->children[] = $s;
		} else {
			$e = new element;
			$e->name = "LINKOPTION";
			$t = $this->children[$key]->sub_makeXHTML($parser);
			$e->children[] = trim($t).$s;
			$this->children[$key]->children[] = $e;
		}
	}

	/**
	* This function generates the XHTML for the entire subtree
	*/
	function sub_makeXHTML(& $parser, $tag = '', $attr = '') {
		$ret = '';

		$attr2 = $this->getSourceAttrs();
		if ($attr != '' AND $attr2 != '')
			$attr .= ' ';
		$attr .= $attr2;

		if ($tag != '') {
			$ret .= '<'.$tag;
			if ($attr != '')
				$ret .= ' '.$attr;
			$ret .= '>';
		}

		# THIS SHOULD BE DONE IN THE WIKI2XML-PARSER INSTEAD
		#	foreach ( array_keys ( $this->children ) AS $x )
		#	   $this->fixLinkTails ( $parser , $x ) ;

		foreach ($this->children as $key => $child) {
			if (is_string($child)) {
				$ret .= $child;
			} elseif ($child->name != 'ATTRS') {
				$ret .= $child->makeXHTML($parser);
			}
		}
		if ($tag != '')
			$ret .= '</'.$tag.">\n";
		return $ret;
	}

	/**
	* Link functions
	*/
	function createInternalLink(& $parser, $target, $display_title, $options) {
		global $wgUser;
		$skin = $wgUser->getSkin();
		$tp = explode(':', $target); # tp = target parts
		$title = ''; # The plain title
		$language = ''; # The language/meta/etc. part
		$namespace = ''; # The namespace, if any
		$subtarget = ''; # The '#' thingy

		$nt = Title :: newFromText($target);
		$fl = strtoupper($this->attrs['FORCEDLINK']) == 'YES';

		if ($fl || count($tp) == 1) {
			# Plain and simple case
			$title = $target;
		} else {
			# There's stuff missing here...
			if ($nt->getNamespace() == NS_IMAGE) {
				$options[] = $display_title;
				return $parser->makeImage($nt, implode('|', $options));
			} else {
				# Default
				$title = $target;
			}
		}

		if ($language != '') {
			# External link within the WikiMedia project
			return "{language link}";
		} else {
			if ($namespace != '') {
				# Link to another namespace, check for image/media stuff
				return "{namespace link}";
			} else {
				return $skin->makeLink($target, $display_title);
			}
		}
	}

	/** @todo document */
	function makeInternalLink(& $parser) {
		$target = '';
		$option = array ();
		foreach ($this->children as $child) {
			if (is_string($child)) {
				# This shouldn't be the case!
			} else {
				if ($child->name == 'LINKTARGET') {
					$target = trim($child->makeXHTML($parser));
				} else {
					$option[] = trim($child->makeXHTML($parser));
				}
			}
		}

		if (count($option) == 0)
			$option[] = $target; # Create dummy display title
		$display_title = array_pop($option);
		return $this->createInternalLink($parser, $target, $display_title, $option);
	}

	/** @todo document */
	function getTemplateXHTML($title, $parts, & $parser) {
		global $wgLang, $wgUser;
		$skin = $wgUser->getSkin();
		$ot = $title; # Original title
		if (count(explode(':', $title)) == 1)
			$title = $wgLang->getNsText(NS_TEMPLATE).":".$title;
		$nt = Title :: newFromText($title);
		$id = $nt->getArticleID();
		if ($id == 0) {
			# No/non-existing page
			return $skin->makeBrokenLink($title, $ot);
		}

		$a = 0;
		$tv = array (); # Template variables
		foreach ($parts AS $part) {
			$a ++;
			$x = explode('=', $part, 2);
			if (count($x) == 1)
				$key = "{$a}";
			else
				$key = $x[0];
			$value = array_pop($x);
			$tv[$key] = $value;
		}
		$art = new Article($nt);
		$text = $art->getContent(false);
		$parser->plain_parse($text, true, $tv);

		return $text;
	}

	/**
	 * This function actually converts wikiXML into XHTML tags
	 * @todo use switch() !
	 */
	function makeXHTML(& $parser) {
		$ret = '';
		$n = $this->name; # Shortcut

		if ($n == 'EXTENSION') {
			# Fix allowed HTML
			$old_n = $n;
			$ext = strtoupper($this->attrs['NAME']);
			
			switch($ext) {
				case 'B':
				case 'STRONG':
					$n = 'BOLD';
					break;
				case 'I':
				case 'EM':
					$n = 'ITALICS';
					break;
				case 'U':
					$n = 'UNDERLINED'; # Hey, virtual wiki tag! ;-)
					break;
				case 'S':
					$n = 'STRIKE';
					break;
				case 'P':
					$n = 'PARAGRAPH';
					break;
				case 'TABLE':
					$n = 'TABLE';
					break;
				case 'TR':
					$n = 'TABLEROW';
					break;
				case 'TD':
					$n = 'TABLECELL';
					break;
				case 'TH':
					$n = 'TABLEHEAD';
					break;
				case 'CAPTION':
					$n = 'CAPTION';
					break;
				case 'NOWIKI':
					$n = 'NOWIKI';
					break;
				}
			if ($n != $old_n) {
				unset ($this->attrs['NAME']); # Cleanup
			} elseif ($parser->nowiki > 0) {
				# No 'real' wiki tags allowed in nowiki section
				$n = '';
			}
		} // $n = 'EXTENSION'

		switch($n) {
			case 'ARTICLE':
				$ret .= $this->sub_makeXHTML($parser);
				break;
			case 'HEADING':
				$ret .= $this->sub_makeXHTML($parser, 'h'.$this->attrs['LEVEL']);
				break;
			case 'PARAGRAPH':
				$ret .= $this->sub_makeXHTML($parser, 'p');
				break;
			case 'BOLD':
				$ret .= $this->sub_makeXHTML($parser, 'strong');
				break;
			case 'ITALICS':
				$ret .= $this->sub_makeXHTML($parser, 'em');
				break;

			# These don't exist as wiki markup
			case 'UNDERLINED':
				$ret .= $this->sub_makeXHTML($parser, 'u');
				break;
			case 'STRIKE':
				$ret .= $this->sub_makeXHTML($parser, 'strike');
				break;

			# HTML comment
			case 'COMMENT':
				# Comments are parsed out
				$ret .= '';
				break;
				

			# Links
			case 'LINK':
				$ret .= $this->makeInternalLink($parser);
				break;
			case 'LINKTARGET':
			case 'LINKOPTION':
				$ret .= $this->sub_makeXHTML($parser);
				break;
		
			case 'TEMPLATE':
				$parts = $this->sub_makeXHTML($parser);
				$parts = explode('|', $parts);
				$title = array_shift($parts);
				$ret .= $this->getTemplateXHTML($title, $parts, & $parser);
				break;

			case 'TEMPLATEVAR':
				$x = $this->sub_makeXHTML($parser);
				if (isset ($parser->mCurrentTemplateOptions["{$x}"]))
					$ret .= $parser->mCurrentTemplateOptions["{$x}"];
				break;

			# Internal use, not generated by wiki2xml parser				
			case 'IGNORE':
				$ret .= $this->sub_makeXHTML($parser);

			case 'NOWIKI':
				$parser->nowiki++;
				$ret .= $this->sub_makeXHTML($parser, '');
				$parser->nowiki--;


			# Unknown HTML extension
			case 'EXTENSION': # This is currently a dummy!!!
				$ext = $this->attrs['NAME'];

				$ret .= '&lt;'.$ext.'&gt;';
				$ret .= $this->sub_makeXHTML($parser);
				$ret .= '&lt;/'.$ext.'&gt; ';
				break;


			# Table stuff

			case 'TABLE':
				$ret .= $this->sub_makeXHTML($parser, 'table');
				break;
			case 'TABLEROW':
				$ret .= $this->sub_makeXHTML($parser, 'tr');
				break;
			case 'TABLECELL':
				$ret .= $this->sub_makeXHTML($parser, 'td');
				break;
			case 'TABLEHEAD':
				$ret .= $this->sub_makeXHTML($parser, 'th');
				break;
			case 'CAPTION':
				$ret .= $this->sub_makeXHTML($parser, 'caption');
				break;
			case 'ATTRS': # SPECIAL CASE : returning attributes
				return $this->getTheseAttrs();


			# Lists stuff
			case 'LISTITEM':
				if ($parser->mListType == 'dl')
					$ret .= $this->sub_makeXHTML($parser, 'dd');
				else
					$ret .= $this->sub_makeXHTML($parser, 'li');
				break;
			case 'LIST':
					$type = 'ol'; # Default
					if ($this->attrs['TYPE'] == 'bullet')
						$type = 'ul';
					else
						if ($this->attrs['TYPE'] == 'indent')
							$type = 'dl';
					$oldtype = $parser->mListType;
					$parser->mListType = $type;
					$ret .= $this->sub_makeXHTML($parser, $type);
					$parser->mListType = $oldtype;
				break;
			
			# Something else entirely
			default:
				$ret .= '&lt;'.$n.'&gt;';
				$ret .= $this->sub_makeXHTML($parser);
				$ret .= '&lt;/'.$n.'&gt; ';
			} // switch($n)

		$ret = "\n{$ret}\n";
		$ret = str_replace("\n\n", "\n", $ret);
		return $ret;
	}

	/**
	 * A function for additional debugging output
	 */
	function myPrint() {
		$ret = "<ul>\n";
		$ret .= "<li> <b> Name: </b> $this->name </li>\n";
		// print attributes
		$ret .= '<li> <b> Attributes: </b>';
		foreach ($this->attrs as $name => $value) {
			$ret .= "$name => $value; ";
		}
		$ret .= " </li>\n";
		// print children
		foreach ($this->children as $child) {
			if (is_string($child)) {
				$ret .= "<li> $child </li>\n";
			} else {
				$ret .= $child->myPrint();
			}
		}
		$ret .= "</ul>\n";
		return $ret;
	}
}

$ancStack = array (); // the stack with ancestral elements

// START Three global functions needed for parsing, sorry guys
/** @todo document */
function wgXMLstartElement($parser, $name, $attrs) {
	global $ancStack;

	$newElem = new element;
	$newElem->name = $name;
	$newElem->attrs = $attrs;

	array_push($ancStack, $newElem);
}

/** @todo document */
function wgXMLendElement($parser, $name) {
	global $ancStack, $rootElem;
	// pop element off stack
	$elem = array_pop($ancStack);
	if (count($ancStack) == 0)
		$rootElem = $elem;
	else
		// add it to its parent
		array_push($ancStack[count($ancStack) - 1]->children, $elem);
}

/** @todo document */
function wgXMLcharacterData($parser, $data) {
	global $ancStack;
	$data = trim($data); // Don't add blank lines, they're no use...
	// add to parent if parent exists
	if ($ancStack && $data != "") {
		array_push($ancStack[count($ancStack) - 1]->children, $data);
	}
}
// END Three global functions needed for parsing, sorry guys

/**
 * Here's the class that generates a nice tree
 * @package MediaWiki
 * @subpackage Experimental
 */
class xml2php {

	/** @todo document */
	function & scanFile($filename) {
		global $ancStack, $rootElem;
		$ancStack = array ();

		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, 'wgXMLstartElement', 'wgXMLendElement');
		xml_set_character_data_handler($xml_parser, 'wgXMLcharacterData');
		if (!($fp = fopen($filename, 'r'))) {
			die('could not open XML input');
		}
		while ($data = fread($fp, 4096)) {
			if (!xml_parse($xml_parser, $data, feof($fp))) {
				die(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser)));
			}
		}
		xml_parser_free($xml_parser);

		// return the remaining root element we copied in the beginning
		return $rootElem;
	}

	/** @todo document */
	function scanString($input) {
		global $ancStack, $rootElem;
		$ancStack = array ();

		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, 'wgXMLstartElement', 'wgXMLendElement');
		xml_set_character_data_handler($xml_parser, 'wgXMLcharacterData');

		if (!xml_parse($xml_parser, $input, true)) {
			die(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser)));
		}
		xml_parser_free($xml_parser);

		// return the remaining root element we copied in the beginning
		return $rootElem;
	}

}

/**
 * @todo document
 * @package MediaWiki
 * @subpackage Experimental
 */
class ParserXML extends Parser {
	/**#@+
	 * @access private
	 */
	# Persistent:
	var $mTagHooks, $mListType;

	# Cleared with clearState():
	var $mOutput, $mAutonumber, $mDTopen, $mStripState = array ();
	var $mVariables, $mIncludeCount, $mArgStack, $mLastSection, $mInPre;

	# Temporary:
	var $mOptions, $mTitle, $mOutputType, $mTemplates, // cache of already loaded templates, avoids
	// multiple SQL queries for the same string
	$mTemplatePath; // stores an unsorted hash of all the templates already loaded
	// in this path. Used for loop detection.

	var $nowikicount, $mCurrentTemplateOptions;

	/**#@-*/

	/**
	 * Constructor
	 * 
	 * @access public
	 */
	function ParserXML() {
		$this->mTemplates = array ();
		$this->mTemplatePath = array ();
		$this->mTagHooks = array ();
		$this->clearState();
	}

	/**
	 * Clear Parser state
	 *
	 * @access private
	 */
	function clearState() {
		$this->mOutput = new ParserOutput;
		$this->mAutonumber = 0;
		$this->mLastSection = "";
		$this->mDTopen = false;
		$this->mVariables = false;
		$this->mIncludeCount = array ();
		$this->mStripState = array ();
		$this->mArgStack = array ();
		$this->mInPre = false;
	}

	/**
	* Turns the wikitext into XML by calling the external parser
	*
	*/
	function html2xml(& $text) {
		global $wgWiki2xml;

		# generating html2xml command path
		$a = $wgWiki2xml;
		$a = explode('/', $a);
		array_pop($a);
		$a[] = 'html2xml';
		$html2xml = implode('/', $a);
		$a = array ();

		$tmpfname = tempnam( wfTempDir(), 'FOO' );
		$handle = fopen($tmpfname, 'w');
		fwrite($handle, utf8_encode($text));
		fclose($handle);
		exec($html2xml.' < '.$tmpfname, $a);
		$text = utf8_decode(implode("\n", $a));
		unlink($tmpfname);
	}

	/** @todo document */
	function runXMLparser(& $text) {
		global $wgWiki2xml;

		$this->html2xml($text);

		$tmpfname = tempnam( wfTempDir(), 'FOO');
		$handle = fopen($tmpfname, 'w');
		fwrite($handle, $text);
		fclose($handle);
		exec($wgWiki2xml.' < '.$tmpfname, $a);
		$text = utf8_decode(implode("\n", $a));
		unlink($tmpfname);
	}

	/** @todo document */
	function plain_parse(& $text, $inline = false, $templateOptions = array ()) {
		$this->runXMLparser($text);
		$nowikicount = 0;
		$w = new xml2php;
		$result = $w->scanString($text);

		$oldTemplateOptions = $this->mCurrentTemplateOptions;
		$this->mCurrentTemplateOptions = $templateOptions;

		if ($inline) { # Inline rendering off for templates
			if (count($result->children) == 1)
				$result->children[0]->name = 'IGNORE';
		}

		if (1)
			$text = $result->makeXHTML($this); # No debugging info
		else
			$text = $result->makeXHTML($this).'<hr>'.$text.'<hr>'.$result->myPrint();
		$this->mCurrentTemplateOptions = $oldTemplateOptions;
	}

	/** @todo document */
	function parse($text, & $title, $options, $linestart = true, $clearState = true) {
		$this->plain_parse($text);
		$this->mOutput->setText($text);
		return $this->mOutput;
	}

}
?>
