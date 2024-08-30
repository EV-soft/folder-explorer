<?php   $DocFil= 'folder-explorer.php';    $DocVer='1.4.0';    $DocRev='2024-08-30';     $DocIni='evs';  $ModulNr=0; ## File informative only
$©= '𝘓𝘐𝘊𝘌𝘕𝘚𝘌 & 𝘊𝘰𝘱𝘺𝘳𝘪𝘨𝘩𝘵 © 2019-24 EV-soft *** See the file: LICENSE';

$sys= $GLOBALS["gbl_ProgRoot"]= './';

## Activate needed libraries: Set 0:deactive  1:Local-source  2:WEB-source-CDN
$needJquery=      '2';
$needTablesorter= '2';
$needPolyfill=    '0';
$needFontawesome= '2';
$needTinymce=     '0';

// require_once ($sys.'php2html.lib.php');    // Creating HTML-functions
require_once ('../p2h/v1.4.x/php2html.lib.php');    // Creating HTML-functions

##### SPECIAL this page only:
define ('p2h_IS_WIN', DIRECTORY_SEPARATOR == '\\');
define ('p2h_PATH', ''); // $p);             // $p = pathinfo($p)['dirname'];

$is_https = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)
    || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https';
defined('p2h_SELF_URL') || define('p2h_SELF_URL', ($is_https ? 'https' : 'http'). '://' . $_SERVER['HTTP_HOST']. $_SERVER['PHP_SELF']);

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
define ('p2h_USER_PATH', $p2h_AppConf['myPlace']?? '');
define ('MAX_UPLOAD_SIZE', $p2h_AppConf['max_upload_size_bytes']?? '');

if ( !defined( 'p2h_SESSION_ID')) { define('p2h_SESSION_ID', 'foldermanager'); }
// session_name(p2h_SESSION_ID ); 
    
$auth_users = array(    # hash                                              # userPath   # userRights
    'fe-admin'  => ['$2y$10$g9W04bYhFOG45HAvnZCK6.FytNiDdzW4OM6Si0L4SaJAm/LyZa.zG','root','rwx'],  // xxxxxxxx
    'fe-user'   => ['$2y$10$vJjLTMo9Zb2oIhd.RF.W8eXsVWfFXVdze4Ta7WC7xAQZb8LtDLoH6','root','rwx'],  // xxxxxxxx
    'visitor'   => ['$2y$10$kD49SD5o81IAU1sU/k8e3.59T9ZiGfzgG9jWpc./Y04V6B1hHzP/a','root','rwx']   // 8x x
);  # To create a new user: Login with wrong password. Error shows you a korrect user: hash comination. Copy this to a new record in $auth_users

$locked_path= 'ev-soft.work/fe/locked2/demo/';

function hex_dump($string, $line_sep='\n',$bytes_per_line=16,$pad_char='·',$want_array=false) { // https://stackoverflow.com/questions/1057572/how-can-i-get-a-hex-dump-of-a-string-in-php
    if (!is_scalar($string)) throw new InvalidArgumentException('$string argument must be a string');
    $text_lines = str_split($string, $bytes_per_line);
    $hex_lines  = str_split(bin2hex($string), $bytes_per_line * 2);
    $offset = 0;
    $output = [];
    $bytes_per_line_div_2 = (int)($bytes_per_line / 2);
    foreach ($hex_lines as $i => $hex_line) {
        $text_line = $text_lines[$i];
        $output []=
            sprintf('%08X',$offset) . '  ' .
            str_pad(
                strlen($text_line) > $bytes_per_line_div_2
                ?   implode(' ', str_split(substr($hex_line,0,$bytes_per_line),2)) . '&nbsp;&nbsp;' .
                    implode(' ', str_split(substr($hex_line,$bytes_per_line),2))
                :   implode(' ', str_split($hex_line,2))
            , $bytes_per_line * 3) .
            '  |' . preg_replace('/[^\x20-\x7E]/', $pad_char, $text_line) . '|';
        $offset += $bytes_per_line;
    }
    $output []= sprintf('%08X', strlen($string));
    return @$want_array ? $output : join($line_sep, $output) . $line_sep;
} // hex_dump()




// if (!isset($path)) $path= '';
//$localVars=[$path, $breadcrumbs ];
//foreach ($localVars as $var)
//    if (!isset($var)) $var= '';
if (!isset($quickView)) $quickView= true;


/**
 * Build URL query string
 * @param name, value
 * @return string
 */
function urlQuery($path,$n1='',$v1='',$n2='',$v2='',$n3='',$v3='',$n4='',$v4='') {
    $res= 'href= "?show='. urlencode($path). 
                       '&amp;'.$n1. '='. urlencode($v1);
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
    global $is_archive;   $is_image;   $is_audio;   $is_video;   $is_text;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $icoc = '';     $showType = 'File'; // default
    if (in_array($ext,['ico','gif','jpg','jpeg','jpc','jp2','jpx','xbm','wbmp','png','bmp','tif','tiff','ttf','eot','woff','woff2','svg']) )
                                                                    { $icoc = 'far fa-file-image colr1';        $showType = 'Image';    $is_image= true;    }
    elseif (in_array($ext,['passwd','ftpquota','sql','js','sh','config','twig','tpl','md','gitignore','c','cpp','cs','yml','py','map','lock','dtd']) )
                                                                    { $icoc = 'far fa-file-code colr3';         $showType = 'Code';                         }
    elseif (in_array($ext,['txt','ini','conf','log','htaccess']))   { $icoc = 'far fa-file-alt colr4';          $showType = 'Text';     $is_text= true;     }
    elseif (in_array($ext,['css','less','sass','scss']))            { $icoc = 'fab fa-css3-alt colr8';          $showType = 'Class'; }
    elseif (in_array($ext,['zip','rar','gz','tar','7z']))           { $icoc = 'far fa-file-archive colr2';      $showType = 'Archive';  $is_archive= true;  }
    elseif (in_array($ext,['php','php4','php5','phps','phtml']))    { $icoc = 'far fa-file-code colr3';         $showType = 'Code';                         }
    elseif (in_array($ext,['htm','html','shtml','xhtml']))          { $icoc = 'fab fa-html5 colr5';             $showType = 'Html';                         }
    elseif (in_array($ext,['xml','xsl']))                           { $icoc = 'far fa-file-excel colr6';        $showType = 'Spreadsheet';                  }
    elseif (in_array($ext,['wav','mp3','mp2','m4a','aac','ogg','oga','wma','mka','flac','ac3','tds']))  
                                                                    { $icoc = 'far fa-music';                   $showType = 'Audio';    $is_audio= true;    }
    elseif (in_array($ext,['m3u','m3u8','pls','cue']) )             { $icoc = 'far fa-headphones';              $showType = 'Audio';    $is_audio= true;    }
    elseif (in_array($ext,['avi','mpg','mpeg','mp4','m4v','flv','f4v','ogm','ogv','mov','mkv','3gp','asf','wm']))
                                                                    { $icoc = 'far fa-file-video';              $showType = 'Video';    $is_video= true;    }
    elseif (in_array($ext,['eml','msg']) )                          { $icoc = 'far fa-envelope';                $showType = 'Mail';                         }
    elseif (in_array($ext,['xls','xlsx','ods']) )                   { $icoc = 'far fa-file-excel colr6';        $showType = 'Spredsheet';                   }
    elseif (in_array($ext,['db','odb']) )                           { $icoc = 'fas fa-database';                $showType = 'Database';                     }
    elseif (in_array($ext,['json','csv']) )                         { $icoc = 'far fa-file-alt colr4';          $showType = 'Data';                         }
    elseif (in_array($ext,['bak']) )                                { $icoc = 'far fa-clipboard';               $showType = 'Backup';                       }
    elseif (in_array($ext,['doc','docx','odt']) )                   { $icoc = 'far fa-file-word';               $showType = 'Word';                         }
    elseif (in_array($ext,['ppt','pptx','odp']) )                   { $icoc = 'far fa-file-powerpoint colr7';   $showType = 'PPT';                          }
    elseif (in_array($ext,['ttf','ttc','otf','woff','woff2','eot','fon']) ) {$icoc = 'far fa-font';             $showType = 'Font';                         }
    elseif (in_array($ext,['pdf']) )                                { $icoc = 'far fa-file-pdf';                $showType = 'PDF';                          }
    elseif (in_array($ext,['psd','ai','eps','fla','swf']) )         { $icoc = 'far fa-file-image colr1';        $showType = 'Image';                        }
    elseif (in_array($ext,['exe','msi']) )                          { $icoc = 'far fa-file';                    $showType = 'Execute';                      }
    elseif (in_array($ext,['bat']) )                                { $icoc = 'far fa-terminal';                $showType = 'Batch';                        }
    else                                                            { $icoc = 'fas fa-info-circle';             $showType = 'File';                         }
    return [$icoc,$showType];
}

