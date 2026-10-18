<?php   $DocFil= 'folder-explorer.php';    $DocVer='1.9.0';    $DocRev='2026-10-18';     $DocIni='evs';  $ModulNr=0; ## File informative only
$©= '𝘓𝘐𝘊𝘌𝘕𝘚𝘌 & 𝘊𝘰𝘱𝘺𝘳𝘪𝘨𝘩𝘵 © 2019-24 EV-soft *** See the file: LICENSE';

$sys= $GLOBALS["gbl_ProgRoot"]= './';

## Activate needed libraries: Set 0:deactive  1:Local-source  2:WEB-source-CDN
$needJquery=      '2';
$needTablesorter= '2';
$needPolyfill=    '0';
$needFontawesome= '2';
$needTinymce=     '0';

// The bundled library (php2html 1.7.3) - the calls in this file need 1.7 (an older shared copy in ../p2h/ would fail):
require_once (__DIR__.'/php2html.lib.php');    // Creating HTML-functions

##### SPECIAL this page only:
define ('p2h_IS_WIN', DIRECTORY_SEPARATOR == '\\');
define ('p2h_PATH', ''); // $p);             // $p = pathinfo($p)['dirname'];
defined('p2h_ICONV_INPUT_ENC') || define('p2h_ICONV_INPUT_ENC', 'UTF-8');  // PHP 7.1+ uses UTF-8 filenames on Windows too

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
define ('p2h_READONLY', ($p2h_AppConf['use_auth'] ?? false)
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
    'visitor'   => ['$2y$10$kD49SD5o81IAU1sU/k8e3.59T9ZiGfzgG9jWpc./Y04V6B1hHzP/a','root','r']     // 8x x  - public demo account: download only
);  # To create a new user: Login with wrong password. Error shows you a korrect user: hash comination. Copy this to a new record in $auth_users
    # userRights in the exchange area:  r: download  w: upload and delete

$locked_path= 'ev-soft.work/fe/locked2/demo/';

## Browsing is restricted to this folder and its subfolders (no access above it):
$fe_root_path= $fe_root_path ?? __DIR__;

## Exchange area: the ONLY folder where files may be uploaded, downloaded and deleted (between this server and the user's PC).
## It is created automatically, closed for direct web access (.htaccess), and files are only served through this page.
$fe_exchange_path=  $fe_exchange_path  ?? $fe_root_path.'/_exchange';
$fe_max_upload=     $fe_max_upload     ?? 20 * 1024 * 1024;     // Max size per file (bytes) - PHP's upload_max_filesize / post_max_size also apply
$fe_exchange_quota= $fe_exchange_quota ?? 200 * 1024 * 1024;    // Max total size of the exchange area (bytes)

## "Remove restrictions" (admins only, per session): browse/download from $fe_unlocked_root, upload any file type
## (never hidden files like .htaccess) and no quota / size limit of its own. Upload and delete stay inside the exchange area.
$fe_admins=        $fe_admins        ?? ['fe-admin'];
$fe_unlocked_root= $fe_unlocked_root ?? dirname(p2h_norm_path($_SERVER['DOCUMENT_ROOT'] ?? __DIR__));   // e.g. /home/<account>

function p2h_js($str) { // Encode a PHP string as a safe JavaScript string literal
    return json_encode((string)$str, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

function p2h_norm_path($path) { // Unified slashes, no trailing slash
    return rtrim(str_replace('\\', '/', $path), '/');
}

function p2h_safe_path($requested, $root) { // Resolve $requested and accept it only inside $root
    $root = p2h_norm_path(realpath($root) ?: $root);
    if ($requested === null || $requested === '') return $root;
    $real = realpath($requested);
    if ($real === false) $real = realpath('/'. ltrim($requested, '/'));
    if ($real === false || !is_dir($real)) return $root;
    $real = p2h_norm_path($real);
    return ($real === $root || strpos($real.'/', $root.'/') === 0) ? $real : $root;
}

function fe_in_dir($path, $dir) { // Is $path the folder $dir or below it (both normalized)
    return $dir !== false && $dir !== '' && ($path === $dir || strpos($path.'/', $dir.'/') === 0);
}

function fe_can($right) { // Rights of the logged user in the exchange area: r= download, w= upload/delete
    global $auth_users;
    if (empty($auth_users)) return true;
    $usr= $_SESSION[p2h_SESSION_ID]['logged'] ?? '';
    return isset($auth_users[$usr][2]) && strpos($auth_users[$usr][2], $right) !== false;
}

function fe_exchange_init($dir) { // Create the exchange folder - closed for direct web access
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return false;
    if (!is_file($dir.'/.htaccess'))
        @file_put_contents($dir.'/.htaccess',
            "# Folder-explorer exchange area: files are only served through folder-explorer.php\n".
            "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n".
            "<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n");
    if (!is_file($dir.'/index.html')) @file_put_contents($dir.'/index.html', '');
    $real= realpath($dir);
    return $real === false ? false : p2h_norm_path($real);
}

function fe_clean_name($name) { // A plain file name: no path, no control or reserved characters
    $name= basename(str_replace('\\', '/', (string)$name));
    $name= preg_replace('/[\x00-\x1F\x7F<>:"\/\\\\|?*]/u', '_', $name) ?? '';
    return trim($name, " .");
}

const FE_DENY_EXT= '\.(php\d*|phtml|phar|pht|phps|cgi|pl|py|sh|asp|aspx|jsp|htaccess|htpasswd|ini)(\.|$)';   // Also used by fe-upload.js

function fe_name_allowed($name) { // Never server scripts, server config or hidden files
    if ($name === '' || $name[0] === '.') return false;                  // Hidden files (.htaccess ...) - also without restrictions
    if ($GLOBALS['fe_unlocked'] ?? false) return true;                   // Admin has removed the restrictions
    if (strcasecmp($name, 'index.html') === 0) return false;
    return !preg_match('/'.FE_DENY_EXT.'/i', $name);
}

function fe_hint($html, $hint, $styl= '') { // The library hint (not the title tooltip of the browser) - opens just below $html
    return '<abbr class="hint feHint"'.($styl > '' ? ' style="'.$styl.'"' : '').'>'.$html.'<data-hint>'.$hint.'</data-hint></abbr>';
}

function fe_is_admin() { // The logged user may remove the restrictions
    return in_array($_SESSION[p2h_SESSION_ID]['logged'] ?? '', $GLOBALS['fe_admins'] ?? [], true);
}

function fe_send_file($file) { // Send a file as a download, and stop
    while (ob_get_level()) ob_end_clean();
    $name= basename($file);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.preg_replace('/[^\x20-\x7E]|"/', '_', $name).'"; filename*=UTF-8\'\''.rawurlencode($name));
    header('Content-Length: '.filesize($file));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($file);
    exit;
}

function fe_rel_dir($base, $rel) { // Create the subfolders of a relative upload path ("photos/2026/a.jpg") below $base
    $parts= array_slice(explode('/', str_replace('\\', '/', $rel)), 0, -1);    // Without the file name
    if (count($parts) > 10) return false;
    $dir= $base;
    foreach ($parts as $part) {
        if (substr(trim($part), 0, 1) === '.') return false;   // ".", ".." and hidden folders (.git ...)
        $part= fe_clean_name($part);
        if ($part === '' || $part[0] === '.') return false;
        $dir.= '/'.$part;
        if (!is_dir($dir) && !@mkdir($dir, 0755)) return false;
    }
    return $dir;
}

function fe_dir_size($dir) { // Total size of all files below $dir
    $size= 0;
    try {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                     RecursiveIteratorIterator::LEAVES_ONLY, RecursiveIteratorIterator::CATCH_GET_CHILD) as $f)
            if ($f->isFile()) $size+= $f->getSize();
    } catch (Exception $e) { }
    return $size;
}

function fe_ini_bytes($val) { // php.ini size ("8M") to bytes
    $val= trim((string)$val);   $num= (float)$val;
    switch (strtolower(substr($val, -1))) { case 'g': $num*= 1024; case 'm': $num*= 1024; case 'k': $num*= 1024; }
    return (int)$num;
}

function fe_msg($html, $status= 'ok') { // Message shown on the next page (already translated and escaped)
    $_SESSION[p2h_SESSION_ID]['message']= $html;
    $_SESSION[p2h_SESSION_ID]['status']=  $status;
}

