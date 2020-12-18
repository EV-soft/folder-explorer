<?php   $DocFil= '\Proj1\folder-explorer.php';    $DocVer='1.0.0';    $DocRev='2020-12-18';     $DocIni='evs';  $ModulNr=0; ## File informative only
$©= '𝘓𝘐𝘊𝘌𝘕𝘚𝘌 & 𝘊𝘰𝘱𝘺𝘳𝘪𝘨𝘩𝘵 ©  2019-2020 EV-soft *** See the file: LICENSE';

// error_reporting(E_ERROR);

$GLOBALS["ØProgRoot"]= './';
require_once ('./php2html.lib.php');    // Creating HTML-functions



##### SPECIAL this page only:
define ('p2h_IS_WIN', DIRECTORY_SEPARATOR == '\\');
define ('p2h_PATH', ''); // $p);             // $p = pathinfo($p)['dirname'];
define ('p2h_SELF_URL', '');

defined('p2h_SHOW_HIDDEN')       || define('p2h_SHOW_HIDDEN', $show_hidden_files?? '');
defined('p2h_ROOT_PATH')         || define('p2h_ROOT_PATH', $p2h_AppConf['root_path']?? '');
defined('p2h_LANG')              || define('p2h_LANG', $lang?? '');
defined('p2h_FILE_EXTENSION')    || define('p2h_FILE_EXTENSION', $p2h_AppConf['allowed_file_extensions']?? '');
defined('p2h_UPLOAD_EXTENSION')  || define('p2h_UPLOAD_EXTENSION', $p2h_AppConf['allowed_upload_extensions']?? '');
defined('p2h_EXCLUDE_ITEMS')     || define('p2h_EXCLUDE_ITEMS', $p2h_AppConf['exclude_items']?? '');
defined('p2h_DOC_VIEWER')        || define('p2h_DOC_VIEWER', $p2h_AppConf['online_viewer']?? '');
define ('p2h_READONLY', $p2h_AppConf['use_auth'] ?? ''
            && !empty($p2h_AppConf['readonly_users']) 
            && isset($_SESSION[p2h_SESSION_ID]['logged']) 
            && in_array($_SESSION[p2h_SESSION_ID]['logged'], 
            $p2h_AppConf['readonly_users']));
// define ('p2h_IS_WIN', DIRECTORY_SEPARATOR == '\\');
define ('p2h_USER_PATH', $p2h_AppConf['myPlace']?? '');
define ('MAX_UPLOAD_SIZE', $p2h_AppConf['max_upload_size_bytes']?? '');

// if (!isset($path)) $path= '';
//$localVars=[$path, $breadcrumbs ];
//foreach ($localVars as $var)
//    if (!isset($var)) $var= '';
if (!isset($quickView)) $quickView= false;

/*
// PAFM:
// https://github.com/mustafa0x/pafm/blob/master/pafm.php:
define('AUTHORIZE', true);

$do = isset($_GET['do']) ? $_GET['do'] : null;

if (AUTHORIZE) {
	session_start();
	doAuth();
}

if ($do) {  // perform requested action
    if (isset($_GET['subject']) && !isNull($_GET['subject'])) {
        $subject = str_replace('/', null, $_GET['subject']);
        $subjectURL = escape($subject);
        $subjectHTML = htmlspecialchars($subject);
    }
    switch ($do) {
        case 'login':                           exit(doLogin());
        case 'logout':                          exit(doLogout());
        case 'shell':           nonce_check();  exit(shell_exec($_POST['cmd']));
        case 'create':          nonce_check();  exit(doCreate($_POST['f_name'], $_GET['f_type'], $path));
        case 'upload':          nonce_check();  exit(doUpload($path));
        case 'chmod':           nonce_check();  exit(doChmod($subject, $path, $_POST['mod']));
        case 'extract':         nonce_check();  exit(doExtract($subject, $path));
        case 'readFile':                        exit(doReadFile($subject, $path));
        case 'rename':          nonce_check();  exit(doRename($subject, $path));
        case 'delete':          nonce_check();  exit(doDelete($subject, $path));
        case 'saveEdit':        nonce_check();  exit(doSaveEdit($subject, $path));
        case 'copy':            nonce_check();  exit(doCopy($subject, $path));
        case 'move':            nonce_check();  exit(doMove($subject, $path));
        case 'moveList':                        exit(moveList($subject, $path));
        case 'installCodeMirror':               exit(installCodeMirror());
        case 'fileExists':                      exit(file_exists($path .'/'. $subject));
        case 'getfs':                           exit(getFs($path .'/'. $subject));
        case 'remoteCopy':      nonce_check();  exit(doRemoteCopy($path));
    }   
}
function nonce_check() {
    if (AUTHORIZE && $_GET['nonce'] != $_SESSION['nonce']) exit(refresh('Invalid nonce, try again.'));
}
function refresh($message, $speed = 2){ global $redir;
    return '<meta http-equiv="refresh" content="'.$speed.';url='.$redir.'">'.$message;
}
function escape($uri) {
    return str_replace('%2F', '/', rawurlencode($uri));
}
// :PAFM
/**/


/**
 * Build URL query string
 * @param name, value
 * @return string
 */
function urlQuery($path,$n1='',$v1='',$n2='',$v2='',$n3='',$v3='',$n4='',$v4='')
{
    $res= 'href= "?p='. urlencode($path). '&amp;'. $n1.'='. urlencode($v1);
    if ($n2>'') $res.= '&amp;'.$n2. '='. urlencode($v2);
    if ($n3>'') $res.= '&amp;'.$n3. '='. urlencode($v3);
    if ($n4>'') $res.= '&amp;'.$n4. '='. urlencode($v4);
    return $res.'"';
}

