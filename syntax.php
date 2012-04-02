<?php
/**
 * DokuWiki Plugin dw2pdf (Syntax Component)
 * 
 * For marking changes in page orientation.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sam Wilson <sam@samwilson.id.au>
 */
/* Must be run within Dokuwiki */
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_PLUGIN'))
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once(DOKU_PLUGIN . 'syntax.php');

class syntax_plugin_dw2pdf extends DokuWiki_Syntax_Plugin {

    var $mode;

    function __construct() {
        $this->mode = substr(get_class($this), 7);
    }

    function getType() {
        return 'substition';
    }

    function getSort() {
        return 1;
    }

    function getPType() {
        return 'block';
    }

    function connectTo($mode) {
        $pattern = '~~PDF:(?:LANDSCAPE|PORTRAIT)~~';
        $this->Lexer->addSpecialPattern($pattern, $mode, $this->mode);
    }

    function handle($match, $state, $pos) {
        return array($match, $state, $pos);
    }

    function render($mode, &$renderer, $data) {
        if ($mode == 'xhtml') {
            $orientation = strtolower(substr($data[0], 6, -2));
            $renderer->doc .= "<div class='dw2pdf-$orientation'></div>" . DOKU_LF;
            return true;
        }
        return false;
    }

}