function fe_redirect($dir) { // Post/Redirect/Get - back to the folder
    header('Location: '.basename(__FILE__).'?show='.urlencode($dir), true, 303);
    exit;
}

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
    if (PHP_INT_SIZE >= 8) { return @filesize($file); }    // 64-bit PHP handles large files natively
    if (!isset($iswin))      {$iswin = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN'); }
    if (!isset($isdarwin))   {$isdarwin = (strtoupper(substr(PHP_OS, 0)) == "DARWIN"); }    //  MacOS
    if (!isset($exec_works)) {$exec_works = (function_exists('exec') && !ini_get('safe_mode') && @exec('echo EXEC') == 'EXEC'); }

    if ($exec_works) {    // try a shell command
        $arg = escapeshellarg($file);   // Never put a raw filename in a shell command
        $cmd = ($iswin) ? "for %F in ($arg) do @echo %~zF" : ($isdarwin ? "stat -f%z $arg" : "stat -c%s $arg");
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
    global $is_archive, $is_image, $is_audio, $is_video, $is_text;
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
    
    .file-name-div {   /* live-preview */
        position: relative;
    }
    .live-preview-img {
        display: none;
        border: 1px solid lightgray;
        position: absolute;
        top: 1.4em;
        left: 40px;
        z-index: 10;
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
 
/* fe_hint(): the hint opens below its own element (abbr.hint data-hint is otherwise placed from the nearest positioned box) */
/* Folder tree: drawn lines (├ └ │) - one level = one step of the Level ruler */
ul.feTree { list-style: none; margin: 0; padding: 0 0 0 7px; line-height: 1.25; }      /* The line just below the icon of the parent folder */
ul.feTree:not(li > ul) { padding-left: 30px; }      /* Room for the level numbers at the far left */
ul.feTree:not(li > ul) > li { padding-left: 28px; }  /* Then one level = 50px */
ul.feTree:not(li > ul) > li::before { width: 23px; }
ul.feTree li { position: relative; padding: 1px 0 1px 43px; white-space: nowrap; }    /* 7 + 43 = 50px: one step of the Level ruler */
ul.feTree li::before { content: ""; position: absolute; left: 0; top: 0; width: 38px; height: 0.8em;
    border-left: 1px solid #9aa5b1; border-bottom: 1px solid #9aa5b1; border-bottom-left-radius: 4px; }
ul.feTree li::after { content: ""; position: absolute; left: 0; top: 0.8em; bottom: 0; border-left: 1px solid #9aa5b1; }
ul.feTree li:last-child::after { display: none; }
ul.feTree .feTreeIc { color: #d9a400; margin-right: 5px; font-size: 0.9em; }
ul.feTree a { text-decoration: none; }
ul.feTree a:hover { text-decoration: underline; }
ul.feTree .feLvl { position: absolute; width: 22px; top: 2px; text-align: right; font: 11px monospace; color: #8a94a0; }
ul.feTree li.feHere > .feLvl { color: #b07d00; font-weight: bold; }
.feRuler { position: relative; height: 18px; margin-top: 6px; border-top: 1px dashed #c5ccd3; font: 11px monospace; color: #8a94a0; white-space: nowrap; }
.feRuler span { position: absolute; top: 3px; }
ul.feTree .feTreeSize { font: 11px monospace; color: #5d6b78; }
ul.feTree .feTreeNum { display: inline-block; min-width: 10ch; text-align: right; }
ul.feTree .feSizeCol { position: absolute; top: 2px; }    /* One column for all levels: --feSizeX is set from the widest folder name */
ul.feTree .feTreeBar { display: inline-block; width: 50px; height: 7px; margin-right: 5px; border: 1px solid #b9c3cc; border-radius: 3px; vertical-align: middle; background: #f3f5f7; overflow: hidden; }
ul.feTree .feTreeBar > span { display: block; height: 100%; background: #3c8dbc; }
ul.feTree li.feHere > abbr > a { background: #fff3c4; border-radius: 3px; padding: 0 4px; }
abbr.hint.feHint { position: relative; display: inline-block; text-decoration: none; }
abbr.hint.feHint:hover data-hint, abbr.hint.feHint:focus-within data-hint { left: 0; top: calc(100% + 4px); }

/* Exchange area upload (fe-upload.js) */
.fe-drop { border: 2px dashed #9bbbd6; border-radius: 10px; padding: 12px; margin: 6px 0; text-align: center; background: #f7fbff; }
.fe-drop.fe-over { border-color: green; background: #eaffea; }
.fe-js { display: none; }
#feUpload.fe-ready .fe-js { display: inline-block; }
#feUpload.fe-ready .fe-pick input { position: absolute; width: 1px; height: 1px; opacity: 0; }   /* Hidden, but still reachable with the keyboard */
.fe-pick:focus-within { outline: 2px solid DodgerBlue; }
.fe-pick { position: relative; }
.fe-pick { cursor: pointer; display: inline-block; margin: 6px 4px 0; }
.fe-list table { border-collapse: collapse; width: 100%; margin: 6px 0; font-size: 12px; }
.fe-list th, .fe-list td { border-bottom: 1px solid #ddd; padding: 2px 6px; text-align: left; }
.fe-list td.fe-size { text-align: right; font-family: monospace; white-space: nowrap; }
.fe-list .fe-bad  { color: #b00020; }
.fe-list .fe-good { color: #1a6e1a; }
.fe-list .fe-wrap { max-height: 260px; overflow: auto; }

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
        if (PHP_VERSION_ID < 80100) finfo_close($finfo);   // Freed automatically since 8.1 (deprecated in 8.5)
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
      try {
        $iter= new RecursiveIteratorIterator(
                 new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                 RecursiveIteratorIterator::SELF_FIRST,
                 RecursiveIteratorIterator::CATCH_GET_CHILD);   // Skip unreadable subfolders instead of failing
        foreach ($iter as $fileinfo) {
          if ($fileinfo->isFile()) {
            $size +=     $fileinfo->getSize();
            $arrNames[]= p2h_norm_path($fileinfo->getPathname());
            $fileCount++;
          }
          elseif ($fileinfo->isDir()) $dirCount++;
        }
      } catch (Exception $e) { /* Unreadable folder: return what was found */ }
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
    $filenames = false;     // Archive listing is not implemented yet
    $content = '';

$result= '
    <div class="left" style=" /* width: 320px; */ overflow-x:visible; margin-left: 5px; background-color: white;">
        <div class="break-word"><i>Content:</i> <b>'. p2h_enc($view_title). /* ' '. p2h_enc(p2h_convert_win($file)).  */'</b></div>'.
        '<i>MIME-type:</i> '.p2h_enc($mime_type). '<br>'.
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
        $result.= fe_hint('<b><a download '.urlQuery(p2h_PATH,'dl',$file_url).'><i class="fa fa-download colrDwn fa-sm"></i> '.
                    lang('@Download').'</a></b>', lang('@Get a copy locally')).' &nbsp;&nbsp;&nbsp;';
        if (!p2h_READONLY) { // prevent "File not found!" 
            $result.= fe_hint('<b><a target="_blank" href="'. p2h_enc($file_url). '"><i class="fa fa-external-link-square-alt colrShw fa-sm"></i> '.
                    lang('@Open').'</a></b>', lang('@View the file content')).' &nbsp;&nbsp;&nbsp;';
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

function listFolders($dir,$currentDir='',$depth=0) {    // Echoes the tree below $dir - returns the total size of the files below $dir when the sizes are shown (else 0)
    $sizes= $GLOBALS['fe_treesize'] ?? false;       // The card tool "Show used space"
    if ($depth > 14) return $sizes ? fe_dir_size($dir) : 0;     // Same depth as the Level axis below the tree
    $pathOnly= $GLOBALS['fe_unlocked'] ?? false;    // Whole account: only the path to the current folder is opened (else far too many folders)
    $arrItems = @scandir($dir);
    if (!is_array($arrItems)) return 0;  // Unreadable folder
    $subs= [];  $total= 0;
    foreach ($arrItems as $item) {
        if ($item === '.' || $item === '..') continue;
        if (is_dir($dir.'/'.$item)) { if (!is_link($dir.'/'.$item)) $subs[]= $item; }  // Do not follow symlinks (loops)
        elseif ($sizes && is_file($dir.'/'.$item)) $total+= (int)@filesize($dir.'/'.$item);
    }
    if (!$subs) return $total;           // No sub folders: no empty list
    $rows= [];
    foreach ($subs as $item) {          // The sub trees first: the share of each folder needs the total of this folder
        $full= p2h_norm_path($dir.'/'.$item);
        $here= ($full === $GLOBALS['currPath']);                            // The folder shown below
        $onPath= !$here && fe_in_dir($GLOBALS['currPath'], $full);          // A folder above it
        ob_start();
        $size= (!$pathOnly || $here || $onPath) ? listFolders($dir.'/'.$item,$currentDir,$depth+1)
                                                : ($sizes ? fe_dir_size($dir.'/'.$item) : 0);
        $rows[]= [$item, $here, $onPath, $size, ob_get_clean()];
        $total+= $size;
    }
    $lvlX= $depth ? 65 + ($depth-1)*50 : 30;                               // Back to the left edge of the tree
    echo '<ul class="feTree">';
    foreach ($rows as [$item, $here, $onPath, $size, $kids]) {
        echo '<li'.($here ? ' class="feHere"' : '').'>'.
             '<span class="feLvl" style="left: -'.$lvlX.'px;">'.$depth.'</span>'.
             '<i class="'.($here || $onPath ? 'fas fa-folder-open' : 'fas fa-folder').' feTreeIc"></i>'.
             fe_hint('<a href= "'.basename(__FILE__).'?show='.urlencode($dir.'/'.$item). '">'.
                 ($currentDir==$item ? '<b>' : ''). p2h_enc($item). ($currentDir==$item ? '</b>' : '').
             '</a>', p2h_enc($dir).'/');
        if ($sizes) {
            $pct= $total > 0 ? $size / $total * 100 : 0;
            $liX= $depth ? 35 + ($depth-1)*50 : 0;                         // This level from level 0
            echo '<span class="feSizeCol" style="left: calc(var(--feSizeX, 400px) - '.(30 + $liX).'px);">'.fe_hint('<span class="feTreeSize">'.
                     '<span class="feTreeBar"><span style="width: '.round($pct, 1).'%;"></span></span><span class="feTreeNum">'.p2h_nice_filesize($size).'</span></span>',
                     number_format((float)$size, 0, ',', ' ').' Bytes<br>'.round($pct).' % '.lang('@of the parent folder')).'</span>';
        }
        echo $kids.'</li>';
    }
    echo '</ul>';
    return $total;
}

function p2h_fileExplore($path,$parentDir) {
    global $exch_path, $in_exchange, $fe_unlocked;
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
            $ext= fe_hint('<a href="'.basename(__FILE__).'?show='.urlencode($file_path).'&amp;calc=true">
                        <pre style="margin: 3px 0 0;"><i style="margin:0 8px;" class="far fa-folder colr0 bgclgold"></i>'.lang('@FOLDER').'</pre>'.
                  '</a>', lang('@Analyse folder content'));
            $foldhint = ' data-title="'.lang('@Calculation of files in folder, is disabled').'" title="" ';
            $showsize = '<div style="width:100%;text-align:center;">'.fe_hint('-', lang('@Not calculated ! - Calculation of files in folder, is disabled')).'</div>';
        }
        // $sortsize = '<span style="display:none;">'.str_pad($foldsize, 18, "0", STR_PAD_LEFT).' </span>';
        $sortsize = '<span class="sortPrefix">'. $foldsize.  ' </span>';
        $ftime = filemtime($file_path); //  Sort criteria
        $sorttime = '<span class="sortPrefix">'.$ftime.'</span>';
        $modif = date('Y-m-d H:i', $ftime);
        $perms = substr(decoct(fileperms($file_path)), -4);
        if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
                $owner = posix_getpwuid(fileowner($file_path)) ?: ['name' => fileowner($file_path)];
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
                          $hint='@Create a ZipClone/backup of folder:'. '<b>'.p2h_enc($path.'/'.$fld).'</b><br>Files with prefix: _CLONE. will not be included! <br>(it is created with ZipClone-backup) ').
             '</div>';
            // if (isset($_GET['zipClone'.$ix])) { echo p2h_folder_backup($currDir=$path.'/'.$fld, $subdirs= 3); unset($_GET['zipClone'.$ix]); }
    } else { $buttns= ''; $meter= 0; }
  //      onclick="window.open('http://kanishkkunal.in','popup','width=600,height=600,scrollbars=no,resizable=no'); return false;">
  
        $rec['ix']= $ix;
        $rec['name']= '<div style="white-space: nowrap; margin: 3px 0 0 0;">'. '<div style="display: inline;"><i class="far fa-folder colr0" style="background-color: gold;"></i>'.
          // str_WithHint($labl='<a href="'.basename(__FILE__). '?show='.$path.'/'.$fld. '" class="txtbutt">&nbsp;'. $fld.'</a>', 
             str_WithHint($labl='<a href="'.basename(__FILE__). '?show='.urlencode($file_path). '" class="txtbutt">&nbsp;'. p2h_enc($fld).'</a>',
             $hint=lang('@Show content in subfolder: <b>').p2h_enc($fld).'</b>').'</div>'. $buttns. '</div>';
        $rec['ext']= $ext;
        $rec['size']= $sortsize.'<div style="font-family:monospace; color:green;">'.fe_hint($showsize, number_format((float)$foldsize, 0,',',' ').' Bytes').'</div>';
        $rec['modifyed']= $sorttime.$modif;
        $rec['perms']= $perms;
        $rec['owner']= p2h_enc($owner['name']);      //$rec['group']= $group['name'];
        $rec['access']= '-';
        $rec['space']= '<meter id="currFold'.$ix.'" low="0.15" optimum="0.30" high="0.60" max="1" value="'.$meter.'" style= "width: 95%;">'.$meter.'</meter>
                        <small style="/* width: 40px; */ display: inline-block; text-align: right; left: 40%; top: -16px; position: relative; background: lightyellow; opacity: 0.7;">'.number_format($meter*100,2).' %</small>';
        $foldRecords[]= $rec;
        
        $Context_Row.=
        // FOLDER Context-Menu: 
        Pmnu_($elem='tabl_row'.($ix-1000),$capt='@<small>FOLDER:</small> '. // $capt='@Regarding FOLDER:<br>'.
                                                // '<i class="'.$icoc.' fa-sm"></i>&nbsp;'. // Fails
                                                 '<b>'.p2h_enc($fld).'</b>',
                                                 $wdth='260px', icon:'', stck:'false', cntx:true, rtrn:true).
       // Pmnu_($elem='tabl_row'.($ix-1000),$capt='@Regarding FOLDER:',$wdth='260px',$icon='far fa-folder colr0 bgclgold',$stck='false',$attr='background-color:lightcyan;',$cntx=true,$rtrn=true).
       //     Pmnu_Item($labl='<b>'.$fld.'</b><br>', $icon='far fa-folder colr0 bgclgold',   $hint='', $type='custom',  $name='cust',$clck='', $attr="'background-color: white; height: 44px; border-style: solid; border-width: 5px 1px 5px 1px; border-color: lightgray; border-radius: 8px; padding-top: 10px;'' ",$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='@Rename folder',       $icon='fas fa-pen-square colrorange',  $hint='@Give another name',        $type='plain',  $name='renm',$clck='console.log('.p2h_js($fld).')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='@Delete folder',       $icon='fas fa-trash-alt colrred',      $hint='@Erase the folder',         $type='plain',  $name='dele',$clck='console.log('.p2h_js($fld).')' ,$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='',                     $icon='',                               $hint='',                                                                  $type='separator',rtrn:true).
            Pmnu_Item($labl='@Copy folder',         $icon='fas fa-copy colrbrown',          $hint='@Make a copy with a new name',                                      $type='plain',  $name='dnld',$clck='fileview('.p2h_js($currentPath).','.p2h_js($fld).')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='@Create ZIP',          $icon='far fa-file-archive colrbrown',  $hint='@Make a compressed file with the content of this subfolder',        $type='plain',  $name='crea',$clck='fileview('.p2h_js($currentPath).','.p2h_js($fld).')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='@Syncronize',          $icon='fas fa-sync colrbrown',          $hint='@Create a ZipClone/backup of this folder with another selected...', $type='plain',  $name='crea',$clck='fileview('.p2h_js($currentPath).','.p2h_js($fld).')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='@Go to',               $icon='fas fa-chevron-right colrblack', $hint='@Look at this subfolder',                                           $type='plain',  $name='upld',$clck='fileview('.p2h_js($currentPath).','.p2h_js($fld).')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='',                     $icon='',                               $hint='',                                                                  $type='separator',rtrn:true).
            Pmnu_Item($labl='@Folder property',     $icon='fa-solid fa-binoculars colrblue',$hint='@Look at the folder property',                                      $type='plain',  $name='prop',$clck='fileview('.p2h_js($currentPath).','.p2h_js($fld).')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='@Create new folder',   $icon='fas fa-plus colrgray',           $hint='@Make a new folder',                                                $type='plain',  $name='dnld',$clck='fileview('.p2h_js($currentPath).','.p2h_js($fld).')',$attr='',$akey='',$enabl='true',$rtrn=true).
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
            $owner = posix_getpwuid(fileowner($file_path)) ?: ['name' => fileowner($file_path)];
            $group = posix_getgrgid(filegroup($file_path));
            $posix = lang('@Owner : Group');
        } else {
            $owner = array('name' => fileowner($file_path));
            $group = array('name' => filegroup($file_path));
            $posix = lang('@PHP posix functions is not accessible! Numeric id is shown in sted of name.');
        }
        if ($calc_folder) {
            $meter= $maxsize > 0 ? $filesize_raw / $maxsize : 0;
            $mime_type = p2h_get_mime_type($file_path);
            $fileInfo= p2h_fileView($view_title, $fld, $ext, $file_path, $filesize_raw, $mime_type);
            $preview= '';
            if ($ix < 1200) // Max 200 previews on one page ($ix starts at 1000)
                $preview= '<div class="live-preview-img">'.
                          ($view_title == 'Image' ? '<img src="'. p2h_enc($currentPath. $fld). '" alt="'.$view_title.'" loading="lazy">' : '').
                          $fileInfo.'</div>';
            $nameFld= '<div class="file-name-div"><i class="'.$icoc.'"></i>&nbsp;'. p2h_enc($fld). $preview. '</div>';
        } else
            $nameFld= '<i class="'.$icoc.'"></i>&nbsp;'. p2h_enc($fld);
        // In the exchange area: the name is a download link, and Download / Delete in the menu work
        $exch_file= $in_exchange && fe_name_allowed($fld);
        $dlUrl= $exch_file   ? basename(__FILE__).'?dl='.rawurlencode(ltrim(substr(p2h_norm_path($file_path), strlen($exch_path)), '/')) :
               ($fe_unlocked ? basename(__FILE__).'?dla='.rawurlencode(p2h_norm_path($file_path)) : '');
        $can_dl= ($exch_file && fe_can('r')) || $fe_unlocked;
        if ($can_dl)
            $nameFld= fe_hint('<a href="'.p2h_enc($dlUrl).'" download><i class="fas fa-download colr9"></i></a>', lang('@Download to your PC')).' '.$nameFld;
        $clckDl=  $can_dl ? 'location.href='.p2h_js($dlUrl)  : 'fileview('.p2h_js($currentPath).','.p2h_js($fld).')';
        $clckDel= ($exch_file && fe_can('w')) ? 'feDelete('.p2h_js($fld).')'     : 'fileview('.p2h_js($currentPath).','.p2h_js($fld).')';
        if ($ext>'') $ext = '.'.p2h_enc($ext);
        $rec['ix']= $ix;
        $rec['name']= $nameFld;
        $rec['ext']= $ext;
        $rec['size']= $sortsize.'<div>'.fe_hint($showsize, number_format((float)$filesize_raw, 0,',',' ').' Bytes').'</div>';
        $rec['modifyed']= $sorttime.$modif;
        $rec['perms']= $perms;
        $rec['owner']= p2h_enc($owner['name']);      //$rec['group']= $group['name'];
        $rec['access']= $accetime.$acces;
        $rec['space']= '<meter id="currFile'.$ix.'" low="0.15" optimum="0.30" high="0.60" max="1" value="'.$meter.'" style= "width: 95%;">'.$meter.'</meter>
                        <small style="text-align: right; display: inline-block; left: 40%; top: -16px; position: relative; background: lightyellow; opacity: 0.7;">'.number_format($meter*100,2).' %</small>';
        $fileRecords[]= $rec;

    $Context_Row.=
        // FILE Context-Menu: 
        Pmnu_($elem='tabl_row'.($ix-1000),$capt='@<small>FILE:</small> '. '<b>'.p2h_enc($fld).'</b>',
                                          $wdth='260px', icon:'', stck:'false', cntx:true, rtrn:true).
            Pmnu_Item($labl='@Preview',        $icon='far fa-eye colrblack',         $hint='@Look at content in viewer',     $type='plain', $name='view', $clck='seecontent('.p2h_js($currentPath).','.p2h_js($fld).')', $attr='', $akey='', $enabl='true', $rtrn=true).
            Pmnu_Item($labl='@Edit content',   $icon='far fa-edit colrgreen',        $hint='@Open file in editor',           $type='plain', $name='edit', $clck='window.open(\'TEST\',\'_self\')', $attr='',$akey='', $enabl='true', $rtrn=true).
            Pmnu_Item($labl='@Rename file',    $icon='fas fa-pen-square colrorange', $hint='@Give another name',             $type='plain', $name='renm', $clck='fileview('.p2h_js($currentPath).','.p2h_js($fld).')', $attr='',$akey='', $enabl='true', $rtrn=true).
            Pmnu_Item($labl='@Delete file',    $icon='fas fa-trash-alt colrred',     $hint='@Erase the file',                $type='plain', $name='dele', $clck=$clckDel, $attr='',$akey='', $enabl='true', $rtrn=true).
            Pmnu_Item($labl='',                $icon='', $hint='', $type='separator', rtrn:true).
            Pmnu_Item($labl='@Copy file',      $icon='fas fa-copy colrbrown',           $hint='@Make a copy with a new name or in another folder', $type='plain',$name='dnld',$clck='fileview('.p2h_js($currentPath).','.p2h_js($fld).')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='@Create ZIP',     $icon='far fa-file-archive colrbrown',   $hint='@Make a compressed file with the content of this file', $type='plain', $name='crea',$clck='fileview('.p2h_js($currentPath).','.p2h_js($fld).')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='@Download',       $icon='fas fa-download colrbrown',       $hint='@Download to local storage',    $type='plain',$name='dnld',$clck=$clckDl,$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='@File properties',$icon='fa-solid fa-binoculars colrblue', $hint='@Look at the files properties', $type='plain',$name='prop',$clck='fileview('.p2h_js($currentPath).','.p2h_js($fld).')',$attr='',$akey='',$enabl='true',$rtrn=true).
            Pmnu_Item($labl='',                $icon='', $hint='', $type='separator', rtrn:true).
            Pmnu_Item($labl=($exch_file ? '@Download / Delete work here - the rest is DEMO' : '@Still an inactive DEMO'), $icon='fas fa-info-circle colrred fa-sm', $hint='@Click outside menu to close', $type='custom',$name='info',$clck='', $attr="'background-color: lightcyan; height: 24px; border-style: solid; border-width: 5px 1px 5px 1px; border-color: lightgray; border-radius: 10px; padding-top: 10px;' ",$attr='',$akey='',$enabl='true',$rtrn=true).
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
<span>'.     // (htm_Input below has the hint)
    htm_Input($labl= 'Search', plho: '@Enter...', icon: '', hint: '@Search for a file in sub-folders, and open that folder',
              vrnt: $type= 'html', name: 'fsearch',
              valu: '<div><i class="fa fa-search"></i> '.($calc_folder== true ? count($arrNames).' '.lang('@Files in subfolders') : lang('@Not active.')).'</div>',
              form: '', wdth: '200px', algn: 'left',attr: 'onclick="ToggShow()" class="dropbtn" ',rtrn: true,
              unit: '', disa: (($_POST['prgMode'] ?? '') != 'mode_A'), rows: '0', step: '', list: [], llgn: 'R', bord: '', ftop: '')
              . '
    <div id="FileSearch" class="dropdown-content" 
        >
        <input type="text" placeholder="'.p2h_enc(lang('@Part of a file or folder name...')).'" id="searchInput" onkeyup="filterFunction()" z-index:202;
            aria-label="'. p2h_enc(lang('@Type a search string: Part of the file- / folder-name')).'"
            style="width:200px; text-align:left; font-family:arial; border: 1px solid black;">
        <div style="font-size: 11px; color: gray; text-align: left; padding: 2px 6px;">'. lang('@Click to open folder containing this file ') .'</div>';
        $name_list= ''; // List to search for file names in sub-folders 
        foreach($arrNames as $file) {
            $pos= strrpos($file,'/')+1;
            $dir= substr($file,0,$pos);
            $fil= substr($file,$pos);
            $name_list .= '<a href="'. basename(__FILE__). '?show='.urlencode(rtrim($dir,'/')).'" >'.
                        '<span class="colrgray">'.p2h_enc(strlen($dir) > 32 ? '...'.substr($dir,-32) : $dir).':</span> '. p2h_enc($fil).'</a>';
            }
            $result.= '<div style= "text-align:left;">'.$name_list.'</div>';
    $result.= '
    </div>
</span>';
return $result;
}

// arrPretty($_POST,'$_POST');
// arrPretty(get_defined_vars(),'Defined_vars:');


##### AUTHENTICATION:  -  must run before any file-system data is collected
if (empty($auth_users)) $use_auth = false; else $use_auth = true;

if (isset($_GET['logout'])) {
    unset($_SESSION[p2h_SESSION_ID]['logged'], $_SESSION[p2h_SESSION_ID]['unlocked']);
}

if ($use_auth) {
    $logged= $_SESSION[p2h_SESSION_ID]['logged'] ?? null;
    if ($logged !== null && isset($auth_users[$logged])) {
        // Logged:
    } elseif (isset($_POST['p2h_usr'], $_POST['p2h_pwd'])) {
        // Logging In:
        unset($_SESSION[p2h_SESSION_ID]['logged']);
        sleep(1);   // Slow down password guessing
        $usr= (string)$_POST['p2h_usr'];
        $pwd= (string)$_POST['p2h_pwd'];
        if (isset($auth_users[$usr][0]) && password_verify($pwd, $auth_users[$usr][0])) {
            if (!headers_sent()) session_regenerate_id(true);    // Prevent session fixation
            $_SESSION[p2h_SESSION_ID]['logged'] = $usr;
            unset($_SESSION[p2h_SESSION_ID]['unlocked']);         // A new login always starts with the restrictions
            p2h_set_msg(lang('@You are logged in as: ').p2h_enc($usr));
        } else {
            // Shows a hash of the typed password, so a new account can be added to $auth_users:
            $temp= p2h_enc($usr).': '.password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 12]);
            p2h_set_msg('@Login failed: Invalid account name and/or password. ', 'error');
            $_SESSION[p2h_SESSION_ID]['message'].= ' '.$temp;
        }
    } else unset($_SESSION[p2h_SESSION_ID]['logged']);
} else $_SESSION[p2h_SESSION_ID]['logged']= $_SESSION[p2h_SESSION_ID]['logged'] ?? 'guest';

$is_logged= isset($_SESSION[p2h_SESSION_ID]['logged']);

##### REMOVE RESTRICTIONS (admins only, this session only)
if ($is_logged && isset($_POST['fe_unlock'])) {
    if (!fe_is_admin())     fe_msg(p2h_enc(lang('@Only an administrator can remove the restrictions')), 'error');
    elseif (!csrf_verify()) fe_msg(p2h_enc(lang('@The form has expired - reload the page and try again')), 'error');
    elseif ($_POST['fe_unlock'] === '1') {
        $_SESSION[p2h_SESSION_ID]['unlocked']= true;
        fe_msg(p2h_enc(lang('@The restrictions are removed for this session')), 'error');
    } else {
        unset($_SESSION[p2h_SESSION_ID]['unlocked']);
        fe_msg(p2h_enc(lang('@The restrictions are active again')));
    }
    fe_redirect(is_string($_POST['show'] ?? null) ? $_POST['show'] : '');
}
$fe_unlocked= $is_logged && fe_is_admin() && !empty($_SESSION[p2h_SESSION_ID]['unlocked']);
if ($fe_unlocked) {                     // No size limit of its own - PHP's upload_max_filesize / post_max_size still apply
    $fe_max_upload=     PHP_INT_MAX;
    $fe_exchange_quota= PHP_INT_MAX;
}


##### PREPARE DATA:
$calc_folder= (($_POST['prgMode'] ?? '') == 'mode_A');
if (isset($_GET['calc'])) {
    $calc_folder= true;
}

$root_path= p2h_norm_path(($fe_unlocked ? realpath($fe_unlocked_root) : false) ?: realpath($fe_root_path) ?: $fe_root_path);
$currPath= p2h_safe_path($_POST['show'] ?? $_GET['show'] ?? '', $root_path);  // Never outside $root_path

##### EXCHANGE AREA: download / upload / delete - only inside $fe_exchange_path, only for logged users with the right
$exch_path=   $is_logged ? fe_exchange_init($fe_exchange_path) : false;
$in_exchange= $exch_path !== false && fe_in_dir($currPath, $exch_path);
$exch_dest=   $in_exchange ? $currPath : $exch_path;    // Uploads go to the current folder, when it is in the exchange area

if ($exch_path !== false && isset($_GET['dl'])) {      // Download a file to the user's PC
    $file= realpath($exch_path.'/'.ltrim(str_replace('\\', '/', (string)$_GET['dl']), '/'));
    if (!fe_can('r') || $file === false || !is_file($file)
        || !fe_in_dir(p2h_norm_path($file), $exch_path) || !fe_name_allowed(basename($file))) {
        http_response_code(404);
        exit(p2h_enc(lang('@File not found')));
    }
    fe_send_file($file);
}

if ($fe_unlocked && isset($_GET['dla'])) {              // Admin without restrictions: download any file below $root_path
    $file= realpath((string)$_GET['dla']);
    if ($file === false || !is_file($file) || !fe_in_dir(p2h_norm_path($file), $root_path)) {
        http_response_code(404);
        exit(p2h_enc(lang('@File not found')));
    }
    fe_send_file($file);
}

if ($exch_path !== false && isset($_FILES['fe_upload'])) {     // Upload files from the user's PC (form, or batches from fe-upload.js)
    $ajax= ($_POST['fe_ajax'] ?? '') === '1';   // fe-upload.js wants a JSON answer per batch
    $done= [];  $fail= [];  $error= '';         // done: [index, saved path]   fail: [index, name, reason]
    if (!fe_can('w'))       $error= lang('@Your account may not upload files');
    elseif (!csrf_verify()) $error= lang('@The form has expired - reload the page and try again');
    else {
        $up= $_FILES['fe_upload'];
        foreach (['name','tmp_name','size','error'] as $k) $up[$k]= (array)$up[$k];
        $rels= (array)($_POST['fe_relpath'] ?? []);     // Relative paths of files from a chosen / dropped folder
        $used= fe_dir_size($exch_path);
        foreach ($up['name'] as $i => $orig) {
            $err= (int)$up['error'][$i];    $size= (int)$up['size'][$i];    $name= fe_clean_name($orig);
            if (substr(trim(basename(strtr((string)$orig, '\\', '/'))), 0, 1) === '.') $name= '.'.$name;   // A hidden file stays hidden - and is refused
            if ($err === UPLOAD_ERR_NO_FILE) continue;
            $dir= fe_rel_dir($exch_dest, is_string($rels[$i] ?? null) ? $rels[$i] : '');
            if     ($err !== UPLOAD_ERR_OK)          $why= lang('@upload error').' '.$err.($err <= 2 ? ' - '.lang('@the file is too large') : '');
            elseif (!fe_name_allowed($name))         $why= lang('@this file type is not allowed');
            elseif ($size > $fe_max_upload)          $why= lang('@the file is too large');
            elseif ($used + $size > $fe_exchange_quota) $why= lang('@the exchange area is full');
            elseif ($dir === false)                  $why= lang('@the folder name is not allowed');
            else {
                $target= $dir.'/'.$name;    $n= 2;      // Never overwrite: "name (2).ext"
                while (file_exists($target))
                    $target= $dir.'/'.pathinfo($name, PATHINFO_FILENAME).' ('.$n++.')'.(pathinfo($name, PATHINFO_EXTENSION) > '' ? '.'.pathinfo($name, PATHINFO_EXTENSION) : '');
                if (move_uploaded_file($up['tmp_name'][$i], $target)) {
                    @chmod($target, 0644);  $used+= $size;
                    $done[]= [$i, ltrim(substr($target, strlen($exch_dest)), '/')];  continue;
                }
                $why= lang('@could not be saved');
            }
            $fail[]= [$i, $name ?: (string)$orig, $why];
        }
    }
    if ($ajax) {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $error, 'done' => $done, 'fail' => $fail]);
        exit;
    }
    $failTxt= implode(', ', array_map(fn($f) => p2h_enc($f[1]).' ('.p2h_enc($f[2]).')', $fail));
    if ($error)    fe_msg(p2h_enc($error), 'error');
    elseif ($done) fe_msg(p2h_enc(lang('@Uploaded')).': <b>'.implode(', ', array_map(fn($d) => p2h_enc($d[1]), $done)).'</b>'.
                          ($fail ? '<br>'.p2h_enc(lang('@Not uploaded')).': '.$failTxt : ''), $fail ? 'error' : 'ok');
    else           fe_msg(p2h_enc(lang('@Not uploaded')).': '.($fail ? $failTxt : p2h_enc(lang('@no file selected'))), 'error');
    fe_redirect($exch_dest);
}

if ($exch_path !== false && isset($_POST['fe_delete'])) {      // Delete a file in the exchange area
    $name= fe_clean_name($_POST['fe_delete']);
    $file= realpath($exch_dest.'/'.$name);
    if (!fe_can('w'))       fe_msg(p2h_enc(lang('@Your account may not delete files')), 'error');
    elseif (!csrf_verify()) fe_msg(p2h_enc(lang('@The form has expired - reload the page and try again')), 'error');
    elseif ($file === false || !is_file($file) || !fe_in_dir(p2h_norm_path($file), $exch_path) || !fe_name_allowed($name))
                            fe_msg(p2h_enc(lang('@File not found')).': '.p2h_enc($name), 'error');
    elseif (@unlink($file)) fe_msg(p2h_enc(lang('@Deleted')).': <b>'.p2h_enc($name).'</b>');
    else                    fe_msg(p2h_enc(lang('@Could not delete')).': '.p2h_enc($name), 'error');
    fe_redirect($exch_dest);
}
if (isset($_POST['csvFile']))   { $csvFile= basename($_POST['csvFile']); }   // No paths in export filename

$root_dir= basename(p2h_norm_path($_SERVER['DOCUMENT_ROOT'] ?? ''));  // web
$parentDir= basename(dirname($currPath));
$currentDir= basename($currPath);

// URL of the current folder (for image previews) - only when it is below DOCUMENT_ROOT:
$docRoot= p2h_norm_path(realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
$currentPath= null;
if ($docRoot > '' && ($currPath === $docRoot || strpos($currPath.'/', $docRoot.'/') === 0)) {
    $rel= trim(substr($currPath, strlen($docRoot)), '/');
    $currentPath= '/'. ($rel > '' ? implode('/', array_map('rawurlencode', explode('/', $rel))). '/' : '');
}

// Build Breadcrumbs (from $root_path down to the current folder):
$breadcrumbs= '';   $GLOBALS['goUp']= '';
$lnkPath= $root_path;
$crumbs= array_merge([basename($root_path) ?: '/'],
                     array_filter(explode('/', substr($currPath, strlen($root_path))), 'strlen'));
foreach ($crumbs as $n => $jt) {
    if ($n > 0) $lnkPath.= '/'.$jt;
    $url= basename(__FILE__) .'?show='.urlencode($lnkPath);
    if ($lnkPath === $currPath) $breadcrumbs.= '<b>'.p2h_enc($jt).'</b>';
    else {
        $GLOBALS['goUp']= $url;     // Ends as link to the parent folder
        $breadcrumbs.= '<a href="'.p2h_enc($url).'" class="txtbutt">'.p2h_enc($jt).'</a>'.'&nbsp; / ';
    }
}

$tabldata= [];  $arrFnames= [];  $arrNames= [];
$totFolds= 0;   $totFiles= 0;    $totSize= 0;   $locSize= 0;   $filesIncurrent= 0;   $Context_Row= '';
if ($is_logged) {
    $data= p2h_fileExplore($currPath,$parentDir);
    $arrfolds= $data[0];
    $arrfiles= $data[1];
    $objNames= $data[2];
    $i= 0;
    if (!$objNames==null)
        foreach ($objNames as $obj) $arrFnames[]= [ $i++, basename($obj)];

    if (is_array($arrfolds) and (count($arrfolds)>0)) $tabldata= $arrfolds;
    if (is_array($arrfiles) and (count($arrfiles)>0)) $tabldata= array_merge($tabldata,$arrfiles);
}

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
    htm_Caption(labl:'Build with PHP2HTML v'.P2H_VERSION,icon:'',hint:'The shortcut to structured and compact code<br>Created by EV-soft.',
                algn:'center',styl:'color: gray;; font-weight:400; font-size: 12px;');
    p2h_show_message();
    htm_nl(1);
    htm_Card_(capt:'Folder-Tree',icon:'fas fa-sitemap',hint: '',form:'',acti:'',clas:'cardW800',
                wdth:'',styl:'background-color: white; ',attr:'text-align: left; padding:8px; background-color: white;',show:true, head:'');
        // Card tool: show the used space of every folder in the tree (on/off for this session)
        if (isset($_GET['treesize'])) $_SESSION[p2h_SESSION_ID]['treesize']= ($_GET['treesize'] === '1');
        $fe_treesize= !empty($_SESSION[p2h_SESSION_ID]['treesize']);
        $tsHint= $fe_treesize ? lang('@USED SPACE: Click to hide the used space of the folders')
                              : lang('@USED SPACE: Click to show the total used space of every folder (can take some time in big folders)');
        echo '<abbr class="hint p2hCtrlHint" id="feSizeTool">'.
                '<a href="'.basename(__FILE__).'?show='.urlencode($currPath).'&amp;treesize='.($fe_treesize ? '0' : '1').'" '.
                    'aria-label="'.p2h_enc(strip_tags($tsHint)).'" aria-pressed="'.($fe_treesize ? 'true' : 'false').'" '.
                    'style="text-decoration: none; margin: 5px 8px 0 0; font-size: 13px; display: inline-block; color: '.($fe_treesize ? '#1a7f37' : '#555').';">'.
                    '<ic class="fas fa-hdd"></ic></a>'.
                '<data-hint>'.$tsHint.'</data-hint>'.
             '</abbr>'.
             '<script>(function(){'.
                'var t= document.getElementById("feSizeTool"), card= t.closest("[id^=card]"), head= card && card.querySelector("[id^=chead]");'.
                'if (!head) return;'.
                'var tools= head.querySelectorAll("abbr.p2hCtrlHint"), last= tools[tools.length - 1];'.
                'head.insertBefore(t, last ? last.nextSibling : null);     /* floats right: after the last one = left of the group */'.
                't.querySelector("a").addEventListener("click", function(e) { e.stopPropagation(); });     /* not the card header click */'.
             '})();</script>';
        htm_wrapp_($ViewHeight='300px; text-align:left; ');

        // $base= $_SERVER['DOCUMENT_ROOT'];        // $base= substr($base,0,-13);
        $base= $root_path;          // $base= substr($base,0,-13);
                    # $body,$algn='left',$marg='8px',$styl='box-shadow: 3px 3px 6px 0px #ccc; padding: 5px; border: solid 1px lightgray; ',$attr='background-color: white; ');
        htm_TextDiv(body:'BASE: <big><b>'.p2h_enc($base).'</b>/'.'</big><br>All folders with name="'.p2h_enc($currentDir).'" (current folder), are marked with bold text.',
                    algn:'left',marg:'8px',styl:'box-shadow: 3px 3px 6px 0px #ccc; padding: 5px; border: solid 1px lightgray; background-color: white; ');
        $treeTotal= listFolders($base,$currentDir);
        if ($fe_treesize)       // The size column just right of the widest folder name
            echo '<script>(function(){'.
                    'var t= document.querySelector("ul.feTree");  if (!t) return;'.
                    'function fit() {'.
                        'var x0= t.getBoundingClientRect().left, max= 0;'.
                        't.querySelectorAll("li > abbr.feHint").forEach(function(a){ max= Math.max(max, a.getBoundingClientRect().right - x0); });'.
                        'if (max <= 0) return;     /* Folded card: measured when it is shown */'.
                        'var z= parseFloat(getComputedStyle(document.body).zoom) || 1;     /* View bar zoom */'.
                        't.style.setProperty("--feSizeX", Math.ceil(max / z + 16) + "px");'.
                    '}'.
                    'fit();  if (window.ResizeObserver) new ResizeObserver(fit).observe(t);'.
                 '})();</script>';
        if ($fe_treesize)
            echo '<div style="margin: 6px 0 0 4px; font-size: 13px;"><i class="fas fa-hdd" style="color: #1a7f37;"></i> '.
                 p2h_enc(lang('@Used space below BASE')).': <b>'.p2h_nice_filesize($treeTotal).'</b> '.
                 '<small style="color: gray;">('.number_format((float)$treeTotal, 0, ',', ' ').' Bytes)</small></div>';
        $ruler= '<span style="left: 0;">Level:</span>';                    // The level axis below the tree
        for ($l= 0; $l <= 14; $l++) $ruler.= '<span style="left: '.(30 + 28 + 50*$l).'px;">'.$l.'</span>';
        echo '<div class="feRuler" style="width: '.(30 + 28 + 50*14 + 20).'px;">'.$ruler.'</div>';

        htm_wrapp_end();
    htm_Card_end($labl='', $icon='',  $hint='', name: '', form: '', subm: false, attr: '', akey: '', kind: 'save', simu: false);
    htm_nl(1);

    ### EXCHANGE AREA: upload from the PC / download to the PC
    if ($exch_path !== false) {
        $exch_rel= ltrim(substr($exch_path, strlen($root_path)), '/');
        $maxFile=  min($fe_max_upload, fe_ini_bytes(ini_get('upload_max_filesize')) ?: PHP_INT_MAX, fe_ini_bytes(ini_get('post_max_size')) ?: PHP_INT_MAX);
        $exch_used= fe_dir_size($exch_path);
        htm_Card_(capt:'@Exchange area',icon:'fas fa-exchange-alt',hint:'@Exchange files between this server and your own PC',form:'',acti:'',clas:'cardW800',
                  wdth:'',styl:'background-color: white;',attr:'text-align: left; padding:8px; background-color: white;',show:true, head:'');
            if (fe_is_admin()) {    // Remove / restore the restrictions - admins only: a lock icon among the card's tool icons
                $ulHint= $fe_unlocked ? lang('@<b>RESTRICTIONS REMOVED:</b> Click to restore the restrictions')
                                      : lang('@<b>REMOVE RESTRICTIONS:</b> see the whole account, upload any file type, no quota - only for you, only in this session');
                // The form (also the button without JavaScript):
                echo '<div id="feUnlockBox" style="float: right; margin: 0 10px 4px 8px;">'.
                        '<form id="feUnlockForm" method="post" action="'.basename(__FILE__).'" style="display:inline;">'.csrf_field(true).
                        '<input type="hidden" name="show" value="'.p2h_enc($currPath).'">'.
                        '<input type="hidden" name="fe_unlock" value="'.($fe_unlocked ? '0' : '1').'">'.
                        '<button type="submit" class="txtbutt" style="border-radius: 54px; padding: 2px 12px; color: #b00020; background: ivory;">'.
                            '<i class="fas fa-'.($fe_unlocked ? 'lock' : 'unlock').'"></i> '.p2h_enc(lang($fe_unlocked ? '@Restore restrictions' : '@Remove restrictions')).'</button>'.
                        '</form>'.
                     '</div>';
                // The tool icon - moved into the card header beside the other tool icons:
                echo '<abbr class="hint p2hCtrlHint" id="feUnlockTool" hidden>'.
                        '<ic class="fas fa-'.($fe_unlocked ? 'lock-open' : 'lock').'" role="button" tabindex="0" '.
                            'aria-label="'.p2h_enc(strip_tags($ulHint)).'" '.
                            'style="height:12px; margin:5px 8px 0 0; font-size: 13px; cursor: pointer; color: '.($fe_unlocked ? '#d00000' : '#555').';"></ic>'.
                        '<data-hint>'.$ulHint.'</data-hint>'.
                     '</abbr>'.
                     '<script>(function(){'.
                        'var t= document.getElementById("feUnlockTool"), f= document.getElementById("feUnlockForm"), box= document.getElementById("feUnlockBox");'.
                        'var card= t.closest("[id^=card]"), head= card && card.querySelector("[id^=chead]");'.
                        'if (!head) return;'.
                        'var tools= head.querySelectorAll("abbr.p2hCtrlHint"), last= tools[tools.length - 1];'.
                        'head.insertBefore(t, last ? last.nextSibling : null);     /* floats right: after the last one = left of the group */'.
                        't.hidden= false;  box.style.display= "none";'.
                        't.querySelector("ic").addEventListener("click", function(e) {'.
                            'e.stopPropagation();     /* not the card header click */'.
                            ($fe_unlocked ? '' : 'if (!confirm('.p2h_js(lang('@Remove the restrictions for this session?')).')) return;').
                            'f.submit();'.
                        '});'.
                     '})();</script>';
                if ($fe_unlocked)
                    echo '<div class="p2hAlert p2hAlert-error" style="margin: 4px 10px 8px;" role="alert">'.
                            '<i class="fas fa-lock-open"></i> <b>'.p2h_enc(lang('@Restrictions removed')).'</b> - '.
                            p2h_enc(lang('@you can see and download everything below')).' <b>'.p2h_enc($root_path).'</b>, '.
                            p2h_enc(lang('@upload any file type (not hidden files) to the exchange area, with no size limit or quota of its own. Only for you, only in this session.')).
                            ' '.p2h_enc(lang('@Restore them with the lock icon in the card header.')).
                         '</div>';
            }
            echo '<div style="text-align:left; padding: 4px 10px; line-height: 1.6;">'.
                 p2h_enc($fe_unlocked ? lang('@Uploads go to this folder - downloads from everywhere') : lang('@The only folder where files can be uploaded and downloaded')).': '.
                 '<a href="'.basename(__FILE__).'?show='.urlencode($exch_path).'" class="txtbutt"><i class="far fa-folder colr0"></i> '.p2h_enc($exch_rel ?: basename($exch_path)).'</a>'.
                 ($in_exchange ? ' <small>('.p2h_enc(lang('@you are here')).')</small>' : '').'<br>'.
                 '<small>'.p2h_enc(lang('@Used')).': '.p2h_nice_filesize($exch_used).
                    ($fe_unlocked ? ' ('.p2h_enc(lang('@no quota')).')' : ' '.p2h_enc(lang('@of')).' '.p2h_nice_filesize($fe_exchange_quota)).
                 ' &nbsp;-&nbsp; '.p2h_enc(lang('@Max per file')).': '.p2h_nice_filesize($maxFile).
                 ' &nbsp;-&nbsp; '.p2h_enc($fe_unlocked ? lang('@All file types allowed - except hidden files') : lang('@Not allowed: server scripts (.php ...) and hidden files')).'</small><br>';
            if (fe_can('w')) {
                // Settings and texts for fe-upload.js (the form also works without JavaScript: plain file field + submit)
                $feCfg= [
                    'maxFile'   => $maxFile,
                    'quotaLeft' => max(0, $fe_exchange_quota - $exch_used),
                    'maxCount'  => max(1, (int)ini_get('max_file_uploads') ?: 20),
                    'maxPost'   => fe_ini_bytes(ini_get('post_max_size')) ?: 8 * 1024 * 1024,
                    'deny'      => $fe_unlocked ? '(?!)' : FE_DENY_EXT,     // (?!) never matches: all types allowed
                    'strict'    => !$fe_unlocked,
                    'txt'       => array_map('lang', [
                        'type'    => '@this file type is not allowed',
                        'folder'  => '@the folder name is not allowed',
                        'big'     => '@the file is too large',
                        'quota'   => '@The selected files are larger than the free space in the exchange area',
                        'ready'   => '@ready',
                        'sending' => '@uploading...',
                        'saved'   => '@uploaded as',
                        'uploaded'=> '@uploaded',
                        'server'  => '@the server refused the upload',
                        'files'   => '@files selected',
                        'none'    => '@No files selected yet',
                        'done'    => '@Finished',
                        'show'    => '@Show the folder',
                        'name'    => '@File',
                        'size'    => '@Size',
                        'status'  => '@Status',
                        'all'     => '@Select / deselect all',
                    ]),
                ];
                echo '<form id="feUpload" method="post" enctype="multipart/form-data" action="'.basename(__FILE__).'?show='.urlencode($exch_dest).'" '.
                           'data-cfg="'.p2h_enc(json_encode($feCfg)).'" style="margin: 6px 0;">'.
                         csrf_field(true).
                         '<div id="feDrop" class="fe-drop">'.
                            '<i class="fas fa-cloud-upload-alt fa-2x colr9"></i><br>'.
                            p2h_enc(lang('@Drag files or folders from your PC here - or')).'<br>'.
                            '<label class="txtbutt fe-pick"><input type="file" name="fe_upload[]" id="feFiles" multiple>'.
                                '<i class="far fa-file"></i> '.p2h_enc(lang('@Choose files')).'</label> '.
                            '<label class="txtbutt fe-pick fe-js"><input type="file" id="feFolder" webkitdirectory multiple>'.
                                '<i class="far fa-folder colr0"></i> '.p2h_enc(lang('@Choose a folder')).'</label>'.
                         '</div>'.
                         '<div id="feList" class="fe-list" aria-live="polite"></div>'.
                         '<button type="submit" id="feSend" class="txtbutt" style="border-radius: 54px; height: 30px; background-color: ivory; padding: 0 15px;">'.
                            '<i class="fas fa-upload"></i> '.p2h_enc(lang('@Upload')).'</button> '.
                         '<button type="button" id="feClear" class="txtbutt fe-js" style="border-radius: 54px; height: 30px; padding: 0 15px;">'.
                            '<i class="fas fa-times"></i> '.p2h_enc(lang('@Clear list')).'</button> '.
                         '<small>'.p2h_enc(lang('@to')).': <b>'.p2h_enc(ltrim(substr($exch_dest, strlen($root_path)), '/')).'</b></small> '.
                         '<progress id="feProg" max="100" value="0" hidden style="width: 200px; vertical-align: middle;"></progress> '.
                         '<span id="feSum"></span>'.
                     '</form>'.
                     '<script src="fe-upload.js?v='.@filemtime(__DIR__.'/fe-upload.js').'" defer></script>';
            }
            else echo '<i>'.p2h_enc(lang('@Your account can only download files from here.')).'</i><br>';
            echo '<small>'.p2h_enc(lang('@Download: click the download icon before a file name in the exchange area, or right-click the row.')).'</small>'.
                 '</div>';
        htm_Card_end(labl:'', icon:'', hint:'', name:'', form:'', subm:false, attr:'', akey:'', kind:'save', simu:false);
        htm_nl(1);
    }

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
          ['<abbr class="hint feHint" style="float:left; top: -5px; position: relative;">
            <big><i>Showing:</i> ', '90%','html','','left','@Tip','?', $_SERVER['SERVER_NAME'].' // '.$breadcrumbs.'</big><data-hint>'.lang('@RightClick for program MENU').'</data-hint></abbr>'],
          ['<span style="float:right; white-space: nowrap; display: inline-block;">'.
               '<abbr class="hint feHint">'.
               ($calc_folder == true ? 
               '<i>Content:</i> Folders: '.$totFolds.' - Files: '.markAllChars($filesIncurrent.'/','i','style="opacity:0.5;"').$totFiles. ' - ' : '' ).
               'Space: '.markAllChars(p2h_nice_filesize($locSize).'/','i','style="opacity:0.5;"').p2h_nice_filesize($totSize).
               ' - Free: '.   p2h_nice_filesize(@disk_free_space($currPath)).
               ' of total: '. p2h_nice_filesize(@disk_total_space($currPath)).
               '<data-hint style="white-space: normal;">'.lang('@Space meaning file-space in local folder / total in subfolders').'</data-hint></abbr>'.
           '</span> <br>
            <div style="width: 800px; margin: auto; padding-top: 10px;">'. 
                '<form method="post" name= "frmMode" style="display:inline;">'. csrf_field(true).  // php2html 1.7 rejects a POST without the token
                str_sp(2).
                SearchSub($arrNames). ' '.
                htm_Input(labl:'@Mode',plho:'@Select...',icon:'',
                          hint:'@The program mode.<br>Speedup by deactivating calculating content in subfolders using "Simple mode"',
                          vrnt:'opti',name:'prgMode',valu: ($calc_folder == true ? 'mode_A' : 'mode_S'), form:'',
                          wdth:'',algn:'left',attr:'',rtrn:true,unit:'',disa:false,rows:'1',step:'',list:[
                            ['mode_S','Simple','@Do not spend time on calulating subfolders'],
                            ['mode_A','Advanced','@Advanced mode exploring all subfolders']
                          ],llgn:'R',bord:'',ftop:'').
                    fe_hint('<input type="submit" value="'.lang('@Set adv. Mode').'" class="txtbutt" style="
                                 border-radius:54px; height:33px; background-color: ivory; padding: 0 15px;">', lang('@Analyse sub-folders in advanced mode...')).
                str_sp(2).
                htm_LinkButt(labl:'@Language', hint:'@Open the translate language page', attr:'', 
                             link:'translate.page.php', targ:'_blank', rtrn:true).
                '<span style= "padding: 0 12px;"> Logged as: <b>'.p2h_enc($_SESSION[p2h_SESSION_ID]['logged']).'</b></span>'.
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
                  htm_Fieldset(capt:'@Future file features:',icon:'', hint:'',wdth:'',marg:'',attr:'',rtrn:true).
                      htm_Fieldset(capt:'@Select:',icon:'', hint:'',wdth:'',marg:'',attr:'',rtrn:true).
                      htm_AcceptButt(labl:'@All',  icon:'', hint:'@All files and folders', form:'', wdth:'', attr:'', akey:'', kind:'spc2', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'', idix:'').
                      htm_AcceptButt(labl:'@None', icon:'', hint:'@Deselect all', form:'', wdth:'', attr:'', akey:'', kind:'spc2', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'', idix:'').
                      htm_AcceptButt(labl:'@Inverse', icon:'', hint:'@Reverse all', form:'', wdth:'', attr:'', akey:'', kind:'spc2', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'', idix:'').
                      htm_Fieldset_end(rtrn:true).
                      
                      htm_Fieldset(capt:'@With selected do:',icon:'', hint:'',wdth:'',marg:'',attr:'',rtrn:true).
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
        data: $tabldata, 
        filt: $FilterOn= true,
        sort: $SorterOn= true,        # FIXIT: Sorting fails if there are both folders and files! (js-error coursed by empty td-fields)- Folders only is OK (BAD data before the first file ?)
        crea: $CreateRec=false,
        modi: $ModifyRec=false,
        vhgh: $ViewHeight= '650px',
        styl: $TblStyle= 'width:98%; cursor: alias; ',
        from: $CalledFrom= __FILE__ ,
        list: $MultiList= ['',''],
        expo: $ExportTo= $csvFile ?? ''
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
if ($exch_path !== false && fe_can('w'))     // Delete in the exchange area: confirm, then POST with the CSRF token
run_Script('
    function feDelete(name) {
        if (!confirm('.p2h_js(lang('@Delete the file').': ').' + name + " ?")) return;
        var f= document.createElement("form");  f.method= "post";  f.action= '.p2h_js(basename(__FILE__).'?show='.urlencode($exch_dest)).';
        [["fe_delete", name], ["csrf_token", '.p2h_js($_SESSION['csrf_token'] ?? '').']].forEach(function(v) {
            var i= document.createElement("input");  i.type= "hidden";  i.name= v[0];  i.value= v[1];  f.appendChild(i);
        });
        document.body.appendChild(f);  f.submit();
    }
');

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
    // Demo of the HEX-viewer on a local file (previously fetched over HTTP on every page load):
    $fileName= 'README.md';
    $fileContent= (string)@file_get_contents(__DIR__.'/'.$fileName, false, null, 0, 2048);  // First 2 KB only
    $hexView= str_replace('&amp;nbsp;', '&nbsp;', p2h_enc(hex_dump($fileContent,"\n",16,"·")));

    htm_Card_(capt:'HEX-view file: '.$fileName,icon:'fas fa-eye',hint: '',form:'',acti:'',clas:'cardW640',
                wdth: '',styl:'background-color: white;',attr:'', show : true, head : 'background-color: white;');
        htm_wrapp_($ViewHeight='500px');
            htm_TextDiv('<pre><code>'.$hexView.'</code></pre>',algn:'left',marg:'12px',styl:'',attr:'font-family:monospace; width:580px; background-color:lightcyan;');
        htm_wrapp_end();
        htm_nl(1);
    htm_Card_end(labl:'', icon:'', hint:'', name:'', form:'', subm:false, attr:'', akey:'', kind:'save', simu:false);

   
    // PROGRAM Context-Menu: 
    echo 
        Pmnu_(elem:'tblSpan',capt:'@Regarding PROGRAM:',wdth:'260px',icon:'',stck:'false', cntx:true, rtrn:true).
            Pmnu_Item(labl:'@Program menu:',          icon:'fas fa-wrench fa-sm', hint:'@Program functions. Go to: Admin / Settings, if you want to save permanently', vrnt:'custom',   name:'cust',clck:'',
                      attr:"'background-color: yellow; height: 44px; border-style: solid; border-width: 5px 1px 5px 1px; border-color: lightgray; border-radius: 10px; padding-top: 10px;'' ",shrt:'',enbl:'false',rtrn:true).
            Pmnu_Item(labl:'@Program settings',       icon:'fa fa-cog colrgray',         hint:'@Change program settings',   vrnt:'plain', name:'sett',clck:'console.log('.p2h_js($labl).')' , attr:'',shrt:'',enbl:'true',rtrn:true).
            Pmnu_Item(labl:'@User settings',          icon:'fa fa-user colrgray',        hint:'@Change user settings',      vrnt:'plain', name:'user',clck:'console.log('.p2h_js($labl).')' , attr:'',shrt:'',enbl:'true',rtrn:true).
            Pmnu_Item(labl:$labl='@Help',             icon:'fa fa-question',             hint:'@Go to program help',        vrnt:'plain', name:'help',clck:'console.log('.p2h_js($labl).')'  ,attr:'',shrt:'',enbl:'true',rtrn:true).
            Pmnu_Item(labl:$labl='',                  icon:'',                           hint:'',                           vrnt:'separator',rtrn:true).
            Pmnu_Item(labl:$labl='@Create New Item',  icon:'fa fa-plus-square colrgray', hint:'@Create new file or folder', vrnt:'plain', name:'crea',clck:'console.log('.p2h_js($labl).')' , attr:'',shrt:'',enbl:'true',rtrn:true).
            Pmnu_Item(labl:$labl='@Upload',           icon:'fas fa-upload colrgray',     hint:'@Upload local file',         vrnt:'plain', name:'upld',clck:'console.log('.p2h_js($labl).')' , attr:'',shrt:'',enbl:'true',rtrn:true).
            Pmnu_Item(labl:$labl='',                  icon:'',                           hint:'',                           vrnt:'separator',rtrn:true).
            Pmnu_Item(labl:$labl='@Logout',           icon:'fas fa-sign-out-alt',        hint:'@Leave the program in locked mode',vrnt:'plain', name:'logu',clck:'console.log('.p2h_js($labl).')' ,attr:'',shrt:'',enbl:'true',rtrn:true).
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
        // Full variable dump only for the admin with ?debug - it contains password hashes, session and server data:
        if (isset($_GET['debug']) && ($_SESSION[p2h_SESSION_ID]['logged'] ?? '') == 'fe-admin') {
            $dump= get_defined_vars();
            unset($dump['auth_users']);
            arrPretty($dump,'Defined_vars:');
        } else
            arrPretty(['currPath' => $currPath, 'root_path' => $root_path, 'calc_folder' => $calc_folder,
                       'folders' => $totFolds, 'files' => $totFiles, 'size' => $totSize], 'Status:');
    htm_Card_end(labl:'', icon:'', hint:'', name:'', form:'', subm:false, attr:'', akey:'', kind:'save', simu:false);
    
    $c= ($exch_path !== false) ? 1 : 0;     // The exchange card shifts the card numbers
    CardOff($First=1,$Last=1);
    CardOff($First=3+$c,$Last=4+$c);
    
htm_Page_end();
}
        
