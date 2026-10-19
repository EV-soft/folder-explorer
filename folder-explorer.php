<?php   $DocFil= 'folder-explorer.php';    $DocVer='2.1.0';    $DocRev='2026-10-19';     $DocIni='evs';  $ModulNr=0; ## File informative only
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
    
$auth_users = array(    # hash                                              # userPath # userRights # Write folders (below $fe_root_path, '' = all of it)
    'fe-admin'  => ['$2y$10$g9W04bYhFOG45HAvnZCK6.FytNiDdzW4OM6Si0L4SaJAm/LyZa.zG','root','rwx', ['']],          // xxxxxxxx
    'fe-user'   => ['$2y$10$vJjLTMo9Zb2oIhd.RF.W8eXsVWfFXVdze4Ta7WC7xAQZb8LtDLoH6','root','rwx', ['_exchange']], // xxxxxxxx
    'visitor'   => ['$2y$10$kD49SD5o81IAU1sU/k8e3.59T9ZiGfzgG9jWpc./Y04V6B1hHzP/a','root','r',   []]             // 8x x  - public demo account: read only
);  # Accounts are made in the program: program menu (right-click the table) - Users. They are saved in fe-users.php, which then replaces this list.
    # userRights:  r: download / see the content of files in the write folders and the shared folder ($fe_exchange_path)
    #              w: upload, copy, move, rename, ZIP, unpack and delete - only in the write folders (4th field; missing = the shared folder)
    # A write folder that does not exist is created and closed for direct web access (.htaccess) - an existing folder is used as it is:
    # files uploaded to a folder the web server shows can be opened directly in the browser.

## Long work (Analyse, the folder sizes of the tree, copy / ZIP of big folders): PHP's time limit (max_execution_time) is
## extended while the work goes on - up to this total in seconds. The web server may have its own limit.
$fe_max_time= $fe_max_time ?? 300;
$gbl_SessionKeep= $gbl_SessionKeep ?? true;     // true: an open page keeps the login alive (no session timeout) - false: logged out after session.gc_maxlifetime without activity (a dialog warns)

## Browsing is restricted to this folder and its subfolders (no access above it):
$fe_root_path= $fe_root_path ?? __DIR__;

## The shared folder: created automatically, closed for direct web access (.htaccess) - files are only served through this page.
## Accounts with the right r may download from it, and it is the write folder of an account with w without its own list.
$fe_exchange_path=  $fe_exchange_path  ?? $fe_root_path.'/_exchange';
$fe_max_upload=     $fe_max_upload     ?? 20 * 1024 * 1024;     // Max size per file (bytes) - PHP's upload_max_filesize / post_max_size also apply
$fe_exchange_quota= $fe_exchange_quota ?? 200 * 1024 * 1024;    // Max total size of the shared folder (bytes) - other write folders have no quota of their own

## "Remove restrictions" (admins only, per session): browse, download and write everywhere below $fe_unlocked_root,
## any file type (never hidden files like .htaccess) and no quota / size limit of its own.
$fe_admins=        $fe_admins        ?? ['fe-admin'];
$fe_unlocked_root= $fe_unlocked_root ?? dirname(p2h_norm_path($_SERVER['DOCUMENT_ROOT'] ?? __DIR__));   // e.g. /home/<account>

## Accounts made in the program (program menu: Users) - when this file exists, it replaces $auth_users and $fe_admins above.
## A PHP file: opened in the browser it shows nothing. It holds password hashes only.
$fe_users_file= $fe_users_file ?? __DIR__.'/fe-users.php';
if (is_file($fe_users_file)) {
    try { $fe_ud= @include $fe_users_file; }
    catch (Throwable $e) { $fe_ud= null;  $fe_users_broken= true; }         // A broken file: the accounts above are used - see the Users card
    if (is_array($fe_ud) && !empty($fe_ud['users']) && is_array($fe_ud['users'])) { $auth_users= $fe_ud['users'];  $fe_admins= $fe_ud['admins'] ?? $fe_admins; }
    unset($fe_ud);
}