$p2h_Style = '
<style>  /* ICON-colors: colrx used in icon class $icoc: */
    .colr0 { color: #0157b3;    }                           /* fa-folder          */
    .colr1 { color: #26b99a;    }                           /* fa-file-image      */
    .colr2 { color: #E16666;  background-color: gold;  }    /* fa-file-archive    */
    .colr2 { color: #E16666;    }                           /* fa-file-archive    */
    .colr3 { color: #cc4b4c;    }                           /* fa-file-code       */
    .colr4 { color: #0096e6;    }                           /* fa-file-alt        */
    .colr5 { color: #d75e72;    }                           /* fa-html5           */
    .colr6 { color: #09c55d;    }                           /* fa-file-excel      */
    .colr7 { color: #f6712e;    }                           /* fa-file-powerpoint */
    .colr8 { color: #f36fa0;    }                           /* fa-file-css3       */
    .colr9 { color: DodgerBlue; }                           /* fa-link            */
    .colrg { color: #cccccc;    }                           /* Gray               */
    
    :root { --lablBgrnd: #cdff9b; }                         /* Redefine default   */
    
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

.modal-header {     /* Modal Header */
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


.dropbtn {          /*  Dropdown Button  */
  color: #4CAF50;
  background-color: white;
  padding: 6px;
  font-size: 14px;
  border: 1px solid green;
  cursor: pointer;
}
.dropbtn:hover, 
.dropbtn:focus {    /*  Dropdown button on hover & focus  */
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


</style>'; // $p2h_Style

function p2h_set_msg($msg, $status = 'ok') {
    $_SESSION[p2h_SESSION_ID]['message'] = lang($msg);
    $_SESSION[p2h_SESSION_ID]['status'] = $status;
}

function p2h_show_message()
{
    if (isset($_SESSION[p2h_SESSION_ID]['message'])) {
        $class = isset($_SESSION[p2h_SESSION_ID]['status']) ? $_SESSION[p2h_SESSION_ID]['status'] : 'ok';
        echo '<p class="message ' . $class . '" >' . $_SESSION[p2h_SESSION_ID]['message'] . '</p>';
        unset($_SESSION[p2h_SESSION_ID]['message']);
        unset($_SESSION[p2h_SESSION_ID]['status']);
    }
}

/* 
function p2h_redirect($url, $code = 302) {
    header('Location: ' . $url, true, $code);
    exit;
}
 */
 
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
             '  <td class="fsize"  style="font-family: monospace;">'. $size. '</td>'.
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
    if ($is_archive) {              // ZIP content
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
    elseif ($is_image) {            // Image content
        if (in_array($ext, array('gif', 'jpg', 'jpeg', 'png', 'bmp', 'ico', 'svg', 'tif'))) {
            echo '<p><img src="'. p2h_enc($file_url). '" alt="" class="preview-img"></p>';
        }
    } 
    elseif ($is_audio) {            // Audio content
        echo '<p><audio src="'. p2h_enc($file_url). '" controls preload="metadata"></audio></p>';
    } 
    elseif ($is_video) {            // Video content
        echo '<div class="preview-video"><video src="'. p2h_enc($file_url). '" width="640" height="360" controls preload="metadata">'.
                lang('@Sorry, your browser doesn`t support embedded videos').
             '</video></div>';
    }
    elseif ($is_text) {
        if (p2h_USE_HIGHLIGHTJS) {  // highlight
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
        } elseif (in_array($ext, ['php', 'php4', 'php5', 'phtml', 'phps'])) {
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
        $arrNames[]= $fileinfo->getPathname(). ' ('.$fileinfo->getSize().' b)';
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
    $file_url = $file;
    
$result= '
    <div class="left" style=" /* width: 320px; */ overflow-x:visible; margin-left: 5px; background-color: white;">
        <div class="break-word"><i>Content:</i> <b>'. $view_title. /* ' '. p2h_enc(p2h_convert_win($file)).  */'</b></div>'.
        '<i>MIME-type:</i> '.$mime_type. '<br>'.
        // <div class="break-word">'.
        '<div >'.
            '<i>'.lang('@Full path').'</i>: '. p2h_enc(p2h_convert_win($file_path)). '<br>'.
            '<i>'.lang('@File size').'</i>: '. p2h_nice_filesize($filesize_raw); if ($filesize_raw >= 1000) ( $result.= ' / '.sprintf( '%s bytes', $filesize_raw) );
            // $result.= '<br> MIME-type: '.$mime_type. '<br>';

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
       '</div>
    </div>
    
    <div> '; 
        $result.= '<b><a download '.urlQuery(p2h_PATH,'dl',$file_url). ' title="'. 
                    lang('@Get a copy locally').'"><i class="fa fa-download colrDwn fa-sm"></i> '.
                    lang('@Download').
                  '</a></b> &nbsp;&nbsp;&nbsp;';
        if (!p2h_READONLY) { // prevent "File not found!" 
            $result.= '<b><a target="_blank" title="'.
                    lang('@View the file content') .'" 
                    href="'. p2h_enc($file_url). '"><i class="fa fa-external-link-square-alt colrShw fa-sm"></i> '.
                    lang('@Open'). 
                    '</a></b> &nbsp;&nbsp;&nbsp;';
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
    */
    $result.= '</div>';
    return $result;
} // p2h_fileView()

function p2h_folders($path)
{   $objects = is_readable($path) ? scandir($path) : array();
    foreach ($objects as $obj) {
    if ($obj == '.' || $obj == '..') { continue; }   // System folder
  //if (substr($obj, 0, 1)   == '.') { continue; }   // Hidden file
    $pathName = $path. '/'. $obj;
    if     (@is_file($pathName))                                   { $files[]   = $obj; } 
    elseif (@is_dir($pathName) && ($obj != '.') && ($obj != '..')) { $folders[] = $obj; echo ' '.$obj; }
    }
    //foreach ($folders as $fold) echo $fold;
}

function listFolders($dir,$currentDir='') {
    $arrItems = scandir($dir);
    unset($arrItems[array_search('.', $arrItems, true)]);    unset($arrItems[array_search('..', $arrItems, true)]);
    if (count($arrItems) < 1) return;    // prevent empty elements
    $file= 0;
    echo '<ul>';
        foreach($arrItems as $item){
            if(is_dir($dir.'/'.$item)) 
            {echo '├ '.
                  '<a href= "'.end(explode('/',__FILE__)).'?show=/'.$dir.'/'.$item. '" 
                      title="'.$dir.'/'.'">'.
                      ($currentDir==$item ? '<b>' : ''). $item. ($currentDir==$item ? '</b>' : '').
                  '</a>'.'/'; 
             listFolders($dir.'/'.$item,$currentDir);
            } else $file++;
        }
    echo '</ul>';
}

function p2h_fileExplore($path,$parentDir) {
    global $calc_folder, $totFolds, $totFiles, $totSize, $filesIncurrent, $locSize, $filesize_raw, $view_title, $root_dir, $currentPath, $arrNames, $quickView, $Context_Row, $ix;
    $objects = is_readable($path) ? scandir($path) : array();
    $folders = array();    $files = array();
    $totFiles= 0;   $totFolds= 0;    $totSize= 0;
    if (is_array($objects)) { // Objects to show in file-table:
        foreach ($objects as $obj) {
            if ($obj == '.' || $obj == '..') { continue; }   // System folder
          //if (substr($obj, 0, 1)   == '.') { continue; }   // Hidden file
            $pathName = $path. '/'. $obj;
            if     (@is_file($pathName))                                    { $files[]   = $obj; } 
            elseif (@is_dir ($pathName) && ($obj != '.') && ($obj != '..')) { $folders[] = $obj; }
        }
    }
    $maxsize= 0;    $foldsize= 0;   $filesIncurrent= 0;     $ix = 1000;     $arrNames= [];   $Context_Row= ''; //  folder checkbox id
    
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
        $fileRef[]= $file_path;
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
            $ext= '<a href="'.basename(__FILE__).'?show='.$_SERVER['DOCUMENT_ROOT'].$currentPath.'&amp;calc=true" title="'.lang('@Analyse folder content').'"> 
                        <pre style="margin: 3px 0 0;"><i style="margin:0 8px;" class="far fa-folder colr0 bgclgold"></i>'.lang('@FOLDER').'</pre>'.
                  '</a>';
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
            $meter= $foldsize / ($maxsize + 0.00001);
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
          // str_WithHint($labl='<a href="'.basename(__FILE__). '?show='.$path.'/'.$fld. '" class="txtbutt">&nbsp;'. $fld.'</a>', 
             str_WithHint($labl='<a href="'.basename(__FILE__). '?show='.$path.'/'.$fld. '" class="txtbutt">&nbsp;'. $fld.'</a>', 
             $hint=lang('@Show content in subfolder: <b>').$fld.'</b>').'</div>'. $buttns. '</div>';
        $rec['ext']= $ext;
        $rec['size']= $sortsize.'<div title="'.number_format((float)$foldsize, 0,',',' ').' Bytes" style="font-family:monospace; color:green;">'.$showsize.'</div>';
        $rec['modifyed']= $sorttime.$modif;
        $rec['perms']= $perms;
        $rec['owner']= $owner['name'];      //$rec['group']= $group['name'];
        $rec['access']= '-';
        $rec['space']= '<meter id="currFold'.$ix.'" low="0.15" optimum="0.30" high="0.60" max="1" value="'.$meter.'" style= "width: 95%;">'.$meter.'</meter>
                        <small style="/* width: 40px; */ display: inline-block; text-align: right; left: 40%; top: -16px; position: relative; background: lightyellow; opacity: 0.7;">'.number_format($meter*100,2).' %</small>';
        $foldRecords[]= $rec;
        
        $Context_Row.=
        // FOLDER Context-Menu: 
        Pmnu_($elem='tabl_row'.($ix-1000),$capt='@<small>FOLDER:</small> '. // $capt='@Regarding FOLDER:<br>'.
                                                // '<i class="'.$icoc.' fa-sm"></i>&nbsp;'. // Fails
                                                 '<b>'.$fld.'</b>',
                                                 $wdth='260px', icon:'', stck:'false', cntx:true, rtrn:true).
       // Pmnu_($elem='tabl_row'.($ix-1000),$capt='@Regarding FOLDER:',$wdth='260px',$icon='far fa-folder colr0 bgclgold',$stck='false',$attr='background-color:lightcyan;',$cntx=true,$rtrn=true).
       //     Pmnu_Item($labl='<b>'.$fld.'</b><br>', $icon='far fa-folder colr0 bgclgold',   $hint='', $type='custom',  $name='cust',$clck='', $attr="'background-color: white; height: 44px; border-style: solid; border-width: 5px 1px 5px 1px; border-color: lightgray; border-radius: 8px; padding-top: 10px;'' ",$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='@Rename folder',       $icon='fas fa-pen-square colrorange',  $hint='@Give another name',        $type='plain',  $name='renm',$clck='console.log(\''.$fld.'\')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='@Delete folder',       $icon='fas fa-trash-alt colrred',      $hint='@Erase the folder',         $type='plain',  $name='dele',$clck='console.log(\''.$labl.'\')' ,$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='',                     $icon='',                               $hint='',                                                                  $type='separator',rtrn:true).
            Pmnu_Item($labl='@Copy folder',         $icon='fas fa-copy colrbrown',          $hint='@Make a copy with a new name',                                      $type='plain',  $name='dnld',$clck='fileview(\''.$currentPath.'\',\''.$fld.'\')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='@Create ZIP',          $icon='far fa-file-archive colrbrown',  $hint='@Make a compressed file with the content of this subfolder',        $type='plain',  $name='crea',$clck='fileview(\''.$currentPath.'\',\''.$fld.'\')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='@Syncronize',          $icon='fas fa-sync colrbrown',          $hint='@Create a ZipClone/backup of this folder with another selected...', $type='plain',  $name='crea',$clck='fileview(\''.$currentPath.'\',\''.$fld.'\')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='@Go to',               $icon='fas fa-chevron-right colrblack', $hint='@Look at this subfolder',                                           $type='plain',  $name='upld',$clck='fileview(\''.$currentPath.'\',\''.$fld.'\')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='',                     $icon='',                               $hint='',                                                                  $type='separator',rtrn:true).
            Pmnu_Item($labl='@Folder property',     $icon='fa-solid fa-binoculars colrblue',$hint='@Look at the folder property',                                      $type='plain',  $name='prop',$clck='fileview(\''.$currentPath.'\',\''.$fld.'\')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='@Create new folder',   $icon='fas fa-plus colrgray',           $hint='@Make a new folder',                                                $type='plain',  $name='dnld',$clck='fileview(\''.$currentPath.'\',\''.$fld.'\')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='',                     $icon='',                               $hint='',                                                                  $type='separator',rtrn:true).
            Pmnu_Item($labl='@Still an inactive DEMO', $icon='fas fa-info-circle colrred fa-sm',     $hint='@Click outside menu to close',                                      $type='custom',  $name='cust',$clck='',
                      $attr="'background-color: lightcyan; height: 24px; border-style: solid; border-width: 5px 1px 5px 1px; border-color: lightgray; border-radius: 8px; padding-bottom: 0; padding-top: 10px;' ",$akey='',$enabl='true',$rtrn=true).
        Pmnu_end($labl='',$hint='',$attr='',$rtrn=true);
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
        $arrAbout= p2h_get_file_icon_class($file_path);
        $icoc = $is_link ? 'far fa-file-alt colr4' : $arrAbout[0];
        $showType= $arrAbout[1];
        $view_title= $showType;
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
            $fileInfo= p2h_fileView($view_title, $fld, $ext, $file_path, $filesize_raw, $mime_type);
            $nameFld= '<div class="file-name-div" title="File content: '.$view_title.' '. '<i class="'.$icoc.'"></i>&nbsp;'. $fld.$fileInfo.'</div>';
            if ($ix < 200) // Max $ix previews on one page
            // if ($Path==$currentPath)
            $nameFld.= '<div class="live-preview-img file-name-div"> <img src="'. p2h_enc($currentPath. $fld). '" alt="'.$view_title.'">'.$fileInfo.'</div>';
        } else
            $nameFld= '<i class="'.$icoc.'"></i>&nbsp;'. $fld;
        if ($ext>'') $ext = '.'.$ext;
        $rec['ix']= $ix;
        $rec['name']= $nameFld;
        $rec['ext']= $ext;
        $rec['size']= $sortsize.'<div title="'.number_format((float)$filesize_raw, 0,',',' ').' Bytes">'.$showsize.'</div>';
        $rec['modifyed']= $sorttime.$modif;
        $rec['perms']= $perms;
        $rec['owner']= $owner['name'];      //$rec['group']= $group['name'];
        $rec['access']= $accetime.$acces;
        $rec['space']= '<meter id="currFile'.$ix.'" low="0.15" optimum="0.30" high="0.60" max="1" value="'.$meter.'" style= "width: 95%;">'.$meter.'</meter>
                        <small style="text-align: right; display: inline-block; left: 40%; top: -16px; position: relative; background: lightyellow; opacity: 0.7;">'.number_format($meter*100,2).' %</small>';
        $fileRecords[]= $rec;

    $Context_Row.=
        // FILE Context-Menu: 
        Pmnu_($elem='tabl_row'.($ix-1000),$capt='@<small>FILE:</small> '. '<b>'.$fld.'</b>',
                                          $wdth='260px', icon:'', stck:'false', cntx:true, rtrn:true).
            Pmnu_Item($labl='@Preview',        $icon='far fa-eye colrblack',         $hint='@Look at content in viewer',     $type='plain', $name='view', $clck='seecontent(\''.$currentPath.'\',\''.$fld.'\')', $attr='', $akey='', $enabl='true', $rtrn=true).
            Pmnu_Item($labl='@Edit content',   $icon='far fa-edit colrgreen',        $hint='@Open file in editor',           $type='plain', $name='edit', $clck='window.open(\'TEST\',\'_self\')', $attr='',$akey='', $enabl='true', $rtrn=true).
            Pmnu_Item($labl='@Rename file',    $icon='fas fa-pen-square colrorange', $hint='@Give another name',             $type='plain', $name='renm', $clck='fileview(\''.$currentPath.'\',\''.$fld.'\')', $attr='',$akey='', $enabl='true', $rtrn=true).
            Pmnu_Item($labl='@Delete file',    $icon='fas fa-trash-alt colrred',     $hint='@Erase the file',                $type='plain', $name='dele', $clck='fileview(\''.$currentPath.'\',\''.$fld.'\')', $attr='',$akey='', $enabl='true', $rtrn=true).
            Pmnu_Item($labl='',                $icon='', $hint='', $type='separator', rtrn:true).
            Pmnu_Item($labl='@Copy file',      $icon='fas fa-copy colrbrown',           $hint='@Make a copy with a new name or in another folder', $type='plain',$name='dnld',$clck='fileview(\''.$currentPath.'\',\''.$fld.'\')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='@Create ZIP',     $icon='far fa-file-archive colrbrown',   $hint='@Make a compressed file with the content of this file', $type='plain', $name='crea',$clck='fileview(\''.$currentPath.'\',\''.$fld.'\')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='@Download',       $icon='fas fa-download colrbrown',       $hint='@Download to local storage',    $type='plain',$name='dnld',$clck='fileview(\''.$currentPath.'\',\''.$fld.'\')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='@File properties',$icon='fa-solid fa-binoculars colrblue', $hint='@Look at the files properties', $type='plain',$name='prop',$clck='fileview(\''.$currentPath.'\',\''.$fld.'\')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='',                $icon='', $hint='', $type='separator', rtrn:true).
            Pmnu_Item($labl='@Still an inactive DEMO', $icon='fas fa-info-circle colrred fa-sm', $hint='@Click outside menu to close', $type='custom',$name='info',$clck='', $attr="'background-color: lightcyan; height: 24px; border-style: solid; border-width: 5px 1px 5px 1px; border-color: lightgray; border-radius: 10px; padding-top: 10px;' ",$attr='',$akey='',$enabl='true',$rtrn=true).
        Pmnu_end($labl='',$hint='',$attr='',$rtrn=true);
   
        $ix++; 
        $fileRecordsIncurrent= $ix-$filesInsub;
    }
    return array($foldRecords ?? null, $fileRecords ?? null, $fileRef ?? null);
} // p2h_fileExplore()
    

function p2h_folder_backup($currDir, $subdirs= 3) { ## Serverside-zip-backup:
  $result= lang('@ZipClone-backup:<br>');
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
  $ix= '00';  $thisdir= './';                       // Aktual folder:'./'   One level up:'./../'
  $files= p2h_getFileList($thisdir, true, $subdirs);    // File-list has to be created, before the zip-file is created, to prevent tempory zip-fraktions.
  $zip = new ZipArchive();                          // PHP ZIP-extension must be active in PHP-system!
  if ($zip->open($destDir.$destFile, ZipArchive::CREATE)!==TRUE) { exit(lang('@Unable to create <').$destFile.'>'); }

  $zip->addFromString('_Readme.txt',    ## Info-file in Zip-file:
    lang('@This ZIP file contains a momentary backup at the time: ').$timestamp.
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
<span class="" title="'.($calc_folder== true ? lang('@Enter search string...') : lang('@Only active in advanced mode!') ). '">'.
    htm_Input($labl= 'Search', $plho='@Enter...', $icon='', $hint='@Search for a file in sub-folders, and open that folder',
              $type= 'html', $name='fsearch',
              $valu= '<div><i class="fa fa-search"></i> '.($calc_folder== true ? count($arrNames).' '.lang('@Files in subfolders') : lang('@Not active.')).'</div>',
              $form= '', $wdth='200px', $algn='left',$attr='onclick="ToggShow()" class="dropbtn" ',$rtrn=true,
              $unit= '', $disa=($_POST['prgMode'] ?? '' != 'mode_A'), $rows='0', $step='', $list=[], $llgn='R', $bord='', $ftop='')
              . '
    <div id="FileSearch" class="dropdown-content" 
        title="'. lang('@Click to open folder containing this file ') .'">
        <input type="text" placeholder="Search.." id="searchInput" onkeyup="filterFunction()" z-index:202;
            title="'. lang('@Type a search string: Part of the file- / folder-name').'"
            style="width:200px; text-align:left; font-family:arial; border: 1px solid black;">';
        $name_list= ''; // List to search for file names in sub-folders 
        foreach($arrNames as $file) {
            $pos= strrpos($file,'/')+1;
            $dir= substr($file,0,$pos);
            $fil= substr($file,$pos);
            $name_list .= '<a href="'. basename(__FILE__). '?show='.$dir.'" >'.
                        '<span class="colrgray">'.(strlen($dir) > 32 ? '...'.substr($dir,-32) : $dir).':</span> '. $fil.'</a>';
            }
            $result.= '<div style= "text-align:left;">'.$name_list.'</div>';
    $result.= '
    </div>
</span>';
return $result;
}

// arrPretty($_POST,'$_POST');
// arrPretty(get_defined_vars(),'Defined_vars:');


##### PREPARE DATA:
$calc_folder= ($_POST['prgMode'] ?? '' == 'mode_A');
$currPath= __DIR__; // Initial path if 'show' is not set

if     (isset($_POST['show']))  { $currPath= '//'.ltrim($_POST['show'],'/'); } # safer
elseif (isset($_GET ['show']))  { $currPath= '//'.ltrim($_GET ['show'],'/'); } # unsafe
if (isset($_POST['csvFile']))   { $csvFile= $_POST['csvFile']; }
for ($ix=0;$ix<50;$ix++)
    if (isset($_GET['zipClone'.$ix])) { echo p2h_folder_backup($currDir=$path.'/'.$fld, $subdirs= 3); unset($_GET['zipClone'.$ix]); }


if (isset($_GET['calc'])) {
    $calc_folder= true;
}

if (isset($_GET['logout'])) {
    unset($_SESSION[p2h_SESSION_ID]['logged']);
    // p2h_redirect(p2h_SELF_URL);
}

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
$objNames= $data[2];
$arrFnames= []; $i= 0;
if (!$objNames==null)
    foreach ($objNames as $obj) $arrFnames[]= [ $i++, substr($obj,strrpos($obj,DIRECTORY_SEPARATOR)+1)];
// arrPretty($arrFnames,'arrFnames:');

// $arrfolds= $GLOBALS['folders']; $arrfiles= $GLOBALS['files'];

$tabldata= [];
if (is_array($arrfolds) and (count($arrfolds)>0)) $tabldata= $arrfolds;
if (is_array($arrfiles) and (count($arrfiles)>0)) $tabldata= array_merge($tabldata,$arrfiles);


if (empty($auth_users)) $use_auth = false; else $use_auth = true;

// Auth:
if ($use_auth) {
    if (isset($_SESSION[p2h_SESSION_ID]['logged'], $auth_users[$_SESSION[p2h_SESSION_ID]['logged'][0]])) {
        // Logged:
    } elseif (isset($_POST['p2h_usr'], $_POST['p2h_pwd'])) {
        // Logging In:
        sleep(1);
        for ($i=1; $i<=5; $i++) $test[$i]= false;
        if (function_exists('password_verify')) {
            if ($test[1]= isset($auth_users[$_POST['p2h_usr']][0]) && 
                $test[2]= isset($_POST['p2h_pwd'])              && 
                $test[3]= password_verify($_POST['p2h_pwd'], $auth_users[$_POST['p2h_usr']][0]) && 
                $test[4]= true /* $is_human= (true!=true) */    && 
                $test[5]= true /* $Legal_BasePath= */
               )
            {   $_SESSION[p2h_SESSION_ID]['logged'] = $_POST['p2h_usr'];
                p2h_set_msg(lang('@You are logged in as: ').$_POST['p2h_usr']);
                // p2h_redirect(p2h_SELF_URL . '?show='); // $_POST['show']
            } else {
                unset($_SESSION[p2h_SESSION_ID]['logged']);
                $temp= $_POST['p2h_usr'].': '.password_hash($_POST['p2h_pwd'], PASSWORD_BCRYPT, [12]);
                $chk= '';
                for ($i=1; $i<=5; $i++) if ($test[$i]==true) $chk=$chk.'ok '; else $chk=$chk.'fail ';
                p2h_set_msg('@Login failed: Wrong Base-path and/or Invalid account name and/or password or is robot ('.$chk.') '.$temp, 'error');
               // p2h_redirect(p2h_SELF_URL);
               // if ($_POST['p2h_pwd']) echo $_POST['p2h_usr'].': '.password_hash($_POST['p2h_pwd'], PASSWORD_BCRYPT, [12]);
            }
        } else {
            p2h_set_msg('@Password_hash not supported, Upgrade PHP version', 'error');
            unset($_SESSION[p2h_SESSION_ID]['logged']);
        }
    }
}


// update root path
if ($use_auth &&                    isset($_SESSION[p2h_SESSION_ID]['logged'])) {
    $root_path = isset($directories_users[$_SESSION[p2h_SESSION_ID]['logged']]) 
                     ? $directories_users[$_SESSION[p2h_SESSION_ID]['logged']] 
                     : __DIR__;
} else $root_path= $locked_path;

// clean and check $root_path
$root_path = rtrim($root_path, '\\/');
$root_path = str_replace('\\', '/', $root_path);
/* 
if (!@is_dir($root_path)) {

    echo "<h1>".lang('Root path')." \"{$root_path}\" ".lang('not found!')." </h1>";
    exit;
}
 */
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
 
 
 // $p2h_Style= '';
 
##### SCREEN OUTPUT:  -  generated by PHP2HTML functions !
#!!!: Remember no OUTPUT to screen, before htm_PagePrep() output or you will get warning !

### LOGIN: 
if (!isset($_SESSION[p2h_SESSION_ID]['logged'])) {
htm_Page_(titl:'Login to Folder-explorer', hint:lang(''), info:lang(''), inis:$p2h_Style, 
          algn:'center', imag:'./_accessories/_background.png', attr:'', pbrd:true );
    htm_Caption('@Connect to Folder-explorer', algn:'center; font-size: 18px');
    htm_nl(1); 
    htm_Card_(capt:'@Signup: ', icon:'fas fa-user-check', hint:'', form:'signup', acti:'', clas:'cardW320', wdth:'640px', styl:'background-color: lightcyan;', attr:'', help:'',show:false, poup:false);
        htm_Input(labl:'@Base-path',    plho:'@Path...',  icon:'', hint:'@The path for wich you have access',       vrnt:'text', name:'text1', valu:$text1=$locked_path, form:'', wdth:'80%', algn:'left', attr:'', rtrn:false, unit:'', disa:false, rows:'3',  step:'');
        htm_Input(labl:'@Your account', plho:'@Name...',    icon:'', hint:'@Type account name', vrnt:'text', name:'p2h_usr', valu:$p2h_usr='visitor',           form:'', wdth:'80%', algn:'left', attr:'required', rtrn:false, unit:'', disa:false, rows:'3',  step:'');
        htm_Input(labl:'@Your email',   plho:'@Email...',    icon:'', hint:'@Type your email',  vrnt:'mail', name:'p2h_mil', valu:$p2h_mil='visitor@mail.addr', form:'', wdth:'80%', algn:'left', attr:'', rtrn:false, unit:'', disa:false, rows:'3',  step:'');
        htm_Input(labl:'@Your password', plho:'@Password...', icon:'', hint:'@Type your password for your account', vrnt:'pass', name:'p2h_pwd', valu:$p2h_pwd='xxxxxxxx', form:'', wdth:'80%', algn:'left', attr:'required', rtrn:false, unit:'', disa:false, rows:'3',  step:'', list:[], llgn:'R', bord:'', ftop:'');
        htm_IconButt(labl:'@Forgotten password ?', icon:'fas fa-key',  hint:'@Click to request a new password', type:'button',   name:'lost', link:'', evnt:'', wdth:'244px', font:'18px', fclr:'gray', bclr:'white', akey:'', rtrn:false);
        htm_nl(2);
        $html= htm_Humantest(capt:'@Are you human? ', icon:'fa-solid fa-arrow-right-to-bracket', hint:'@Grab and slide to right to change state', 
                             form:'human', wdth:'100%', hght:'22px', yclr:'lightgray', nclr:'white', xytx:'@YES', ntxt:'@NO',rtrn:true);
        htm_Inbox(labl:'@Robot ?',  plho:'', icon:'',hint:'@Confirm you are not a robot',
                  vrnt:'',name:'robot',valu:$html, form:'',wdth:'80%;',algn:'center',
                  attr:'color: green;',rtrn:false,unit:'',disa:false,rows:'2',step:'',list:[],llgn:'R',bord:'1px solid var(--grayColor);',ftop:'');
        htm_nl(1); 
        htm_MiniNote('<span class="colrorange">'.lang('@Orange ').'</span>'.lang('@frames are required fields.'));
    htm_Card_end(labl:'Login', icon:'', hint:'@Login with the given data', name:'butt', form:'signup', subm:true, attr:'', akey:'', kind:'navi', simu:false);
    p2h_show_message();
    htm_Page_end();
}
else {
### FOLDEREXPLORER: 
htm_Page_(titl:'Folder-explorer', hint:lang('@Tip: Toggle fullscreen-mode with function key: F11'),info:lang(''), inis:$p2h_Style, 
          algn:'center', imag:'./_accessories/_background.png',attr:'', pbrd:true );
    // print_r($tabldata);
    htm_Caption(labl:'Folder-explorer',icon:'',hint:'The advanced file-explorer with focus on folders and space taken up of files.',
                algn:'center',styl:'color:'.$gbl_TitleColr.'; font-weight:600; font-size: 18px; padding: 4px;' );
    htm_nl(1);
    htm_Caption(labl:'Build with PHP2HTML v4',icon:'',hint:'The shortcut to structured and compact code<br>Created by EV-soft.',
                algn:'center',styl:'color: gray;; font-weight:400; font-size: 12px;');
    p2h_show_message();
    htm_nl(1);
    htm_Card_(capt:'Folder-Tree',icon:'fas fa-sitemap',hint: '',form:'',acti:'',clas:'cardW800',
                wdth:'',styl:'background-color: white; ',attr:'text-align: left; padding:8px; background-color: white;',show:true, head:'');
        echo '<span style="text-align:left;"><pre style="margin-bottom: 0;">Level:  0:     1:     2:     3:     4:     5:     6:     7:     8:     9:    10:    11:    12:    13:    14:  </pre></span>';
        htm_wrapp_($ViewHeight='300px; text-align:left; ');

        // $base= $_SERVER['DOCUMENT_ROOT'];        // $base= substr($base,0,-13);
        $base= __DIR__.'';        // $base= substr($base,0,-13);
                    # $body,$algn='left',$marg='8px',$styl='box-shadow: 3px 3px 6px 0px #ccc; padding: 5px; border: solid 1px lightgray; ',$attr='background-color: white; ');
        htm_TextDiv(body:'BASE: <big><b>'.$base.'</b>/'.'</big><br>All folders with name="'.$currentDir.'" (current folder), are marked with bold text.',
                    algn:'left',marg:'8px',styl:'box-shadow: 3px 3px 6px 0px #ccc; padding: 5px; border: solid 1px lightgray; background-color: white; ');
        echo '<style> ul { padding-left: 50px; line-height: 1.2; } </style>';
        listFolders($base,$currentDir);

        htm_wrapp_end();
    htm_Card_end($labl='', $icon='',  $hint='', $name='', $form='', $subm=false, $attr='', $akey='', $kind='save', $simu=false);
    htm_nl(1);

    htm_Card_(capt:'Folder-Details',icon:'fas fa-info',hint: '',form:'xxx',acti:'',clas:'cardWmax',
                wdth: '',styl:'background-color: white;',attr:'', show:true, head: 'background-color: white;');
    echo '<small>';
    
    if (count($arrFnames)== 0) $dis= 'disabled'; else $dis= '';
    if (count($tabldata)==0) {
        echo '<big> '. $_SERVER['SERVER_NAME'].' // '.$breadcrumbs.'</big>';
        htm_nl(2);
        htm_Caption('@Empty folder.');
        htm_nl(4);
    } else
    htm_Table(
        $TblCapt= array( //#['0:Label', '1:Width', '2:Type', '3:OutFormat', '4:horJust', '5:Tip', '6:placeholder', '7:Content';], ...
          ['<span style="float:left; top: -5px; position: relative;" title="'.lang('@RightClick for program MENU').'">
            <big><i>Showing:</i> ', '90%','html','','left','@Tip','?', $_SERVER['SERVER_NAME'].' // '.$breadcrumbs.'</big></span>'],
          ['<span style="float:right; white-space: nowrap; display: inline-block;">'.
               '<span title="'.lang('@Space meaning file-space in local folder / total in subfolders').'">'.
               ($calc_folder == true ? 
               '<i>Content:</i> Folders: '.$totFolds.' - Files: '.markAllChars($filesIncurrent.'/','i','style="opacity:0.5;"').$totFiles. ' - ' : '' ).
               'Space: '.markAllChars(p2h_nice_filesize($locSize).'/','i','style="opacity:0.5;"').p2h_nice_filesize($totSize).
               ' - Free: '.   p2h_nice_filesize(disk_free_space( __FILE__ )).
               ' of total: '. p2h_nice_filesize(disk_total_space(__FILE__ )). 
               '</span>'.
           '</span> <br>
            <div style="width: 800px; margin: auto; padding-top: 10px;">'. 
                '<form method="post" name= "frmMode" style="display:inline;">'.
                str_sp(2).
                SearchSub($arrNames). ' '.
                htm_Input(labl:'@Mode',plho:'@Select...',icon:'',
                          hint:'@The program mode.<br>Speedup by deactivating calculating content in subfolders using "Simple mode"',
                          vrnt:'opti',name:'prgMode',valu: ($calc_folder == true ? 'mode_A' : 'mode_S'), form:'',
                          wdth:'',algn:'left',attr:'',rtrn:true,unit:'',disa:false,rows:'1',step:'',list:[
                            ['mode_S','Simple','@Do not spend time on calulating subfolders'],
                            ['mode_A','Advanced','@Advanced mode exploring all subfolders']
                          ],llgn:'R',bord:'',ftop:'').
                    '<input type="submit" value="'.lang('@Set adv. Mode').'" class="txtbutt" style="
                                 border-radius:54px; height:33px; background-color: ivory; padding: 0 15px;" 
                                 title="'. lang('@Analyse sub-folders in advanced mode...').'">'.
                str_sp(2).
                htm_LinkButt(labl:'@Language', hint:'@Open the translate language page', attr:'', 
                             link:'translate.page.php', targ:'_blank', rtrn:true).
                '<span style= "padding: 0 12px;"> Logged as: <b>'.$_SESSION[p2h_SESSION_ID]['logged'].'</b></span>'.
                '</form>'.
                '<abbr class="hint" >
                    <form name="zzz" style=" display: inline-block; ">
                        <button class="buttstyl" type= "submit" name="logout" style="color:white; background:green;" >'.
                            ' <data-ic class="fas fa-sign-out-alt" style="font-size:18px; color:white;"> </data-ic> '.
                            lang('@Logout'). '&nbsp; 
                        </button>
                    </form>
                    <data-hint>'.lang('@Leave the program in locked mode').'</data-hint> 
                 </abbr>'.                
            '</div>'
                , '90%','html','','left'] 
        ), // $TblCapt
        $RowPref= array(
            ['@Select',    '04%', 'html', '', ['center'],/* ColTip: */'@Mark here to select object',    /* Html: */ 
                htm_Input(vrnt: 'chck', name:'objt', wdth:'52px; padding:0;', bord:'border: 0; box-shadow:none; background:transparent; margin:0; padding:0;', attr:'', rtrn:true, 
                          list:[['chck','?','@Handle this file/folder - Click to select/unselect','']] )
            ] ),
        $RowBody= array(
        // ['0:ColLabl', '1:ColWidth', '2:ContType', '3:OutFormat', '4:[horJust_etc]', '5:fldKey', '6:ColTip','7:placeholder','8:default','9:[selectList]'],
            ['@Ix',        '04%', 'show', '', ['center'],                   'ix',       '@System index','..auto..'], 
            ['@Name', $calc_folder == true ? '15%' : '15%',
                                  'html', '', ['left'  ],                   'name',     '@The name of file or directory'], // Don`t change this hint. It will remove the goUp-button in header!
            ['@Count/Ext', '10%', 'html', '', ['center','','',$sort=false], 'ext',      '@Folder content or the extension of the filename'],
            ['@Size',      '05%', 'html', '', ['right' ],                   'size',     '@The used space'],
            ['@Modifyed',  '10%', 'html', '', ['center'],                   'modifyed', '@Last modifyed date/time'],
            ['@Perms',     '04%', 'text', '', ['center'],                   'perms',    '@The file permissions (UNIX.mode)'],
            ['@Owner',     '04%', 'text', '', ['center'],                   'owner',    '@ID for the file creater (UNIX.owner)'],
            ['@Accessed',  '10%', 'html', '', ['center'],                   'access',   '@File accessed date/time'],
            ['@Space',     '20%', ($calc_folder == true ? 'html' : 'hidd'),
                                          '', ['left'  ,'','',$sort=false], 'space', '@Used space related to<br>Sum of all files in current folder and subfolders.']
        ),
        $RowSuff= array(), // tabl_row.ix
        $TblNote= '<small>'.lang('@Filtering/Searching: Hold mouse over the colored row below the column headers.').'</small><br><br>'.
                  htm_Fieldset_(capt:'@Future file features:',icon:'', hint:'',wdth:'',marg:'',attr:'',rtrn:true).
                      htm_Fieldset_(capt:'@Select:',icon:'', hint:'',wdth:'',marg:'',attr:'',rtrn:true).
                      htm_AcceptButt(labl:'@All',  icon:'', hint:'@All files and folders', form:'', wdth:'', attr:'', akey:'', kind:'spc2', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'', idix:'').
                      htm_AcceptButt(labl:'@None', icon:'', hint:'@Deselect all', form:'', wdth:'', attr:'', akey:'', kind:'spc2', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'', idix:'').
                      htm_AcceptButt(labl:'@Inverse', icon:'', hint:'@Reverse all', form:'', wdth:'', attr:'', akey:'', kind:'spc2', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'', idix:'').
                      htm_Fieldset_end(rtrn:true).
                      
                      htm_Fieldset_(capt:'@With selected do:',icon:'', hint:'',wdth:'',marg:'',attr:'',rtrn:true).
                      htm_AcceptButt(labl:'@COPY',       icon:'', hint:'@Make a copy of selected file/folder(s)',                 form:'', wdth:'100px;', attr:$dis, akey:'', kind:'',     rtrn:true, tplc:'LblTip_text', tsty:'', acti:'fcopy("path","name")', idix:'').
                      htm_AcceptButt(labl:'@MOVE',       icon:'', hint:'@Transfer selected files/folder(s) to another folder(s)', form:'', wdth:'100px;', attr:$dis, akey:'', kind:'crea', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'fmove("path","name")', idix:'').
                      htm_AcceptButt(labl:'@Create ZIP', icon:'', hint:'@Make a copy of selected file/folder in a ZIP-file',      form:'', wdth:'100px;', attr:$dis, akey:'', kind:'',     rtrn:true, tplc:'LblTip_text', tsty:'', acti:'f_zip("path","name")', idix:'').
                      htm_AcceptButt(labl:'@DELETE',     icon:'', hint:'@Erase selected file/folder(s)',                          form:'', wdth:'100px;', attr:$dis, akey:'', kind:'eras', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'fdelt("path","name")', idix:'').
                      htm_AcceptButt(labl:'@Property',   icon:'', hint:'@Change properties for selected file/folder(s)',          form:'', wdth:'100px;', attr:$dis, akey:'', kind:'spc1', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'fprop("path","name")', idix:'').
                      htm_AcceptButt(labl:'@DOWNLOAD',   icon:'', hint:'@Transfer selected file/folder(s) to a local folder',     form:'', wdth:'100px;', attr:$dis, akey:'', kind:'get_', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'fdwnl("path","name")', idix:'').
                      htm_Fieldset_end(rtrn:true).
                      // lang('@ Other: ').
                      htm_AcceptButt(labl:'@UPLOAD',     icon:'', hint:'@Transfer a file/folder(s) from a local folder',          form:'', wdth:'100px;', attr:$dis, akey:'', kind:'get_', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'fupld("path","name")', idix:'').
                      htm_Input(labl:'@Destination',plho:'Path ?',icon:'',hint:'@For COPY/MOVE/ZIP/UPLOAD If it is not the current folder',vrnt:'text',name:'dest',valu:'',form:'',wdth:'',algn:'',attr:'',rtrn:true,unit:'',disa:false,rows:'',step:'',list:[],llgn:'',bord:'',ftop:'20px;').
                  htm_Fieldset_end(rtrn:true),
        $tabldata, 
        $FilterOn= true,
        $SorterOn= true,        # FIXIT: Sorting fails if there are both folders and files! (js-error coursed by empty td-fields)- Folders only is OK (BAD data before the first file ?)
        $CreateRec=false,
        $ModifyRec=false,
        $ViewHeight= '650px',
        $TblStyle= 'width:98%; cursor: alias; ',
        $CalledFrom= __FILE__ ,
        $MultiList= ['',''],
        $ExportTo= $csvFile ?? ''
    );
    echo $Context_Row;
    
run_Script('
    function fcopy(path, name) { alert("FileCopy:      " + path + " " + name); }
    function fmove(path, name) { alert("FileMove:      " + path + " " + name); }
    function f_zip(path, name) { alert("FileZip:       " + path + " " + name); }
    function fdwnl(path, name) { alert("FileDownload:  " + path + " " + name); }
    function fdelt(path, name) { alert("FileDelete:    " + path + " " + name); }
    function fprop(path, name) { alert("FileProperty:  " + path + " " + name); }
    function fupld(path, name) { alert("FileUpload:    " + path + " " + name); }
');

    // function setcheck(id) { document.querySelector(\'section.button input[type="checkbox"]\').checked = true; }
run_Script('
    function setcheck(id) {    document.getElementById(id).checked = true; }
    function setuncheck(id) {  document.getElementById(id).checked = false; }
'); // const input = document.querySelector('section.button input[type="checkbox"]');

run_Script('
    function fileview(path, fld) { alert("FILE: " + path + fld); }
    function seecontent(path, fld) { alert("FILE: " + "ev-soft.work" + path + fld); 
        let setFile = "ev-soft.work/fe/_assets/font-awesome6/6.1.1/"  + fld;
        alert(setFile);
    }
    '
);


$selected= [];  $ChkBoxes= [];
for ($ix=0;$ix<count($arrFnames ?? 0);$ix++) { $key= 'i'.$ix.'_chck'; 
    $ChkBoxes[] = [ $arrFnames[$ix][1], (array_key_exists($key, $_POST) ? 'checked' : 'unchecked')];
    
    if ((isset($_POST[$key])) and ($_POST[$key]=='checked')) { 
        $selected[]= $arrFnames[$ix][1]; 
        // run_Script("setcheck($key);");
    }
    
    // else run_Script("setuncheck($key);");
    // run_Script("setcheck('i5_chck');"); 
    // run_Script('$(function() { $("input[name=\'i2_chck\']").checked(true); })');
    // option1ChkBox = array_key_exists('chkBoxName', $_POST) ? true : false;
}
// <input type="checkbox" name="'.'i'.$ix.'_chck'.'" value="checked" style="width: 20px; box-shadow: none;">
// $(function() { $("input[name=''.'i'.$ix.'_chck'.'']").val("checked"); });
// elementX.checked = true;

// arrPretty($selected,'Selected:');
// arrPretty($ChkBoxes,'ChkBoxes:');

    $body1= '(<b>Context-Menu:</b> Right-click on table-row to get File/Folder-menu,  Right-click elsewere in table-window, to get Program-menu [Not active yet])<br>
             <b>Advanced mode:</b> Collect file names, size and folders in subfolders - Show used space graphichal. Show Syncronize- / ZipClone-buttons,  
             show more details about files<br>
             <b>Unrounded size:</b> Hover over size and the size will be shown on bytes.<br>
             <b>Auto mode:</b> When you change folder, the mode is automatic changed to simple.<br>
             In tables you can ajust columns width by dragging the vertical column border.';
    $body2= '@Comming later !';
    htm_Tabs_(head:'', styl:'', rtrn:false);
    htm_Tab(labl:'@Nice to know:',body:$body1, name:'nice',styl:'text-align: left; box-shadow: 3px 3px 6px 0px #ccc; padding: 5px; background-color: lightyellow;', bclr:'lightyellow;', dflt:true);
    htm_Tab(labl:'@Accessory:',   body:$body2, name:'acce',styl:'text-align: left; box-shadow: 3px 3px 6px 0px #ccc; padding: 5px; background-color: lightcyan;',bclr:'lightcyan;');
    htm_Tab(labl:'@File-viewers:',body:$body2, name:'view',styl:'text-align: left; box-shadow: 3px 3px 6px 0px #ccc; padding: 5px; background-color: lightgray;',bclr:'lightgray;');
    htm_Tabs_end(foot:'', styl:'', rtrn:false);
    /* 
    htm_TextDiv(lang('@Nice to know:').'<br>
                <b>Context-Menu:</b> Right-click on table-row to get File/Folder-menu,  Right-click elsewere in table to get Program-menu<br>
                <b>Advanced mode:</b> Collect file names, size and folders in subfolders - Show used space graphichal. Show Syncronize- / ZipClone-buttons<br>
                <b>Unrounded size:</b> Hover over size'); 
      */                     
    echo '</small>';
    htm_nl(2);
    htm_Card_end(labl:'', icon:'', hint:'', name:'', form:'', subm:false, attr:'', akey:'', kind:'save', simu:false);
    
    htm_nl(2);
    htm_Page_Sect(body:lang('@This is a program-version under development. Some facilities is not functioning yet.').'<br>'.$© );
    htm_nl(2);
    
 /*     
    // $fileName= 'http://ev-soft.work/fe/'. '_assets/font-awesome6/6.1.1/LICENSE.txt';
    $fileName= './'. 'LICENSE';
    $fileContent= file_get_contents ($fileName);

    htm_Card_(capt:'TEXT-view file: '.$fileName,icon:'fas fa-eye',hint: '',form:'',acti:'',clas:'cardW640',
                wdth: '',styl:'background-color: white;',attr:'', show : true, head : '');
        $fileContent= file_get_contents ($fileName);
        htm_wrapp_($ViewHeight='500px');
        htm_TextDiv(str_replace(chr(13),'<br>',htmlentities($fileContent))); 
        htm_wrapp_end();
        htm_nl(1);
    htm_Card_end(labl:'', icon:'', hint:'', name:'', form:'', subm:false, attr:'', akey:'', kind:'save', simu:false);
    
    htm_Card_(capt:'HTML-view file: '.$fileName,icon:'fas fa-eye',hint: '',form:'',acti:'',clas:'cardW640',
                wdth: '',styl:'background-color: white;',attr:'', show : true, head : '');
        htm_wrapp_($ViewHeight='500px');
        htm_TextDiv($fileContent); 
        htm_wrapp_end();
        htm_nl(1);
    htm_Card_end(labl:'', icon:'', hint:'', name:'', form:'', subm:false, attr:'', akey:'', kind:'save', simu:false);
    
    htm_Card_(capt:'BIN-view file: '.$fileName,icon:'fas fa-eye',hint: '',form:'',acti:'',clas:'cardW640',
                wdth: '',styl:'background-color: white;',attr:'', show : true, head : '');
        htm_wrapp_($ViewHeight='500px');
        htm_TextDiv(preg_replace('/[^\x20-\x7E]/', $pad_char='·', $fileContent),algn:'left',marg:'12px',styl:'',attr:'font-family:monospace; width:580px; background-color:lightcyan;'); 
        htm_wrapp_end();
        htm_nl(1);
    htm_Card_end(labl:'', icon:'', hint:'', name:'', form:'', subm:false, attr:'', akey:'', kind:'save', simu:false);
  */  
    $fileName= 'http://ev-soft.work/fe/'. '_assets/font-awesome6/6.1.1/LICENSE.txt';
    $fileContent= file_get_contents ($fileName);
    
    htm_Card_(capt:'HEX-view file: '.$fileName,icon:'fas fa-eye',hint: '',form:'',acti:'',clas:'cardW640',
                wdth: '',styl:'background-color: white;',attr:'', show : true, head : 'background-color: white;');
        htm_wrapp_($ViewHeight='500px');
            htm_TextDiv('<pre><code>'.hex_dump($fileContent,"<br>",16,"·").'</code></pre>',algn:'left',marg:'12px',styl:'',attr:'font-family:monospace; width:580px; background-color:lightcyan;'); 
        htm_wrapp_end();
        htm_nl(1);
    htm_Card_end(labl:'', icon:'', hint:'', name:'', form:'', subm:false, attr:'', akey:'', kind:'save', simu:false);

   
    // PROGRAM Context-Menu: 
    echo 
        Pmnu_(elem:'tblSpan',capt:'@Regarding PROGRAM:',wdth:'260px',icon:'',stck:'false', cntx:true, rtrn:true).
            Pmnu_Item(labl:'@Program menu:',          icon:'fas fa-wrench fa-sm', hint:'@Program functions. Go to: Admin / Settings, if you want to save permanently', vrnt:'custom',   name:'cust',clck:'',
                      attr:"'background-color: yellow; height: 44px; border-style: solid; border-width: 5px 1px 5px 1px; border-color: lightgray; border-radius: 10px; padding-top: 10px;'' ",shrt:'',enbl:'false',rtrn:true).
            Pmnu_Item(labl:'@Program settings',       icon:'fa fa-cog colrgray',         hint:'@Change program settings',   vrnt:'plain', name:'sett',clck:'console.log(\''.$labl.'\')' , attr:'',shrt:'',enbl:'true',rtrn:true).
            Pmnu_Item(labl:'@User settings',          icon:'fa fa-user colrgray',        hint:'@Change user settings',      vrnt:'plain', name:'user',clck:'console.log(\''.$labl.'\')' , attr:'',shrt:'',enbl:'true',rtrn:true).
            Pmnu_Item(labl:$labl='@Help',             icon:'fa fa-question',             hint:'@Go to program help',        vrnt:'plain', name:'help',clck:'console.log(\''.$labl.'\')'  ,attr:'',shrt:'',enbl:'true',rtrn:true).
            Pmnu_Item(labl:$labl='',                  icon:'',                           hint:'',                           vrnt:'separator',rtrn:true).
            Pmnu_Item(labl:$labl='@Create New Item',  icon:'fa fa-plus-square colrgray', hint:'@Create new file or folder', vrnt:'plain', name:'crea',clck:'console.log(\''.$labl.'\')' , attr:'',shrt:'',enbl:'true',rtrn:true).
            Pmnu_Item(labl:$labl='@Upload',           icon:'fas fa-upload colrgray',     hint:'@Upload local file',         vrnt:'plain', name:'upld',clck:'console.log(\''.$labl.'\')' , attr:'',shrt:'',enbl:'true',rtrn:true).
            Pmnu_Item(labl:$labl='',                  icon:'',                           hint:'',                           vrnt:'separator',rtrn:true).
            Pmnu_Item(labl:$labl='@Logout',           icon:'fas fa-sign-out-alt',        hint:'@Leave the program in locked mode',vrnt:'plain', name:'logu',clck:'console.log(\''.$labl.'\')' ,attr:'',shrt:'',enbl:'true',rtrn:true).
            Pmnu_Item(labl:$labl='@Still an inactive DEMO', icon:'fas fa-info-circle colrred fa-sm', hint:'@Click outside menu to close',     vrnt:'custom', name:'cust',clck:'',
                      attr:"'background-color: lightcyan; height: 24px; border-style: solid; border-width: 5px 1px 5px 1px; border-color: lightgray; border-radius: 10px; padding-top: 10px;' ",shrt:'',enbl:'true',rtrn:true).
        Pmnu_end(labl:'',hint:'',attr:'',rtrn:true).
     
        '<script>'.
        // Modal window:
        '   var modal = document.getElementById("divModal");            '.  // Get the modal div                           
        '   var btn = document.getElementById("btnModalSearch1002");    '.  // Get the button that opens the modal         
        '   var span = document.getElementsByClassName("close")[0];     '.  // Get the <span> element that closes the modal
            // btn.onclick = function() { modal.style.display = "block"; }  // When the user clicks on the button, open the modal
        //s    span.onclick = function() { modal.style.display = "none"; }  // When the user clicks on <span> (x), close the modal
        '   window.onclick = function(event) {                          '.  // When the user clicks anywhere outside of the modal, close it
        '     if (event.target == modal) { modal.style.display = "none"; }
            }            

            
        function ToggShow() { /* When the user clicks the button, toggle between hiding and showing the dropdown content */
          document.getElementById("FileSearch").classList.toggle("show");
        }

        function filterFunction() { 
          var input, filter, ul, li, a, i;
          input = document.getElementById("searchInput");
          filter = input.value.toUpperCase();
          div = document.getElementById("FileSearch");
          a = div.getElementsByTagName("a");
          for (i = 0; i < a.length; i++) {
            txtValue = a[i].textContent || a[i].innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) // fails in some situations ! ?
                 { a[i].style.display = ""; } 
            else { a[i].style.display = "none";
            }
          }
        } 
        </script>'; // Context-Menu
 
    htm_nl(1);
    htm_Card_(capt:'Develop: ',icon:'fas fa-eye',hint: '',form:'',acti:'',clas:'cardW640',
                wdth: '',styl:'background-color: white;',attr:'', show : true, head: 'background-color: white;');
        arrPretty(get_defined_vars(),'Defined_vars:');
    htm_Card_end(labl:'', icon:'', hint:'', name:'', form:'', subm:false, attr:'', akey:'', kind:'save', simu:false);
    
    CardOff($First=1,$Last=1);
    CardOff($First=3,$Last=4);
    
htm_Page_end();
}
        
