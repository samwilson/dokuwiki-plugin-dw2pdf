<?php
 /**
  * dw2Pdf Plugin: Conversion from dokuwiki content to pdf.
  *
  * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
  * @author     Luigi Micco <l.micco@tiscali.it>
  * @author     Andreas Gohr <andi@splitbrain.org>
  * @author     Sam Wilson <sam@samwilson.id.au>
  */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

class action_plugin_dw2pdf extends DokuWiki_Action_Plugin {

    private $tpl;

    /** @var DokuPDF The mPDF object. */
    private $mpdf;

    /**
     * Constructor. Sets the correct template
     */
    function __construct(){
        $tpl;
        if(isset($_REQUEST['tpl'])){
            $tpl = trim(preg_replace('/[^A-Za-z0-9_\-]+/','',$_REQUEST['tpl']));
        }
        if(!$tpl) $tpl = $this->getConf('template');
        if(!$tpl) $tpl = 'default';
        if(!is_dir(DOKU_PLUGIN.'dw2pdf/tpl/'.$tpl)) $tpl = 'default';

        $this->tpl = $tpl;
    }

    /**
     * Register the events
     */
    function register(&$controller) {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'convert',array());
    }

    /**
     * Do the HTML to PDF conversion work
     */
    function convert(&$event, $param) {
        global $ACT;
        global $REV;
        global $ID;
        global $conf;

        // our event?
        if (( $ACT != 'export_pdfbook' ) && ( $ACT != 'export_pdf' )) return false;

        // check user's rights
        if ( auth_quickaclcheck($ID) < AUTH_READ ) return false;

        // it's ours, no one else's
        $event->preventDefault();

        // one or multiple pages?
        $list = array();
        if ( $ACT == 'export_pdf' ) {
            $list[0] = $ID;
        } elseif (isset($_COOKIE['list-pagelist'])) {
            $list = explode("|", $_COOKIE['list-pagelist']);
        }

        // prepare cache
        $cache = new cache(join(',',$list).$REV.$this->tpl,'.dw2.pdf');
        $depends['files']   = array_map('wikiFN',$list);
        $depends['files'][] = __FILE__;
        $depends['files'][] = dirname(__FILE__).'/renderer.php';
        $depends['files'][] = dirname(__FILE__).'/mpdf/mpdf.php';
        $depends['files']   = array_merge($depends['files'], getConfigFiles('main'));

        // hard work only when no cache available
        if(!$this->getConf('usecache') || !$cache->useCache($depends)){
            // initialize PDF library
            require_once(dirname(__FILE__)."/DokuPDF.class.php");
            $this->mpdf = new DokuPDF();

            // let mpdf fix local links
            $self = parse_url(DOKU_URL);
            $url  = $self['scheme'].'://'.$self['host'];
            if($self['port']) $url .= ':'.$port;
            $this->mpdf->setBasePath($url);

            // Set the title
            $title = $_GET['pdfbook_title'];
            if(!$title) $title = p_get_first_heading($ID);
            $this->mpdf->SetTitle($title);

            // some default settings
            $this->mpdf->mirrorMargins = 1;
            $this->mpdf->useOddEven    = 1;
            $this->mpdf->setAutoTopMargin = 'stretch';
            $this->mpdf->setAutoBottomMargin = 'stretch';

            // load the template
            $template = $this->load_template($title);

            // prepare HTML header styles
            $html  = '<html><head><style>'
                   . $this->load_css()
                   . '@page { size:auto; '.$template['page'].'}'
                   . '@page :first {'.$template['first'].'}'
                   . '@page landscape-page { size:landscape }'
                   . 'div.dw2pdf-landscape { page:landscape-page }'
                   . '@page portrait-page { size:portrait }'
                   . 'div.dw2pdf-portrait { page:portrait-page }'
                   . '</style></head><body>'
                   . $template['html']
                   . '<div class="dokuwiki">';

            // loop over all pages
            $cnt = count($list);
            for($n=0; $n<$cnt; $n++){
                $page = $list[$n];

                $html .= p_cached_output(wikiFN($page,$REV),'dw2pdf',$page);
                $html .= $template['cite'];
                if ($n < ($cnt - 1)){
                    $html .= '<pagebreak />';
                }
            }

            $html .= '</div></body></html>';
            $this->mpdf->WriteHTML($html);

            // write to cache file
            $this->mpdf->Output($cache->cache, 'F');
        }

        // deliver the file
        header('Content-Type: application/pdf');
        header('Cache-Control: must-revalidate, no-transform, post-check=0, pre-check=0');
        header('Pragma: public');
        http_conditionalRequest(filemtime($cache->cache));

        $filename = rawurlencode(cleanID(strtr($title, ':/;"','    ')));
        if($this->getConf('output') == 'file'){
            header('Content-Disposition: attachment; filename="'.$filename.'.pdf";');
        }else{
            header('Content-Disposition: inline; filename="'.$filename.'.pdf";');
        }

        if (http_sendfile($cache->cache)) exit;

        $fp = @fopen($cache->cache,"rb");
        if($fp){
            http_rangeRequest($fp,filesize($cache->cache),'application/pdf');
        }else{
            header("HTTP/1.0 500 Internal Server Error");
            print "Could not read file - bad permissions?";
        }
        exit();
    }


    /**
     * Load the various template files and prepare the HTML/CSS for insertion
     */
    protected function load_template($title){
        global $ID;
        global $REV;
        global $conf;
        $tpl = $this->tpl;

        // this is what we'll return
        $output = array(
            'html'  => '',
            'page'  => '',
            'first' => '',
            'cite'  => '',
        );

        // prepare header/footer elements
        $html = '';
        foreach(array('header','footer') as $t){
            foreach(array('','_odd','_even','_first') as $h){
                if(file_exists(DOKU_PLUGIN.'dw2pdf/tpl/'.$tpl.'/'.$t.$h.'.html')){
                    $html .= '<htmlpage'.$t.' name="'.$t.$h.'">'.DOKU_LF;
                    $html .= file_get_contents(DOKU_PLUGIN.'dw2pdf/tpl/'.$tpl.'/'.$t.$h.'.html').DOKU_LF;
                    $html .= '</htmlpage'.$t.'>'.DOKU_LF;

                    // register the needed pseudo CSS
                    if($h == '_first'){
                        $output['first'] .= $t.': html_'.$t.$h.';'.DOKU_LF;
                    }elseif($h == '_even'){
                        $output['page'] .= 'even-'.$t.'-name: html_'.$t.$h.';'.DOKU_LF;
                    }elseif($h == '_odd'){
                        $output['page'] .= 'odd-'.$t.'-name: html_'.$t.$h.';'.DOKU_LF;
                    }else{
                        $output['page'] .= $t.': html_'.$t.$h.';'.DOKU_LF;
                    }
                }
            }
        }

        // generate qr code for this page using google infographics api
        $qr_code = '';
        if ($this->getConf('qrcodesize')) {
            $url = urlencode(wl($ID,'','&',true));
            $qr_code = '<img src="https://chart.googleapis.com/chart?chs='.
                       $this->getConf('qrcodesize').'&cht=qr&chl='.$url.'" />';
        }

        // prepare replacements
        $replace = array(
                '@ID@'      => $ID,
                '@NS@'      => getNS($ID),
                '@NS_CSS@'  => str_replace(':', '__', getNS($ID)),
                '@PAGE@'    => '{PAGENO}',
                '@PAGES@'   => '{nb}',
                '@TITLE@'   => hsc($title),
                '@WIKI@'    => $conf['title'],
                '@WIKIURL@' => DOKU_URL,
                '@UPDATE@'  => dformat(filemtime(wikiFN($ID,$REV))),
                '@PAGEURL@' => wl($ID,($REV)?array('rev'=>$REV):false, true, "&"),
                '@DATE@'    => dformat(time()),
                '@BASE@'    => DOKU_BASE,
                '@TPLBASE@' => DOKU_BASE.'lib/plugins/dw2pdf/tpl/'.$tpl.'/',
                '@TPLBASE@' => DOKU_PLUGIN.'dw2pdf/tpl/'.$tpl.'/',
                '@QRCODE@'  => $qr_code,
                '@REVNUM@'  => $this->get_revision_count(),
        );

        // Relace DATA values, if the Data plugin is available.
        $html = $this->handleDataReplacements($html);

        // set HTML element
        $output['html'] = str_replace(array_keys($replace), array_values($replace), $html);

        // citation box
        if(file_exists(DOKU_PLUGIN.'dw2pdf/tpl/'.$tpl.'/citation.html')){
            $output['cite'] = file_get_contents(DOKU_PLUGIN.'dw2pdf/tpl/'.$tpl.'/citation.html');
            $output['cite'] = str_replace(array_keys($replace), array_values($replace), $output['cite']);
        }

        // Set up custom fonts if config_fonts.php is present in the template
        $this->setupTemplateFonts();

        return $output;
    }

    /**
     * Get the number of revisions for the current page (including when the
     * current view is an old revision).  Has the option to limit to revisions
     * of any of the following change types:
     *     DOKU_CHANGE_TYPE_CREATE,
     *     DOKU_CHANGE_TYPE_EDIT,
     *     DOKU_CHANGE_TYPE_MINOR_EDIT,
     *     DOKU_CHANGE_TYPE_DELETE, and
     *     DOKU_CHANGE_TYPE_REVERT.
     * All except minor revisions are included if the $types parameter is not
     * used.
     * 
     * Note: This is public and static because it can be useful in templates,
     * and should probably not live in this class at all.
     * 
     * @uses getRevisions()
     * @uses getRevisionInfo()
     * @param array $types Revision types to be included in the count.
     * @return int The total number of revisions.
     */
    public static function get_revision_count($types = null) {
        global $ID, $INFO, $REV;
        if (empty($types)) {
            // These types are defined in inc/changelog.php
            $types = array(
                DOKU_CHANGE_TYPE_CREATE,
                DOKU_CHANGE_TYPE_EDIT,
                //DOKU_CHANGE_TYPE_MINOR_EDIT,
                DOKU_CHANGE_TYPE_DELETE,
                DOKU_CHANGE_TYPE_REVERT,
            );
        }
        $count = 1;
        // Get all revisions, including current
        $revisions = array_merge(
            array($INFO['meta']['last_change']['date']),
            getRevisions($ID, 0, PHP_INT_MAX)
        );
        // Get revision timestamp of the currently-viewed page revision
        $before = (is_int($REV)) ? $REV : $INFO['meta']['last_change']['date'];
        foreach ($revisions as $revision) {
            $info = getRevisionInfo($ID, $revision);
            // Of requred type and earlier than $before
            if (in_array($info['type'], $types) && $info['date']<=$before) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Check for the config_fonts.php file in the template directory, and set up
     * mPDF to use any fonts found in that file.  The path to the system font
     * directory also needs to be set in conf/local.protected.php by defining
     * the _MPDF_SYSTEM_TTFONTS constant.
     * 
     * @return void
     */
    private function setupTemplateFonts() {
        // Get the config file
        $config_file = DOKU_PLUGIN.'dw2pdf/tpl/'.$this->tpl.'/config_fonts.php';
        if (!file_exists($config_file)) return;
        include_once($config_file);

        // Load custom fonts
        if (isset($fontdata)) {
            $this->mpdf->fontdata = array_merge($fontdata, $this->mpdf->fontdata);
            // This loop repeats what is done in the constructor of mPDF, around line 1135
            foreach ($fontdata AS $f => $fs) {
                if (isset($fs['R']) && $fs['R']) { $this->mpdf->available_unifonts[] = $f; }
                if (isset($fs['B']) && $fs['B']) { $this->mpdf->available_unifonts[] = $f.'B'; }
                if (isset($fs['I']) && $fs['I']) { $this->mpdf->available_unifonts[] = $f.'I'; }
                if (isset($fs['BI']) && $fs['BI']) { $this->mpdf->available_unifonts[] = $f.'BI'; }
            }
            $this->mpdf->default_available_fonts = $this->mpdf->available_unifonts;
        }
    }

    /**
     * Replace all @DATA:column@ patterns with values retrieved from the
     * data plugin's metadata database.
     * 
     * @global string $ID The current page ID.
     * @param string $html Input HTML, in which to find replacement strings.
     * @return string HTML string with replacements made.
     */
    public function handleDataReplacements($html) {
        global $ID;

        // Load helper (or give up)
        $helper = plugin_load('helper', 'data');
        if ($helper == NULL) return $html;

        // Find replacements (or give up)
        $count = preg_match_all('/@DATA:(.*?)@/', $html, $matches);
        if ($count < 1) return $html;
        $replaceable = array();
        for ($m=0; $m<count($matches[0]); $m++) {
            $replaceable[strtolower($matches[1][$m])] = $matches[0][$m];
        }

        // Set up SQLite, and retrieve this page's metadata
        $sqlite = $helper->_getDB();
        $sql = "SELECT key, value
            FROM pages JOIN data ON data.pid=pages.pid
            WHERE pages.page = '".$ID."'";
        $rows = $sqlite->res2arr($sqlite->query($sql));

        // Get replacement values and build the replacement array
        $replace = array();
        foreach ($rows as $row) {
            if (isset($replaceable[$row['key']])) {
                $replace[$replaceable[$row['key']]] = $row['value'];
            }
        }

        // Perform replacements
        $html = str_replace(array_keys($replace), array_values($replace), $html);

        return $html;
    }

    /**
     * Load all the style sheets and apply the needed replacements
     */
    protected function load_css(){
        //reusue the CSS dispatcher functions without triggering the main function
        define('SIMPLE_TEST',1);
        require_once(DOKU_INC.'lib/exe/css.php');

        // prepare CSS files
        $files = array_merge(
                    array(
                        DOKU_INC.'lib/styles/screen.css'
                            => DOKU_BASE.'lib/styles/',
                        DOKU_INC.'lib/styles/print.css'
                            => DOKU_BASE.'lib/styles/',
                    ),
                    css_pluginstyles('all'),
                    $this->css_pluginPDFstyles(),
                    array(
                        DOKU_PLUGIN.'dw2pdf/conf/style.css'
                            => DOKU_BASE.'lib/plugins/dw2pdf/conf/',
                        DOKU_PLUGIN.'dw2pdf/tpl/'.$this->tpl.'/style.css'
                            => DOKU_BASE.'lib/plugins/dw2pdf/tpl/'.$this->tpl.'/',
                        DOKU_PLUGIN.'dw2pdf/conf/style.local.css'
                            => DOKU_BASE.'lib/plugins/dw2pdf/conf/',
                    )
                 );
        $css = '';
        foreach($files as $file => $location){
            $css .= css_loadfile($file, $location);
        }

        // apply pattern replacements
        $css = css_applystyle($css,DOKU_INC.'lib/tpl/'.$conf['template'].'/');

        return $css;
    }


    /**
     * Returns a list of possible Plugin PDF Styles
     *
     * Checks for a pdf.css, falls back to print.css
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function css_pluginPDFstyles(){
        global $lang;
        $list = array();
        $plugins = plugin_list();

        $usestyle = explode(',',$this->getConf('usestyles'));
        foreach ($plugins as $p){
            if(in_array($p,$usestyle)){
                $list[DOKU_PLUGIN."$p/screen.css"] = DOKU_BASE."lib/plugins/$p/";
                $list[DOKU_PLUGIN."$p/style.css"] = DOKU_BASE."lib/plugins/$p/";
            }

            if(file_exists(DOKU_PLUGIN."$p/pdf.css")){
                $list[DOKU_PLUGIN."$p/pdf.css"] = DOKU_BASE."lib/plugins/$p/";
            }else{
                $list[DOKU_PLUGIN."$p/print.css"] = DOKU_BASE."lib/plugins/$p/";
            }
        }
        return $list;
    }

}