function p2h_js($str) { // Encode a PHP string as a safe JavaScript string literal
    return json_encode((string)$str, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

function p2h_norm_path($path) { // Unified slashes, no trailing slash - but "/" stays "/"
    $p= rtrim(str_replace('\\', '/', (string)$path), '/');
    return $p === '' && $path !== '' && $path !== null ? '/' : $p;
}

function p2h_safe_path($requested, $root) { // Resolve $requested and accept it only inside $root
    $root = p2h_norm_path(realpath($root) ?: $root);
    if ($requested === null || $requested === '') return $root;
    $real = realpath($requested);
    if ($real === false) $real = realpath('/'. ltrim($requested, '/'));
    if ($real === false || !is_dir($real)) return $root;
    $real = p2h_norm_path($real);
    return ($real === $root || strpos($real.'/', rtrim($root, '/').'/') === 0) ? $real : $root;
}

function fe_in_dir($path, $dir) { // Is $path the folder $dir or below it (both normalized)
    return $dir !== false && $dir !== '' && ($path === $dir || strpos($path.'/', rtrim($dir, '/').'/') === 0);
}

function fe_can($right) { // Rights of the logged user: r= read (download, content), w= write (upload, copy, move, delete) - where: fe_can_read(), fe_can_write()
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

function fe_more_time() { // Called in long loops: every 10 s of work PHP's time limit starts again - until $fe_max_time in total
    static $start= null, $last= 0.0, $orig= null;
    if ($orig === null) $orig= (int)ini_get('max_execution_time');
    if ($orig === 0) return;                            // No limit at all (CLI, or the server's setting): never set one
    $now= microtime(true);
    if ($now - $last < 10) return;
    $last= $now;
    if ($start === null) $start= $_SERVER['REQUEST_TIME_FLOAT'] ?? $now;
    $left= ($GLOBALS['fe_max_time'] ?? 300) - ($now - $start);
    if ($left > 1 && function_exists('set_time_limit')) @set_time_limit(max($orig, (int)min(30, ceil($left))));   // Never shorter than the server's own limit
}

function fe_show_path($path) { // A path to show: "root/sub/x" - the whole server path only when the restrictions are removed
    global $fe_unlocked, $root_path;
    $path= p2h_norm_path((string)$path);
    if (!empty($fe_unlocked) || !fe_in_dir($path, (string)$root_path)) return $path === '' ? '/' : $path;
    return rtrim(basename($root_path).'/'.ltrim(substr($path, strlen($root_path)), '/'), '/');
}

function fe_resolve_dest_new($raw) { // A destination - a new last folder is made, when the folder above it may be written in (one level)
    $d= fe_resolve_dest($raw);
    if ($d !== false) return $d;
    $raw= rtrim(trim(str_replace('\\', '/', (string)$raw)), '/');
    $pos= strrpos($raw, '/');
    if ($pos === false) return false;
    $parent= fe_resolve_dest(substr($raw, 0, $pos));
    $name=   fe_clean_name(substr($raw, $pos + 1));
    if ($parent === false || $name === '' || $name[0] === '.' || !fe_can('w') || !fe_can_write($parent)) return false;
    if (!is_dir($parent.'/'.$name) && !@mkdir($parent.'/'.$name, 0755)) return false;
    return p2h_norm_path(realpath($parent.'/'.$name) ?: '') ?: false;
}

function fe_resolve_dest($raw) { // A destination folder: "root/sub" (as shown), "sub" (below the root) or a whole path - '' = the current folder, false = not found
    global $root_path, $currPath;
    $raw= trim(str_replace('\\', '/', (string)$raw));
    if ($raw === '') return $currPath;
    $rn= basename($root_path);  $tries= [];
    if ($raw === $rn || strpos($raw, $rn.'/') === 0) $tries[]= $root_path.'/'.substr($raw, strlen($rn));      // As shown on the page
    $tries[]= $root_path.'/'.ltrim($raw, '/');                                                               // Below the root
    if (preg_match('#^(/|[a-z]:/)#i', $raw)) $tries[]= $raw;                                                 // A whole path
    foreach ($tries as $t) {
        $d= realpath($t);
        if ($d !== false && is_dir($d) && fe_in_dir(p2h_norm_path($d), $root_path)) return p2h_norm_path($d);
    }
    return false;
}

function fe_hidden_below($path, $dir) { // A hidden folder or file (.git, .htaccess ...) between $dir and $path
    foreach (explode('/', trim(substr($path, strlen($dir)), '/')) as $part) if ($part !== '' && $part[0] === '.') return true;
    return false;
}

function fe_write_dirs() { // The folders where the logged user may write - normalized (unlocked admin: everything)
    global $auth_users, $fe_root_path, $fe_exchange_path, $fe_unlocked, $root_path;
    static $dirs= null;
    if ($dirs !== null) return $dirs;
    $dirs= [];
    if (!fe_can('w')) return $dirs;
    if (!empty($fe_unlocked)) return $dirs= [$root_path];
    $usr=  $_SESSION[p2h_SESSION_ID]['logged'] ?? '';
    $base= p2h_norm_path(realpath($fe_root_path) ?: $fe_root_path);
    foreach ($auth_users[$usr][3] ?? [ltrim(substr(p2h_norm_path(realpath($fe_exchange_path) ?: $fe_exchange_path), strlen($base)), '/')] as $rel) {
        $rel= trim(str_replace('\\', '/', (string)$rel), '/');
        if ($rel !== '' && preg_match('#(^|/)\.#', $rel)) continue;        // Never a hidden folder - or ..
        $d= $rel === '' ? $base : (is_dir($base.'/'.$rel) ? p2h_norm_path(realpath($base.'/'.$rel)) : fe_exchange_init($base.'/'.$rel));
        if ($d !== false && fe_in_dir($d, $base)) $dirs[]= $d;
    }
    return $dirs;
}

function fe_can_write($dir) { // May the logged user write in the folder $dir
    if (!is_string($dir) || $dir === '') return false;
    foreach (fe_write_dirs() as $w)
        if (fe_in_dir($dir, $w) && (!empty($GLOBALS['fe_unlocked']) || !fe_hidden_below($dir, $w))) return true;
    return false;
}

function fe_can_read($file) { // May the logged user download / see the content of $file: in the write folders and the shared folder
    global $fe_unlocked, $root_path, $exch_path;
    $file= p2h_norm_path((string)$file);
    $real= realpath($file);                             // A link must not lead out of the root
    if ($real === false || !fe_in_dir(p2h_norm_path($real), $root_path)) return false;
    if (!empty($fe_unlocked)) return fe_in_dir($file, $root_path);
    if (!fe_name_allowed(basename($file))) return false;
    $dirs= fe_write_dirs();
    if (fe_can('r') && $exch_path) $dirs[]= $exch_path;
    foreach ($dirs as $d) if (fe_in_dir($file, $d) && !fe_hidden_below($file, $d)) return true;
    return false;
}

function fe_unique($target) { // Never overwrite: "name (2).ext" - "folder (2)" - a link (also a broken one) counts as there
    if (!file_exists($target) && !is_link($target)) return $target;
    $dir= dirname($target);  $b= pathinfo($target, PATHINFO_FILENAME);  $e= pathinfo($target, PATHINFO_EXTENSION);
    if (is_dir($target)) { $b= basename($target);  $e= ''; }
    for ($n= 2; ; $n++) { $t= $dir.'/'.$b.' ('.$n.')'.($e > '' ? '.'.$e : '');  if (!file_exists($t) && !is_link($t)) return $t; }
}

function fe_copy_tree($src, $dst, &$n, &$skip) { // Copy a file or a folder - only files the user may read, never over an existing file
    fe_more_time();
    if (is_link($src)) { $skip++;  return; }            // Never a link: a loop - or files outside the root
    if (is_dir($src)) {
        if (empty($GLOBALS['fe_unlocked']) && basename($src)[0] === '.') { $skip++;  return; }     // Hidden folders (.git ...)
        if (!is_dir($dst) && !@mkdir($dst, 0755)) { $skip++;  return; }
        foreach (scandir($src) ?: [] as $f) if ($f !== '.' && $f !== '..') fe_copy_tree($src.'/'.$f, $dst.'/'.$f, $n, $skip);
        return;
    }
    if (!fe_can_read($src) || file_exists($dst) || is_link($dst) || !@copy($src, $dst)) { $skip++;  return; }
    @touch($dst, filemtime($src));
    $n++;
}

function fe_transfer($items, $dest, $op, &$n, &$skip) { // Copy or move files / folders to the folder $dest - returns the moved items
    global $fe_unlocked, $exch_path, $fe_exchange_quota;
    $room= ($exch_path && fe_in_dir($dest, $exch_path) && empty($fe_unlocked)) ? $fe_exchange_quota - fe_dir_size($exch_path) : PHP_INT_MAX;
    $done= [];
    foreach ($items as $src) {
        foreach ($items as $o) if ($o !== $src && fe_in_dir($src, $o)) continue 2;      // Inside another of the items: comes with it
        if (fe_in_dir($dest, $src) || ($op === 'move' && p2h_norm_path(dirname($src)) === $dest)) { $skip++;  continue; }  // Into itself - or moved where it is (a copy there: "name (2)")
        $dst= fe_unique($dest.'/'.basename($src));
        if ($op === 'move' && $room !== PHP_INT_MAX && !fe_in_dir($src, $exch_path)) {     // Into the shared folder from outside it: its quota
            $size= is_dir($src) ? fe_dir_size($src) : filesize($src);
            if ($size > $room) { $skip++;  continue; }
            $room-= $size;
        }
        if ($op === 'move') {
            if (fe_can_write(p2h_norm_path(dirname($src))) && (!empty($fe_unlocked) || (basename($src)[0] !== '.' && (is_dir($src) || fe_name_allowed(basename($src)))))
                && @rename($src, $dst)) { $n++;  $done[]= $src; }
            else $skip++;
        } else {
            $size= is_dir($src) ? fe_dir_size($src) : filesize($src);
            if ($size > $room) { $skip++;  continue; }
            fe_copy_tree($src, $dst, $n, $skip);
            $room-= $size;
        }
    }
    return $done;
}

function fe_sync_tree($src, $dst, &$n, &$skip) { // One way: copy what is missing or newer in $src to $dst - nothing is deleted, an older file is replaced
    fe_more_time();
    if (is_link($src) || is_link($dst)) { $skip++;  return; }       // Never a link: a loop - or files outside the root (also in the destination)
    if (is_dir($src)) {
        if (empty($GLOBALS['fe_unlocked']) && basename($src)[0] === '.') { $skip++;  return; }     // Hidden folders (.git ...)
        if (!is_dir($dst) && (file_exists($dst) || !@mkdir($dst, 0755))) { $skip++;  return; }
        foreach (scandir($src) ?: [] as $f) if ($f !== '.' && $f !== '..') fe_sync_tree($src.'/'.$f, $dst.'/'.$f, $n, $skip);
        return;
    }
    if (!fe_can_read($src) || is_dir($dst)) { $skip++;  return; }
    if (is_file($dst)) {
        if (filemtime($dst) >= filemtime($src)) return;                           // The same or newer there: kept
        if (empty($GLOBALS['fe_unlocked']) && !fe_name_allowed(basename($dst))) { $skip++;  return; }   // Never replace a protected file
    }
    if (@copy($src, $dst)) { @touch($dst, filemtime($src));  $n++; }
    else $skip++;
}

function fe_delete_tree($path, &$n, &$skip) { // Delete a file, or a folder with its content - a folder with something protected left (.htaccess ...) stays
    fe_more_time();
    if (is_dir($path) && !is_link($path)) {
        if (empty($GLOBALS['fe_unlocked']) && basename($path)[0] === '.') { $skip++;  return; }     // A hidden folder: kept
        foreach (scandir($path) ?: [] as $f) if ($f !== '.' && $f !== '..') fe_delete_tree($path.'/'.$f, $n, $skip);
        if (@rmdir($path)) $n++; else $skip++;
        return;
    }
    if ((!empty($GLOBALS['fe_unlocked']) || fe_name_allowed(basename($path))) && @unlink($path)) $n++;
    else $skip++;
}

function fe_zip_add($z, $path, $local, &$n, &$skip) { // Add a file or a folder to a ZIP - only files the user may read
    fe_more_time();
    if (is_link($path)) { $skip++;  return; }           // Never a link: a loop - or files outside the root
    if ($n + $skip >= 20000) { $skip++;  return; }      // At most 20000 files - the rest is counted as skipped (and told)
    if (is_dir($path)) {
        if (empty($GLOBALS['fe_unlocked']) && basename($path)[0] === '.') { $skip++;  return; }
        $z->addEmptyDir($local);
        foreach (scandir($path) ?: [] as $f) if ($f !== '.' && $f !== '..') fe_zip_add($z, $path.'/'.$f, $local.'/'.$f, $n, $skip);
    } elseif (fe_can_read($path) && $z->addFile($path, $local)) $n++;
    else $skip++;
}

function fe_unzip($zip, $dest, $room, &$n, &$skip) { // Unpack a ZIP in $dest: safe names only, at most $room bytes, never over an existing file
    if (!class_exists('ZipArchive')) return false;
    $z= new ZipArchive();
    if ($z->open($zip, ZipArchive::RDONLY) !== true) return false;
    $total= 0;
    for ($i= 0; $i < $z->numFiles; $i++) {
        if ($i >= 20000) { $skip+= $z->numFiles - $i;  break; }
        fe_more_time();
        $st=    $z->statIndex($i);
        $name=  str_replace('\\', '/', $st['name']);
        $isDir= substr($name, -1) === '/';
        $parts= array_values(array_filter(explode('/', $name), 'strlen'));
        $bad=   !$parts || $name[0] === '/' || preg_match('#^[a-z]:#i', $name);
        foreach ($parts as $part) if ($part[0] === '.') $bad= true;       // .. and hidden files / folders
        if ($bad || (!$isDir && !fe_name_allowed(end($parts)))) { $skip++;  continue; }
        $dir= fe_rel_dir($dest, implode('/', $parts).($isDir ? '/-' : ''));
        if ($dir === false) { $skip++;  continue; }
        if ($isDir) continue;
        if (($total+= $st['size']) > $room) { $skip++;  continue; }
        $target= fe_unique($dir.'/'.fe_clean_name(end($parts)));
        $in=  $z->getStream($st['name']);
        $out= $in ? @fopen($target, 'xb') : false;
        if (!$out) { if ($in) fclose($in);  $skip++;  continue; }
        stream_copy_to_stream($in, $out, $st['size']);      // Never more than the ZIP says (a false size in the ZIP)
        fclose($in);  fclose($out);
        @touch($target, $st['mtime']);
        $n++;
    }
    $z->close();
    return true;
}

function fe_clean_name($name) { // A plain file name: no path, no control or reserved characters
    $name= str_replace('\\', '/', (string)$name);
    $name= substr($name, strrpos('/'.$name, '/'));     // The last part (not basename(): it takes "a:" for a drive on Windows - and depends on the locale)
    $name= trim(preg_replace('/[\x00-\x1F\x7F<>:"\/\\\\|?*]/u', '_', $name) ?? '', " .");
    if (DIRECTORY_SEPARATOR === '\\' && preg_match('/^(con|prn|aux|nul|com[1-9]|lpt[1-9])(\.|$)/i', $name)) $name= '_'.$name;   // Windows: a device, not a file
    return $name;
}

function fe_given_name($name) { // The name of an existing file or folder of the current folder - as it is (it may have characters a new name may not)
    $name= (string)$name;
    return ($name === '' || $name === '.' || $name === '..' || strpbrk($name, "/\\\0") !== false) ? '' : $name;
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

function fe_send_file($file, $name= '') { // Send a file as a download, and stop
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();     // A long download must not lock the other pages of the user
    while (ob_get_level()) ob_end_clean();
    $name= $name ?: basename($file);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.preg_replace('/[^\x20-\x7E]|"/', '_', $name).'"; filename*=UTF-8\'\''.rawurlencode($name));
    header('Content-Length: '.filesize($file));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($file);
    exit;
}

// Hover preview of a file icon (fe-preview.js) - extension => [kind, MIME type]
// img, video, audio, font: shown everywhere - txt, pdf, zip, doc: only where the file may be downloaded (else: lock)
const FE_PV_MEDIA= ['jpg'=>['img','image/jpeg'], 'jpeg'=>['img','image/jpeg'], 'png'=>['img','image/png'], 'gif'=>['img','image/gif'],
    'webp'=>['img','image/webp'], 'bmp'=>['img','image/bmp'], 'ico'=>['img','image/x-icon'], 'svg'=>['img','image/svg+xml'], 'avif'=>['img','image/avif'],
    'mp4'=>['video','video/mp4'], 'm4v'=>['video','video/mp4'], 'webm'=>['video','video/webm'], 'ogv'=>['video','video/ogg'], 'mov'=>['video','video/quicktime'],
    'mp3'=>['audio','audio/mpeg'], 'wav'=>['audio','audio/wav'], 'ogg'=>['audio','audio/ogg'], 'oga'=>['audio','audio/ogg'], 'opus'=>['audio','audio/ogg'],
    'm4a'=>['audio','audio/mp4'], 'aac'=>['audio','audio/aac'], 'flac'=>['audio','audio/flac'],
    'ttf'=>['font','font/ttf'], 'otf'=>['font','font/otf'], 'woff'=>['font','font/woff'], 'woff2'=>['font','font/woff2'],
    'jfif'=>['img','image/jpeg'], 'pjpeg'=>['img','image/jpeg'], 'apng'=>['img','image/apng'],
    'mkv'=>['video','video/x-matroska'], 'weba'=>['audio','audio/webm'],
    'pdf'=>['pdf','application/pdf'], 'zip'=>['zip',''], 'docx'=>['doc',''], 'odt'=>['doc',''], 'pptx'=>['doc',''], 'odp'=>['doc',''],
    'rtf'=>['doc',''], 'epub'=>['doc',''],
    'csv'=>['table',''], 'tsv'=>['table',''], 'xlsx'=>['table',''], 'ods'=>['table',''],
    'tar'=>['arch',''], 'tgz'=>['arch',''], 'gz'=>['arch',''], 'tbz'=>['arch',''], 'bz2'=>['arch','']];
const FE_PV_TXT= ['txt','text','md','markdown','log','ini','cnf','conf','cfg','properties','toml','json','jsonc','map','webmanifest',
    'eml','vcf','ics','m3u','m3u8','pls','cue','nfo','bib','ass','ssa','sub','gpx','kml','geojson','reg','gitattributes','lst','plist','xaml',
    'resx','config','manifest','csproj','vbproj','sln','proto','graphql','gql','hbs','ejs','pug','haml','erb','coffee','elm','ex','exs','erl',
    'hs','ml','clj','cljs','lisp','scm','f','f90','for','pas','asm','s','v','vhd','sv','cmake','nim','zig','jl','tf','hcl','nix','bas','au3','ahk',
    'xml','xsl','xslt','yml','yaml','css','scss','sass','less','js','mjs','cjs','ts','tsx','jsx','vue','svelte','htm','html','xhtml','shtml',
    'php','php3','php4','php5','php7','php8','phtml','inc','twig','tpl','mustache','sql','sh','bash','zsh','bat','cmd','ps1','py','rb','pl','pm',
    'lua','java','kt','kts','swift','go','rs','c','h','cpp','hpp','cc','cxx','cs','vb','dart','scala','groovy','gradle','r','tex','rst',
    'srt','vtt','diff','patch','htaccess','env','gitignore','editorconfig','lock','mk','makefile','dockerfile'];
function fe_temp_cleanup() { // Old temp files of the program: ZIPs never fetched (2 hours), counters of failed logins and folder sizes (a day)
    $t= sys_get_temp_dir();
    foreach ([['fez*', 7200], ['fe-login-*.txt', 86400], ['fe-size-*.txt', 86400]] as [$pat, $age])
        foreach (glob($t.'/'.$pat) ?: [] as $f) if (is_file($f) && @filemtime($f) < time() - $age) @unlink($f);
}

function fe_offer_download($file, $name, $temp, $note= '') { // A ZIP / file for the browser - fePost(.., true) gets a link (?fdl=), else it is sent at once
    if ($temp && mt_rand(1, 10) === 1) fe_temp_cleanup();      // Now and then
    if (($_POST['fe_ajax'] ?? '') === '1') {
        $tok= bin2hex(random_bytes(12));
        $_SESSION[p2h_SESSION_ID]['fdl'][$tok]= ['f' => $file, 'n' => $name, 't' => $temp, 'at' => time()];
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        exit(json_encode(['url' => basename(__FILE__).'?fdl='.$tok, 'note' => $note]));
    }
    if ($temp) register_shutdown_function(function() use ($file) { @unlink($file); });
    fe_send_file($file, $name);
}

function fe_protected_dirs() { // The folders closed for direct web access by the program: the shared folder and the write folders of all accounts
    global $auth_users, $fe_root_path, $exch_path;
    static $dirs= null;
    if ($dirs !== null) return $dirs;
    $dirs= $exch_path ? [$exch_path] : [];
    $base= p2h_norm_path(realpath($fe_root_path) ?: $fe_root_path);
    foreach ($auth_users as $u) foreach ((array)($u[3] ?? []) as $rel) {
        $rel= trim(str_replace('\\', '/', (string)$rel), '/');
        if ($rel === '' || !($d= realpath($base.'/'.$rel))) continue;      // '' = the whole root: not a protected folder
        if (is_file($d.'/.htaccess')) $dirs[]= p2h_norm_path($d);
    }
    return $dirs;
}

function fe_media_ok($file) { // Images, video, audio, fonts: shown everywhere - but in a protected folder only to those who may read there
    if (fe_can_read($file)) return true;
    foreach (fe_protected_dirs() as $d) if (fe_in_dir(p2h_norm_path($file), $d)) return false;
    return true;
}

function fe_preview_kind($name, $text_ok, $media_ok= true) { // The kind of preview of a file - '' = none
    $ext= strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($ext === '' && in_array(strtolower($name), ['makefile','dockerfile','license','readme'], true)) $ext= 'txt';
    $kind= FE_PV_MEDIA[$ext][0] ?? (in_array($ext, FE_PV_TXT, true) ? 'txt' : ($text_ok ? 'hex' : ''));    // Any other file: its first bytes (hex)
    if (in_array($kind, ['img','video','audio','font'], true) && !$media_ok) return 'lock';
    if (in_array($kind, ['txt','pdf','zip','doc','table','arch'], true) && !$text_ok) return 'lock';
    return $kind;
}

function fe_send_inline($file, $mime) { // Send a file to be shown in the page (preview) - HTTP Range for audio / video
    $size= filesize($file);  $from= 0;  $to= $size - 1;
    header('Content-Type: '.$mime);
    header('Accept-Ranges: bytes');
    if (preg_match('/^bytes=(\d*)-(\d*)$/', $_SERVER['HTTP_RANGE'] ?? '', $m) && ($m[1] !== '' || $m[2] !== '')) {
        if ($m[1] === '') $from= max(0, $size - (int)$m[2]);
        else { $from= (int)$m[1];  if ($m[2] !== '') $to= min($to, (int)$m[2]); }
        if ($from > $to) { http_response_code(416);  header('Content-Range: bytes */'.$size);  exit; }
        http_response_code(206);
        header('Content-Range: bytes '.$from.'-'.$to.'/'.$size);
    }
    header('Content-Length: '.($to - $from + 1));
    $h= fopen($file, 'rb');  fseek($h, $from);  $left= $to - $from + 1;
    while ($left > 0 && !feof($h) && !connection_aborted()) {
        $part= fread($h, (int)min(65536, $left));
        if ($part === false || $part === '') break;
        echo $part;  flush();  $left-= strlen($part);
    }
    fclose($h);
    exit;
}

function fe_hexdump($data, $file= '') { // The first bytes of a file as hex + text - with its type when PHP knows it
    $mime= ($file > '' && function_exists('finfo_open') && ($fi= @finfo_open(FILEINFO_MIME_TYPE))) ? (string)@finfo_file($fi, $file) : '';
    $out= $mime > '' ? $mime."\n\n" : '';
    for ($i= 0; $i < strlen($data); $i+= 16) {
        $chunk= substr($data, $i, 16);
        $out.= sprintf('%06x  %-47s  %s', $i, implode(' ', str_split(bin2hex($chunk), 2)), preg_replace('/[^\x20-\x7E]/', '.', $chunk))."\n";
    }
    return $out;
}

function fe_bom($txt) { // Text with a byte order mark: UTF-16 to UTF-8, and the mark away
    if (strncmp($txt, "\xEF\xBB\xBF", 3) === 0) return substr($txt, 3);
    foreach (["\xFF\xFE" => 'UTF-16LE', "\xFE\xFF" => 'UTF-16BE'] as $bom => $enc)
        if (strncmp($txt, $bom, 2) === 0 && function_exists('iconv')) {
            $body= substr($txt, 2);  $body= substr($body, 0, strlen($body) - strlen($body) % 2);
            return (string)iconv($enc, 'UTF-8//IGNORE', $body);
        }
    return $txt;
}

function fe_utf8($txt, $cut= false) { // Text as UTF-8 - $cut: only the start was read (a character cut at the end is removed) - other text is read as Windows-1252
    for ($i= 0; $cut && $i < 3 && !preg_match('//u', $txt); $i++) $txt= substr($txt, 0, -1);
    if (!preg_match('//u', $txt))
        $txt= function_exists('iconv') ? (string)iconv('Windows-1252', 'UTF-8//IGNORE', $txt) : preg_replace('/[\x80-\xFF]/', '?', $txt);
    return $txt;
}

function fe_rtf_drop($r) { // RTF: away with the groups that are no text: {\* ...}, font / colour tables, pictures ... - with braces inside them
    $n= strlen($r);  $out= '';  $i= 0;
    while ($i < $n) {
        if ($r[$i] === '{' && preg_match('~\G\{\\\\(\*|fonttbl|colortbl|stylesheet|info|pict|header|footer|listtable|listoverridetable|rsidtbl|xmlnstbl|latentstyles)~A', $r, $m, 0, $i)) {
            $d= 0;
            for ($j= $i; $j < $n; $j++) {               // To the brace that closes the group
                if ($r[$j] === '\\') { $j++;  continue; }
                if ($r[$j] === '{') $d++;
                elseif ($r[$j] === '}' && --$d === 0) break;
            }
            $i= $j + 1;  $out.= '{}';          // (an empty group: a control word before it ends there - nsi{...}Hej is not nsiHej)
            continue;
        }
        $out.= $r[$i++];
    }
    return $out;
}

function fe_xml_text($xml) { // The text of an office XML part: paragraphs and line breaks kept, a table row on one line
    $xml= substr($xml, 0, 4000000);
    $xml= preg_replace(['#<w:del\b[^>]*>.*?</w:del>#s', '#<w:delText\b[^>]*>.*?</w:delText>#s', '#<w:instrText\b[^>]*>.*?</w:instrText>#s',
                        '#<text:tracked-changes\b.*?</text:tracked-changes>#s', '#<office:annotation\b.*?</office:annotation>#s', '#<office:annotation-end\b[^>]*/>#'],
                       '', $xml);      // Deleted text (tracked changes), field codes and comments: not text
    $xml= preg_replace_callback('#<text:s(?:\s+text:c="(\d+)")?\s*/>#', fn($m) => str_repeat(' ', min(200, max(1, (int)($m[1] ?? 1)))), $xml);  // Spaces
    $xml= preg_replace(['#</w:p>(\s*</w:tc>)#', '#</text:p>(\s*</table:table-cell>)#',                  // The last paragraph of a cell: no new line
                        '#</a:p>(\s*</a:txBody>\s*(?:<a:tcPr\b[^>]*/>|<a:tcPr\b.*?</a:tcPr>)?\s*</a:tc>)#s'], '$1', $xml);
    $xml= preg_replace(['#</w:tc>|</table:table-cell>|</a:tc>#', '#</w:tr>|</table:table-row>|</a:tr>#'], ["\t", "\n"], $xml);   // Cells / rows
    $xml= preg_replace(['#<w:tab/>|<text:tab/>#', '#<w:br/>|<a:br/>|<text:line-break/>|</w:p>|</a:p>|</text:p>|</text:h>|</p>|</h[1-6]>|<br\s*/?>|</li>|</div>#i'], ["\t", "\n"], substr($xml, 0, 4000000));
    $txt= html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1 | ENT_HTML5, 'UTF-8');
    return trim(preg_replace(["/\t+\n/", "/\n{3,}/"], ["\n", "\n\n"], $txt));
}

function fe_preview_text($file, $kind) { // Text for the preview: a text file, the list of an archive, the text of a document, or the first bytes (hex)
    $ext= strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($kind === 'hex') return fe_hexdump((string)file_get_contents($file, false, null, 0, 512), $file);
    if ($kind === 'doc' && $ext === 'rtf') {            // RTF: the text without the control words
        $r= (string)file_get_contents($file, false, null, 0, 400000);
        $r= fe_rtf_drop($r);                             // Tables, pictures, {\* ...}: not text
        $r= preg_replace_callback('~\\\\u(-?\d+) ?(?:\\\\\'[0-9a-f]{2}|\?)?~i', function($m) {     // \u230? = æ (and its fallback after it)
                $n= (int)$m[1];  if ($n < 0) $n+= 65536;  return function_exists('mb_chr') ? (string)mb_chr($n, 'UTF-8') : '?'; }, $r);
        $r= preg_replace_callback("~\\\\'([0-9a-f]{2})~i", function($m) {                      // \'e6 = æ (Windows-1252)
                return function_exists('iconv') ? (string)iconv('Windows-1252', 'UTF-8//IGNORE', chr(hexdec($m[1]))) : '?'; }, $r);
        $r= preg_replace(['~\\\\(par|line)\b ?~', '~\\\\tab\b ?~', '~\\\\[a-z]+-?\d* ?~i', '~[{}]~'], ["\n", "\t", '', ''], $r);
        return substr(fe_utf8(trim(preg_replace("/\n{3,}/", "\n\n", $r))), 0, 16384);
    }
    if ($kind === 'arch') {                             // tar, tar.gz, tgz, tar.bz2 - a single .gz: its text
        if (class_exists('PharData')) {
            try {
                $ph= new PharData($file);  $rows= [];  $n= 0;  $sum= 0;
                foreach (new RecursiveIteratorIterator($ph, RecursiveIteratorIterator::SELF_FIRST) as $f) {
                    fe_more_time();
                    $name= substr($f->getPathname(), strlen('phar://'.str_replace('\\', '/', $file)) + 1);
                    if ($f->isFile()) { $n++;  $sum+= $f->getSize(); }
                    if (count($rows) < 300) $rows[]= str_pad($f->isFile() ? html_entity_decode(p2h_nice_filesize($f->getSize())) : '', 10, ' ', STR_PAD_LEFT).'  '.$name.($f->isDir() ? '/' : '');
                }
                return $n.' '.lang('@files').', '.html_entity_decode(p2h_nice_filesize($sum)).' '.lang('@unpacked')."\n".implode("\n", $rows);
            } catch (Exception $e) { }
        }
        if ($ext === 'gz' && function_exists('gzopen') && ($g= @gzopen($file, 'rb'))) {
            $data= (string)gzread($g, 16384);  gzclose($g);
            return strpos($data, "\0") !== false ? fe_hexdump(substr($data, 0, 512)) : fe_utf8($data, strlen($data) >= 16384);
        }
        return null;
    }
    if ($kind === 'zip' || $kind === 'doc') {
        if (!class_exists('ZipArchive')) return null;
        $z= new ZipArchive();
        if ($z->open($file, ZipArchive::RDONLY) !== true) return null;
        if ($kind === 'zip') {
            $n= $z->numFiles;  $sum= 0;  $rows= [];
            for ($i= 0; $i < $n; $i++) {
                $st= $z->statIndex($i);  $sum+= $st['size'];
                if ($i < 300) $rows[]= str_pad(substr($st['name'], -1) === '/' ? '' : html_entity_decode(p2h_nice_filesize($st['size'])), 10, ' ', STR_PAD_LEFT).'  '.$st['name'];
            }
            $z->close();
            return $n.' '.lang('@files').', '.html_entity_decode(p2h_nice_filesize($sum)).' '.lang('@unpacked')."\n".implode("\n", $rows);
        }
        $txt= '';
        if ($ext === 'pptx') {                          // The text of each slide
            $sl= [];
            for ($i= 0; $i < $z->numFiles; $i++) if (preg_match('#^ppt/slides/slide(\d+)\.xml$#', $z->getNameIndex($i), $m)) $sl[(int)$m[1]]= $z->getNameIndex($i);
            ksort($sl);
            foreach ($sl as $no => $part) { $txt.= '--- '.$no." ---\n".fe_xml_text((string)$z->getFromName($part))."\n\n";  if (strlen($txt) > 16384) break; }
        } elseif ($ext === 'epub') {                    // The text of the chapters, in the order of their names
            $ch= [];
            $opf= preg_match('#full-path="([^"]+)"#', (string)$z->getFromName('META-INF/container.xml'), $om) ? html_entity_decode($om[1], ENT_QUOTES | ENT_XML1, 'UTF-8') : '';
            $pkg= $opf > '' ? (string)$z->getFromName($opf) : '';
            if ($pkg > '') {                            // The reading order: the spine of the package file
                $dir= strpos($opf, '/') !== false ? substr($opf, 0, strrpos($opf, '/') + 1) : '';  $man= [];
                if (preg_match_all('#<(?:\w+:)?item\b[^>]*>#', $pkg, $im))
                    foreach ($im[0] as $tag)
                        if (preg_match('#\bid="([^"]+)"#', $tag, $a) && preg_match('~\bhref="([^"#]+)~', $tag, $h)) $man[$a[1]]= $dir.rawurldecode(html_entity_decode($h[1], ENT_QUOTES | ENT_XML1, 'UTF-8'));
                if (preg_match_all('#<(?:\w+:)?itemref\b[^>]*\bidref="([^"]+)"#', $pkg, $sp))
                    foreach ($sp[1] as $id) if (isset($man[$id]) && $z->locateName($man[$id]) !== false) $ch[]= $man[$id];
            }
            if (!$ch) {                                 // No spine: the order of the names
                for ($i= 0; $i < $z->numFiles; $i++) if (preg_match('#\.x?html?$#i', $z->getNameIndex($i))) $ch[]= $z->getNameIndex($i);
                natsort($ch);
            }
            foreach ($ch as $part) { $txt.= fe_xml_text(preg_replace('#<head.*?</head>#is', '', (string)$z->getFromName($part)))."\n\n";  if (strlen($txt) > 16384) break; }
        } else {                                        // docx - odt / odp
            $xml= $z->getFromName('word/document.xml');
            if ($xml === false) $xml= $z->getFromName('content.xml');
            if ($xml !== false) $txt= fe_xml_text($xml);
        }
        $z->close();
        return $txt === '' ? null : substr(trim($txt), 0, 16384);
    }
    $raw= (string)file_get_contents($file, false, null, 0, 16384);
    $txt= fe_bom($raw);                                 // UTF-16 (Windows) and byte order marks
    if (strpos($txt, "\0") !== false) return fe_hexdump(substr($raw, 0, 512), $file);      // A binary file: its first bytes
    return fe_utf8($txt, strlen($txt) >= 16384);
}

function fe_preview_table($file) { // The first rows of a table: CSV / TSV, Excel (xlsx) or Calc (ods) - ['rows' => [[cells]], 'more' => bool, 'sheet' => name]
    $ext= strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $max= 40;  $cols= 30;  $rows= [];  $more= false;  $sheet= '';
    $cell= function($v) { $v= trim((string)$v);  return mb_strlen($v) > 120 ? mb_substr($v, 0, 120).'…' : $v; };
    if ($ext === 'csv' || $ext === 'tsv') {
        $data= fe_bom((string)@file_get_contents($file, false, null, 0, 262144));
        if (!preg_match('//u', $data)) $data= fe_utf8($data, strlen($data) >= 262144);     // Windows-1252 (or a character cut at the end)
        $h= fopen('php://memory', 'w+b');  fwrite($h, $data);  rewind($h);
        $first= (string)fgets($h, 65536);  rewind($h);
        $sep= $ext === 'tsv' ? "\t" : (substr_count($first, ';') > substr_count($first, ',') ? ';' : (substr_count($first, "\t") > substr_count($first, ',') ? "\t" : ','));
        while (($r= fgetcsv($h, 65536, $sep, '"', '')) !== false) {
            if (count($rows) >= $max) { $more= true;  break; }
            $rows[]= array_map($cell, array_slice($r, 0, $cols));
        }
        fclose($h);
        return ['rows' => $rows, 'more' => $more, 'sheet' => ''];
    }
    if (!class_exists('XMLReader') || !class_exists('ZipArchive')) return null;
    $z= new ZipArchive();
    if ($z->open($file, ZipArchive::RDONLY) !== true) return null;
    $zp= 'zip://'.str_replace('\\', '/', $file).'#';
    if ($ext === 'xlsx') {
        $shared= [];                                    // The shared strings (at most 20000)
        if ($z->locateName('xl/sharedStrings.xml') !== false) {
            $xr= new XMLReader();
            if (@$xr->open($zp.'xl/sharedStrings.xml')) {
                while ($xr->read() && count($shared) < 20000)
                    if ($xr->nodeType === XMLReader::ELEMENT && $xr->localName === 'si')
                        $shared[]= html_entity_decode(strip_tags(preg_replace('#<rPh\b.*?</rPh>#s', '', $xr->readInnerXml())), ENT_QUOTES | ENT_XML1, 'UTF-8');   // (no phonetic guide)
                $xr->close();
            }
        }
        $wb= (string)$z->getFromName('xl/workbook.xml');
        $part= 'xl/worksheets/sheet1.xml';
        if (preg_match('#<sheet [^>]*>#', $wb, $sm)) {      // The first sheet of the workbook - through its relation (it need not be sheet1.xml)
            if (preg_match('#name="([^"]*)"#', $sm[0], $m)) $sheet= html_entity_decode($m[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
            if (preg_match('#r:id="([^"]*)"#', $sm[0], $m) && preg_match('#<Relationship [^>]*Id="'.preg_quote($m[1], '#').'"[^>]*>#', (string)$z->getFromName('xl/_rels/workbook.xml.rels'), $rm)
                && preg_match('#Target="([^"]*)"#', $rm[0], $tm)) {
                $tg= ltrim(html_entity_decode($tm[1], ENT_QUOTES | ENT_XML1, 'UTF-8'), '/');
                $tg= strpos($tg, 'xl/') === 0 ? $tg : 'xl/'.$tg;
                if ($z->locateName($tg) !== false) $part= $tg;
            }
        }
        if ($z->locateName($part) === false) for ($i= 0; $i < $z->numFiles; $i++) if (preg_match('#^xl/worksheets/[^/]+\.xml$#', $z->getNameIndex($i))) { $part= $z->getNameIndex($i);  break; }
        $base1904= (bool)preg_match('#<workbookPr [^>]*date1904="(1|true)"#', $wb);        // Excel for Mac: days from 1904
        $dateXf= [];                                    // The cell styles that are dates (number formats 14-22, 45-47 or own formats with d / m / y)
        $st= (string)$z->getFromName('xl/styles.xml');
        $own= [];
        if (preg_match_all('#<numFmt [^>]*numFmtId="(\d+)"[^>]*formatCode="([^"]*)"#', $st, $mm, PREG_SET_ORDER))
            foreach ($mm as $f) $own[(int)$f[1]]= preg_match('/[dmy]/i', preg_replace('/"[^"]*"|\[[^\]]*\]/', '', html_entity_decode($f[2])));
        if (preg_match('#<cellXfs[^>]*>(.*?)</cellXfs>#s', $st, $cx) && preg_match_all('#<xf\b([^>]*)>#', $cx[1], $xm))
            foreach ($xm[1] as $k => $at) {
                $id= preg_match('#numFmtId="(\d+)"#', $at, $im) ? (int)$im[1] : 0;
                $dateXf[$k]= ($id >= 14 && $id <= 22) || ($id >= 45 && $id <= 47) || !empty($own[$id]);
            }
        $z->close();
        $xr= new XMLReader();
        if (!@$xr->open($zp.$part)) return null;
        $row= null;  $ci= 0;  $t= '';  $rn= 0;
        while ($xr->read()) {
            if ($xr->nodeType === XMLReader::ELEMENT && $xr->localName === 'row') {
                $no= (int)$xr->getAttribute('r') ?: $rn + 1;
                for ($k= 0; $k < min(5, $no - $rn - 1) && count($rows) < $max; $k++) $rows[]= [];      // Empty rows before it
                $rn= $no;
                if (count($rows) >= $max) { $more= true;  break; }
                $row= [];
            } elseif ($xr->nodeType === XMLReader::ELEMENT && $xr->localName === 'c' && $row !== null) {
                $ref= (string)$xr->getAttribute('r');  $t= (string)$xr->getAttribute('t');  $sx= (int)$xr->getAttribute('s');
                $ci= 0;  foreach (str_split(preg_replace('/\d+/', '', $ref) ?: 'A') as $ch) $ci= $ci * 26 + (ord(strtoupper($ch)) - 64);
                $ci--;
                $val= '';
                if ($xr->isEmptyElement) continue;
                $inner= $xr->readInnerXml();
                if ($t === 'inlineStr') $val= html_entity_decode(strip_tags(preg_replace('#<rPh\b.*?</rPh>#s', '', $inner)), ENT_QUOTES | ENT_XML1, 'UTF-8');
                elseif (preg_match('#<v(?:\s[^>]*)?>(.*?)</v>#s', $inner, $m)) {
                    $v= html_entity_decode($m[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
                    $val= $t === 's' ? ($shared[(int)$v] ?? '…') : ($t === 'b' ? ($v ? 'TRUE' : 'FALSE') : $v);
                    if ($t === 'd' && preg_match('/^(\d{4}-\d\d-\d\d)(?:T(\d\d:\d\d))?/', $v, $dm))      // An ISO date: 2023-10-20T00:00:00
                        $val= $dm[1].(isset($dm[2]) && $dm[2] !== '00:00' ? ' '.$dm[2] : '');
                    if (($t === '' || $t === 'n') && is_numeric($v) && preg_match('/\.\d{10,}/', $v))       // 0.10000000000000001 -> 0.1
                        $val= rtrim(rtrim(sprintf('%.'.max(0, 15 - max(1, (int)floor(log10(max(abs((float)$v), 1e-300))) + 1)).'F', (float)$v), '0'), '.');
                    if (($t === '' || $t === 'n') && !empty($dateXf[$sx]) && is_numeric($v)) {             // A date: the serial number of Excel as a date
                        $sec= (int)round(((float)$v - ($base1904 ? 24107 : 25569)) * 86400);
                        $val= (float)$v < 1 ? gmdate('H:i', (int)round((float)$v * 86400))          // A time only
                                            : gmdate(fmod((float)$v, 1) > 0 ? 'Y-m-d H:i' : 'Y-m-d', $sec);
                    }
                }
                if ($ci < $cols) { while (count($row) < $ci) $row[]= '';  $row[]= $cell($val); }
            } elseif ($xr->nodeType === XMLReader::END_ELEMENT && $xr->localName === 'row' && $row !== null) { $rows[]= $row;  $row= null; }
        }
        $xr->close();
        return ['rows' => $rows, 'more' => $more, 'sheet' => $sheet];
    }
    // ods: the first table of content.xml
    $z->close();
    $xr= new XMLReader();
    if (!@$xr->open($zp.'content.xml')) return null;
    $row= null;  $inTable= false;
    while ($xr->read()) {
        if ($xr->nodeType === XMLReader::ELEMENT && $xr->localName === 'table' && !$inTable) { $inTable= true;  $sheet= (string)$xr->getAttribute('table:name'); }
        elseif ($xr->nodeType === XMLReader::END_ELEMENT && $xr->localName === 'table' && $inTable) break;
        elseif ($inTable && $xr->nodeType === XMLReader::ELEMENT && $xr->localName === 'table-row') {
            if (count($rows) >= $max) { $more= true;  break; }
            $node= simplexml_load_string($xr->readOuterXml(), 'SimpleXMLElement', 0, 'urn:oasis:names:tc:opendocument:xmlns:table:1.0', false);
            $r= [];
            if ($node) foreach ($node->children('urn:oasis:names:tc:opendocument:xmlns:table:1.0') as $c) {
                $rep= min($cols, max(1, (int)$c->attributes('urn:oasis:names:tc:opendocument:xmlns:table:1.0')['number-columns-repeated']));
                $txt= trim(preg_replace('/\s*[\n\t]\s*/', ' ', fe_xml_text((string)$c->asXML())));     // More paragraphs: one line
                for ($k= 0; $k < $rep && count($r) < $cols; $k++) $r[]= $cell($txt);
            }
            while ($r && end($r) === '') array_pop($r);                // No empty cells at the end (repeated to the last column)
            $rrep= max(1, (int)$xr->getAttribute('table:number-rows-repeated'));
            if ($r || $rrep < 100) for ($k= 0; $k < min($rrep, 5) && count($rows) < $max; $k++) $rows[]= $r;   // A long run of empty rows: once
            $xr->next();
        }
    }
    $xr->close();
    while ($rows && !end($rows)) array_pop($rows);                     // No empty rows at the end
    return ['rows' => $rows, 'more' => $more, 'sheet' => $sheet];
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
        if (is_link($dir) || (!is_dir($dir) && !@mkdir($dir, 0755))) return false;      // Never into a link
    }
    return $dir;
}

function fe_dir_size($dir) { // Total size of all files below $dir
    $size= 0;
    try {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                     RecursiveIteratorIterator::LEAVES_ONLY, RecursiveIteratorIterator::CATCH_GET_CHILD) as $f)
            { fe_more_time();  if ($f->isFile()) $size+= $f->getSize(); }
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

function fe_save_users($users, $admins) { // Write the accounts file (fe-users.php) - written to a temp file first, then renamed
    $f= $GLOBALS['fe_users_file'];
    $tmp= $f.'.'.bin2hex(random_bytes(4)).'.tmp';
    $code= "<?php // Folder-explorer accounts - written by the program (program menu: Users). Password hashes only.\n".
           'return '.var_export(['users' => $users, 'admins' => array_values(array_unique($admins))], true).";\n";
    if (@file_put_contents($tmp, $code, LOCK_EX) === false) return false;
    @chmod($tmp, 0640);
    if (!@rename($tmp, $f)) { @unlink($tmp);  return false; }
    if (function_exists('opcache_invalidate')) @opcache_invalidate($f, true);
    return true;
}

function fe_may_change_pw($usr) { // Own password: admins and accounts that may write - not a read only (shared demo) account
    return isset($GLOBALS['auth_users'][$usr]) && (in_array($usr, $GLOBALS['fe_admins'] ?? [], true) || strpos((string)$GLOBALS['auth_users'][$usr][2], 'w') !== false);
}

function fe_login_tries($fail= null) { // Failed logins from this address to this account: $fail true = one more, false = forget them - the number in the last 15 minutes
    $f= sys_get_temp_dir().'/fe-login-'.md5(__FILE__.'|'.($_SERVER['REMOTE_ADDR'] ?? '').'|'.strtolower(trim((string)($_POST['p2h_usr'] ?? '')))).'.txt';
    $n= (is_file($f) && @filemtime($f) > time() - 900) ? (int)@file_get_contents($f) : 0;
    if ($fail === true)  { @file_put_contents($f, (string)++$n, LOCK_EX);  if (mt_rand(1, 20) === 1) fe_temp_cleanup(); }
    if ($fail === false) @unlink($f);
    return $n;
}

function fe_redirect($dir) { // Post/Redirect/Get - back to the folder
    header('Location: '.basename(__FILE__).'?show='.urlencode($dir), true, 303);
    exit;
}





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

function p2h_nice_filesize($size, $fix= false)    // Output nice rounded filesize - $fix: always 2 decimals (in columns: the numbers line up)
{   if (!is_numeric($size)) return $size; //  string 'Folder'
    $units = array('&nbsp;B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $power = $size > 0 ? floor(log($size, 1024)) : 0;
    $num= $size / pow(1024, $power);
    if (round($num, 2) >= 1024 && $power < count($units) - 1) { $power++;  $num/= 1024; }     // 1023.999 KB: 1.00 MB
    return sprintf('%s %s', $power && $fix ? number_format($num, 2, '.', '') : round($num, 2), $units[$power]);    // Bytes: always whole
}

function p2h_get_file_icon_class($path) { // Get CSS classname for file
    global $is_archive, $is_image, $is_audio, $is_video, $is_text;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $icoc = '';     $showType = 'File'; // default
    if (in_array($ext,['ico','gif','jpg','jpeg','jpc','jp2','jpx','xbm','wbmp','png','bmp','tif','tiff','svg']) )
                                                                    { $icoc = 'far fa-file-image colr1';        $showType = 'Image';    $is_image= true;    }
    elseif (in_array($ext,['passwd','ftpquota','sql','js','sh','config','twig','tpl','md','gitignore','c','cpp','cs','yml','py','map','lock','dtd']) )
                                                                    { $icoc = 'far fa-file-code colr3';         $showType = 'Code';                         }
    elseif (in_array($ext,['txt','ini','conf','log','htaccess']))   { $icoc = 'far fa-file-alt colr4';          $showType = 'Text';     $is_text= true;     }
    elseif (in_array($ext,['css','less','sass','scss']))            { $icoc = 'fab fa-css3-alt colr8';          $showType = 'Class'; }
    elseif (in_array($ext,['zip','rar','gz','tar','7z','tgz','bz2','tbz','xz']))           { $icoc = 'far fa-file-archive colr2';      $showType = 'Archive';  $is_archive= true;  }
    elseif (in_array($ext,['php','php4','php5','phps','phtml']))    { $icoc = 'far fa-file-code colr3';         $showType = 'Code';                         }
    elseif (in_array($ext,['htm','html','shtml','xhtml']))          { $icoc = 'fab fa-html5 colr5';             $showType = 'Html';                         }
    elseif (in_array($ext,['xml','xsl']))                           { $icoc = 'far fa-file-code colr3';         $showType = 'Code';                  }
    elseif (in_array($ext,['wav','mp3','mp2','m4a','aac','ogg','oga','wma','mka','flac','ac3','tds','weba','opus']))  
                                                                    { $icoc = 'far fa-music';                   $showType = 'Audio';    $is_audio= true;    }
    elseif (in_array($ext,['m3u','m3u8','pls','cue']) )             { $icoc = 'far fa-headphones';              $showType = 'Audio';    $is_audio= true;    }
    elseif (in_array($ext,['avi','mpg','mpeg','mp4','m4v','flv','f4v','ogm','ogv','mov','mkv','3gp','asf','wm','webm']))
                                                                    { $icoc = 'far fa-file-video';              $showType = 'Video';    $is_video= true;    }
    elseif (in_array($ext,['eml','msg']) )                          { $icoc = 'far fa-envelope';                $showType = 'Mail';                         }
    elseif (in_array($ext,['xls','xlsx','ods','csv','tsv']))                { $icoc = 'far fa-file-excel colr6';        $showType = 'Spreadsheet';                   }
    elseif (in_array($ext,['db','odb']) )                           { $icoc = 'fas fa-database';                $showType = 'Database';                     }
    elseif (in_array($ext,['json']) )                               { $icoc = 'far fa-file-alt colr4';          $showType = 'Data';                         }
    elseif (in_array($ext,['bak']) )                                { $icoc = 'far fa-clipboard';               $showType = 'Backup';                       }
    elseif (in_array($ext,['doc','docx','odt','rtf']))                { $icoc = 'far fa-file-word';               $showType = 'Word';                         }
    elseif (in_array($ext,['ppt','pptx','odp']) )                   { $icoc = 'far fa-file-powerpoint colr7';   $showType = 'PPT';                          }
    elseif (in_array($ext,['ttf','ttc','otf','woff','woff2','eot','fon']) ) {$icoc = 'far fa-font';             $showType = 'Font';                         }
    elseif (in_array($ext,['epub','mobi']) )                        { $icoc = 'fas fa-book';                    $showType = 'E-book';                       }
    elseif (in_array($ext,['vcf']) )                                { $icoc = 'far fa-address-card';            $showType = 'Contact';                      }
    elseif (in_array($ext,['ics']) )                                { $icoc = 'far fa-calendar-alt';            $showType = 'Calendar';                     }
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
    /* File icon preview (fePv script): the image or the start of the text, the file information of Analyse mode below */
    .feFileIc { cursor: zoom-in; }
    .fePv { position: fixed; z-index: 3000; max-width: min(700px, 92vw); max-height: 75vh; overflow: hidden; pointer-events: none;
            background: #fff; color: #222; border: 1px solid #b9b9b9; border-radius: 6px; box-shadow: 0 4px 14px rgba(0,0,0,.25);
            padding: 6px 8px; font: 12px/1.35 Arial, sans-serif; text-align: left; }
    .fePv[hidden] { display: none; }
    .fePvHead { font-weight: 600; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .fePvHead small { font-weight: normal; color: #666; }
    .fePvBody img { display: block; max-width: 100%; max-height: 50vh; margin: auto;
                    background: repeating-conic-gradient(#eee 0% 25%, #fff 0% 50%) 0 0 / 16px 16px; }
    /* Cards: the same width when folded (wdth: 800px) - the full width card counts its border too, and never wider than the window */
    [id^=card].cardWmax { box-sizing: border-box; max-width: min(100%, calc(100vw - 32px)); }
    .fePvTbl { border-collapse: collapse; font: 11px/1.35 Arial, sans-serif; background: #fff; }
    .fePvTbl td { border: 1px solid #d0d7de; padding: 1px 5px; white-space: nowrap; max-width: 220px; overflow: hidden; text-overflow: ellipsis; }
    .fePvTbl tr:first-child td { background: #eef3f8; font-weight: 600; }
    .fePvTblWrap { max-height: 56vh; overflow: hidden; }
    .fePvCode { display: flex; max-height: 56vh; overflow: hidden; background: #f6f8fa; border-radius: 4px; }
    .fePvCode pre { margin: 0; padding: 4px 8px 4px 6px; white-space: pre; tab-size: 4; font: 11px/1.4 Consolas, monospace; color: #24292e; }
    .fePvCode pre.fePvLn { padding: 4px 6px; color: #a0a0a0; text-align: right; background: #eceff2; user-select: none; }
    .fePv-txt { min-width: 260px; }
    .hl-c { color: #6a737d; font-style: italic; }   .hl-s { color: #032f62; }   .hl-k { color: #d73a49; }   .hl-n { color: #005cc5; }
    .hl-v { color: #e36209; }   .hl-t { color: #22863a; }   .hl-a { color: #6f42c1; }   .hl-p { color: #005cc5; }   .hl-h { color: #005cc5; font-weight: bold; }
    .fePvBody video { display: block; max-width: min(480px, 88vw); max-height: 50vh; background: #000; }
    .fePvBig { font-size: 64px; line-height: 1; text-align: center; color: #888; padding: 6px 30px; }
    .fePvFontS { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 560px; line-height: 1.3; }
    .fePvPdf { display: block; width: min(440px, 88vw); height: min(560px, 62vh); border: 0; background: #fff; }
    .fePv pre.fePvNote { margin: 0; font: inherit; }
    /* Transfer card: write folders, basket, upload */
    .feXfer { padding: 2px 10px; line-height: 1.6; text-align: left; }
    .feXfRow { margin: 4px 0 8px; }
    .feXfHere { color: #1b7a32; }
    .feXfBtn { border-radius: 54px; padding: 2px 12px; margin: 2px 4px 2px 0; background: ivory; }
    .feXfBtn:disabled { opacity: .5; cursor: default; }
    .feBkList { border-collapse: collapse; margin: 4px 0 2px 18px; font-size: 13px; }
    .feBkList td { padding: 1px 8px 1px 0; vertical-align: middle; }
    .feBkList .feBkSize { text-align: right; color: #555; white-space: nowrap; }
    .feBkDel { border: 0; background: none; color: #b00020; cursor: pointer; padding: 0 4px; }
    .feXfSum { margin-left: 18px; color: #555; font-size: 12px; }
    .feXfDest { margin: 4px 0 2px; }
    .feXfDestRow input { width: min(360px, 60vw); padding: 2px 6px; border: 1px solid #9aa5b1; border-radius: 4px; font: inherit; }
    /* Users and password cards */
    /* Dark theme: the parts of Folder-explorer with their own colours */
    [data-theme="dark"] .fe-drop { background: #1d2731; border-color: #4b6a85; color: #d6dde4; }
    [data-theme="dark"] .fe-drop.fe-over { background: #1f3a26; }
    [data-theme="dark"] .feFileName.feOn { background: #2d4a6b; color: #fff; }
    [data-theme="dark"] .feNiceSec h4 { color: #8cc4f0; }
    [data-theme="dark"] .feXfSum, [data-theme="dark"] .feBkList .feBkSize, [data-theme="dark"] .feUsrTbl th,
    [data-theme="dark"] .feSelDest, [data-theme="dark"] .feXfHere { color: #aeb8c2; }
    [data-theme="dark"] .feUsrNew td { border-top-color: #555; }
    [data-theme="dark"] .fePm, [data-theme="dark"] .fePv { background: #232a31; color: #e3e7eb; border-color: #4a5560; }
    [data-theme="dark"] .fePmItem:hover, [data-theme="dark"] .fePmItem:focus-visible { background: #2d4a6b; }
    [data-theme="dark"] .fePmItem:disabled, [data-theme="dark"] .fePmItem:disabled i { color: #6c7680; }
    [data-theme="dark"] .fePmHead, [data-theme="dark"] .fePvHead small, [data-theme="dark"] .fePmFoot, [data-theme="dark"] .fePvNote { color: #aeb8c2; }
    [data-theme="dark"] .fePmSep, [data-theme="dark"] .fePmHead { border-color: #4a5560; background-color: transparent; }
    [data-theme="dark"] .fePmSep { background: #4a5560; }
    [data-theme="dark"] .fePvTbl { background: #1d2731; }
    [data-theme="dark"] .fePvTbl td { border-color: #3a4a58; }
    [data-theme="dark"] .fePvTbl tr:first-child td { background: #26384a; }
    [data-theme="dark"] .fe-drop .fe-pick { color: #8cc4f0; background: #24323f; border-color: #4b6a85; }
    [data-theme="dark"] .fePmItem.fePmDel, [data-theme="dark"] .fePmItem.fePmDel i { color: #ff7a8a; }
    /* Nice to know: sections in columns */
    .feSelDest { display: block; margin: 4px 0 2px; color: #555; }
    .feNice { columns: 2 340px; column-gap: 22px; font-size: 12.5px; line-height: 1.45; }
    .feNiceSec { break-inside: avoid; margin-bottom: 8px; }
    .feNiceSec h4 { margin: 2px 0 2px; font-size: 13px; color: #1a5276; }
    .feNiceSec ul { margin: 0; padding-left: 16px; }
    .feNiceSec li { margin: 1px 0; }
    .feUsers { text-align: left; }
    .feUsrTbl { border-collapse: collapse; margin-bottom: 6px; }
    .feUsrTbl th { text-align: left; font-size: 12px; color: #555; padding: 2px 6px; border-bottom: 1px solid #ddd; }
    .feUsrTbl td { padding: 3px 4px; vertical-align: middle; text-align: left; }
    .feUsrTbl .feXfBtn { padding: 2px 9px; margin: 0; }
    .feUsrTbl input, .feUsrTbl select, .fePwForm input { padding: 2px 5px; border: 1px solid #9aa5b1; border-radius: 4px; font: inherit; }
    .feUsrNew td { border-top: 1px dashed #ccc; }
    .feUsrDel { color: #b00020; }
    .fePwForm label { display: inline-block; margin: 0 10px 6px 0; }
    /* Menu on the file name (fe-preview.js) */
    .feFileName { cursor: context-menu; border-radius: 3px; }
    #tblSpan0 tr { -webkit-touch-callout: none; }      /* A long press opens the file / folder menu (not the callout of iOS) */
    .feFileName.feOn { background: #e3eefc; }
    .fePm { position: fixed; z-index: 3001; min-width: 190px; padding: 4px; background: #fff; color: #222; text-align: left;
            border: 1px solid #b9b9b9; border-radius: 6px; box-shadow: 0 4px 14px rgba(0,0,0,.25); font: 13px/1.3 Arial, sans-serif; }
    .fePm[hidden] { display: none; }
    .fePmItem { display: block; width: 100%; padding: 5px 10px; border: 0; border-radius: 4px; background: none; color: inherit;
                font: inherit; text-align: left; white-space: nowrap; cursor: pointer; }
    .fePmItem:hover, .fePmItem:focus-visible { background: #e3eefc; outline: none; }
    .fePmItem i { width: 18px; text-align: center; margin-right: 6px; color: #4a6fa5; }
    .fePmItem.fePmDel i, .fePmItem.fePmDel { color: #b00020; }
    .fePmItem:disabled, .fePmItem:disabled i { color: #aaa; cursor: default; background: none; }
    .fePmSep { height: 1px; margin: 4px 6px; background: #ddd; }
    .fePmHead { padding: 3px 10px 5px; font-weight: 600; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 280px; border-bottom: 1px solid #ddd; margin-bottom: 3px; }
    .fePmFoot { padding: 3px 10px 1px; color: #888; font-size: 11px; font-style: italic; }
    .fePvNote { color: #666; font-style: italic; }
    .fePvInfo { margin-top: 5px; padding-top: 4px; border-top: 1px solid #ddd; }
    .fePvInfo .left { margin: 0 !important; }

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
.feTreeWrap { text-align: left; border: 1px solid gray; padding-bottom: 2px; overflow-x: auto; overflow-y: hidden; }
.feRuler { position: relative; height: 18px; margin-top: 6px; border-top: 1px dashed #c5ccd3; font: 11px monospace; color: #8a94a0; white-space: nowrap; }
.feRuler span { position: absolute; top: 3px; }
ul.feTree .feTreeSize { font: 11px monospace; color: #5d6b78; }
ul.feTree .feTreeNum { display: inline-block; min-width: 10ch; text-align: right; }
.feDetTools { text-align: center; padding-top: 10px; }
.feDetTools.feInHead { float: right; padding: 0; margin: -3px 14px -3px 0; cursor: default; }
.feInHead .feModeSw { height: 28px; }
.feDetTools.feInHead { display: flex; align-items: center; gap: 8px; }          /* (frmMode is inside the card form "xxx": the browser drops the inner form tag) */
.feInHead span:has(> #FileSearch) { position: relative; }                       /* The search list opens below the field */
.feInHead .inpField { padding: 0 !important; width: auto !important; vertical-align: middle !important; }
.feInHead .fieldContent { padding: 0 !important; box-shadow: none !important; border: 0 !important; background: transparent !important; }
.feInHead .inpField label { display: none; }                                    /* No "Search" label: the icon says it */
.feInHead .dropbtn { height: 28px; box-sizing: border-box; display: flex; align-items: center; padding: 0 12px !important; border: 1px solid #9aa5b1 !important;
    border-radius: 14px; white-space: nowrap; cursor: pointer; font-size: 13px; }
.feInHead .dropbtn[aria-disabled=true] { cursor: default; color: #888; }
.feInHead .dropdown-content { right: 0; top: calc(100% + 4px); text-align: left; z-index: 1000; }      /* Above the sticky table header */
.feInHead .inpField { position: relative; }
.feInHead .inpField:hover > abbr.hint data-hint {                               /* The hint of the (hidden) label: when the mouse is over the field */
    display: block; position: absolute; left: 0 !important; top: calc(100% + 6px) !important; width: 200px; min-width: 160px;
    border: solid 1px #aaa; border-radius: 6px; box-shadow: 3px 3px 3px var(--ButtnShad); white-space: pre-line; overflow-wrap: break-word;
    background-color: var(--HintsBgrd); color: var(--HintsText); font-style: normal; font-weight: 400; font-size: 12px; text-align: center; padding: 5px 3px; z-index: 1001; }
.feInHead span:has(> #FileSearch.show) .inpField abbr.hint data-hint { display: none; }   /* Not over the open file list */
.feModeSw { display: inline-flex; padding: 0; border: 1px solid #9aa5b1; border-radius: 17px; overflow: hidden; background: #fff; cursor: pointer; height: 33px; vertical-align: middle; box-shadow: none; }
.feModeSw > span { display: inline-flex; align-items: center; gap: 5px; padding: 0 13px; color: #555; font-size: 13px; }
.feModeSw[aria-pressed=false] > .feMq, .feModeSw[aria-pressed=true] > .feMa { background: #3c8dbc; color: #fff; }
.feModeSw:hover > span:not(:only-child) { filter: brightness(0.97); }
.feModeSw:focus-visible { outline: 2px solid #3c8dbc; outline-offset: 2px; }
.feRelSw button { font-size: 12px; padding: 1px 9px; margin: 0; border: 1px solid #9aa5b1; background: #fff; color: #333; cursor: pointer; box-shadow: none; }
.feRelSw button:first-of-type { border-radius: 10px 0 0 10px; }
.feRelSw button:last-of-type  { border-radius: 0 10px 10px 0; border-left: 0; }
.feSizeCol.fePending .feTreeNum { color: #999; }
.feSizeCol .feCount { cursor: pointer; color: #3c8dbc; }
.feCountAll { font-size: 12px; padding: 1px 9px; margin-left: 8px; border: 1px solid #9aa5b1; border-radius: 10px; background: #fff; cursor: pointer; }
.feRelSw button[aria-pressed=true] { background: #3c8dbc; border-color: #3c8dbc; color: #fff; }
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
        $size = p2h_nice_filesize($arrRow['filesize'], true);
        $comp = p2h_nice_filesize($arrRow['compressed_size'], true);
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
          fe_more_time();
          if ($fileinfo->isFile()) {
            $size +=     $fileinfo->getSize();
            if ($fileCount < 5000) $arrNames[]= p2h_norm_path($fileinfo->getPathname());    // The search list: at most 5000 names
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
            '<i>'.lang('@Full path').'</i>: '. p2h_enc(fe_show_path(p2h_convert_win($file_path))). '<br>'.
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

function fe_size_cache($dir, $bytes= null, &$age= null) { // The size of a folder, remembered 15 minutes (a temp file per folder) - for the tree
    // -1 = being counted (or the count ran out of time: then it is not tried again for 15 minutes)
    $f= sys_get_temp_dir().'/fe-size-'.md5(__FILE__.'|'.$dir).'.txt';
    if ($bytes !== null) {
        @file_put_contents($f, (string)$bytes, LOCK_EX);
        if (mt_rand(1, 50) === 1) fe_temp_cleanup();   // Now and then: away with the old temp files
        return $bytes;
    }
    $t= @filemtime($f);
    if ($t === false || $t <= time() - 900) return null;
    $age= time() - $t;
    return (int)@file_get_contents($f);
}

function listFolders($dir,$currentDir='',$depth=0) {    // Echoes the tree below $dir - returns the bytes of the files directly in $dir (when the sizes are shown)
    // The sizes are added up in the browser (fe-tree.js): an opened folder sends its own files (data-own), a closed one its remembered
    // size (data-size) - or it is counted later (data-lazy, ?fsz=), so the page is shown at once also for a whole disk.
    // Only the current folder and its sub folders are counted at once - the rest of the tree is counted when asked (data-manual)
    $sizes= $GLOBALS['fe_treesize'] ?? false;       // The card tool "Show used space"
    $pathOnly= $GLOBALS['fe_unlocked'] ?? false;    // Whole account: only the path to the current folder is opened (else far too many folders)
    $cur= $GLOBALS['currPath'];
    $needOwn= $sizes && (fe_in_dir(p2h_norm_path($dir), $cur) || fe_in_dir($cur, p2h_norm_path($dir)));   // Below the current folder - or on the path to it
    $arrItems = @scandir($dir);
    if (!is_array($arrItems)) return 0;  // Unreadable folder
    $subs= [];  $own= 0;
    fe_more_time();
    foreach ($arrItems as $item) {
        if ($item === '.' || $item === '..') continue;
        if (is_dir($dir.'/'.$item)) { if (!is_link($dir.'/'.$item)) $subs[]= $item; }  // Do not follow symlinks (loops)
        elseif ($needOwn && is_file($dir.'/'.$item)) $own+= (int)@filesize($dir.'/'.$item);
    }
    if (!$subs) return $own;             // No sub folders: no empty list
    $lvlX= $depth ? 65 + ($depth-1)*50 : 30;                               // Back to the left edge of the tree
    echo '<ul class="feTree">';
    foreach ($subs as $item) {
        $full= p2h_norm_path($dir.'/'.$item);
        $here= ($full === $GLOBALS['currPath']);                            // The folder shown below
        $onPath= !$here && fe_in_dir($GLOBALS['currPath'], $full);          // A folder above it
        $open= (!$pathOnly || $here || $onPath) && $depth < 14;             // Same depth as the Level axis below the tree
        echo '<li'.($here ? ' class="feHere"' : '').'>'.
             '<span class="feLvl" style="left: -'.$lvlX.'px;">'.$depth.'</span>'.
             '<i class="'.($here || $onPath ? 'fas fa-folder-open' : 'fas fa-folder').' feTreeIc"></i>'.
             fe_hint('<a href= "'.basename(__FILE__).'?show='.urlencode($dir.'/'.$item). '">'.
                 ($here ? '<b>' : ''). p2h_enc($item). ($here ? '</b>' : '').     // Only the current folder (was every folder with its name)
             '</a>', p2h_enc(fe_show_path($dir)).'/');
        $kids= '';
        if ($open) { ob_start();  $mine= listFolders($full, $currentDir, $depth+1);  $kids= ob_get_clean(); }
        if ($sizes) {
            $liX= $depth ? 35 + ($depth-1)*50 : 0;                         // This level from level 0
            $inCur= fe_in_dir($full, $GLOBALS['currPath']);                  // The current folder or below it: counted at once
            $known= ($open && ($inCur || $onPath)) ? null : fe_size_cache($full);
            $attr=  ($open && ($inCur || $onPath)) ? 'data-own="'.$mine.'"'
                  : ($known !== null && $known >= 0 ? 'data-size="'.$known.'"' : 'data-lazy="'.p2h_enc($full).'"'.($inCur ? '' : ' data-manual="1"'));
            echo '<span class="feSizeCol" '.$attr.
                     ' style="left: calc(var(--feSizeX, 400px) - '.(30 + $liX).'px);">'.
                 fe_hint('<span class="feTreeSize"><span class="feTreeBar"><span></span></span><span class="feTreeNum">&hellip;</span></span>',
                         '<span class="feTreeB"></span> Bytes<br><span class="feTreePct"></span>').'</span>';
        }
        echo $kids.'</li>';
    }
    echo '</ul>';
    return $own;
}

function p2h_fileExplore($path,$parentDir) {
    global $exch_path, $can_write_here, $basket, $fe_unlocked;
    global $calc_folder, $totFolds, $totFiles, $totSize, $filesIncurrent, $locSize, $filesize_raw, $view_title, $root_dir, $root_path, $currentPath, $arrNames, $quickView, $Context_Row, $ix;
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
            fe_more_time();
            $maxsize+= p2h_get_size($path. '/'. $fld); 
            $filesIncurrent+= 1;
        }
        $infoOf= [];                                    // Counted once - used again below (was counted twice)
        foreach ($folders as $fld) {
            $infoOf[$fld]= $foldinfo= p2h_get_directoryInfo($path. '/'. $fld);
            $maxsize+=  $foldinfo[0];
        }
    }
    foreach ($folders as $fld) {
        fe_more_time();
        $file_path= $path. '/'. $fld;
        $fileRef[]= $file_path;
        $is_link = is_link($file_path);
        $ext = pathinfo($fld, PATHINFO_EXTENSION);
        $icoc = $is_link ? 'icon-link_folder' : 'far fa-folder colr0';
        if ($calc_folder == true) {
            $foldinfo = $infoOf[$fld] ?? p2h_get_directoryInfo($file_path);
            $foldsize  = $foldinfo[0];
            $filecount = $foldinfo[1];
            $dir_count = $foldinfo[2];
            if (count($arrNames) < 5000) $arrNames= array_merge($arrNames, array_slice($foldinfo[3], 0, 5000 - count($arrNames)));
            $totFiles += $filecount;
            $totFolds += $dir_count;
            $totSize  += $foldsize;
            $showsize  = p2h_nice_filesize($foldsize, true);
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
        $meter= $calc_folder ? $foldsize / ($maxsize + 0.00001) : 0;     // (Synchronize and ZIP: in the folder menu - right-click the row)
  //      onclick="window.open('http://kanishkkunal.in','popup','width=600,height=600,scrollbars=no,resizable=no'); return false;">
  
        $rec['ix']= $ix;
        $rec['name']= '<div style="white-space: nowrap; margin: 3px 0 0 0;">'. '<div style="display: inline;"><i class="far fa-folder colr0" style="background-color: gold;"></i>'.
          // str_WithHint($labl='<a href="'.basename(__FILE__). '?show='.$path.'/'.$fld. '" class="txtbutt">&nbsp;'. $fld.'</a>', 
             str_WithHint($labl='<a href="'.basename(__FILE__). '?show='.urlencode($file_path). '" class="txtbutt">&nbsp;'. p2h_enc($fld).'</a>',
             $hint=lang('@Show content in subfolder: <b>').p2h_enc($fld).'</b>').'</div>'.
             '<span class="feBp" data-bp="'.p2h_enc(p2h_norm_path($file_path)).'" data-fm="'.p2h_enc(json_encode([     // The folder menu (fe-preview.js): right-click the row
                 'dir'=>1, 'n'=>$fld, 'r'=>ltrim(substr(p2h_norm_path($file_path), strlen($root_path)), '/'), 't'=>lang('@Folder'),
                 's'=>$calc_folder ? number_format((float)$foldsize, 0, ',', ' ') : '', 'm'=>$modif, 'a'=>'', 'p'=>$perms, 'u'=>(string)$owner['name'],
                 'e'=>($can_write_here && $fld[0] !== '.') ? 1 : (!fe_can('w') ? 2 : ($can_write_here ? 3 : 0)), 'w'=>$can_write_here && fe_can('w') ? 1 : 0,
                 'g'=>basename(__FILE__).'?show='.urlencode($file_path), 'b'=>p2h_norm_path($file_path),
                 'k'=>in_array(p2h_norm_path($file_path), $basket ?? [], true) ? 1 : 0], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)).'" hidden></span></div>';
        $rec['ext']= $ext;
        $rec['size']= $sortsize.'<div style="font-family:monospace; color:green;">'.fe_hint($showsize, number_format((float)$foldsize, 0,',',' ').' Bytes').'</div>';
        $rec['modifyed']= $sorttime.$modif;
        $rec['perms']= $perms;
        $rec['owner']= p2h_enc($owner['name']);      //$rec['group']= $group['name'];
        $rec['access']= '-';
        $rec['space']= '<meter id="currFold'.$ix.'" low="0.15" optimum="0.30" high="0.60" max="1" value="'.$meter.'" style= "width: 95%;">'.$meter.'</meter>
                        <small style="/* width: 40px; */ display: inline-block; text-align: right; left: 40%; top: -16px; position: relative; background: lightyellow; opacity: 0.7;">'.number_format($meter*100,2).' %</small>';
        $foldRecords[]= $rec;
        
        flush();
        $ix++;
    }
    
    $filesInsub= $ix;       $locSize= 0;    $all_files_size= 0; $meter= 0;
    foreach ($files as $fld) {
        fe_more_time();
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
        $showsize = p2h_nice_filesize($filesize_raw, true);
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
        }
        // Where the user may read: a download icon before the name - where the user may write: rename, copy ... in the file menu
        $wr_file= $can_write_here && fe_name_allowed($fld);
        $can_dl=  fe_can_read($file_path);
        $dlUrl=   basename(__FILE__).'?dl='.rawurlencode(p2h_norm_path($file_path));
        // The file icon: preview when the mouse is over it (fePv script) - the file information of Analyse mode is shown below it
        $pvKind= $is_link ? '' : fe_preview_kind($fld, $can_dl, $can_dl || fe_media_ok($file_path));
        $ic= '<i class="'.$icoc.' feFileIc" data-pk="'.$pvKind.'" data-pn="'.p2h_enc($fld).'" data-ps="'.p2h_enc(html_entity_decode(p2h_nice_filesize($filesize_raw))).'" data-pm="'.$modif.'"'.
             ($pvKind > '' && $pvKind !== 'lock' ? ' data-pv="'.p2h_enc(basename(__FILE__).'?pv='.rawurlencode(p2h_norm_path($file_path)).'&v='.$ftime).'"' : '').'></i>';
        // The file name: a menu when the mouse rests on it (fe-preview.js) - only the actions the user may do
        $fm= ['n'=>$fld, 'r'=>ltrim(substr(p2h_norm_path($file_path), strlen($root_path)), '/'), 't'=>lang('@'.$showType),
              's'=>number_format((float)$filesize_raw, 0, ',', ' '), 'm'=>$modif, 'a'=>$acces, 'p'=>$perms, 'u'=>(string)$owner['name'],
              'e'=>$wr_file ? 1 : (!fe_can('w') ? 2 : ($can_write_here ? 3 : 0)),  // Change the file: 1 = yes, 2 = the account may not, 3 = a protected file, 0 = not a write folder
              'b'=>p2h_norm_path($file_path), 'k'=>in_array(p2h_norm_path($file_path), $basket ?? [], true) ? 1 : 0,     // Basket: path, in it
              'w'=>$can_write_here && fe_can('w') ? 1 : 0,                                                              // New folder here
              'o'=>($pvKind > '' && $pvKind !== 'lock') ? basename(__FILE__).'?pv='.rawurlencode(p2h_norm_path($file_path)).'&v='.$ftime.($pvKind === 'table' ? '&raw=1' : '') : '',
              'd'=>$can_dl ? $dlUrl : ''];
        $nm= '<span class="feBp" data-bp="'.p2h_enc(p2h_norm_path($file_path)).'" hidden></span><span class="feFileName" tabindex="0" role="button" aria-haspopup="menu" data-fm="'.p2h_enc(json_encode($fm, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)).'">'.p2h_enc($fld).'</span>';
        if ($calc_folder)
            $nameFld= '<div class="file-name-div">'.$ic.'&nbsp;'. $nm. ($ix < 1200 ? '<div class="live-preview-img">'.$fileInfo.'</div>' : ''). '</div>';  // Max 200 on one page ($ix starts at 1000)
        else
            $nameFld= $ic.'&nbsp;'. $nm;
        if ($can_dl)
            $nameFld= fe_hint('<a href="'.p2h_enc($dlUrl).'" download><i class="fas fa-download colr9"></i></a>', lang('@Download to your PC')).' '.$nameFld;
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

   
        $ix++; 
        $fileRecordsIncurrent= $ix-$filesInsub;
    }
    return array($foldRecords ?? null, $fileRecords ?? null, $fileRef ?? null);
} // p2h_fileExplore()
    


function p2h_getFileList($dir, $recurse=false, $depth=false)    // Create list of files recursive, and save to array
{ $return = array();
  if(substr($dir, -1) != "/") $dir .= "/";                  // Add slash, if missing
  $dirPtr = @dir($dir)                                      // Create pointer to the folder
    or die(lang('@p2h_getFileList: Opening Folder ').$dir.lang('@  for Reading... '));
  while (false !== ($entry = $dirPtr->read())) {            // and read the list of files
    fe_more_time();
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
              valu: '<div><i class="fa fa-search"></i> '.($calc_folder== true ? ($GLOBALS['totFiles'] ?? count($arrNames)).' '.lang('@Files in subfolders') : lang('@Only in Analyse mode')).'</div>',
              form: '', wdth: '200px', algn: 'left',attr: 'onclick="ToggShow()" class="dropbtn" ',rtrn: true,
              unit: '', disa: !$calc_folder, rows: '0', step: '', list: [], llgn: 'R', bord: '', ftop: '')
              . '
    <div id="FileSearch" class="dropdown-content" 
        >
        <input type="text" placeholder="'.p2h_enc(lang('@Part of a file or folder name...')).'" id="searchInput" onkeyup="filterFunction()" z-index:202;
            aria-label="'. p2h_enc(lang('@Type a search string: Part of the file- / folder-name')).'"
            style="width:200px; text-align:left; font-family:arial; border: 1px solid black;">
        <div style="font-size: 11px; color: gray; text-align: left; padding: 2px 6px;">'. lang('@Click to open folder containing this file ') .
            (($GLOBALS['totFiles'] ?? 0) > count($arrNames) ? '<br>'.p2h_enc(lang('@The search list has the first')).' '.count($arrNames).' - '.p2h_enc(lang('@go to a sub folder to search the rest')) : '').'</div>';
        $name_list= ''; // List to search for file names in sub-folders 
        foreach($arrNames as $file) {
            $pos= strrpos($file,'/')+1;
            $dir= substr($file,0,$pos);
            $fil= substr($file,$pos);
            $shown= fe_show_path(rtrim($dir, '/')).'/';
            $name_list .= '<a href="'. basename(__FILE__). '?show='.urlencode(rtrim($dir,'/')).'" >'.
                        '<span class="colrgray">'.p2h_enc(mb_strlen($shown) > 32 ? '...'.mb_substr($shown,-32) : $shown).':</span> '. p2h_enc($fil).'</a>';
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

if (isset($_GET['logout']) && is_string($_GET['t'] ?? null) && hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$_GET['t'])) {
    unset($_SESSION[p2h_SESSION_ID]['logged'], $_SESSION[p2h_SESSION_ID]['unlocked'], $_SESSION[p2h_SESSION_ID]['basket'], $_SESSION[p2h_SESSION_ID]['fdl']);
    header('Location: '.basename(__FILE__));   // A clean address: the login form must not post ?logout again
    exit;
}

if ($use_auth) {
    $logged= $_SESSION[p2h_SESSION_ID]['logged'] ?? null;
    if ($logged !== null && isset($auth_users[$logged])) {
        // Logged:
    } elseif (isset($_POST['p2h_usr'], $_POST['p2h_pwd']) && fe_login_tries() >= 10) {
        unset($_SESSION[p2h_SESSION_ID]['logged']);     // Too many failed logins from this address - also when the password is right
        sleep(1);
        p2h_set_msg('@Too many failed logins - wait 15 minutes and try again', 'error');
    } elseif (isset($_POST['p2h_usr'], $_POST['p2h_pwd']) && ($_POST['p2h_human'] ?? '') !== 'notbot') {
        unset($_SESSION[p2h_SESSION_ID]['logged']);     // The slider "Are you human?" was not moved (it was only checked in the browser)
        sleep(1);
        p2h_set_msg('@Please complete the captcha', 'error');
    } elseif (isset($_POST['p2h_usr'], $_POST['p2h_pwd'])) {
        // Logging In:
        unset($_SESSION[p2h_SESSION_ID]['logged']);
        sleep(1);   // Slow down password guessing
        $usr= (string)$_POST['p2h_usr'];
        $pwd= (string)$_POST['p2h_pwd'];
        if (isset($auth_users[$usr][0]) && password_verify($pwd, $auth_users[$usr][0])) {
            if (!headers_sent()) session_regenerate_id(true);    // Prevent session fixation
            $_SESSION['csrf_token']= bin2hex(random_bytes(32));  // A new token for the logged in session
            fe_login_tries(false);
            $_SESSION[p2h_SESSION_ID]['logged'] = $usr;
            $_SESSION[p2h_SESSION_ID]['pwfp']= substr(hash('sha256', (string)$auth_users[$usr][0]), 0, 16);     // The other sessions are logged out when it changes
            unset($_SESSION[p2h_SESSION_ID]['unlocked'], $_SESSION[p2h_SESSION_ID]['basket'], $_SESSION[p2h_SESSION_ID]['fdl']);  // A new login: restrictions on, an empty basket
            p2h_set_msg(lang('@You are logged in as: ').p2h_enc($usr));
        } else {
            if (!isset($auth_users[$usr][0])) password_verify($pwd, '$2y$10$kDgQcpPzD8aMOLCS/fb74.P/19oWozxK99H1enPpvLdVOhR64u9Du');  // The same time
            fe_login_tries(true);
            p2h_set_msg('@Login failed: Invalid account name and/or password. ', 'error');
        }
    } else unset($_SESSION[p2h_SESSION_ID]['logged']);
} else $_SESSION[p2h_SESSION_ID]['logged']= $_SESSION[p2h_SESSION_ID]['logged'] ?? 'guest';

if ($use_auth && isset($_POST['p2h_usr'], $_POST['p2h_pwd']) && !headers_sent()) {     // Post/Redirect/Get: the message is in the session
    if (!isset($_SESSION[p2h_SESSION_ID]['logged'])) $_SESSION[p2h_SESSION_ID]['last_user']= mb_substr(trim((string)$_POST['p2h_usr']), 0, 64);   // Not logged in: the name again
    else unset($_SESSION[p2h_SESSION_ID]['last_user']);
    header('Location: '.basename(__FILE__).(is_string($_GET['show'] ?? null) ? '?show='.urlencode($_GET['show']) : ''), true, 303);   // The folder asked for
    exit;
}
// The password of the account changed (by the user in another session, or by an administrator): this session is logged out
if (isset($_SESSION[p2h_SESSION_ID]['logged'], $_SESSION[p2h_SESSION_ID]['pwfp']) && isset($auth_users[$_SESSION[p2h_SESSION_ID]['logged']])
    && !hash_equals((string)$_SESSION[p2h_SESSION_ID]['pwfp'], substr(hash('sha256', (string)$auth_users[$_SESSION[p2h_SESSION_ID]['logged']][0]), 0, 16))) {
    unset($_SESSION[p2h_SESSION_ID]['logged'], $_SESSION[p2h_SESSION_ID]['unlocked'], $_SESSION[p2h_SESSION_ID]['basket'], $_SESSION[p2h_SESSION_ID]['fdl'], $_SESSION[p2h_SESSION_ID]['pwfp']);
    p2h_set_msg('@The password of the account was changed - log in again', 'error');
}
if ($use_auth && isset($_SESSION[p2h_SESSION_ID]['logged']) && !isset($auth_users[$_SESSION[p2h_SESSION_ID]['logged']])) {     // The account was deleted
    unset($_SESSION[p2h_SESSION_ID]['logged'], $_SESSION[p2h_SESSION_ID]['unlocked'], $_SESSION[p2h_SESSION_ID]['basket'], $_SESSION[p2h_SESSION_ID]['fdl'], $_SESSION[p2h_SESSION_ID]['pwfp']);
    p2h_set_msg('@The account does not exist any more', 'error');
}
if (isset($_SESSION[p2h_SESSION_ID]['logged']) && !isset($_SESSION[p2h_SESSION_ID]['pwfp']) && isset($auth_users[$_SESSION[p2h_SESSION_ID]['logged']][0]))
    $_SESSION[p2h_SESSION_ID]['pwfp']= substr(hash('sha256', (string)$auth_users[$_SESSION[p2h_SESSION_ID]['logged']][0]), 0, 16);     // (a session from before)
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

##### RIGHTS: write in the account's write folders - read (download, content) there and in the shared folder ($fe_exchange_path)
$exch_path=      $is_logged ? fe_exchange_init($fe_exchange_path) : false;
$can_write_here= $is_logged && fe_can_write($currPath);
$quota_here=     $exch_path !== false && fe_in_dir($currPath, $exch_path) && !$fe_unlocked;     // Only the shared folder has a quota

if ($is_logged && isset($_GET['fdl'])) {                // A prepared download (fe_offer_download) - once
    $tok= (string)$_GET['fdl'];
    $d=   $_SESSION[p2h_SESSION_ID]['fdl'][$tok] ?? null;
    unset($_SESSION[p2h_SESSION_ID]['fdl'][$tok]);
    foreach ($_SESSION[p2h_SESSION_ID]['fdl'] ?? [] as $k => $o)        // Never fetched (older than an hour): forget it - and its temp file
        if ($o['at'] < time() - 3600) { if ($o['t']) @unlink($o['f']);  unset($_SESSION[p2h_SESSION_ID]['fdl'][$k]); }
    if (!$d || !is_file($d['f'])) { http_response_code(404);  exit(p2h_enc(lang('@File not found'))); }
    if ($d['t']) register_shutdown_function(function() use ($d) { @unlink($d['f']); });
    fe_send_file($d['f'], $d['n']);
}

if ($is_logged && isset($_GET['dl'])) {                 // Download a file to the user's PC - where the user may read
    $dl=   str_replace('\\', '/', (string)$_GET['dl']);
    $file= preg_match('#^(/|[a-z]:/)#i', $dl) ? realpath($dl) : ($exch_path ? realpath($exch_path.'/'.ltrim($dl, '/')) : false);  // Old links: below the shared folder
    if ($file === false || !is_file($file) || !fe_can_read($file)) {
        http_response_code(404);
        exit(p2h_enc(lang('@File not found')));
    }
    fe_send_file($file);
}

if ($is_logged && isset($_GET['fsz'])) {               // The size of one folder of the tree (fe-tree.js) - remembered 15 minutes
    session_write_close();                              // Counting a big folder must not lock the session of the other requests
    $dir= realpath((string)$_GET['fsz']);
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: private, no-store');
    if ($dir === false || !is_dir($dir) || !fe_in_dir(p2h_norm_path($dir), $root_path)) { http_response_code(404);  exit('{}'); }
    $dir= p2h_norm_path($dir);
    $known= fe_size_cache($dir, null, $age);
    if ($known !== null && $known >= 0) exit(json_encode(['b' => $known]));
    if ($known === -1) exit(json_encode($age < $fe_max_time + 30 ? ['busy' => 1] : ['err' => 1]));     // Being counted - or it ran out of time
    fe_size_cache($dir, -1);                            // Counting now: another page view waits for it instead of starting it again
    exit(json_encode(['b' => fe_size_cache($dir, fe_dir_size($dir))]));
}

if ($is_logged && isset($_GET['pv'])) {                // Hover preview of a file icon (fe-preview.js)
    session_write_close();                              // A long video must not lock the session of the other requests
    $file= realpath((string)$_GET['pv']);
    $kind= '';
    if ($file !== false && is_file($file) && fe_in_dir(p2h_norm_path($file), $root_path)) {
        $kind= fe_preview_kind(basename($file), fe_can_read($file), fe_media_ok($file));
    }
    while (ob_get_level()) ob_end_clean();
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=300');
    if ($kind !== 'pdf')                                 // An SVG or HTML opened directly runs no script (the PDF viewer of the browser needs a frame without sandbox)
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");
    $mime= FE_PV_MEDIA[strtolower(pathinfo((string)$file, PATHINFO_EXTENSION))][1] ?? '';
    if (in_array($kind, ['img','video','audio','font','pdf'], true) && ($kind !== 'img' || filesize($file) <= 20 * 1024 * 1024))
        fe_send_inline($file, $mime);
    if ($kind === 'table' && ($tb= fe_preview_table($file)) !== null) {
        if (!empty($_GET['raw'])) {                     // "View" in a new tab: the rows as text
            header('Content-Type: text/plain; charset=utf-8');
            exit(($tb['sheet'] > '' ? $tb['sheet']."\n\n" : '').implode("\n", array_map(fn($r) => implode("\t", $r), $tb['rows'])).($tb['more'] ? "\n…" : ''));
        }
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode($tb, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
    }
    if (in_array($kind, ['txt','zip','doc','arch','hex'], true) && ($txt= fe_preview_text($file, $kind)) !== null) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $txt;
        exit;
    }
    http_response_code(404);
    exit;
}

if ($is_logged && isset($_FILES['fe_upload'])) {     // Upload files from the user's PC to the current folder (form, or batches from fe-upload.js)
    $ajax= ($_POST['fe_ajax'] ?? '') === '1';   // fe-upload.js wants a JSON answer per batch
    $done= [];  $fail= [];  $error= '';         // done: [index, saved path]   fail: [index, name, reason]
    $upDest= fe_can('w') && csrf_verify() ? fe_resolve_dest_new($_POST['fe_dest'] ?? '') : fe_resolve_dest($_POST['fe_dest'] ?? '');      // The destination (Transfer card) - empty: the current folder
    if (!fe_can('w'))       $error= lang('@Your account may not upload files');
    elseif ($upDest === false) $error= lang('@The destination folder is not found').': '.(string)($_POST['fe_dest'] ?? '');
    elseif (!fe_can_write($upDest)) $error= lang('@You may not write in the destination folder').': '.fe_show_path($upDest);
    elseif (!csrf_verify()) $error= lang('@The form has expired - reload the page and try again');
    else {
        $quota_up= $exch_path !== false && fe_in_dir($upDest, $exch_path) && !$fe_unlocked;     // Only the shared folder has a quota
        $up= $_FILES['fe_upload'];
        foreach (['name','tmp_name','size','error'] as $k) $up[$k]= (array)$up[$k];
        $rels= (array)($_POST['fe_relpath'] ?? []);     // Relative paths of files from a chosen / dropped folder
        foreach (array_slice(array_filter((array)($_POST['fe_mkdir'] ?? []), 'is_string'), 0, 1000) as $rel)    // Empty folders of a dropped folder
            fe_rel_dir($upDest, rtrim($rel, '/').'/-');
        $used= $quota_up ? fe_dir_size($exch_path) : 0;   $quota= $quota_up ? $fe_exchange_quota : PHP_INT_MAX;
        foreach ($up['name'] as $i => $orig) {
            $err= (int)$up['error'][$i];    $size= (int)$up['size'][$i];    $name= fe_clean_name($orig);
            if (substr(trim(basename(strtr((string)$orig, '\\', '/'))), 0, 1) === '.') $name= '.'.$name;   // A hidden file stays hidden - and is refused
            if ($err === UPLOAD_ERR_NO_FILE) continue;
            $dir= fe_rel_dir($upDest, is_string($rels[$i] ?? null) ? $rels[$i] : '');
            if     ($err !== UPLOAD_ERR_OK)          $why= lang('@upload error').' '.$err.($err <= 2 ? ' - '.lang('@the file is too large') : '');
            elseif (!fe_name_allowed($name))         $why= lang('@this file type is not allowed');
            elseif ($size > $fe_max_upload && !$fe_unlocked) $why= lang('@the file is too large');
            elseif ($used + $size > $quota)          $why= lang('@the shared folder is full');
            elseif ($dir === false)                  $why= lang('@the folder name is not allowed');
            else {
                $target= $dir.'/'.$name;    $n= 2;      // Never overwrite: "name (2).ext"
                while (file_exists($target) || is_link($target))
                    $target= $dir.'/'.pathinfo($name, PATHINFO_FILENAME).' ('.$n++.')'.(pathinfo($name, PATHINFO_EXTENSION) > '' ? '.'.pathinfo($name, PATHINFO_EXTENSION) : '');
                if (move_uploaded_file($up['tmp_name'][$i], $target)) {
                    @chmod($target, 0644);  $used+= $size;
                    $done[]= [$i, fe_show_path($target)];  continue;
                }
                $why= lang('@could not be saved');
            }
            $fail[]= [$i, $name ?: (string)$orig, $why];
        }
    }
    if ($ajax) {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $error, 'done' => $done, 'fail' => $fail,
                          'show' => basename(__FILE__).'?show='.urlencode(is_string($upDest ?? null) ? $upDest : $currPath)]);   // The destination
        exit;
    }
    $failTxt= implode(', ', array_map(fn($f) => p2h_enc($f[1]).' ('.p2h_enc($f[2]).')', $fail));
    if ($error)    fe_msg(p2h_enc($error), 'error');
    elseif ($done) fe_msg(p2h_enc(lang('@Uploaded')).': <b>'.implode(', ', array_map(fn($d) => p2h_enc($d[1]), $done)).'</b>'.
                          ($fail ? '<br>'.p2h_enc(lang('@Not uploaded')).': '.$failTxt : ''), $fail ? 'error' : 'ok');
    else           fe_msg(p2h_enc(lang('@Not uploaded')).': '.($fail ? $failTxt : p2h_enc(lang('@no file selected'))), 'error');
    fe_redirect($currPath);
}

if ($is_logged && isset($_POST['fe_delete'])) {      // Delete a file in the current folder
    $name= fe_given_name($_POST['fe_delete']);
    $file= realpath($currPath.'/'.$name);
    if (!fe_can('w') || !$can_write_here) fe_msg(p2h_enc(lang('@You may not write in this folder')), 'error');
    elseif (!csrf_verify()) fe_msg(p2h_enc(lang('@The form has expired - reload the page and try again')), 'error');
    elseif ($file === false || !is_file($file) || p2h_norm_path(dirname(p2h_norm_path($file))) !== $currPath || !fe_name_allowed($name))
                            fe_msg(p2h_enc(lang('@File not found')).': '.p2h_enc($name), 'error');
    elseif (@unlink($file)) fe_msg(p2h_enc(lang('@Deleted')).': <b>'.p2h_enc($name).'</b>');
    else                    fe_msg(p2h_enc(lang('@Could not delete')).': '.p2h_enc($name), 'error');
    fe_redirect($currPath);
}
if ($is_logged && isset($_POST['fe_act'])) {      // File / folder menu: rename / move, copy, ZIP, unpack, new folder - in the current folder
    $act=  (string)$_POST['fe_act'];
    $name= fe_given_name($_POST['fe_name'] ?? '');
    $to=   trim(str_replace('\\', '/', (string)($_POST['fe_to'] ?? '')), '/ ');
    $file= $name === '' ? false : realpath($currPath.'/'.$name);
    $isDir= $file !== false && is_dir($file);
    $err=  '';  $target= '';  $n= 0;  $skip= 0;
    $room= $quota_here ? $fe_exchange_quota - fe_dir_size($exch_path) : PHP_INT_MAX;
    if (!fe_can('w') || (!$can_write_here && $act !== 'sync')) $err= lang('@You may not write in this folder');
    elseif (!csrf_verify()) $err= lang('@The form has expired - reload the page and try again');
    elseif ($act === 'sync') {              // Synchronize a folder of this folder to a destination folder (it writes only there)
        $dest= '';
        foreach ([$root_path.'/'.ltrim($to, '/'), $to] as $try) {
            $d= $to === '' ? false : realpath($try);
            if ($d !== false && is_dir($d) && fe_in_dir(p2h_norm_path($d), $root_path)) { $dest= p2h_norm_path($d);  break; }
        }
        if (!$isDir || p2h_norm_path(dirname(p2h_norm_path($file))) !== $currPath) $err= lang('@File not found').': '.$name;
        elseif ($dest === '')                                     $err= lang('@The destination folder is not found').': '.$to;
        elseif (!fe_can_write($dest))                             $err= lang('@You may not write in the destination folder').': '.$to;
        elseif (fe_in_dir($dest, p2h_norm_path($file)) || $dest === p2h_norm_path($file)) $err= lang('@Not an allowed name or folder').': '.$to;
        elseif ($exch_path && fe_in_dir($dest, $exch_path) && !$fe_unlocked && fe_dir_size($file) > $fe_exchange_quota - fe_dir_size($exch_path))
                                                                  $err= lang('@the shared folder is full');
        else $target= $dest;
    }
    elseif ($act === 'mkdir') {             // A new folder in the current folder
        $new= fe_clean_name($to);
        if ($new === '' || strpos($to, '/') !== false) $err= lang('@Not an allowed name or folder').': '.$to;
        elseif (file_exists($target= $currPath.'/'.$new)) $err= lang('@There is a file with this name already').': '.$new;
    }
    elseif (!in_array($act, ['move', 'copy', 'zip', 'unzip'], true) || $file === false || ($isDir ? $act === 'unzip' : !is_file($file))
            || p2h_norm_path(dirname(p2h_norm_path($file))) !== $currPath || (!$isDir && !fe_name_allowed($name)))
                            $err= lang('@File not found').': '.$name;
    elseif ($act === 'zip') {               // name.zip beside it - never overwrite: "name (2).zip"
        $target= fe_unique($currPath.'/'.($isDir ? $name : preg_replace('/\.[^.]*$/', '', $name)).'.zip');
    } elseif ($act === 'move' || $act === 'copy') {     // A new name - or a path below the current folder: "old/new name.txt"
        $into= realpath($currPath.'/'.$to);               // An existing folder ("sub" or "sub/"): into it, with the same name
        if (substr((string)($_POST['fe_to'] ?? ''), -1) === '/' || ($to !== '' && $into !== false && is_dir($into) && p2h_norm_path($into) !== p2h_norm_path($file)))
            $to= trim($to, '/').'/'.$name;
        $new= fe_clean_name($to);
        $toDir= p2h_norm_path($currPath.'/'.(strpos($to, '/') !== false ? substr($to, 0, strrpos($to, '/')) : ''));
        $dir= ($new === '' || (!$isDir && !fe_name_allowed($new)) || ($isDir && fe_in_dir($toDir, p2h_norm_path($file)))) ? false : fe_rel_dir($currPath, $to);   // Checked before folders are made
        $dirReal= $dir === false ? '' : p2h_norm_path(realpath($dir) ?: '');
        if ($dir === false || (!$isDir && !fe_name_allowed($new)) || !fe_can_write($dirReal) || ($isDir && fe_in_dir($dirReal, p2h_norm_path($file))))
            $err= lang('@Not an allowed name or folder').': '.$to;
        elseif (file_exists($target= $dir.'/'.$new) && !($act === 'move' && strtolower(p2h_norm_path(realpath($target) ?: '')) === strtolower(p2h_norm_path($file))
                                                           && basename($target) !== $name))     // Only upper / lower case changed: allowed
            $err= lang('@There is a file with this name already').': '.$to;
    }
    if ($err === '' && ($act === 'copy' || $act === 'zip') && ($isDir ? fe_dir_size($file) : filesize($file)) > $room) $err= lang('@the shared folder is full');
    if ($err === '') {
        if ($act === 'sync')      { fe_sync_tree($file, $target, $n, $skip);  $ok= true; }
        elseif ($act === 'mkdir') $ok= @mkdir($target, 0755);
        elseif ($act === 'move')  $ok= @rename($file, $target);
        elseif ($act === 'copy')  { if ($isDir) { fe_copy_tree($file, $target, $n, $skip);  $ok= is_dir($target); } else $ok= @copy($file, $target); }
        elseif ($act === 'unzip') $ok= fe_unzip($file, $currPath, $room, $n, $skip) && $n > 0;
        else {
            $ok= false;
            if (class_exists('ZipArchive')) {
                $z= new ZipArchive();
                if ($z->open($target, ZipArchive::CREATE | ZipArchive::EXCL) === true) { fe_zip_add($z, $file, $name, $n, $skip);  $z->close(); }
                $ok= is_file($target) && $n > 0;
                if (!$ok) @unlink($target);
            }
        }
        $rel= ltrim(substr(p2h_norm_path($target), strlen($currPath)), '/');
        $skipTxt= $skip ? ' - '.$skip.' '.p2h_enc(lang('@skipped')) : '';
        if (!$ok)                 fe_msg(p2h_enc(lang('@It could not be done')).': '.p2h_enc($name ?: $to).$skipTxt, 'error');
        elseif ($act === 'sync')  fe_msg(p2h_enc(lang('@Synchronized')).' <b>'.p2h_enc($name).'</b> &rarr; <b>'.p2h_enc(ltrim(substr($target, strlen($root_path)), '/') ?: '/').'</b>: '.
                                         $n.' '.p2h_enc(lang('@files copied or updated')).$skipTxt, $skip ? 'error' : 'ok');
        elseif ($act === 'unzip') fe_msg(p2h_enc(lang('@Unpacked')).': <b>'.$n.'</b> '.p2h_enc(lang('@files')).
                                         ($skip ? ' - '.$skip.' '.p2h_enc(lang('@skipped (not allowed names, too large, or more than 20000)')) : ''), $skip ? 'error' : 'ok');
        else                      fe_msg(p2h_enc(lang(['mkdir'=>'@Folder created', 'move'=>'@Renamed / moved to', 'copy'=>'@Copied to', 'zip'=>'@ZIP file created'][$act])).
                                         ': <b>'.p2h_enc($rel).'</b>'.$skipTxt, $skip ? 'error' : 'ok');
    } else fe_msg(p2h_enc($err), 'error');
    fe_redirect($currPath);
}

##### ACCOUNTS: the users (admins) - and the own password (everybody but a read only account)
if ($is_logged && (isset($_POST['fe_users']) || isset($_POST['fe_pw']))) {
    $me= $_SESSION[p2h_SESSION_ID]['logged'] ?? '';
    $back= basename(__FILE__).'?show='.urlencode($currPath).(isset($_POST['fe_users']) ? '&users=1' : '&pw=1');
    $err= '';  $ok= '';
    $users= $auth_users;  $admins= $fe_admins;
    if (!csrf_verify()) $err= lang('@The form has expired - reload the page and try again');
    elseif (isset($_POST['fe_pw'])) {                   // The own password
        $old= (string)($_POST['pw_old'] ?? '');  $new= (string)($_POST['pw_new'] ?? '');
        if (!fe_may_change_pw($me))                                   $err= lang('@This account can not change its password');
        elseif (!password_verify($old, (string)($users[$me][0] ?? ''))) $err= lang('@The present password is wrong');
        elseif (mb_strlen($new) < 8)                                  $err= lang('@The new password must have at least 8 characters');
        elseif ($new !== (string)($_POST['pw_new2'] ?? ''))           $err= lang('@The two new passwords are not the same');
        else {
            $users[$me][0]= password_hash($new, PASSWORD_DEFAULT);  $ok= lang('@Your password is changed');
            $_SESSION[p2h_SESSION_ID]['pwfp']= substr(hash('sha256', $users[$me][0]), 0, 16);       // This session stays - the others are logged out
        }
    }
    elseif (!fe_is_admin()) $err= lang('@Only an administrator can manage the users');
    else {                                              // Users: save (new or changed) / delete
        $op=   (string)$_POST['fe_users'];
        $name= trim((string)($_POST['u_name'] ?? ''));
        if (!preg_match('/^[A-Za-z0-9._-]{2,32}$/', $name)) $err= lang('@A user name: 2-32 letters, digits . _ -').': '.$name;
        elseif ($op === 'delete') {
            if (!isset($users[$name]))                          $err= lang('@Unknown user').': '.$name;
            elseif ($name === $me)                              $err= lang('@You can not delete yourself');
            else { unset($users[$name]);  $admins= array_diff($admins, [$name]);  $ok= lang('@User deleted').': '.$name; }
        } elseif ($op === 'save') {
            $isNew= !isset($users[$name]);
            $pwd=   (string)($_POST['u_pwd'] ?? '');
            $dirs=  [];
            foreach (preg_split('/[,\n]+/', (string)($_POST['u_dirs'] ?? '')) as $d) {
                $d= trim(str_replace('\\', '/', $d));
                if ($d === '') continue;
                if ($d === '/') { $dirs[]= '';  continue; }                    // "/" = everything below the root
                $d= trim($d, '/');
                if (preg_match('#(^|/)\.#', $d) || strpbrk($d, ':*?"<>|') !== false) { $err= lang('@Not an allowed name or folder').': '.$d;  break; }
                $dirs[]= $d;
            }
            $right= ($_POST['u_right'] ?? '') === 'rw' ? 'rwx' : 'r';
            $admin= !empty($_POST['u_admin']);
            if ($err === '' && $isNew && mb_strlen($pwd) < 8)          $err= lang('@The new password must have at least 8 characters');
            elseif ($err === '' && $pwd !== '' && mb_strlen($pwd) < 8) $err= lang('@The new password must have at least 8 characters');
            elseif ($err === '' && $name === $me && !$admin)          $err= lang('@You can not take your own administrator right');
            elseif ($err === '' && $name === $me && $pwd !== '')      $err= lang('@Your own password: program menu - Change my password (it asks for the present password)');
            $made= [];
            if ($err === '' && $right !== 'r') {        // A write folder that does not exist: made now (closed for direct web access) - or the save is refused
                $base= p2h_norm_path(realpath($fe_root_path) ?: $fe_root_path);
                foreach ($dirs as $d) if ($d !== '' && !is_dir($base.'/'.$d)) {
                    if (fe_exchange_init($base.'/'.$d) === false) { $err= lang('@The folder could not be made').': '.$d;  break; }
                    $made[]= $d;
                }
            }
            if ($err === '') {
                $users[$name]= [$pwd !== '' ? password_hash($pwd, PASSWORD_DEFAULT) : $users[$name][0], 'root', $right];
                if ($dirs) $users[$name][]= array_values(array_unique($dirs));      // No write folders: the shared folder (fe_write_dirs) - not nowhere
                $admins= $admin ? array_merge($admins, [$name]) : array_diff($admins, [$name]);
                $ok= lang($isNew ? '@User created' : '@User saved').': '.$name.($made ? ' - '.lang('@new folder').': '.implode(', ', $made) : '');
            }
        } else $err= lang('@It could not be done');
        if ($err === '' && !array_intersect(array_keys($users), $admins)) $err= lang('@There must be at least one administrator');
    }
    if ($err === '' && $ok !== '') {
        if (fe_save_users($users, $admins)) fe_msg(p2h_enc($ok));
        else fe_msg(p2h_enc(lang('@The accounts file could not be written')).': '.p2h_enc(basename($fe_users_file)), 'error');
    } elseif ($err !== '') fe_msg(p2h_enc($err), 'error');
    header('Location: '.$back, true, 303);
    exit;
}

##### BASKET: files and folders collected from any folder (the source) - copied or moved to the current folder (the destination), or downloaded as one ZIP
if ($is_logged) {
    $basket= $_SESSION[p2h_SESSION_ID]['basket'] ?? [];
    $basket= array_values(array_filter(is_array($basket) ? $basket : [], fn($p) => is_string($p) && file_exists($p) && fe_in_dir($p, $root_path)));
    $_SESSION[p2h_SESSION_ID]['basket']= $basket;    // Gone - or outside again after "Restore restrictions": out of the basket
} else $basket= [];
if ($is_logged && isset($_POST['fe_bk'])) {
    $op=    (string)$_POST['fe_bk'];
    $paths= array_filter((array)($_POST['fe_bp'] ?? []), 'is_string');
    $n= 0;  $skip= 0;
    if (!csrf_verify()) fe_msg(p2h_enc(lang('@The form has expired - reload the page and try again')), 'error');
    elseif ($op === 'add') {
        foreach ($paths as $pth) {
            $r= realpath($pth);  $r= $r === false ? '' : p2h_norm_path($r);
            if ($r !== '' && $r !== $root_path && fe_in_dir($r, $root_path) && !in_array($r, $basket, true)) { $basket[]= $r;  $n++; }
        }
        fe_msg(p2h_enc(lang('@Put in the basket')).': <b>'.$n.'</b> - '.p2h_enc(lang('@go to the destination folder and copy or move them there')));
    }
    elseif ($op === 'remove') $basket= array_values(array_diff($basket, array_map('p2h_norm_path', $paths)));
    elseif ($op === 'clear')  $basket= [];
    elseif ($op === 'zip') {                            // Download the basket as one ZIP - only the files the user may read
        $tmp= class_exists('ZipArchive') && $basket ? tempnam(sys_get_temp_dir(), 'fez') : false;
        $z=   $tmp ? new ZipArchive() : null;
        if ($z && $z->open($tmp, ZipArchive::OVERWRITE) === true) {
            $used= [];
            foreach ($basket as $src) {
                $local= basename($src);
                for ($k= 2; isset($used[strtolower($local)]); $k++)                                     // Same name from two folders: "a (2).txt"
                    $local= is_dir($src) || pathinfo($src, PATHINFO_EXTENSION) === '' ? basename($src).' ('.$k.')'
                                                                                    : pathinfo($src, PATHINFO_FILENAME).' ('.$k.').'.pathinfo($src, PATHINFO_EXTENSION);
                $used[strtolower($local)]= 1;
                fe_zip_add($z, $src, $local, $n, $skip);
            }
            $z->close();
            if ($n > 0) fe_offer_download($tmp, 'basket-'.date('Ymd-His').'.zip', true, $skip ? lang('@Not in the ZIP').': '.$skip.' ('.lang('@not allowed, links, or more than 20000 files').')' : '');
            @unlink($tmp);
        }
        fe_msg(p2h_enc(lang('@Nothing in the basket may be downloaded')).($skip ? ' ('.$skip.' '.p2h_enc(lang('@skipped')).')' : ''), 'error');
    }
    elseif ($op === 'copy' || $op === 'move') {         // To the destination (Transfer card) - empty: the current folder
        $bkDest= fe_resolve_dest_new($_POST['fe_dest'] ?? '');
        if ($bkDest === false)  fe_msg(p2h_enc(lang('@The destination folder is not found')).': '.p2h_enc((string)($_POST['fe_dest'] ?? '')), 'error');
        elseif (!fe_can('w') || !fe_can_write($bkDest)) fe_msg(p2h_enc(lang('@You may not write in the destination folder')).': '.p2h_enc(fe_show_path($bkDest)), 'error');
        else {
            $done= fe_transfer($basket, $bkDest, $op, $n, $skip);
            if ($op === 'move') $basket= array_values(array_diff($basket, $done));
            fe_msg(p2h_enc(lang($op === 'move' ? '@Moved to' : '@Copied to')).' <b>'.p2h_enc(fe_show_path($bkDest)).'</b>: '.$n.' '.p2h_enc(lang($op === 'move' ? '@files / folders' : '@files')).
                   ($skip ? ' - '.$skip.' '.p2h_enc(lang('@skipped (not allowed, no right to move, too large, or already here)')) : ''), $skip ? 'error' : 'ok');
        }
    }
    $_SESSION[p2h_SESSION_ID]['basket']= $basket;
    fe_redirect($currPath);
}

##### FILE FEATURES below the table: copy, move, ZIP, delete, download - the checked rows of the current folder
if ($is_logged && isset($_POST['fe_sel'])) {
    $op=    (string)$_POST['fe_sel'];
    $items= [];
    foreach (array_filter((array)($_POST['fe_bp'] ?? []), 'is_string') as $pth) {
        $r= realpath($pth);  $r= $r === false ? '' : p2h_norm_path($r);
        if ($r !== '' && p2h_norm_path(dirname($r)) === $currPath && !in_array($r, $items, true)) $items[]= $r;      // Only rows of this folder
    }
    $dRaw= trim((string)($_POST['fe_dest'] ?? ''));      // The destination: a folder below the root - empty: this folder
    $dest= in_array((string)($_POST['fe_sel'] ?? ''), ['copy', 'move', 'zip'], true) && csrf_verify() ? fe_resolve_dest_new($dRaw) : fe_resolve_dest($dRaw);
    if ($dest === false) $dest= '';
    $destRel= $dest === '' ? $dRaw : fe_show_path($dest);
    $n= 0;  $skip= 0;
    $skipTxt= function() use (&$skip) { return $skip ? ' - '.$skip.' '.p2h_enc(lang('@skipped (not allowed, no right, too large, or already there)')) : ''; };
    if (!csrf_verify())     fe_msg(p2h_enc(lang('@The form has expired - reload the page and try again')), 'error');
    elseif (!$items)        fe_msg(p2h_enc(lang('@Check one or more rows in the table first')), 'error');
    elseif ($dest === '')   fe_msg(p2h_enc(lang('@The destination folder is not found')).': '.p2h_enc($dRaw), 'error');
    elseif ($op === 'copy' || $op === 'move' || $op === 'zip') {
        if (!fe_can('w') || !fe_can_write($dest)) fe_msg(p2h_enc(lang('@You may not write in the destination folder')).': '.p2h_enc($destRel), 'error');
        elseif ($op !== 'zip') {
            fe_transfer($items, $dest, $op, $n, $skip);
            fe_msg(p2h_enc(lang($op === 'move' ? '@Moved to' : '@Copied to')).' <b>'.p2h_enc($destRel).'</b>: '.$n.' '.p2h_enc(lang($op === 'move' ? '@files / folders' : '@files')).$skipTxt(), $skip ? 'error' : 'ok');
        } elseif (!class_exists('ZipArchive')) fe_msg(p2h_enc(lang('@It could not be done')), 'error');
        else {
            $one= count($items) === 1 ? (is_dir($items[0]) ? basename($items[0]) : pathinfo($items[0], PATHINFO_FILENAME)) : '';    // A folder "my.photos": not "my"
            $target= fe_unique($dest.'/'.($one > '' ? $one : basename($currPath).' - '.lang('@selection')).'.zip');
            $z= new ZipArchive();
            if ($z->open($target, ZipArchive::CREATE | ZipArchive::EXCL) === true) {
                foreach ($items as $it) fe_zip_add($z, $it, basename($it), $n, $skip);
                $z->close();
            }
            $full= is_file($target) && $exch_path && fe_in_dir($dest, $exch_path) && !$fe_unlocked && fe_dir_size($exch_path) > $fe_exchange_quota;
            if (!is_file($target) || $n === 0 || $full) {
                @unlink($target);
                fe_msg(p2h_enc(lang($full ? '@the shared folder is full' : '@It could not be done')).$skipTxt(), 'error');
            } else fe_msg(p2h_enc(lang('@ZIP file created')).': <b>'.p2h_enc(ltrim(substr(p2h_norm_path($target), strlen($root_path)), '/')).'</b> - '.$n.' '.p2h_enc(lang('@files')).$skipTxt(), $skip ? 'error' : 'ok');
        }
    }
    elseif ($op === 'delete') {
        if (!fe_can('w') || !$can_write_here) fe_msg(p2h_enc(lang('@You may not write in this folder')), 'error');
        else {
            foreach ($items as $it) fe_delete_tree($it, $n, $skip);
            fe_msg(p2h_enc(lang('@Deleted')).': '.$n.' '.p2h_enc(lang('@files / folders')).
                   ($skip ? ' - '.$skip.' '.p2h_enc(lang('@not deleted (protected files are kept, and the folders they are in)')) : ''), $skip ? 'error' : 'ok');
        }
    }
    elseif ($op === 'download') {
        if (count($items) === 1 && is_file($items[0]) && fe_can_read($items[0])) fe_offer_download($items[0], basename($items[0]), false);
        $tmp= class_exists('ZipArchive') ? tempnam(sys_get_temp_dir(), 'fez') : false;
        $z=   $tmp ? new ZipArchive() : null;
        if ($z && $z->open($tmp, ZipArchive::OVERWRITE) === true) {
            foreach ($items as $it) fe_zip_add($z, $it, basename($it), $n, $skip);
            $z->close();
            if ($n > 0) fe_offer_download($tmp, basename($currPath).'.zip', true, $skip ? lang('@Not in the ZIP').': '.$skip.' ('.lang('@not allowed, links, or more than 20000 files').')' : '');
            @unlink($tmp);
        }
        fe_msg(p2h_enc(lang('@Nothing of it may be downloaded')).$skipTxt(), 'error');
    }
    fe_redirect($currPath);
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

 
 
 // $p2h_Style= '';
 
##### SCREEN OUTPUT:  -  generated by PHP2HTML functions !
#!!!: Remember no OUTPUT to screen, before htm_PagePrep() output or you will get warning !

### LOGIN: 
if (!isset($_SESSION[p2h_SESSION_ID]['logged'])) {
    // The public demo account is suggested only while its password is the demo password (an administrator may have changed it):
    $fe_demo= $fe_demo_login ?? ['visitor', 'xxxxxxxx'];
    $fe_demo_ok= false;
    if (is_array($fe_demo) && isset($auth_users[$fe_demo[0]][0])) {        // Remembered per password hash (a temp file) - not checked on every view
        $fe_dk= sys_get_temp_dir().'/fe-demo-'.md5(__FILE__.'|'.$fe_demo[0].'|'.$fe_demo[1].'|'.$auth_users[$fe_demo[0]][0]).'.txt';
        if (is_file($fe_dk)) $fe_demo_ok= @file_get_contents($fe_dk) === '1';
        else { $fe_demo_ok= password_verify((string)$fe_demo[1], (string)$auth_users[$fe_demo[0]][0]);  @file_put_contents($fe_dk, $fe_demo_ok ? '1' : '0'); }
    }
    $fe_demo_user= $fe_demo_ok ? $fe_demo[0] : '';  $fe_demo_pwd= $fe_demo_ok ? $fe_demo[1] : '';
    if (($_SESSION[p2h_SESSION_ID]['last_user'] ?? '') !== '') {            // A failed login: the name that was typed - not the demo account
        $fe_demo_user= (string)$_SESSION[p2h_SESSION_ID]['last_user'];  $fe_demo_pwd= '';
    }
htm_Page_(titl:'Login to Folder-explorer', hint:lang(''), info:lang(''), inis:$p2h_Style, 
          algn:'center', imag:'./_accessories/_background.png', attr:'', pbrd:true );
    htm_Caption('@Connect to Folder-explorer', algn:'center; font-size: 18px');
    htm_nl(1); 
    htm_Card_(capt:'@Log in', icon:'fas fa-user-check', hint:'', form:'signup', acti:'', clas:'cardW320', wdth:'640px', styl:'background-color: lightcyan;', attr:'', help:'',show:false, poup:false);
        htm_Input(labl:'@Your account', plho:'@Name...',    icon:'', hint:'@Type account name', vrnt:'text', name:'p2h_usr', valu:$p2h_usr=$fe_demo_user,           form:'', wdth:'80%', algn:'left', attr:'required', rtrn:false, unit:'', disa:false, rows:'3',  step:'');
        htm_Input(labl:'@Your password', plho:'@Password...', icon:'', hint:'@Type your password for your account', vrnt:'pass', name:'p2h_pwd', valu:$p2h_pwd=$fe_demo_pwd, form:'', wdth:'80%', algn:'left', attr:'required', rtrn:false, unit:'', disa:false, rows:'3',  step:'', list:[], llgn:'R', bord:'', ftop:'');
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
    $gbl_PageHelp= false;  $gbl_NotePad= false;     // Not in this view bar: help for this page and My notes
    htm_ViewBar(user: $_SESSION[p2h_SESSION_ID]['logged'] ?? '', lout: $use_auth ? basename(__FILE__).'?logout=1&t='.urlencode((string)($_SESSION['csrf_token'] ?? '')) : '');     // Zoom, full screen, column view, language, notes, help, theme, user (top right, movable)
    // print_r($tabldata);
    htm_Caption(labl:'Folder-explorer',icon:'',hint:'The advanced file-explorer with focus on folders and space taken up of files.',
                algn:'center',styl:'color:'.$gbl_TitleColr.'; font-weight:600; font-size: 18px; padding: 4px;' );
    htm_nl(1);
    htm_Caption(labl:'Build with PHP2HTML v'.P2H_VERSION,icon:'',hint:'The shortcut to structured and compact code<br>Created by EV-soft.',
                algn:'center',styl:'color: gray;; font-weight:400; font-size: 12px;');
    p2h_show_message();
    htm_nl(1);
    htm_Card_(capt:'Folder-Tree',icon:'fas fa-sitemap',hint: '',form:'',acti:'',clas:'cardW800',vhgh:'360px',    // The card body scrolls: the size grip can show the whole tree
                wdth:'800px',styl:'background-color: white; ',attr:'text-align: left; padding:8px; background-color: white;',show:true, head:'');
        // Card tool: show the used space of every folder in the tree (on/off for this session)
        if (isset($_GET['treesize'])) $_SESSION[p2h_SESSION_ID]['treesize']= ($_GET['treesize'] === '1');
        $fe_treesize= !empty($_SESSION[p2h_SESSION_ID]['treesize']);
        $tsHint= $fe_treesize ? lang('@ANALYSE: Click to hide the used space of the folders')
                              : lang('@ANALYSE: Click to show the used space of every folder (can take time in big folders)');
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
        echo '<div class="feTreeWrap">';                                  // No height of its own (a fixed height stopped the size grip)

        // $base= $_SERVER['DOCUMENT_ROOT'];        // $base= substr($base,0,-13);
        $base= $root_path;          // $base= substr($base,0,-13);
                    # $body,$algn='left',$marg='8px',$styl='box-shadow: 3px 3px 6px 0px #ccc; padding: 5px; border: solid 1px lightgray; ',$attr='background-color: white; ');
        htm_TextDiv(body:'BASE: <big><b>'.p2h_enc($base).'</b>/'.'</big><br>'.lang('@The current folder').' (<b>'.p2h_enc($currentDir).'</b>) '.lang('@is marked with bold text and an open folder.'),
                    algn:'left',marg:'8px',styl:'box-shadow: 3px 3px 6px 0px #ccc; padding: 5px; border: solid 1px lightgray; background-color: white; ');
        ob_start();                                                         // The tree first: the total is shown above it
        $treeOwn=  listFolders($base,$currentDir);     // The files directly in BASE (the rest is added up in fe-tree.js)
        $treeHtml= ob_get_clean();
        if ($fe_treesize)
            echo '<div style="margin: 0 0 6px 4px; font-size: 13px;"><i class="fas fa-hdd" style="color: #1a7f37;"></i> '.
                 p2h_enc(lang('@Used space below BASE')).': <span id="feTreeTotal" data-own="'.(int)$treeOwn.'"><b>&hellip;</b> <small style="color: gray;"></small></span>'.
                 // The bars: share of the parent folder or of BASE (switched in the browser, remembered there)
                 '<span class="feRelSw" role="group" aria-label="'.p2h_enc(lang('@Share of')).'" hidden> &nbsp; '.p2h_enc(lang('@Share of')).': '.
                    '<button type="button" data-rel="parent" aria-pressed="true">'.p2h_enc(lang('@Parent folder')).'</button>'.
                    '<button type="button" data-rel="base" aria-pressed="false">BASE</button></span></div>';
        echo $treeHtml;
        if ($fe_treesize)       // The sizes: added up - and the closed folders counted - in the browser
            echo '<script src="fe-tree.js?v='.@filemtime(__DIR__.'/fe-tree.js').'" defer data-t="'.p2h_enc(json_encode([
                    'url'=>basename(__FILE__).'?fsz=', 'counting'=>lang('@counting...'), 'err'=>lang('@could not be counted (too many files?)'),
                    'manual'=>lang('@not counted - click to count'), 'all'=>lang('@Count all'),
                    'allHint'=>lang('@Count the folders outside the current folder too - it can take long for a big tree'),
                    'ofParent'=>lang('@of the parent folder'), 'ofBase'=>lang('@of BASE')])).'"></script>';
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
        $ruler= '<span style="left: 0;">Level:</span>';                    // The level axis below the tree
        for ($l= 0; $l <= 14; $l++) $ruler.= '<span style="left: '.(30 + 28 + 50*$l).'px;">'.$l.'</span>';
        echo '<div class="feRuler" style="width: '.(30 + 28 + 50*14 + 20).'px;">'.$ruler.'</div>';

        echo '</div>';     // feTreeWrap
    htm_Card_end($labl='', $icon='',  $hint='', name: '', form: '', subm: false, attr: '', akey: '', kind: 'save', simu: false);
    htm_nl(1);

    ### ACCOUNTS (program menu): the users - only admins - and the own password
    $me= $_SESSION[p2h_SESSION_ID]['logged'] ?? '';
    if (isset($_GET['users']) && fe_is_admin()) {
        htm_Card_(capt:'@Users',icon:'fas fa-users',hint:'@The accounts of Folder-explorer - read: download and preview, write: upload, copy, move, delete in the write folders',
                  form:'',acti:'',clas:'cardW800',wdth:'800px',styl:'background-color: white;',attr:'text-align: left; padding:8px; background-color: white;',show:true, head:'');
            $act= basename(__FILE__).'?show='.urlencode($currPath);
            echo '<div class="feUsers"><table class="feUsrTbl"><tr><th>'.p2h_enc(lang('@User')).'</th><th>'.p2h_enc(lang('@Right')).'</th>'.
                 '<th>'.p2h_enc(lang('@Write folders')).' <small>('.p2h_enc(lang('@comma between - / = all')).')</small></th><th>Admin</th>'.
                 '<th>'.p2h_enc(lang('@New password')).'</th><th></th></tr>';
            $rows= $auth_users;  $rows['']= ['', 'root', 'r', []];        // The last row: a new user
            $n= 0;
            foreach ($rows as $name => $u) {
                $fid= 'feU'.(++$n);  $new= $name === '';
                $dirs= implode(', ', array_map(fn($d) => $d === '' ? '/' : $d, (array)($u[3] ?? [])));
                echo '<tr'.($new ? ' class="feUsrNew"' : '').'><td>'.
                        ($new ? '<input form="'.$fid.'" name="u_name" placeholder="'.p2h_enc(lang('@new user')).'" pattern="[A-Za-z0-9._-]{2,32}" required size="9">'
                              : '<b>'.p2h_enc($name).'</b>'.($name === $me ? ' <small>('.p2h_enc(lang('@you')).')</small>' : '').'<input form="'.$fid.'" type="hidden" name="u_name" value="'.p2h_enc($name).'">').'</td>'.
                     '<td><select form="'.$fid.'" name="u_right"><option value="r">'.p2h_enc(lang('@read')).'</option>'.
                        '<option value="rw"'.(strpos((string)$u[2], 'w') !== false ? ' selected' : '').'>'.p2h_enc(lang('@read + write')).'</option></select></td>'.
                     '<td><input form="'.$fid.'" name="u_dirs" value="'.p2h_enc($dirs).'" size="16" placeholder="'.p2h_enc(lang('@the shared folder')).'"></td>'.
                     '<td style="text-align:center;"><input form="'.$fid.'" type="checkbox" name="u_admin" value="1"'.(in_array($name, $fe_admins, true) ? ' checked' : '').'></td>'.
                     '<td><input form="'.$fid.'" type="password" name="u_pwd" autocomplete="new-password" size="9" placeholder="'.p2h_enc($new ? lang('@at least 8') : lang('@unchanged')).'"'.($new ? ' required minlength="8"' : '').'></td>'.
                     '<td style="white-space:nowrap;"><form id="'.$fid.'" method="post" action="'.p2h_enc($act).'">'.csrf_field(true).
                        fe_hint('<button type="submit" name="fe_users" value="save" class="txtbutt feXfBtn" aria-label="'.p2h_enc(lang($new ? '@Create' : '@Save')).'">'.
                                '<i class="fas fa-'.($new ? 'user-plus' : 'save').'"></i></button>', p2h_enc(lang($new ? '@Create the user' : '@Save the changes of this user'))).
                        (!$new && $name !== $me ? ' '.fe_hint('<button type="submit" name="fe_users" value="delete" class="txtbutt feXfBtn feUsrDel" aria-label="'.p2h_enc(lang('@Delete')).'" '.
                            'onclick="return feConfirmSubmit(this, '.p2h_enc(p2h_js(lang('@Delete the user').' '.$name.'?')).')"><i class="fas fa-user-times"></i></button>', p2h_enc(lang('@Delete the user'))) : '').
                     '</form></td></tr>';
            }
            if (!empty($fe_users_broken)) echo '<div class="p2hAlert p2hAlert-error" role="alert">'.p2h_enc(lang('@The accounts file can not be read (an error in it) - the accounts in folder-explorer.php are used. Saving here writes a new file.')).'</div>';
            echo '</table><small>'.p2h_enc(lang('@Saved in')).' <code>'.p2h_enc(basename($fe_users_file)).'</code>'.
                 (is_file($fe_users_file) ? '' : ' - '.p2h_enc(lang('@until the first save the accounts in folder-explorer.php are used'))).'</small> &nbsp; '.
                 '<a href="'.p2h_enc($act).'" class="txtbutt feXfBtn"><i class="fas fa-times"></i> '.p2h_enc(lang('@Close')).'</a></div>';
        htm_Card_end(labl:'', icon:'', hint:'', name:'', form:'', subm:false, attr:'', akey:'', kind:'save', simu:false);
        htm_nl(1);
    }
    if (isset($_GET['pw']) && fe_may_change_pw($me)) {
        htm_Card_(capt:'@Change my password',icon:'fas fa-key',hint:'',form:'',acti:'',clas:'cardW800',wdth:'800px',
                  styl:'background-color: white;',attr:'text-align: left; padding:8px; background-color: white;',show:true, head:'');
            $act= basename(__FILE__).'?show='.urlencode($currPath);
            echo '<form method="post" action="'.p2h_enc($act).'" class="fePwForm">'.csrf_field(true).
                 '<label>'.p2h_enc(lang('@Present password')).' <input type="password" name="pw_old" autocomplete="current-password" required></label> '.
                 '<label>'.p2h_enc(lang('@New password')).' <input type="password" name="pw_new" autocomplete="new-password" minlength="8" required></label> '.
                 '<label>'.p2h_enc(lang('@New password again')).' <input type="password" name="pw_new2" autocomplete="new-password" minlength="8" required></label> '.
                 '<button type="submit" name="fe_pw" value="1" class="txtbutt feXfBtn"><i class="fas fa-key"></i> '.p2h_enc(lang('@Change')).'</button> '.
                 '<a href="'.p2h_enc($act).'" class="txtbutt feXfBtn"><i class="fas fa-times"></i> '.p2h_enc(lang('@Close')).'</a></form>';
        htm_Card_end(labl:'', icon:'', hint:'', name:'', form:'', subm:false, attr:'', akey:'', kind:'save', simu:false);
        htm_nl(1);
    }

    ### TRANSFER: the folders the user may write in, the basket (source -> destination), upload from the PC
    if ($is_logged) {
        $rootRel=  function($d) use ($root_path) { $r= ltrim(substr($d, strlen($root_path)), '/');  return $r === '' ? '/' : $r; };
        $maxFile=  min($fe_unlocked ? PHP_INT_MAX : $fe_max_upload, fe_ini_bytes(ini_get('upload_max_filesize')) ?: PHP_INT_MAX, fe_ini_bytes(ini_get('post_max_size')) ?: PHP_INT_MAX);
        htm_Card_(capt:'@Transfer',icon:'fas fa-exchange-alt',hint:'@Collect files and folders in the basket, and copy or move them to another folder - upload from your PC and download to it',
                  form:'',acti:'',clas:'cardW800',wdth:'800px',styl:'background-color: white;',attr:'text-align: left; padding:8px; background-color: white;',show:true, head:'');
            if (fe_is_admin()) {    // Remove / restore the restrictions - admins only: a lock icon among the card's tool icons
                $ulHint= $fe_unlocked ? lang('@<b>RESTRICTIONS REMOVED:</b> Click to restore the restrictions')
                                      : lang('@<b>REMOVE RESTRICTIONS:</b> see and change the whole account, any file type, no quota - only for you, only in this session');
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
                            ($fe_unlocked ? 'f.submit();' : 'var q= '.p2h_js(lang('@Remove the restrictions for this session?')).';'.
                                              'if (typeof sysConfirm === "function") sysConfirm(q, function(){ f.submit(); });  else if (confirm(q)) f.submit();').
                        '});'.
                     '})();</script>';
                if ($fe_unlocked)
                    echo '<div class="p2hAlert p2hAlert-error" style="margin: 4px 10px 8px;" role="alert">'.
                            '<i class="fas fa-lock-open"></i> <b>'.p2h_enc(lang('@Restrictions removed')).'</b> - '.
                            p2h_enc(lang('@you can see and download everything below')).' <b>'.p2h_enc($root_path).'</b>, '.
                            p2h_enc(lang('@and write everywhere - any file type (not hidden files), with no size limit or quota of its own. Only for you, only in this session.')).
                            ' '.p2h_enc(lang('@Restore them with the lock icon in the card header.')).
                         '</div>';
            }
            $wd= fe_write_dirs();
            $destDef= $can_write_here ? $currPath : ($wd[0] ?? '');      // The destination: the folder you are in - else the first write folder
            echo '<div class="feXfer">';
            // Where the user may write
            echo '<div class="feXfRow"><b>'.p2h_enc(lang('@You may write in')).':</b> ';
            if (!$wd) echo '<i>'.p2h_enc(lang('@nothing - your account can only read')).'</i>';
            foreach ($wd as $d)
                echo '<a href="'.basename(__FILE__).'?show='.urlencode($d).'" class="txtbutt feXfDir"><i class="far fa-folder colr0"></i> '.p2h_enc($rootRel($d)).'</a> ';
            echo $can_write_here ? ' <small class="feXfHere"><i class="fas fa-check"></i> '.p2h_enc(lang('@also here')).'</small>' : '';
            echo '</div>';
            if ($wd && fe_can('w')) {      // Where uploads and the basket go - typed or chosen, it need not be the folder you are in
                $opts= array_unique(array_merge($can_write_here ? [$currPath] : [], $wd));
                echo '<div class="feXfRow feXfDestRow"><label for="feDest"><i class="fas fa-arrow-right"></i> <b>'.p2h_enc(lang('@Destination')).'</b>:</label> '.
                     '<input type="text" id="feDest" list="feDestOpts" autocomplete="off" value="'.p2h_enc(fe_show_path($destDef)).'" '.
                        'aria-describedby="feDestTip" placeholder="'.p2h_enc(lang('@a folder you may write in')).'"> '.
                     '<datalist id="feDestOpts">'.implode('', array_map(fn($d) => '<option value="'.p2h_enc(fe_show_path($d)).'">', $opts)).'</datalist>'.
                     ' <small id="feDestTip">'.p2h_enc(lang('@for upload and the basket - choose or type a folder you may write in')).'</small></div>';
            }

            // The basket
            echo '<div class="feXfRow feBasket"><b><i class="fas fa-shopping-basket"></i> '.p2h_enc(lang('@Basket')).'</b> ';
            $chkBtn= '<button type="button" class="txtbutt feXfBtn" onclick="feBasketChecked()"><i class="far fa-check-square"></i> '.p2h_enc(lang('@Put the checked rows in the basket')).'</button>';
            if (!$basket)
                echo '<small>'.p2h_enc(lang('@empty - put files in it with the file menu (rest the mouse on a file name), or check rows in the table:')).'</small> '.$chkBtn;
            else {
                $bSize= 0;  $bDirs= 0;
                echo '<table class="feBkList">';
                foreach ($basket as $b) {
                    $isDir= is_dir($b);
                    if ($isDir) $bDirs++; else $bSize+= (int)@filesize($b);
                    echo '<tr><td><i class="'.($isDir ? 'far fa-folder colr0' : 'far fa-file').'"></i></td>'.
                         '<td><a href="'.basename(__FILE__).'?show='.urlencode(dirname($b)).'">'.p2h_enc($fe_unlocked ? $b : basename($root_path).'/'.ltrim(substr($b, strlen($root_path)), '/')).'</a></td>'.   // Unlocked: the whole path (which drive / account folder)
                         '<td class="feBkSize">'.($isDir ? p2h_enc(lang('@folder')) : p2h_nice_filesize((int)@filesize($b))).'</td>'.
                         '<td>'.fe_hint('<button type="button" class="feBkDel" onclick="feBasket(\'remove\', ['.p2h_enc(p2h_js($b)).'])" aria-label="'.p2h_enc(lang('@Take out of the basket')).'">'.
                                '<i class="fas fa-times"></i></button>', p2h_enc(lang('@Take out of the basket'))).'</td></tr>';
                }
                echo '</table>';
                echo '<div class="feXfSum">'.count($basket).' '.p2h_enc(lang('@in the basket')).' - '.p2h_nice_filesize($bSize).
                     ($bDirs ? ' + '.$bDirs.' '.p2h_enc(lang('@folders')) : '').'</div>';
                $why= lang('@Your account may not change files');
                $off= ($wd && fe_can('w')) ? '' : ' disabled';
                $btn= function($op, $icon, $labl, $hint, $dis= '') {
                    return fe_hint('<button type="button" class="txtbutt feXfBtn"'.$dis.' onclick="feBasket(\''.$op.'\')"><i class="'.$icon.'"></i> '.p2h_enc(lang($labl)).'</button>', p2h_enc($hint));
                };
                echo $btn('copy', 'fas fa-copy', '@Copy to the destination', $off ? $why : lang('@Copy everything in the basket to the destination folder (above) - the basket is kept'), $off).
                     $btn('move', 'fas fa-dolly', '@Move to the destination', $off ? $why : lang('@Move everything in the basket to the destination folder (above) - from folders you may write in'), $off).
                     $btn('zip', 'fas fa-file-archive', '@Download as ZIP', lang('@One ZIP file with everything in the basket you may read - to your PC')).
                     $btn('clear', 'fas fa-times', '@Empty the basket', lang('@Take everything out of the basket - nothing is deleted')).' '.$chkBtn;
            }
            echo '</div>';

            // Upload from the PC to the current folder
            echo '<div class="feXfRow">';
            if ($wd && fe_can('w')) {
                $used= $exch_path !== false ? fe_dir_size($exch_path) : 0;
                // Settings and texts for fe-upload.js (the form also works without JavaScript: plain file field + submit)
                $feCfg= [
                    'maxFile'   => $maxFile,
                    'quotaLeft' => 1e18,       // The server checks the quota of the shared folder for the destination chosen
                    'maxCount'  => max(1, (int)ini_get('max_file_uploads') ?: 20),
                    'maxPost'   => fe_ini_bytes(ini_get('post_max_size')) ?: 8 * 1024 * 1024,
                    'deny'      => $fe_unlocked ? '(?!)' : FE_DENY_EXT,     // (?!) never matches: all types allowed
                    'strict'    => !$fe_unlocked,
                    'txt'       => array_map('lang', [
                        'type'    => '@this file type is not allowed',
                        'folder'  => '@the folder name is not allowed',
                        'big'     => '@the file is too large',
                        'quota'   => '@The selected files are larger than the free space in the shared folder',
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
                echo '<b><i class="fas fa-cloud-upload-alt"></i> '.p2h_enc(lang('@Upload from your PC')).' <i class="fas fa-arrow-right"></i> '.p2h_enc(lang('@the destination (above)')).'</b><br><small>'.
                        ($exch_path !== false && !$fe_unlocked ? p2h_enc(lang('@Shared folder')).' '.p2h_enc(fe_show_path($exch_path)).': '.p2h_nice_filesize($used).' '.p2h_enc(lang('@of')).' '.p2h_nice_filesize($fe_exchange_quota).' &nbsp;-&nbsp; ' : '').
                        p2h_enc(lang('@Max per file')).': '.p2h_nice_filesize($maxFile).
                        ' &nbsp;-&nbsp; '.p2h_enc($fe_unlocked ? lang('@All file types allowed - except hidden files') : lang('@Not allowed: server scripts (.php ...) and hidden files')).
                        ' &nbsp;-&nbsp; '.p2h_enc(lang('@A ZIP file is unpacked with the file menu: Unpack here')).'</small>';
                echo '<form id="feUpload" method="post" enctype="multipart/form-data" action="'.basename(__FILE__).'?show='.urlencode($currPath).'" '.
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
                         '<input type="hidden" name="fe_dest" value="'.p2h_enc(fe_show_path($destDef)).'">'.     // (without JavaScript: the default destination)

                         '<progress id="feProg" max="100" value="0" hidden style="width: 200px; vertical-align: middle;"></progress> '.
                         '<span id="feSum"></span>'.
                     '</form>'.
                     '<script src="fe-upload.js?v='.@filemtime(__DIR__.'/fe-upload.js').'" defer></script>';
            }
            elseif (fe_can('w')) echo '<small><i>'.p2h_enc(lang('@Your account has no folder to write in.')).'</i></small>';
            else echo '<small><i>'.p2h_enc(lang('@Your account can only read: view and download where it may - and download the basket as a ZIP.')).'</i></small>';
            echo '</div>';
            echo '</div>';     // feXfer
        htm_Card_end(labl:'', icon:'', hint:'', name:'', form:'', subm:false, attr:'', akey:'', kind:'save', simu:false);
        htm_nl(1);
    }

    htm_Card_(capt:'Folder-Details',icon:'fas fa-info',hint: '',form:'xxx',acti:'',clas:'cardWmax',
                wdth:'800px',styl:'background-color: white;',attr:'', show:true, head: 'background-color: white;');
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
            <big><i>Showing:</i> ', '90%','html','','left','@Tip','?', $_SERVER['SERVER_NAME'].' // '.$breadcrumbs.'</big><data-hint>'.lang('@Right-click a row: the file / folder menu - elsewhere in the table: the folder menu (upload, new folder ...)').'</data-hint></abbr>'],
          ['<span style="float:right; white-space: nowrap; display: inline-block;">'.
               '<abbr class="hint feHint">'.
               ($calc_folder == true ? 
               '<i>Content:</i> Folders: '.$totFolds.' - Files: '.markAllChars($filesIncurrent.'/','i','style="opacity:0.5;"').$totFiles. ' - ' : '' ).
               'Space: '.markAllChars(p2h_nice_filesize($locSize).'/','i','style="opacity:0.5;"').p2h_nice_filesize($totSize).
               ' - Free: '.   p2h_nice_filesize(@disk_free_space($currPath)).
               ' of total: '. p2h_nice_filesize(@disk_total_space($currPath)).
               '<data-hint style="white-space: normal;">'.lang('@Space meaning file-space in local folder / total in subfolders').'</data-hint></abbr>'.
           '</span> <br>'.
            '<div id="feDetTools" class="feDetTools">'.          // Search + mode: moved into the card header (the tool field) by the script below the table
                '<form method="post" name= "frmMode" style="display:inline-flex; align-items:center; gap: 8px;">'. csrf_field(true).  // php2html 1.7 rejects a POST without the token
                SearchSub($arrNames).
                // Mode: one button - Quick (the folder at once) / Analyse (the content of all sub-folders is calculated)
                fe_hint('<button type="submit" name="prgMode" value="'.($calc_folder ? 'mode_S' : 'mode_A').'" class="feModeSw" aria-pressed="'.($calc_folder ? 'true' : 'false').'" '.
                            'aria-label="'.p2h_enc(lang('@Mode')).': '.p2h_enc(lang($calc_folder ? '@Analyse' : '@Quick')).'">'.
                            '<span class="feMq"><i class="fas fa-bolt"></i> '.lang('@Quick').'</span>'.
                            '<span class="feMa"><i class="fas fa-chart-pie"></i> '.lang('@Analyse').'</span></button>',
                        '<b>'.lang('@Quick').':</b> '.lang('@shows the folder at once.').'<br>'.
                        '<b>'.lang('@Analyse').':</b> '.lang('@calculates the content of all sub-folders - size, graphs, Sync/ZipClone - and can take time in big folders.').'<br>'.
                        '<i>'.lang('@Click to switch.').'</i>').
                '</form>'.      // (Language, "Logged as" and Logout: in the view bar)
            '</div><div style="clear: both;"></div>'
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
            ['@Modified',  '10%', 'html', '', ['center'],                   'modifyed', '@Last modified date/time'],
            ['@Perms',     '04%', 'text', '', ['center'],                   'perms',    '@The file permissions (UNIX.mode)'],
            ['@Owner',     '04%', 'text', '', ['center'],                   'owner',    '@ID for the file creater (UNIX.owner)'],
            ['@Accessed',  '10%', 'html', '', ['center'],                   'access',   '@File accessed date/time'],
            ['@Space',     '20%', ($calc_folder == true ? 'html' : 'hidd'),
                                          '', ['left'  ,'','',$sort=false], 'space', '@Used space related to<br>Sum of all files in current folder and subfolders.']
        ),
        $RowSuff= array(), // tabl_row.ix
        $TblNote= '<small>'.lang('@Filtering/Searching: Hold mouse over the colored row below the column headers.').'</small><br><br>'.
                  htm_Fieldset(capt:'@File features - for the checked rows:',icon:'', hint:'',wdth:'',marg:'',attr:'',rtrn:true).
                      htm_Fieldset(capt:'@Select:',icon:'', hint:'',wdth:'',marg:'',attr:'',rtrn:true).
                      htm_AcceptButt(labl:'@All',  icon:'', hint:'@All files and folders', form:'', wdth:'', attr:'', akey:'', kind:'spc2', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feSel("all")', idix:'').
                      htm_AcceptButt(labl:'@None', icon:'', hint:'@Deselect all', form:'', wdth:'', attr:'', akey:'', kind:'spc2', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feSel("none")', idix:'').
                      htm_AcceptButt(labl:'@Inverse', icon:'', hint:'@Reverse all', form:'', wdth:'', attr:'', akey:'', kind:'spc2', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feSel("inverse")', idix:'').
                      htm_AcceptButt(labl:'@Basket', icon:'fas fa-shopping-basket', hint:'@Put the checked rows in the basket - to copy or move them to a folder somewhere else', form:'', wdth:'', attr:'', akey:'', kind:'spc2', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feBasketChecked()', idix:'').
                      htm_Fieldset_end(rtrn:true).

                      htm_Fieldset(capt:'@With selected do:',icon:'', hint:'',wdth:'',marg:'',attr:'',rtrn:true).
                      htm_AcceptButt(labl:'@COPY',       icon:'', hint:'@Copy the checked files / folders to the destination folder (below) - never over an existing file', form:'', wdth:'100px;', attr:$dis, akey:'', kind:'',     rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feDo("copy")', idix:'').
                      htm_AcceptButt(labl:'@MOVE',       icon:'', hint:'@Move the checked files / folders to the destination folder (below)',       form:'', wdth:'100px;', attr:$dis, akey:'', kind:'crea', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feDo("move")', idix:'').
                      htm_AcceptButt(labl:'@Create ZIP', icon:'', hint:'@One ZIP file with the checked files / folders - in the destination folder (below)', form:'', wdth:'100px;', attr:$dis, akey:'', kind:'',     rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feDo("zip")', idix:'').
                      htm_AcceptButt(labl:'@DELETE',     icon:'', hint:'@Delete the checked files / folders (folders with their content) - you are asked first', form:'', wdth:'100px;', attr:$dis, akey:'', kind:'eras', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feDo("delete")', idix:'').
                      htm_AcceptButt(labl:'@Property',   icon:'', hint:'@Number and size of the checked files and folders',  form:'', wdth:'100px;', attr:$dis, akey:'', kind:'spc1', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feDo("prop")', idix:'').
                      htm_AcceptButt(labl:'@DOWNLOAD',   icon:'', hint:'@Download the checked files / folders to your PC - more than one file as a ZIP', form:'', wdth:'100px;', attr:$dis, akey:'', kind:'get_', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feDo("download")', idix:'').
                      htm_Fieldset_end(rtrn:true).
                      htm_AcceptButt(labl:'@UPLOAD',     icon:'', hint:'@Upload files from your PC to this folder (the Transfer card)', form:'', wdth:'100px;', attr:'', akey:'', kind:'get_', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feDo("upload")', idix:'').
                      '<small class="feSelDest">'.p2h_enc(lang('@COPY / MOVE / Create ZIP go to the destination of the Transfer card - without one: this folder')).'</small>'.
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
    // Search + mode up into the header of this card - left of the tool icons (without JavaScript they stay above the table)
    echo '<script>(function(){'.
            'var t= document.getElementById("feDetTools"), card= t && t.closest("[id^=card]"), head= card && card.querySelector("[id^=chead]");'.
            'if (!head) return;'.
            'var tools= head.querySelectorAll("abbr.p2hCtrlHint"), last= tools[tools.length - 1];'.
            'head.insertBefore(t, last ? last.nextSibling : null);     /* floats right: after the last icon = left of the group */'.
            't.classList.add("feInHead");'.
            '["click", "pointerdown", "dblclick"].forEach(function(ev){ t.addEventListener(ev, function(e){ e.stopPropagation(); }); });     /* not the card header (fold, move) */'.
         '})();</script>';
    

    // function setcheck(id) { document.querySelector(\'section.button input[type="checkbox"]\').checked = true; }
if ($is_logged)     // File menu and basket: POST with the CSRF token to the current folder
run_Script('
    function fePost(fields, download) {
        var f= document.createElement("form");  f.method= "post";  f.action= '.p2h_js(basename(__FILE__).'?show='.urlencode($currPath)).';
        fields.concat([["csrf_token", '.p2h_js($_SESSION['csrf_token'] ?? '').']]).forEach(function(v) {
            var i= document.createElement("input");  i.type= "hidden";  i.name= v[0];  i.value= v[1];  f.appendChild(i);
        });
        if (download && window.fetch && window.URLSearchParams) {     /* A download (ZIP): the server prepares it and answers with a link - */
            var fd= new FormData(f);  fd.append("fe_ajax", "1");          /* the browser saves it to disk; a page (a message) is shown by a reload */
            document.body.style.cursor= "progress";
            fetch(f.action, {method: "POST", body: new URLSearchParams(fd), credentials: "same-origin", redirect: "manual"}).then(function(r) {     /* manual: the message stays for the reload */
                if (!/json/.test(r.headers.get("Content-Type") || "")) { location.reload();  return; }
                return r.json().then(function(j) {
                    document.body.style.cursor= "";
                    if (!j.url) { location.reload();  return; }
                    if (j.note && typeof sysAlert === "function") sysAlert(j.note);
                    location.href= j.url;       /* An attachment: the page stays */
                });
            }).catch(function() { location.reload(); });
            return;
        }
        document.body.appendChild(f);  f.submit();
    }
    function feBasket(op, paths) {      /* add, remove, clear, copy, move, zip */
        var de= document.getElementById("feDest");
        fePost([["fe_bk", op], ["fe_dest", de ? de.value.trim() : ""]].concat((paths || []).map(function(p) { return ["fe_bp[]", p]; })), op === "zip");
    }
    function feRows() {     /* The check boxes of the table rows (each row has its path in .feBp) */
        return Array.prototype.slice.call(document.querySelectorAll("table input[type=checkbox]")).filter(function(c) {
            var tr= c.closest("tr");  return tr && tr.querySelector(".feBp");
        });
    }
    function feSel(how) { feRows().forEach(function(c) { c.checked= how === "all" ? true : (how === "none" ? false : !c.checked); }); }
    function feDo(op) {     /* File features below the table */
        var T= '.p2h_js(json_encode(['check'=>lang('@Check one or more rows in the table first'), 'noUp'=>lang('@You may not upload to this folder'),
                                     'delQ'=>lang('@Delete the checked files and folders (folders with all their content)?'), 'title'=>lang('@The checked rows'),
                                     'files'=>lang('@Files'), 'folders'=>lang('@Folders'), 'size'=>lang('@Size of the files'), 'bytes'=>lang('@bytes')])).';
        T= JSON.parse(T);
        if (op === "upload") {
            var up= document.getElementById("feFiles");
            if (!up) { sysAlert(T.noUp);  return; }
            var card= up.closest("[id^=card]"), open= card && window["CardMaximize" + card.id.slice(4)];
            if (typeof open === "function") open();         /* A folded card: the list and the progress must be seen */
            card.scrollIntoView({block: "center"});  up.click();  return;
        }
        var rows= feRows().filter(function(c) { return c.checked; }).map(function(c) { return c.closest("tr"); });
        if (!rows.length) { sysAlert(T.check);  return; }
        if (op === "prop") {
            var nf= 0, nd= 0, sz= 0, names= [];
            rows.forEach(function(tr) {
                var fn= tr.querySelector(".feFileName"), f= null;
                try { f= fn ? JSON.parse(fn.dataset.fm) : null; } catch (e) {}
                if (f) { nf++;  sz+= parseInt(String(f.s).replace(/\D/g, ""), 10) || 0;  names.push(f.n); }
                else { nd++;  names.push(tr.querySelector(".feBp").dataset.bp.split("/").pop() + "/"); }
            });
            sysAlert(T.files + ": " + nf + "\n" + T.folders + ": " + nd + "\n" + T.size + ": " + sz.toLocaleString() + " " + T.bytes + "\n\n" +
                     names.slice(0, 15).join("\n") + (names.length > 15 ? "\n\u2026" : ""), T.title);
            var ds= document.querySelectorAll("dialog.p2hDlg .p2hDlgBody > span");
            if (ds.length) { ds[ds.length - 1].style.whiteSpace= "pre-line";  ds[ds.length - 1].style.textAlign= "left"; }
            return;
        }
        var de= document.getElementById("feDest"), ps= rows.map(function(tr) { return tr.querySelector(".feBp").dataset.bp; });     /* The one destination (Transfer card) */
        var go= function() {
            fePost([["fe_sel", op], ["fe_dest", de ? de.value.trim() : ""]].concat(ps.map(function(p) { return ["fe_bp[]", p]; })), op === "download");
        };
        if (op === "delete") sysConfirm(T.delQ + " (" + ps.length + ")", go);  else go();
    }
    function feNewFolder() {     /* Program menu: a new folder in this folder */
        var ask= typeof sysPrompt === "function" ? sysPrompt : function(q, fn) { var v= prompt(q);  if (v !== null) fn(v); };
        ask('.p2h_js(lang('@Name of the new folder:')).', function(v) { if (v && String(v).trim()) fePost([["fe_act", "mkdir"], ["fe_name", ""], ["fe_to", String(v).trim()]]); });
    }
    function feHelp() {          /* Program menu: the tab Nice to know below the table - its card opened first */
        var b= document.querySelector(".p2hTabs button[onclick*=nice]"), card= b && b.closest("[id^=card]"), open= card && window["CardMaximize" + card.id.slice(4)];
        if (typeof open === "function") open();
        if (b) { b.click();  b.scrollIntoView({block: "center"}); }
    }
    function feConfirmSubmit(btn, q) {     /* A submit button that asks first - with the program dialog (theme, language) */
        if (typeof sysConfirm !== "function") return confirm(q);
        sysConfirm(q, function() {
            var f= btn.form, i= document.createElement("input");  i.type= "hidden";  i.name= btn.name;  i.value= btn.value;
            f.appendChild(i);  f.submit();
        });
        return false;
    }
    function feBasketChecked() {
        var ps= [];
        document.querySelectorAll("table input[type=checkbox]:checked").forEach(function(c) {
            var tr= c.closest("tr"), b= tr && tr.querySelector(".feBp");
            if (b && ps.indexOf(b.dataset.bp) < 0) ps.push(b.dataset.bp);
        });
        if (ps.length) feBasket("add", ps);
        else if (typeof sysAlert === "function") sysAlert('.p2h_js(lang('@Check one or more rows in the table first')).');
    }
');
if ($is_logged && fe_can('w'))
run_Script('
    function feAct(act, name, to) {     // File menu: move / copy / zip / unzip in the current folder
        fePost([["fe_act", act], ["fe_name", name], ["fe_to", to || ""]]);
    }
    function feDelete(name) {
        var q= '.p2h_js(lang('@Delete the file').': ').' + name + " ?", go= function() { fePost([["fe_delete", name]]); };
        if (typeof sysConfirm === "function") sysConfirm(q, go);  else if (confirm(q)) go();
    }
');

// File icon preview (fe-preview.js)
echo '<script src="fe-preview.js?v='.@filemtime(__DIR__.'/fe-preview.js').'" defer data-t="'.p2h_enc(json_encode([
    'load'=>lang('@Loading...'), 'none'=>lang('@No preview of this file type'), 'empty'=>lang('@The file is empty'),
    'play'=>lang('@The browser can not play this file'), 'lock'=>lang('@The content is only shown where you may download the file'),
    'open'=>lang('@View'), 'down'=>lang('@Download'), 'prop'=>lang('@File properties'), 'move'=>lang('@Rename / move'),
    'copy'=>lang('@Copy file'), 'zip'=>lang('@Create ZIP'), 'del'=>lang('@Delete file'),
    'moveQ'=>lang('@New name - or a path below this folder (sub/name.ext):'), 'copyQ'=>lang('@Name of the copy - or a path below this folder:'),
    'copyN'=>lang('@copy'), 'onlyEx'=>lang('@Rename, copy, ZIP and delete: only in folders you may write in'),
    'bkIn'=>lang('@Put in the basket'), 'bkOut'=>lang('@Take out of the basket'), 'unzip'=>lang('@Unpack here'),
    'prog'=>['title'=>'Folder-explorer', 'admin'=>fe_is_admin() ? 1 : 0, 'pw'=>fe_may_change_pw($_SESSION[p2h_SESSION_ID]['logged'] ?? '') ? 1 : 0,
             'unl'=>$fe_unlocked ? 1 : 0, 'out'=>$use_auth ? basename(__FILE__).'?logout=1&t='.urlencode((string)($_SESSION['csrf_token'] ?? '')) : '',
             'urlUsers'=>basename(__FILE__).'?show='.urlencode($currPath).'&users=1', 'urlPw'=>basename(__FILE__).'?show='.urlencode($currPath).'&pw=1',
             'users'=>lang('@Users ...'), 'pwT'=>lang('@Change my password'), 'lock'=>lang('@Remove restrictions'), 'unlock'=>lang('@Restore restrictions'),
             'lockQ'=>lang('@Remove the restrictions for this session?'), 'help'=>lang('@Help'), 'about'=>lang('@About Folder-explorer'), 'out_'=>lang('@Log out'),
             'aboutTxt'=>'Folder-explorer '.$DocVer.' ('.$DocRev.")\n".'PHP2HTML / Clever-Html-Engine '.P2H_VERSION."\n".(fe_is_admin() ? 'PHP '.PHP_VERSION."\n" : '').
                         lang('@User').': '.($_SESSION[p2h_SESSION_ID]['logged'] ?? '')."\n\n".'© 2019-2026 EV-soft - github.com/EV-soft/folder-explorer'],
    'goto'=>lang('@Open the folder'), 'dirZip'=>lang('@Download as ZIP'), 'dirProp'=>lang('@Folder properties'), 'moveD'=>lang('@Rename / move folder'),
    'copyD'=>lang('@Copy folder'), 'delD'=>lang('@Delete folder'), 'delDQ'=>lang('@Delete the folder with all its content?'),
    'delFQ'=>lang('@Delete the file').':', 'newD'=>lang('@New folder'), 'newQ'=>lang('@Name of the new folder:'),
    'sync'=>lang('@Synchronize to ...'), 'syncQ'=>lang('@Destination folder below the root (e.g. _exchange/backup) - what is missing or newer here is copied there, an older file there is replaced, nothing is deleted:'),
    'dests'=>array_map(fn($d) => ltrim(substr($d, strlen($root_path)), '/') ?: '/', fe_write_dirs()),
    'noRight'=>lang('@Your account may not change files'), 'locked'=>lang('@This file can not be changed here'), 'more'=>lang('@Right-click the row for more'),
    'pName'=>lang('@Name'), 'pPath'=>lang('@Path'), 'pType'=>lang('@Type'), 'pSize'=>lang('@Size'), 'pMod'=>lang('@Modified'),
    'pAcc'=>lang('@Accessed'), 'pPerm'=>lang('@Permissions'), 'pOwn'=>lang('@Owner'), 'bytes'=>lang('@bytes')])).'"></script>';

run_Script('
    function setcheck(id) {    document.getElementById(id).checked = true; }
    function setuncheck(id) {  document.getElementById(id).checked = false; }
'); // const input = document.querySelector('section.button input[type="checkbox"]');



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

    $nice= [     // Nice to know: [heading, [lines]] - the texts are translated (_trans.sys.json)
        ['Menus - how to open them', [
            '<b>File menu:</b> rest the mouse on a file name - or click it (also on a touch screen) - or right-click the row.',
            '<b>Folder menu:</b> right-click the row of a folder. A click on the folder name opens the folder.',
            '<b>Program menu:</b> right-click the table outside a row: users, your password, restrictions, help, about, log out.',
            '<b>Keyboard:</b> Tab to a file name and press Enter - the menu key or Shift+F10 opens the menu of the row (or the program menu) - arrow up / down in the menu - Esc closes it.',
            'Greyed actions may not be done here - the reason is shown at the bottom of the menu.',
        ]],
        ['Preview', [
            'Rest the mouse on a <b>file icon</b>: images, video, audio, fonts, text and code with colours, PDF, tables, documents, archives - or the first bytes (hex). See the tab File-viewers.',
            '<b>View</b> in the file menu opens the file in a new tab.',
        ]],
        ['Select and do', [
            'Check the rows in the column <b>Select</b> - or <b>All / None / Inverse</b> below the table.',
            'For the checked rows: <b>COPY, MOVE, Create ZIP</b> (to the field Destination), <b>DELETE, Property, DOWNLOAD</b> - and <b>Basket</b>.',
        ]],
        ['Transfer', [
            '<b>Basket:</b> collect files and folders from any folder, choose the <b>destination</b>, then copy or move them there - or download them as one ZIP.',
            '<b>Upload:</b> drag files or whole folders from your PC onto the drop zone - they go to the destination. A ZIP file is unpacked with the file menu: Unpack here.',
            'Nothing is ever overwritten: a second copy is called name (2).',
        ]],
        ['Folder tree', [
            'The current folder is bold, with an open folder. The number at the left is the level.',
            'The card tool <b>hdd</b> shows the used space: the current folder is counted at once - other folders with their calculator, or <b>Count all</b>. The bars show the share of the parent folder or of BASE.',
        ]],
        ['Quick and Analyse', [
            '<b>Quick</b> lists the folder at once. <b>Analyse</b> also counts the files and sizes in all sub folders, and lets you search for a file name in them. Quick is used again when you change folder.',
        ]],
        ['Table', [
            'Click a column heading to sort - type in the row below the headings to filter - drag a column border to change its width.',
            'Rest the mouse on a size: it is shown in bytes. <b>Up</b> goes to the folder above.',
        ]],
        ['Cards and the view bar', [
            'Click a card heading to fold or open it - drag the grip in the corner to change its height - the icons in the heading fold all, widen and so on.',
            'The view bar (top right, can be moved): zoom, full screen (also F11), column view, language, light / dark theme, your user and log out.',
        ]],
        ['Rights and login', [
            '<b>Read:</b> see the folders, download and preview in your write folders and the shared folder. <b>Write:</b> upload, copy, move, rename, ZIP, unpack and delete in your write folders.',
            'Server scripts (.php ...) and hidden files are never shown or written - an administrator can remove the restrictions for one session.',
            'You stay logged in while a page of the program is open.',
        ]],
    ];
    $body1= '<div class="feNice">';
    foreach ($nice as [$h, $lines])
        $body1.= '<div class="feNiceSec"><h4>'.p2h_enc(lang('@'.$h)).'</h4><ul>'.implode('', array_map(fn($l) => '<li>'.lang('@'.$l).'</li>', $lines)).'</ul></div>';
    $body1.= '</div>';
    $body2= lang('@The preview (rest the mouse on a file icon) shows:').'<ul style="margin: 4px 0;">'.
            '<li>'.lang('@Images, video, audio and fonts - everywhere').'</li>'.
            '<li>'.lang('@Text and code with syntax colours, PDF, tables (CSV, TSV, Excel, Calc), the text of Word, ODT, PowerPoint, Impress, RTF and e-books, the file list of ZIP and tar archives - and the first bytes (hex) of any other file - only where you may download the file').'</li>'.
            '</ul>'.lang('@Not shown: 7z and rar archives (hex only), other sheets than the first.');
    htm_Tabs_(head:'', styl:'', rtrn:false);
    htm_Tab(labl:'@Nice to know:',body:$body1, name:'nice',styl:'text-align: left; box-shadow: 3px 3px 6px 0px #ccc; padding: 5px; background-color: lightyellow;', bclr:'lightyellow;', dflt:true);
    htm_Tab(labl:'@File-viewers:',body:$body2, name:'view',styl:'text-align: left; box-shadow: 3px 3px 6px 0px #ccc; padding: 5px; background-color: lightgray;',bclr:'lightgray;');
    htm_Tabs_end(foot:'', styl:'', rtrn:false);
    /* 
    htm_TextDiv(lang('@Nice to know:').'<br>
                <b>Context-Menu:</b> Right-click on table-row to get File/Folder-menu,  Right-click elsewere in table to get Program-menu<br>
                <b>Analyse mode:</b> Collect file names, size and folders in subfolders - Show used space graphichal. Synchronize and ZIP a folder: right-click its row<br>
                <b>Unrounded size:</b> Hover over size'); 
      */                     
    echo '</small>';
    htm_nl(2);
    htm_Card_end(labl:'', icon:'', hint:'', name:'', form:'', subm:false, attr:'', akey:'', kind:'save', simu:false);
    
    htm_nl(2);
    htm_Page_Sect(body:$© );
    htm_nl(2);
    
    // (The program menu: right-click the table outside a row - fe-preview.js)
    echo 
     
        '<script>'.
        '
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
 
    CardOff($First=1,$Last=1);     // The folder tree: folded at the start

htm_Page_end();
}
        