function p2h_get_size($file) {  // Recover all file sizes larger than > 2GB.  32bits / 64bits / linux
    static $iswin;
    static $isdarwin;
    static $exec_works;
    if (!isset($iswin))      {$iswin = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN'); }
    if (!isset($isdarwin))   {$isdarwin = (strtoupper(substr(PHP_OS, 0)) == "DARWIN"); }    //  MacOS
    if (!isset($exec_works)) {$exec_works = (function_exists('exec') && !ini_get('safe_mode') && @exec('echo EXEC') == 'EXEC'); }

    if ($exec_works) {    // try a shell command
        $cmd = ($iswin) ? "for %F in (\"$file\") do @echo %~zF" : ($isdarwin ? "stat -f%z \"$file\"" : "stat -c%s \"$file\"");
        @exec($cmd, $output);
        if (is_array($output) && ctype_digit($size = trim(implode("\n", $output)))) { return $size; }
    }
    if ($iswin && class_exists("COM")) {    // try the Windows COM interface:
        try {
            $fsobj = new COM('Scripting.FileSystemObject');
            $f = $fsobj->GetFile( realpath($file) );
            $size = $f->Size;
        } catch (Exception $e)  { $size = null; }
        if (ctype_digit($size)) { return $size; }
    }
    return filesize($file);    // if all else fails
}

function p2h_nice_filesize($size)    // Output nice rounded filesize
{   if (!is_numeric($size)) return $size; //  string 'Folder'
    $units = array('&nbsp;B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $power = $size > 0 ? floor(log($size, 1024)) : 0;
    return sprintf('%s %s', round($size / pow(1024, $power), 2), $units[$power]);
}

function p2h_get_file_icon_class($path) { // Get CSS classname for file
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (in_array($ext,['ico','gif','jpg','jpeg','jpc','jp2','jpx','xbm','wbmp','png','bmp','tif','tiff','ttf','eot','woff','woff2','svg']) )
                                                                    $icoc = 'far fa-file-image colr1';
    elseif (in_array($ext,['passwd','ftpquota','sql','js','json','sh','config','twig','tpl','md','gitignore','c','cpp','cs','yml','py','map','lock','dtd']) )
                                                                    $icoc = 'far fa-file-code colr3';
    elseif (in_array($ext,['txt','ini','conf','log','htaccess']))   $icoc = 'far fa-file-alt colr4';
    elseif (in_array($ext,['css','less','sass','scss']))            $icoc = 'fab fa-css3-alt colr8';
    elseif (in_array($ext,['zip','rar','gz','tar','7z']))           $icoc = 'far fa-file-archive colr2';
    elseif (in_array($ext,['php','php4','php5','phps','phtml']))    $icoc = 'far fa-file-code colr3';
    elseif (in_array($ext,['htm','html','shtml','xhtml']))          $icoc = 'fab fa-html5 colr5';
    elseif (in_array($ext,['xml','xsl']))                           $icoc = 'far fa-file-excel colr6';
    elseif (in_array($ext,['wav','mp3','mp2','m4a','aac','ogg','oga','wma','mka','flac','ac3','tds']))
                                                                    $icoc = 'far fa-music';
    elseif (in_array($ext,['m3u','m3u8','pls','cue']) )             $icoc = 'far fa-headphones';
    elseif (in_array($ext,['avi','mpg','mpeg','mp4','m4v','flv','f4v','ogm','ogv','mov','mkv','3gp','asf','wmv']))
                                                                    $icoc = 'far fa-file-video';
    elseif (in_array($ext,['eml','msg']) )                          $icoc = 'far fa-envelope';
    elseif (in_array($ext,['xls','xlsx','ods']) )                   $icoc = 'far fa-file-excel colr6';
    elseif (in_array($ext,['db','odb']) )                           $icoc = 'fas fa-database';
    elseif (in_array($ext,['csv']) )                                $icoc = 'far fa-file-alt colr4';
    elseif (in_array($ext,['bak']) )                                $icoc = 'far fa-clipboard';
    elseif (in_array($ext,['doc','docx','odt']) )                   $icoc = 'far fa-file-word';
    elseif (in_array($ext,['ppt','pptx','odp']) )                   $icoc = 'far fa-file-powerpoint colr7';
    elseif (in_array($ext,['ttf','ttc','otf','woff','woff2','eot','fon']) ) $icoc = 'far fa-font';
    elseif (in_array($ext,['pdf']) )                                $icoc = 'far fa-file-pdf';
    elseif (in_array($ext,['psd','ai','eps','fla','swf']) )         $icoc = 'far fa-file-image colr1';
    elseif (in_array($ext,['exe','msi']) )                          $icoc = 'far fa-file';
    elseif (in_array($ext,['bat']) )                                $icoc = 'far fa-terminal';
    else                                                            $icoc = 'fas fa-info-circle';
    return $icoc;
}

$p2h_Style = '
<style>  // ICON-colors: colrx used in icon class $icoc:
    .colr0 { color: #0157b3;    }  /* fa-folder          */
    .colr1 { color: #26b99a;    }  /* fa-file-image      */
    .colr2 { color: #E16666;  background-color: gold;  }  /* fa-file-archive    */
    .colr2 { color: #E16666;    }  /* fa-file-archive    */
    .colr3 { color: #cc4b4c;    }  /* fa-file-code       */
    .colr4 { color: #0096e6;    }  /* fa-file-alt        */
    .colr5 { color: #d75e72;    }  /* fa-html5           */
    .colr6 { color: #09c55d;    }  /* fa-file-excel      */
    .colr7 { color: #f6712e;    }  /* fa-file-powerpoint */
    .colr8 { color: #f36fa0;    }  /* fa-file-css3       */
    .colr9 { color: DodgerBlue; }  /* fa-link            */
    .colrg { color: #cccccc;    }  /* Gray               */
    
    .txtbutt {
        text-decoration: none; 
        padding: 2px; 
        margin-right:4px;
        border: 1px solid lightgray; 
        color: blue; 
        box-shadow: 2px 2px 1px lightgray;
    }
    
    .table td.div.file-name-div {   /* live-preview */
        position: relative;
    }
    .live-preview-img {
        display: none;
        border: 1px solid lightgray;
        position: absolute;
        top: 180px;
        left: 330px;
        max-width: 800px;
        background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAAAKklEQVR42mL5//8/Azbw+PFjrOJMDCSCUQ3EABZc4S0rKzsaSvTTABBgAMyfCMsY4B9iAAAAAElFTkSuQmCC)
    }
    .file-name-div:hover  .live-preview-img {
        display: block;
        font-style: italic;      
    }



.modal {            /* The Modal (background) */
  display: none;    /* Hidden by default */
  position: fixed;  /* Stay in place */
  z-index: 1;       /* Sit on top */
  left: 0;
  top: 0;
  margin: auto;
  width: 600px;
  height: 100%;     /* Full height */
  overflow: auto;   /* Enable scroll if needed */
  background-color: rgb(0,0,0);         /* Fallback color */
  background-color: rgba(0,0,0,0.4);    /* Black w/ opacity */
}


/* Modal Header */
.modal-header {
  padding: 2px 16px;
  background-color: #5cb85c;
  color: white;
}

.modal-body {       /* Modal Body */
  padding: 2px 16px;
}

.modal-footer {     /* Modal Footer */
  padding: 2px 16px;
  background-color: #5cb85c;
  color: white;
}

.modal-content {    /* Modal Content/Box */
  position: relative;
  background-color: #fefefe;
  margin: auto;
  padding: 0;
  border: 1px solid #888;
  width: 80%;
  box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19);
  animation-name: animatetop;
  animation-duration: 0.4s
}

@keyframes animatetop { /* Add Animation */
  from {top: -300px; opacity: 0}
  to {top: 0; opacity: 1}
}

.close {    /*  The Close Button */
  color: #aaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
}

.close:hover,
.close:focus {
  color: black;
  text-decoration: none;
  cursor: pointer;
}


.dropbtn {   /*  Dropdown Button  */
  color: #4CAF50;
  background-color: white;
  padding: 6px;
  font-size: 14px;
  border: 1px solid green;
  cursor: pointer;
}
.dropbtn:hover, .dropbtn:focus {    /*  Dropdown button on hover & focus  */
  background-color: #3e8e41; 
  color: black;
}
#searchInput {      /* The search field  */
  box-sizing: border-box;
  /* background-image: url( \'searchicon.png\');  */
  font-family: "Font Awesome 5 Free";   content: "\f002";
  background-position: 14px 12px;
  background-repeat: no-repeat;
  font-size: 14px;
  width:100%;
  padding: 5px 10px 6px 14px;
  border: none;
  border-bottom: 1px solid #ddd;
}
#searchInput:focus {            /*  The search field when it gets focus/clicked on  */
    outline: 3px solid #ddd;
}
.dropdown {                     /*  The container <div> - needed to position the dropdown content  */
  position: relative;
  display: inline-block;
}


.dropdown-content {             /*  Dropdown Content (Hidden by Default) */
  /*  width:170%; */
  position: absolute;
  display: none;
  background-color: #f6f6f6;
  max-height: 320px;
  overflow: auto;
  border: 1px solid #ddd;
  z-index: 1;
}

.dropdown-content a {            /* Links inside the dropdown  */
  color: black;
  padding: 2px 6px;
  text-decoration: none;
  font-size: 12px;
  display: block;
}

.dropdown-content a:hover {      /* Change color of dropdown links on hover  */
    background-color: #f1f1f1;
    color: blue;
}
 
.show {     /* Show the dropdown menu (use JS to add this class to the .dropdown-content container when the user clicks on the dropdown button)  */
    display:block;
}

</style>';


function p2h_get_mime_type($file_path) { // Get file mime type
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        return $mime; } 
    elseif (function_exists('mime_content_type')) { return mime_content_type($file_path); } 
    elseif (!stristr(ini_get('disable_functions'), 'shell_exec')) {
        $file = escapeshellarg($file_path);
        $mime = shell_exec('file -bi '. $file);
        return $mime; } 
    else { return '--'; }
}

function p2h_test_archive($ext) // Test archive file extensions
{ global $view_title, $is_archive;
  if (in_array($ext,['tar', 'zip'])) 
    { $is_archive = true; $view_title= lang('Archive'); }
    else $is_archive = false;
}

function p2h_test_image($ext) // Test image file extensions
{ global $view_title, $is_image;
  if (in_array($ext,['ico', 'gif', 'jpg', 'jpeg', /* 'jpc', 'jp2', 'jpx', 'xbm', 'wbmp', 'psd', */ 'png', 'bmp', 'tif', 'tiff', 'svg'])) 
    { $is_image = true; $view_title= lang('Image'); }
    else $is_image = false;
}

function p2h_test_video($ext) // Test video file extensions
{ global $view_title, $is_video;
  if (in_array($ext,['avi', 'webm', 'wmv', 'mp4', 'm4v', 'ogm', 'ogv', 'mov', 'mkv'])) 
    { $is_video = true; $view_title= lang('Video'); }
    else $is_video = false;
}

function p2h_test_audio($ext) // Test audio file extensions
{ global $view_title, $is_audio;
  if (in_array($ext,['wav', 'mp3', 'ogg', 'm4a'])) 
    { $is_audio = true; $view_title= lang('Audio'); }
    else $is_audio = false;
}

function p2h_test_text($ext) // Test text file extensions
{ global $view_title, $is_text;
  if (in_array($ext,['txt', 'css', 'ini', 'conf', 'log', 'htaccess', 'passwd', 'ftpquota', 'sql', 'js', 'json', 'sh', 'config',
        'php', 'php4', 'php5', 'phps', 'phtml', 'htm', 'html', 'shtml', 'xhtml', 'xml', 'xsl', 'm3u', 'm3u8', 'pls', 'cue',
        'eml', 'msg', 'csv', 'bat', 'twig', 'tpl', 'md', 'gitignore', 'less', 'sass', 'scss', 'c', 'cpp', 'cs', 'py','yml',
        'map', 'lock', 'dtd', 'svg', 'scss', 'asp', 'aspx', 'asx', 'asmx', 'ashx', 'jsx', 'jsp', 'jspx', 'cfm', 'cgi'])) 
    { $is_text = true; $view_title= lang('Text'); }
    else $is_text = false;
}

/*
    $is_zip = false;
    $is_gzip = false;
    $is_text = false;
    $is_onlineViewer = false;
*/

function p2h_enc($text)
{ return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }

function p2h_convert_win($filename) {
    if (p2h_IS_WIN && function_exists('iconv')) 
    { $filename = iconv(p2h_ICONV_INPUT_ENC, 'UTF-8//IGNORE', $filename); }
    return $filename;
}

function archList($arrRow,$part='Body') {
    if ($part=='Head') {
        echo '<table class="table-bordered table-hover table-sm bg-white table-striped" id="zip_table" style="margin:auto;">'.
             '<tr>'.
             '  <th>'. lang('@Index').     '</th>'.
             '  <th>'. lang('@Path').      '</th>'.
             '  <th>'. lang('@Name').      '</th>'.    // ' <th>'. lang('Ext'). '</th>'. //' <th>'. lang('CRC'). '</th>'.
             '  <th>'. lang('@Size').      '</th>'.
             '  <th>'. lang('@Time').      '</th>'.    // ' <th>'. lang('Comp_method'). '</th>'.
             '  <th>'. lang('@Comp_size'). '</th>'.
             '  <th>'. lang('@Comp.').     '</th>'.
             '</tr>';
    } else
    if ($part=='Body') {
        $rate = intval($arrRow['compressed_size'] / ($arrRow['filesize']+ 0.0001) * 100).' %';
        $path = pathinfo($arrRow['name'],PATHINFO_DIRNAME );
        $name = basename($arrRow['name']);
        $size = p2h_nice_filesize($arrRow['filesize']);
        $comp = p2h_nice_filesize($arrRow['compressed_size']);
        $clr = '';
        // if ($path=='.') { $path = '<b>ROOT</b>'; $size = ''; $comp = ''; $rate = ''; $clr= 'style="color: #707070;"';}
        echo '<tr '.$clr.'>'.
             '  <td class="tblcol" style="width: 50px;">'. $arrRow['index'].   '</td>'.
             '  <td class="tblcol" style="text-align: left;">'. $path. '</td>'.
             '  <td class="tblcol" style="text-align: left;">'. $name.  '</td>'.       //' <td class="tblcol">'. pathinfo($arrRow['name'], PATHINFO_EXTENSION). '</td>'.  //' <td class="tblcol">'. $arrRow['crc']. '</td>'.
             '  <td class="fsize" >'. $size. '</td>'.
             '  <td class="tblcol">'. date('Y-m-d H:i', $arrRow['filetime']). '</td>'. //' <td class="tblcol">'. $arrRow['comp_method'].                             '</td>'.
             '  <td class="fsize" >'. $comp. '</td>'.
             '  <td class="tblcol">'. $rate. '</td>'.
             '</tr>';
    } else
    if ($part=='Foot')
        echo '</table><hr><br>';
}

function p2h_Preview($ext) {
    global $view_title, $is_image;
    echo '<div style="background-color:yellow; width:900px; margin:auto;">';                     // Ouput window - Centered div for showing file content:
    echo '<h6 class="card-header"> <i class="far fa-eye"></i> '. $view_title. ': '. $actualFile. // Actual file & size
         ' &nbsp;'. lang('@Size').' : <small>'.$filesize.'</small> </h6>';
    if ($is_archive) {             // ZIP content
        if ($filenames !== false) {
            echo '<code class="maxheight">';
            $fn= '';
            archList($fn,'Head');
            foreach ($filenames as $fn)
                archList($fn,'Body');
            archList($fn,'Foot');
            echo '</code>';
        } else echo '<p>Error while fetching archive info</p>';
    } 
    elseif ($is_image) {          // Image content
        if (in_array($ext, array('gif', 'jpg', 'jpeg', 'png', 'bmp', 'ico', 'svg', 'tif'))) {
            echo '<p><img src="'. p2h_enc($file_url). '" alt="" class="preview-img"></p>';
        }
    } 
    elseif ($is_audio) {          // Audio content
        echo '<p><audio src="'. p2h_enc($file_url). '" controls preload="metadata"></audio></p>';
    } 
    elseif ($is_video) {          // Video content
        echo '<div class="preview-video"><video src="'. p2h_enc($file_url). '" width="640" height="360" controls preload="metadata">'.
                lang('@Sorry, your browser doesn`t support embedded videos').
             '</video></div>';
    }
    elseif ($is_text) {
        if (p2h_USE_HIGHLIGHTJS) {   // highlight
            $hljs_classes = [
                'shtml' => 'xml',
                'htaccess' => 'apache',
                'phtml' => 'php',
                'lock' => 'json',
                'svg' => 'xml',
            ];
            $hljs_class = isset($hljs_classes[$ext]) ? 'lang-'. $hljs_classes[$ext] : 'lang-'. $ext;
            if (empty($ext) || in_array(strtolower($file), p2h_test_text_names()) || preg_match('#\.min\.(css|js)$#i', $file)) {
                $hljs_class = 'nohighlight';
            }
            $content = '<pre class="with-hljs left"><code class="'. $hljs_class. '">'. p2h_enc($content). '</code></pre>';
        } elseif (in_array($ext, ['php', 'php4', 'php5', 'phtml', 'phps'])) {  // php highlight
            $content = highlight_string($content, true);
        } else {
            $content = '<pre>'. p2h_enc($content). '</pre>';
        }
        echo $content;
    } echo '</div>';
}

function p2h_get_directoryInfo($directory) {    // Get directory total size
    global $calc_folder;
    if ($calc_folder== true) { //  Slower output
      $size = 0;  $fileCount= 0;  $dirCount= 0;     $arrNames= [];
      foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $fileinfo)
      if ($fileinfo->isFile()) { 
        $size +=     $fileinfo->getSize(); 
        $arrNames[]= $fileinfo->getPathname();
        $fileCount++; 
      }
      else $dirCount++;
          
      $dirCount= $dirCount / 2 - 1; // counting: '.' and '..' in each directory
      return [$size, $fileCount, $dirCount, $arrNames];
    }
    else return [lang('@Folder')]; //  Quick output
}

function p2h_fileView($view_title, $file, $ext, $file_path, $filesize_raw, $mime_type) {
// if(!$quickView) p2h_fileView();
    $is_zip = false;
    $is_gzip = false;
    $is_text = false;
    $is_onlineViewer = false;
    
    $is_image = false;
    $file_url = '';
    
$result= '
    <div class="left" style="width: 720px; margin: auto; background-color: white;">
        <p class="break-word"><b>'. $view_title. ': '. p2h_enc(p2h_convert_win($file)). '</b></p>
        <p class="break-word">'.
            lang('@Full path').': '. p2h_enc(p2h_convert_win($file_path)). '<br>'.
            lang('@File size').': '. p2h_nice_filesize($filesize_raw); if ($filesize_raw >= 1000) ( $result.= ' / '.sprintf( '%s bytes', $filesize_raw) );
            $result.= '<br> MIME-type: '.$mime_type. '<br>';
            //<?
            $view_title= '';
            $ext= ltrim($ext,'.');
            p2h_test_archive($ext);
            p2h_test_image($ext);
            p2h_test_video($ext);
            p2h_test_audio($ext);
            p2h_test_text($ext);
            if ($view_title=='') $view_title= 'Unknown file type: '.$ext;
            // ZIP info: $is_archive
            if (($is_zip || $is_gzip) && $filenames !== false) {
                $total_files = 0;
                $total_comp = 0;
                $total_uncomp = 0;
                foreach ($filenames as $fn) {
                    if (!$fn['folder']) { $total_files++; }
                    $total_comp += $fn['compressed_size'];
                    $total_uncomp += $fn['filesize'];
                }
                $result.= lang('@Files in archive').': '. $total_files. '<br>';
                $result.= lang('@Total size').': '.       p2h_nice_filesize($total_uncomp). '<br>';
                $result.= lang('@Size in archive').': '.  p2h_nice_filesize($total_comp). '<br>';
                $result.= lang('@Compressed size').': '.  round(($total_comp / $total_uncomp) * 100). ' %<br>';
            }
            // Image info:
            if ($is_image) {
                $image_size = getimagesize($file_path);
                $result.= lang('@Image sizes') .': '.  (isset($image_size[0]) ? $image_size[0] : '0'). ' x '. 
                                                       (isset($image_size[1]) ? $image_size[1] : '0'). '<br>';
            }
            // Video info $is_video
            // Audio info $is_audio
            // Text info:
            if ($is_text) {
                $is_utf8 = p2h_is_utf8($content);
                if (function_exists('iconv')) {
                    if (!$is_utf8) {
                        $content = iconv(p2h_ICONV_INPUT_ENC, 'UTF-8//IGNORE', $content);
                    }
                }
                $result.= 'Charset: '. ($is_utf8 ? 'utf-8' : '8 bit'). '<br>';
            }
       $result.= 
       '</p>
    </div>
    
    <p>
        <b>'; 
        $lnk= '<a '.urlQuery(p2h_PATH,'dl',$file). ' data-title="'. lang('@Get a copy locally').'"> ';
        $result.= $lnk.
        ' <i class="fa fa-download colrDwn fa-sm"></i> '; $result.= lang('@Download'); '</a></b> &nbsp;';
        if (!p2h_READONLY) { // prevent "File not found!" 
            '<b><a href="'; $lnk= p2h_enc($file_url). '" target="_blank" data-title="'; $tit= lang('@View the file content') .'">';
            $result.= $lnk. $tit. ' 
                <i class="fa fa-external-link-square-alt colrShw fa-sm"></i> '; $result.= lang('@Open'). '</a></b> &nbsp;';
        }
/*
        // ZIP actions
        if (!p2h_READONLY && ($is_zip || $is_gzip) && $filenames !== false) {
            $zip_name = pathinfo($file_path, PATHINFO_FILENAME);
            ?>
            <b><a <? $result.= urlQuery(p2h_PATH,'unzip',$file); ?>data-title="<? $result.= lang('@UnZip to actual folder'); ?>">
                        <i class="fas fa-box-open colrMint fa-sm"></i> <? $result.= lang('@UnZip') ?></a></b> &nbsp;
            <b><a <? $result.= urlQuery(p2h_PATH,'unzip',$file,'tofolder','1'); ?> 
                    data-title="<? printf('UnZip to a sub-folder named: %s ',p2h_enc($zip_name)); ?>">
                    <i class="fas fa-box-open colrMint fa-sm"></i> <? $result.= lang('@UnZip to folder') ?></a></b> &nbsp;
            <?
        }
        if ($is_text && !p2h_READONLY) {
            ?>
            <b><a <? $result.= urlQuery(p2h_PATH,'edit',$file); ?> class="edit-file" data-title="<? $result.= lang('@Open the file in Plain Editor'); ?>">
                <i class="fas fa-pen-square colrEdi"></i> <? $result.= lang('@Edit') ?>
                </a></b> &nbsp;
            <b><a <? $result.= urlQuery(p2h_PATH,'edit',$file,'&env=ace'); ?> data-title="<? $result.= lang('@Open the file in Advanced Editor'); ?>"
                  class="edit-file"><i class=" far fa-edit colrEdi"></i> <? $result.= lang('@Advanced Editor') ?>
                </a></b> &nbsp;
        <? } ?>
        <b><a href="?p=<? $result.= urlencode(p2h_PATH) ?>"><i class="fa fa-chevron-circle-left go-back"></i> <? $result.= lang('@Back') ?></a></b>
    </p>
    */
    return $result;
} // p2h_fileView()

function p2h_fileExplore($path,$parentDir) {
    global $calc_folder, $totFolds, $totFiles, $totSize, $filesIncurrent, $locSize, $filesize_raw, $view_title, $currentPath, $arrNames, $quickView, $Context_Row, $ix;
    $objects = is_readable($path) ? scandir($path) : array();
    $folders = array();    $files = array();
    $totFiles= 0;   $totFolds= 0;    $totSize= 0;
    if (is_array($objects)) { // Objects to show in file-table:
        foreach ($objects as $obj) {
            if ($obj == '.' || $obj == '..') { continue; }   // System folder
          //if (substr($obj, 0, 1)   == '.') { continue; }   // Hidden file
            $pathName = $path. '/'. $obj;
            if     (@is_file($pathName))                                   { $files[]   = $obj; } 
            elseif (@is_dir($pathName) && ($obj != '.') && ($obj != '..')) { $folders[] = $obj; }
        }
    }
    $maxsize= 0;    $foldsize= 0;   $filesIncurrent= 0; $ix = 1000;     $arrNames= [];   $Context_Row= ''; //  folder checkbox id
    if ($calc_folder == true) {
        foreach ($files as $fld) {
            $maxsize+= p2h_get_size($path. '/'. $fld); 
            $filesIncurrent+= 1;
        }
        foreach ($folders as $fld) {
            $foldinfo = p2h_get_directoryInfo($path. '/'. $fld);
            $maxsize+=  $foldinfo[0];
        }
    }
    foreach ($folders as $fld) {
        $file_path= $path. '/'. $fld;
        $is_link = is_link($file_path);
        $ext = pathinfo($fld, PATHINFO_EXTENSION);
        $icoc = $is_link ? 'icon-link_folder' : 'far fa-folder colr0';
        if ($calc_folder == true) {
            $foldinfo = p2h_get_directoryInfo($file_path);
            $foldsize  = $foldinfo[0];
            $filecount = $foldinfo[1];
            $dir_count = $foldinfo[2];
            $arrNames  = array_merge($arrNames, $foldinfo[3]);
            $totFiles += $filecount;
            $totFolds += $dir_count;
            $totSize  += $foldsize;
            $showsize  = p2h_nice_filesize($foldsize);
            $ext= '<pre style="margin: 3px 0 0;">'.
                '<i class="far fa-folder colr0 bgclgold"></i> '.str_pad($dir_count,4,' ').' '.
                '<i class="far fa-file"></i> '.  str_pad($filecount,4,' ').'</pre>';
            $foldhint = ' data-title="'.lang('@Folder info').': '.$dir_count.' '.lang('@folders').', '.$filecount.' '.lang('@files').'. " title="" ';
            //if (is_file($file_path)) 
            //    $fileRef[]= $file_path;
        } else {
            $ext= '<pre style="margin: 3px 0 0;"><i class="far fa-folder colr0 bgclgold"></i> '.lang('@FOLDER').'</pre>';
            $foldhint = ' data-title="'.lang('@Calculation of files in folder, is disabled').'" title="" ';
            $showsize = '<div style="width:100%;text-align:center;" title="'.lang('@Not calculated ! - Calculation of files in folder, is disabled').'">-</div>';
        }
        // $sortsize = '<span style="display:none;">'.str_pad($foldsize, 18, "0", STR_PAD_LEFT).' </span>';
        $sortsize = '<span class="sortPrefix">'. $foldsize.  ' </span>';
        $ftime = filemtime($file_path); //  Sort criteria
        $sorttime = '<span class="sortPrefix">'.$ftime.'</span>';
        $modif = date('Y-m-d H:i', $ftime);
        $perms = substr(decoct(fileperms($file_path)), -4);
        if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
                // $owner = posix_getpwuid(fileowner($file_path));
                $owner = posix_getpwuid(posix_getpwuid(fileowner($file_path)))['name'];
                $group = posix_getgrgid(filegroup($file_path));
                $posix = lang('@Owner : Group');
        } else {
                $owner = array('name' => fileowner($file_path));
                $group = array('name' => filegroup($file_path));
                $posix = lang('@PHP posix functions is not accessible! Numeric id is shown in sted of name');
        }
        if ($calc_folder) {
            $meter= $foldsize / $maxsize;
            $buttns= '<div style= "display: inline; text-align:right; float: right;">'.
        //     str_WithHint($labl='<button onclick="window.open(\'#\',\'popup\', width=400, height=400, scrollbars=\'no\', resizable=\'no\'); return false;" '.
        //                  'style="border-color: darkgrey; padding-inline-start: 5px; padding-inline-end: 5px; "><i class="fas fa-search"> </i></button>', $hint='@Search for filename in all subfolders').
        //     str_WithHint($labl='<button id="btnModalSearch'.$ix.'" '.
        //                  'style="border-color: darkgrey; padding-inline-start: 5px; padding-inline-end: 5px; "><i class="fas fa-search"> </i></button>', $hint='@Search for filename in all subfolders').
        // if (isset($_POST["zipClone"])) { p2h_folder_backup($currDir=$path.'/'.$fld, $subdirs= 3); }
        str_WithHint($labl='<form name="doSync'.$ix.'" style="display:inline;"><button name="Syncronize'.$ix.'" '. // p2h_folder_backup($currDir=$path.'/'.$fld, $subdirs= 3)
                          'style= "border-color: darkgrey; padding-inline-start: 5px; padding-inline-end: 5px;">
                          <i class="fas fa-sync colrg"></i></button></form>', 
                          $hint='@Syncronize this folder with another selected...').
        str_WithHint($labl='<form name="doZip'.$ix.'" style="display:inline;"><button name="zipClone'.$ix.'" '. // p2h_folder_backup($currDir=$path.'/'.$fld, $subdirs= 3)
                          'style= "border-color: darkgrey; padding-inline-start: 5px; padding-inline-end: 5px;">
                          <i class="far fa-file-archive colr9"></i></button></form>', 
                          $hint='@Create a ZipClone/backup of folder:'. '<b>'.$path.'/'.$fld.'</b><br>Files with prefix: _CLONE. will not be included! <br>(it is created with ZipClone-backup) ').
             '</div>';
            // if (isset($_GET['zipClone'.$ix])) { echo p2h_folder_backup($currDir=$path.'/'.$fld, $subdirs= 3); unset($_GET['zipClone'.$ix]); }
    } else { $buttns= ''; $meter= 0; }
  //      onclick="window.open('http://kanishkkunal.in','popup','width=600,height=600,scrollbars=no,resizable=no'); return false;">
  
        $rec['ix']= $ix;
        $rec['name']= '<div style="white-space: nowrap; margin: 3px 0 0 0;">'. '<div style="display: inline;"><i class="far fa-folder colr0" style="background-color: gold;"></i>'.
             str_WithHint($labl='<a href="'.basename(__FILE__). '?show='.$path.'/'.$fld. '" class="txtbutt">&nbsp;'. $fld.'</a>', 
             $hint=lang('@Show content in subfolder: <b>').$fld.'</b>').'</div>'. $buttns. '</div>';
        $rec['ext']= $ext;
        $rec['size']= $sortsize.'<div title="'.number_format((float)$foldsize, 0,',',' ').' Bytes">'.$showsize.'</div>';
        $rec['modifyed']= $sorttime.$modif;
        $rec['perms']= $perms;
        $rec['owner']= $owner['name'];      //$rec['group']= $group['name'];
        $rec['access']= '-';
        $rec['space']= '<meter id="currFold'.$ix.'" low="0.15" optimum="0.30" high="0.60" max="1" value="'.$meter.'" style= "width: 92%;">'.$meter.'</meter>
                        <small style="width: 40px; text-align: right;">'.number_format($meter*100,2).' %</small>';
        $foldRecords[]= $rec;
        $Context_Row.=
        "<script>".
        Pmnu_Prepare($id='tabl_row'.($ix-1000),$widt='260px').
            Pmnu_Item($type='custo',$lbl='@Regarding folder: <br><b>'.$fld.'</b>', $tip='', $icon='far fa-folder colr0 bgclgold',   $id='cust',$click='',
                      $attr="'background-color: white; height: 44px; border-style: solid; border-width: 5px 1px 5px 1px; border-color: lightgray; border-radius: 8px; padding-top: 10px;'' ").
            Pmnu_Item($type='plain',$lbl='@Rename folder',  $tip='@Give another name',    $icon='fas fa-pen-square colgray',  $id='renm',$click='cssTogg(\$infVisi,vis_ful)').
            Pmnu_Item($type='plain',$lbl='@Delete folder',  $tip='@Erase the folder',     $icon='fas fa-trash-alt colgray',  $id='dele',$click='cssTogg(\$infVisi,vis_ful)').
                      
            Pmnu_Sepe().
            Pmnu_Item($type='plain',$lbl='@Copy folder',    $tip='@Make a copy with a new name', $icon='fas fa-copy colgray', $id='dnld',$click='cssTogg(\$infVisi,vis_ful)').
            Pmnu_Item($type='plain',$lbl='@Create ZIP',     $tip='@Make a compressed file with the content of this subfolder',  $icon='far fa-file-archive colgray',  $id='crea',$click='cssTogg(\$infVisi,vis_ful)').
            Pmnu_Item($type='plain',$lbl='@Syncronize',     $tip='@Create a ZipClone/backup of this folder with another selected...',  $icon='fas fa-sync colgray',  $id='crea',$click='cssTogg(\$infVisi,vis_ful)').
            Pmnu_Item($type='plain',$lbl='@Go to',          $tip='@Look at this subfolder',      $icon='fas fa-chevron-right colgray',      $id='upld',$click='cssTogg(\$infVisi,vis_ful)').
            Pmnu_Item($type='plain',$lbl='@Folder property',$tip='@Look at the folder property', $icon='fas fa-info colgray',   $id='prop',$click='cssTogg(\$infVisi,vis_ful)').
            Pmnu_Sepe().
            Pmnu_Item($type='plain',$lbl='@Create folder',  $tip='@Make a new folder', $icon='fas fa-plus colgray', $id='dnld',$click='cssTogg(\$infVisi,vis_ful)').
            Pmnu_Item($type='custo',$lbl='@Inactive DEMO',  $tip='@Click outside menu to close', $icon='fas fa-info fa-sm',   $id='cust',$click='',
                      $attr="'background-color: lightcyan; height: 24px; border-style: solid; border-width: 5px 1px 5px 1px; border-color: lightgray; border-radius: 8px; padding-bottom: 0; padding-top: 10px;' ",$sep=' ').
        Pmnu_Finish().
        "</script>";
        flush();
        $ix++;
    }
    $filesInsub= $ix;       $locSize= 0;    $all_files_size= 0; $meter= 0;
    foreach ($files as $fld) {
        $file_path= $path. '/'. $fld;
        if (is_file($file_path)) 
            $fileRef[]= $file_path;
        $is_link = is_link($file_path);
        $ext = pathinfo($fld, PATHINFO_EXTENSION);
        $icoc = $is_link ? 'far fa-file-alt colr4' : p2h_get_file_icon_class($file_path);
        $ftime = filemtime($file_path); //  Sort criteria
        $modif = date('Y-m-d H:i', $ftime);
        $sorttime = '<span class="sortPrefix">'.$ftime.'</span>';
        $atime = fileatime($file_path); //  Sort criteria
        $acces = date('Y-m-d H:i', $atime);
        $accetime = '<span class="sortPrefix">'.$atime.'</span>';
        $filesize_raw = p2h_get_size($file_path);
        // $sortsize = '<span style="display:none;">'.str_pad($filesize_raw, 18, "0", STR_PAD_LEFT).'</span>';
        $sortsize = '<span class="sortPrefix">'. $filesize_raw.  ' </span>';
        $showsize = p2h_nice_filesize($filesize_raw);
        $all_files_size += $filesize_raw;
        $totSize+=  $filesize_raw;
        $locSize+=  $filesize_raw;
        $perms = substr(decoct(fileperms($file_path)), -4);
        if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
            $owner = posix_getpwuid(fileowner($file_path));
            $group = posix_getgrgid(filegroup($file_path));
            $posix = lang('@Owner : Group');
        } else {
            $owner = array('name' => fileowner($file_path));
            $group = array('name' => filegroup($file_path));
            $posix = lang('@PHP posix functions is not accessible! Numeric id is shown in sted of name.');
        }
        if ($calc_folder) {
            $meter= $filesize_raw / $maxsize;
            $mime_type = p2h_get_mime_type($file_path);
            if(!$quickView) $fileInfo= p2h_fileView($view_title, $fld, $ext, $file_path, $filesize_raw, $mime_type);
            $nameFld= '<div class="file-name-div" title="File content: '.$view_title./* ' '.$fileInfo. */'">'.'<i class="'.$icoc.'"></i>&nbsp;'. $fld.'</div>';
            if ($ix < 1500) // Max 500 previews on one page
            $nameFld.= '<div class="live-preview-img file-name-div"> <img src="'. p2h_enc($currentPath. $fld). '" alt="">'.$fileInfo.'</div>';
        } else
            $nameFld= '<i class="'.$icoc.'"></i>&nbsp;'. $fld;
            // $meter= 0;
        if ($ext>'') $ext = '.'.$ext;
        $rec['ix']= $ix;
        $rec['name']= $nameFld;
        $rec['ext']= $ext;
        $rec['size']= $sortsize.'<div title="'.number_format((float)$filesize_raw, 0,',',' ').' Bytes">'.$showsize.'</div>';
        $rec['modifyed']= $sorttime.$modif;
        $rec['perms']= $perms;
        $rec['owner']= $owner['name'];      //$rec['group']= $group['name'];
        $rec['access']= $accetime.$acces;
        $rec['space']= '<meter id="currFile'.$ix.'" low="0.15" optimum="0.30" high="0.60" max="1" value="'.$meter.'" style= "width: 92%;">'.$meter.'</meter>
                        <small style="width: 40px; text-align: right;">'.number_format($meter*100,2).' %</small>';
        $fileRecords[]= $rec;

    $Context_Row.=
        "<script>".
        Pmnu_Prepare($id='tabl_row'.($ix-1000),$widt='260px').
            Pmnu_Item($type='custo',$lbl='@Regarding file: <br><b>'.$fld.'</b>', $tip='', $icon= $icoc.' fa-sm',   $id='cust',$click='',
                      $attr="'background-color: white; height: 44px; border-style: solid; border-width: 5px 1px 5px 1px; border-color: lightgray; border-radius: 10px; padding-top: 10px;'' ").
            Pmnu_Item($type='plain',$lbl='@Preview',        $tip='@Look at content in viewer', $icon='far fa-eye colgray', $id='view',$click='cssTogg(\$infVisi,vis_ful)').
            Pmnu_Item($type='plain',$lbl='@Edit content',   $tip='@Open file in editor',    $icon='far fa-edit colgray',  $id='edit',$click='cssTogg(\$infVisi,vis_ful)').
            Pmnu_Item($type='plain',$lbl='@Rename file',    $tip='@Give another name',      $icon='fas fa-pen-square colgray',  $id='renm',$click='cssTogg(\$infVisi,vis_ful)').
            Pmnu_Item($type='plain',$lbl='@Delete file',    $tip='@Erase the file',         $icon='fas fa-trash-alt colgray',  $id='dele',$click='cssTogg(\$infVisi,vis_ful)').
                                  
            Pmnu_Sepe().
            Pmnu_Item($type='plain',$lbl='@Copy file',      $tip='@Make a copy with a new name', $icon='fas fa-copy colgray', $id='dnld',$click='cssTogg(\$infVisi,vis_ful)').
            Pmnu_Item($type='plain',$lbl='@Download',       $tip='@Download to local storage',  $icon='fas fa-download colgray', $id='dnld',$click='cssTogg(\$infVisi,vis_ful)').
            Pmnu_Item($type='plain',$lbl='@File property',  $tip='@Look at the files property', $icon='fas fa-info colgray',   $id='prop',$click='cssTogg(\$infVisi,vis_ful)').
            Pmnu_Item($type='custo',$lbl='@Inactive DEMO',  $tip='@Click outside menu to close', $icon='fas fa-info fa-sm',   $id='info',$click='',
                      $attr="'background-color: lightcyan; height: 24px; border-style: solid; border-width: 5px 1px 5px 1px; border-color: lightgray; border-radius: 10px; padding-top: 10px;' ",$sep=' ').
        Pmnu_Finish().
        "</script>";
        $ix++; 
        $fileRecordsIncurrent= $ix-$filesInsub;
    }
    return array($foldRecords ?? null,$fileRecords ?? null);
} // p2h_fileExplore()


function p2h_folder_backup($currDir, $subdirs= 3) { ## Serverside-zip-backup:
  $result= lang('@ZipClone-backup:<br>');
  //$currDir= dirname(__FILE__).'/';
  $destDir = '';
  $destFile = '_CLONE.'.basename($currDir).'.zip';
  $timestamp= date("Y-m-d H:i");
  $server = $_SERVER['SERVER_NAME'];
  $lf= chr(10).chr(13);
  
  $result.= 'Server: '.$server.'<br>';
  $result.= lang('@You\'re in folder: ').$currDir.'<br>';
  $result.= lang('@So We Start Compression of files... <br>');  // $result.= 'This should be shown, befor starting zipning!', bet is shown at last!
  
  if (file_exists($destDir.$destFile))              // Will be deleted so old version will not be included in zip
    {unlink($destDir.$destFile);}
  $ix= '00';  $thisdir= './';                       // Aktual folder:'./'   One niveau up:'./../'
  $files= p2h_getFileList($thisdir, true, $subdirs);    // File-list has to be created, before the zip-file is created, to prevent tempory zip-fraktions.
  $zip = new ZipArchive();                          // PHP ZIP-extension must be active in PHP-system!
  if ($zip->open($destDir.$destFile, ZipArchive::CREATE)!==TRUE) { exit(lang('@Unable to create <').$destFile.'>'); }

  $zip->addFromString('_Readme.txt',    ## Info-file in Zip-file:
    lang('@This ZIP file contains a moment\'s image per: ').$timestamp.
    lang('@ of files in the folder: ').$lf.$currDir.
    lang('@, as well as subfolders, restricted to ').$subdirs.
    lang('@ levels.').$lf.
    lang('@On the server: ').$server.$lf.$lf.
    lang('@Note that the routine deletes an optionally older zip file named ').$destFile.$lf.
    lang('@Do you want to keep old versions, please rename ').$destFile.
    lang('@ before starting the routine!').$lf
  );
  
   foreach ($files as $fil) {
    $filref= $fil['path'].$fil['name']; ## Add data-files to Zip-file:
      if (substr($fil['path'],strlen($thisdir),1)!='._')    //  Step over folder with this prefix
      // or (substr($filref,0,6) == '_CLONE.')              // ZIP-File with prefix: '_CLONE.'
      if (is_file( $filref ))
        if ($zip->addFile($filref,$filref))  {   } 
        else {$result.= lang('@<br> FAILED: ').$filref;}
  } $result.= '<br>';
  
## Result:  // $result.= skulle vises, inden der gås igang med zipning!
  $result.= lang('@<br>Finished - '). $zip->numFiles.lang('@ files in the zip file: ').$destFile.'<br><br>'; 
  $zip->close();
  
## Download-button:
  $result.= '<div style= "margin:1px 5px; padding:2px 6px; border:2px; box-shadow: 2px 2px 4px #888888;'.
       ' width:100px; " title= "Download Zip-file"> '.
       '<a href="'.$destFile.'">DOWNLOAD</a></div><br>'; 
  $result.= lang('@See useful information in _Readme.txt in the zip file <br>');
  return $result;
} ##  </p2h_folder_backup>

function p2h_getFileList($dir, $recurse=false, $depth=false)    // Create list of files recursive, and save to array
{ $return = array();
  if(substr($dir, -1) != "/") $dir .= "/";                  // Add slash, if missing
  $dirPtr = @dir($dir)                                      // Create pointer to the folder
    or die(lang('@p2h_getFileList: Opening Folder ').$dir.lang('@  for Reading... '));
  while (false !== ($entry = $dirPtr->read())) {            // and read the list of files
    if (($entry == ".") or ($entry == "..")                 // Go on if system folders
      or (substr($entry,0,6) == '_CLONE.')                  // or unzipped folder
    ) continue;
    $de= $dir.$entry;
    if (is_dir($de)) {
      $return[] = array( "path" => $dir, "name" => $entry.'/',   "type" => filetype($de),   "size" => 0,    "lastmod" => filemtime($de) );
      $mappe= $de.'/';
      if ($recurse && is_readable($mappe)) {
        if($depth === false) { $return = array_merge($return, p2h_getFileList($mappe, true)); } 
        elseif($depth > 0)   { $return = array_merge($return, p2h_getFileList($mappe, true, $depth-1)); }
      }
    } elseif (is_readable($de)) {
      $return[] = array( "path" => $dir, "name" => $entry, "type" => mime_content_type($de), "size" => filesize($de), "lastmod" => filemtime($de) );
    } else ; // echo lang('@<br>Skip: ').$de.lang('@ Unreadable!<br>');
  } $dirPtr->close();
  return $return;
}


function SearchSub(&$arrNames) { global $calc_folder;
$result= '
<div class="dropdown" title="'.($calc_folder== true ? lang('@Enter search string...') : lang('@Only active in advanced mode!') ). '">'.
    htm_Input($type='html',$name='fsearch',
              $valu='<div><i class="fa fa-search"></i> '.($calc_folder== true ? count($arrNames).' '.lang('@Files in subfolders') : lang('@Not active.')).'</div>',
              $labl='Search',$hint='@Search for a file in sub-folders, and open that folder',
              $plho='@Enter...',$wdth='200px',$algn='left',$unit='',$disa=false,
              $rows='0',$step='',$more='onclick="ToggShow()" class="dropbtn" ',$list=[],$llgn='R',$bord='',$proc=false).
    '<div id="FileSearch" class="dropdown-content" 
        title="'. lang('@Click to open this folder') .'">
        <input type="text" placeholder="Search.." id="searchInput" onkeyup="filterFunction()" 
            title="'. lang('@Type a search string: Part of the file- / folder-name').'"
            style="width:200px; text-align:left; border: 1px solid black;">';
        $name_list= ''; // List to search for file names in sub-folders 
        foreach($arrNames as $file) {
            $pos= strrpos($file,'/')+1;
            $dir= substr($file,0,$pos);
            $fil= substr($file,$pos);
            $name_list .= '<a href="'. basename(__FILE__). '?show='.$dir.'" >'.
                        '<span class="colgray">'.(strlen($dir) > 32 ? '...'.substr($dir,-32) : $dir).':</span> '. $fil.'</a>';
            }
            $result.= '<div style= "text-align:left;">'.$name_list.'</div>';
    $result.= "
    </div>
</div>";
return $result;
}

// arrPrint($_POST,'$_POST');

##### PREPARE DATA:
// if (!isset($calc_folder)) $calc_folder= true;
$calc_folder= ($_POST['prgMode'] ?? '' == 'mode_A');
$currPath= __DIR__; // Initial path if 'show' is not set

if (isset($_GET['show']))     { $currPath= '//'.ltrim($_GET['show'],'/'); }
if (isset($_POST['csvFile'])) { $csvFile= $_POST['csvFile']; }
for ($ix=0;$ix<50;$ix++)
    if (isset($_GET['zipClone'.$ix])) { echo p2h_folder_backup($currDir=$path.'/'.$fld, $subdirs= 3); unset($_GET['zipClone'.$ix]); }

$root_dir= end(explode('/',$_SERVER['DOCUMENT_ROOT'])); // web
$parentDir= array_reverse(explode('/',$currPath))[2] ?? '';
$currentDir= end(explode('/',$currPath));
$currentPath= substr($currPath,strlen($root_dir)+strpos($currPath,$root_dir)).'/';

// Build Breadcrumbs:
$publ= false;   $lnkPath= '//';     $breadcrumbs= '';
foreach($JumpTo= explode('/',$path ?? ''.$currPath) as $jt) // if ($jt[0]>'') 
{ 
    $lnkPath.= $jt.'/';
    if ($jt==$currentDir) $breadcrumbs.= '<b>'.$jt.'</b>';
    else    if ($publ)  {
        $url= basename(__FILE__) .'?show='.rtrim($lnkPath,'/');
        if ($jt==$currentDir) $GLOBALS['goUp']= ''; else $GLOBALS['goUp']= $url;
        $breadcrumbs.= '<a href="'.$url.'" class="txtbutt">'.$jt.'</a>'.'&nbsp; / ';
    }
    if ($jt==$root_dir) $publ= true;
}

$data= p2h_fileExplore($currPath,$parentDir);
$arrfolds= $data[0];
$arrfiles= $data[1];
// $arrfolds= $GLOBALS['folders']; $arrfiles= $GLOBALS['files'];

$tabldata= [];
if (is_array($arrfolds) and (count($arrfolds)>0)) $tabldata= $arrfolds;
if (is_array($arrfiles) and (count($arrfiles)>0)) $tabldata= array_merge($tabldata,$arrfiles);


/* 
function doAuth() {
	global $do, $pathURL, $footer;
	$pwd = isset($_SESSION['pwd']) ? $_SESSION['pwd'] : '';
	if ($do == 'login' || $do == 'logout')
		return; //TODO: login/logout take place here
	if ($pwd != crypt(PASSWORD, PASSWORD_SALT))
		if ($do)
			exit('Please refresh the page and login');
		else
			exit('<!DOCTYPE html>');
}
*/
 
##### SCREEN OUTPUT:  -  generated by PHP2HTML functions !
#!!!: Remember no OUTPUT to screen, before htm_PagePrep() output

htm_PagePrep($pageTitl='Folder-explorer', $ØPageImage='./_assets/images/_background.png',$align='center',$PgInfo=lang(''),$PgHint=lang('@Tip: Toggle fullscreen-mode with function key: F11'),$headScript=$p2h_Style);
    // print_r($tabldata);
    htm_Caption($labl='Folder-explorer',$style='color:'.$ØTitleColr.'; font-weight:600; font-size: 18px; padding: 4px;',$align='center',
        $hint='The advanced file-explorer with focus on folders and space taken up of files.');
    htm_nl(1);
    htm_Caption($labl='Build with PHP2HTML',$style='color: gray;; font-weight:400; font-size: 12px;',$align='center',
        $hint='The shortcut to structured and compact code<br>Created by EV-soft.');
    htm_nl(1);
    // exit('<!DOCTYPE html>');
?>
<!--
    <div id="divModal" class="modal">    <!-- The Modal div/window -- >
        <div class="modal-content">     <!-- Modal content -- >
          <div class="modal-header">
            <span class="close">&times;</span>
            <h3>Search in subfolders</h3>
          </div>
          <div class="modal-body">
                <br>
                < ? echo SearchSub($arrNames); ?>
                <br><br><br>
          </div>
          <div class="modal-footer">
            <h4>Search for filename</h4>
          </div>
        </div>
    </div>
    -->
<?

    echo '<small>';
    htm_Table(
        $TblCapt= array( //#['0:Label', '1:Width', '2:Type', '3:OutFormat', '4:horJust', '5:Tip', '6:placeholder', '7:Content';], ...
          ['<big>Showing: ', '90%','html','','left','@Tip','?', $_SERVER['SERVER_NAME'].' // '.$breadcrumbs.'</big><br>'],
          ['<span style="white-space: nowrap; display: inline-block;">'.
          '<span title="'.lang('@Space meaning file-space in local folder / total in subfolders').'">'.
           ($calc_folder == true ? 
          'Content: Folders: '.$totFolds.' - Files: '.markAllChars($filesIncurrent.'/','i','style="opacity:0.5;"').$totFiles. ' - ' : '' ).
           'Space: '.markAllChars(p2h_nice_filesize($locSize).'/','i','style="opacity:0.5;"').p2h_nice_filesize($totSize).
           ' - Free: '.   p2h_nice_filesize(disk_free_space( __FILE__ )).
           ' of total: '. p2h_nice_filesize(disk_total_space( __FILE__ )). 
           '</span>'.
           ' '.           SearchSub($arrNames).
           ' '. //'<span style="display: inline-block;">'.
                '<form method="post" name= "frmMode" style="display:inline;">'.
                str_sp(2).
                htm_Input($type='opti',$name='prgMode',$valu= ($calc_folder == true ? 'mode_A' : 'mode_S'), $labl='@Select Mode',
                          $hint='@The program mode.<br>Speedup by deactivating calculating content in subfolders using "Simple mode"',
                          $plho='@Select...',$width='',$algn='left',$unit='',$disa=false,
                          $rows='1',$step='',$more='',$list= [
                    ['mode_S','Simple','@Do not spend time on calulating subfolders'],
                    ['mode_A','Advanced','@Advanced mode exploring all subfolders']
                    ],'','', $proc= false,$form='').
                    '<input type="submit" value="'.lang('@Set Mode').'" class="txtbutt">'.
                    // '<input type="button" value="'.lang('@Manage language').'">'.
                    //' <a href="translate.page.php">'.lang('@Manage language').'</a> '.
                //    htm_Input($type='html',$name='lang',$valu= '<a href="translate.page.php">'.lang('@Manage language').'</a>', $labl='@Language',
                //          $hint='@Go to the translate language page',
                //          $plho='@Select...',$width='',$algn='left',$unit='',$disa=false,
                //          $rows='1',$step='',$more='',$list= [],'','', $proc= false).
                    '</form>'.
                str_sp(2).
                htm_Input($type='text',$name='csvFile',$valu= $csvFile ?? '', $labl='@Export to',
                          $hint='@Output path/file name. If none given export is deactivated',
                          $plho='@File...',$width='120px',$algn='left',$unit='',$disa=false,
                          $rows='1',$step='',$more='',$list= [],'','', $proc= false, $form='csv').
                str_sp(5).
                htm_LinkButt($labl='@Language', $gotoLink='translate.page.php', $hint='@Open the translate language page', $target='_blank', $proc=false).
           '</span>',  '90%','html','','left'] 
        ),
        $RowPref= array(),
        $RowBody= array(
            ['@Ix',        '02%', 'show', '', ['center'],                   '@System index','..auto..'], 
            ['@Name',      '10%', 'html', '', ['left'  ],                   '@The name of file or directory'], // Don`t change this hint. It will remove the goUp-button in header!
            ['@Count/Ext', '05%', 'html', '', ['center','','',$sort=false], '@Folder content or the extension of the filename'],
            ['@Size',      '05%', 'html', '', ['right' ],                   '@The used space'],
            ['@Modifyed',  '08%', 'html', '', ['center'],                   '@Last modifyed date/time'],
            ['@Perms',     '02%', 'text', '', ['center'],                   '@The file permissions (UNIX.mode)'],
            ['@Owner',     '02%', 'text', '', ['center'],                   '@ID for the file creater (UNIX.owner)'],
            ['@Accessed',  '08%', 'html', '', ['center'],                   '@File accessed date/time'],
            ['@Space',     '35%', ($calc_folder == true ? 'html' : 'hidd') , 
                                          '', ['left'  ,'','',$sort=false], '@Used space related to<br>Sum of all files in current folder and subfolders.']
        ),
        $RowSuff= array(), // tabl_row.ix
        $TblNote= '',
        $tabldata, 
        $fldNames=['ix','name','ext','size','modifyed','perms','owner','access','space'],
        $FilterOn= true,
        $SorterOn= true,        # FIXIT: Sorting fails if there are both folders and files! (js-error coursed by empty td-fields)- Folders only is OK (BAD data before the first file ?)
        $CreateRec=false,
        $ModifyRec=false,
        $ViewHeight= '650px',
        $TblStyle= 'width:98%; cursor: alias; " title= "'.lang('@RightClick for program MENU'),
        $CalledFrom= __FILE__ ,
        $MultiList= ['',''],
        $ExportTo= $csvFile ?? ''
    );
    echo $Context_Row;
    echo '</small>';
    
    //echo '<div id="with_selected">xxx<div>';
    echo // Context-Menu:
        "<script>".
        Pmnu_Prepare($id='tblSpan',$widt='260px').
            Pmnu_Item($type='custo',$lbl='@Program menu:',    $tip='@Program functions. Go to: Admin / Settings, if you want to save permanently', $icon='fas fa-info fa-sm',   $id='cust',$click='',
                      $attr="'background-color: white; height: 44px; border-style: solid; border-width: 5px 1px 5px 1px; border-color: lightgray; border-radius: 10px; padding-top: 10px;'' ").
/*                       
            Pmnu_Item($type='plain',$lbl='@Upload',           $tip='@Upload local file',               $icon='fas fa-upload colgray',      $id='upld',$click='cssTogg(\$infVisi,vis_ful)').
            Pmnu_Item($type='plain',$lbl='@Create New Item',  $tip='@Create new file or folder',       $icon='fa fa-plus-square colgray',  $id='crea',$click='cssTogg(\$infVisi,vis_ful)').
 */
            Pmnu_Item($type='plain',$lbl='@Program settings', $tip='@Change program settings',         $icon='fa fa-cog colgray',          $id='sett',$click='cssTogg(\$infVisi,vis_ful)').
            Pmnu_Item($type='plain',$lbl='@User settings',    $tip='@Change user settings',            $icon='fa fa-user colgray',          $id='user',$click='cssTogg(\$infVisi,vis_ful)').
            Pmnu_Item($type='plain',$lbl='@Help',             $tip='@Go to program help',              $icon='fa fa-question',             $id='help',$click='cssTogg(\$infVisi,vis_ful)').
                      
            Pmnu_Sepe().
            Pmnu_Item($type='plain',$lbl='@Logout',           $tip='@Leave the program in locked mode',$icon='fas fa-sign-out-alt',        $id='logu',$click='cssTogg(\$infVisi,vis_ful)').
            Pmnu_Item($type='custo',$lbl='@Inactive DEMO',    $tip='@Click outside menu to close',     $icon='fas fa-info fa-sm',          $id='cust',$click='',
                      $attr="'background-color: lightcyan; height: 24px; border-style: solid; border-width: 5px 1px 5px 1px; border-color: lightgray; border-radius: 10px; padding-top: 10px;' ",$sep=' ').
        Pmnu_Finish().
        "
            
        // Modal window:
            var modal = document.getElementById('divModal');            // Get the modal div
            var btn = document.getElementById('btnModalSearch1002');    // Get the button that opens the modal
            var span = document.getElementsByClassName('close')[0];     // Get the <span> element that closes the modal
            // btn.onclick = function() { modal.style.display = 'block'; } // When the user clicks on the button, open the modal
        //s    span.onclick = function() { modal.style.display = 'none'; } // When the user clicks on <span> (x), close the modal
            window.onclick = function(event) {                          // When the user clicks anywhere outside of the modal, close it
              if (event.target == modal) { modal.style.display = 'none'; }
            }            


            
        function ToggShow() { /* When the user clicks on the button, toggle between hiding and showing the dropdown content */
          document.getElementById(\"FileSearch\").classList.toggle(\"show\");
        }

        function filterFunction() {
          var input, filter, ul, li, a, i;
          input = document.getElementById(\"searchInput\");
          filter = input.value.toUpperCase();
          div = document.getElementById(\"FileSearch\");
          a = div.getElementsByTagName(\"a\");
          for (i = 0; i < a.length; i++) {
            txtValue = a[i].textContent || a[i].innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
              a[i].style.display = \"\";
            } else {
              a[i].style.display = \"none\";
            }
          }
        } 


        </script>";
            
htm_PageFina();


?>