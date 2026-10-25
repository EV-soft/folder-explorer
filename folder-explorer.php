<?php   $DocFil= 'folder-explorer.php';    $DocVer='2.3.3';    $DocRev='2026-10-25';     $DocIni='evs';  $ModulNr=0; ## File informative only
$©= '𝘓𝘐𝘊𝘌𝘕𝘚𝘌 & 𝘊𝘰𝘱𝘺𝘳𝘪𝘨𝘩𝘵 © 2019-26 EV-soft *** Built with <a href="https://github.com/EV-soft/Clever-Html-Engine" target="_blank" rel="noopener">php2html - Clever HTML Engine</a>';
define('FE_VERSION', $DocVer);  define('FE_DATE', $DocRev);  define('FE_FOOTER', $©);     // Kept: the library overwrites $DocVer, $DocRev and $© when it is included

## Libraries (jQuery, Tablesorter, Font Awesome ...): automatic - php2html loads what a page uses (the login page: no table, no jQuery).
## To force one: $needJquery= '2';  ('0' off, '1' local, '2' CDN) - or $gbl_AutoLibs= false; for the old way (only what is set).

// The bundled library (php2html 1.8.0 - with p2h_audit.inc.php and p2h_trans.inc.php) - the calls in this file need 1.8 (an older shared copy in ../p2h/ would fail):
require_once (__DIR__.'/php2html.lib.php');    // Creating HTML-functions
## The activity log (program menu: Activity log - administrators): who did what, when and from where. A PHP file: opened in the browser it shows nothing.
$gbl_AuditFile= $gbl_AuditFile ?? __DIR__.'/fe-audit.log.php';
require_once (__DIR__.'/p2h_audit.inc.php');
## The translations (program menu: Translations - administrators): the texts of the program in other languages (_trans.sys.json)
$gbl_TransFile= $gbl_TransFile ?? __DIR__.'/_trans.sys.json';
require_once (__DIR__.'/p2h_trans.inc.php');

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

## The trash: deleted files and folders are moved here - a hidden folder, closed for direct web access and never shown in the tree.
## Program menu - Trash: restore them, or delete them for good. After $fe_trash_days days they are deleted for good, and when the trash
## holds more than $fe_trash_max bytes the oldest go first. $fe_trash_days= 0: no trash - deleted at once.
$fe_trash_path= $fe_trash_path ?? __DIR__.'/.fe-trash';
$fe_trash_days= $fe_trash_days ?? 30;
$fe_trash_max=  $fe_trash_max  ?? 500 * 1024 * 1024;
## Share links (file menu: Share link): a link to a file - or a folder, fetched as a ZIP - for people without an account, with an end date and maybe a password.
## Accounts with the right w make them for what they may download - and a link only works while its maker may still download it.
## Program menu - Shared links: see, copy and delete them. $fe_share_days= 0: no share links.
$fe_shares_file= $fe_shares_file ?? __DIR__.'/fe-shares.php';
$fe_share_days=  $fe_share_days  ?? 90;         // The longest time a link may work (days) - administrators may also make a link without an end


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
        if ($d !== false && is_dir($d) && fe_in_dir(p2h_norm_path($d), $root_path) && !fe_is_trash($d)) return p2h_norm_path($d);
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

function fe_conf_init() { // A taken name (POST fe_conf): ask (the answers per name in fe_ch), rename "name (2)", overwrite (the old file into the trash), skip
    $m= (string)($_POST['fe_conf'] ?? 'rename');                  // Without it (no JavaScript): "name (2)" - nothing is overwritten
    $GLOBALS['fe_conf']= in_array($m, ['ask', 'rename', 'overwrite', 'skip'], true) ? $m : 'rename';
    $ch= json_decode((string)($_POST['fe_ch'] ?? ''), true);
    $GLOBALS['fe_ch']= is_array($ch) ? array_filter($ch, fn($v) => in_array($v, ['rename', 'overwrite', 'skip'], true)) : [];
    $a= (string)($_POST['fe_chall'] ?? '');                       // The choice of All - for the names not in the list (more than the dialog shows)
    $GLOBALS['fe_chall']= in_array($a, ['rename', 'overwrite', 'skip'], true) ? $a : 'rename';
}

function fe_choice($key) { // What to do with the taken name $key: rename / overwrite / skip - asked, but not in the answers: the choice of All (none: rename)
    $m= $GLOBALS['fe_conf'] ?? 'rename';
    return $m === 'ask' ? ($GLOBALS['fe_ch'][$key] ?? $GLOBALS['fe_chall'] ?? 'rename') : $m;
}

function fe_up_repl($base, $rel, $name) { // Upload: the bytes of the file it will overwrite (0: none) - for the quota of the shared folder
    $d= fe_rel_dir($base, $rel, false);
    $t= $d === false ? '' : $d.'/'.$name;
    return ($t !== '' && is_file($t) && !is_link($t) && !fe_made($t) && fe_choice(ltrim(substr($t, strlen($base)), '/')) === 'overwrite') ? (int)filesize($t) : 0;
}

function fe_made($path= null) { // Made by this request: a second item of the same name (two folders in the basket, twice in a ZIP) never replaces the first - null: is it?
    static $made= [];
    $k= p2h_norm_path((string)$path);
    if (in_array(PHP_OS_FAMILY, ['Windows', 'Darwin'], true)) $k= strtolower($k);     // There A.txt and a.txt are the same file - on Linux two files
    if (func_num_args() === 2) { $made[$k]= 1;  return true; }
    return isset($made[$k]);
}

function fe_check_answer($list) { // The precheck of "Stop and ask": the taken names as JSON - nothing is done
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['c' => array_values(array_slice($list, 0, 500))], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function fe_conflict($key, $new, $there, $size= null, $time= null) { // One taken name for the dialog: k key, d 1 = two folders (merged) - x 1 = a file against a folder (only "name (2)"), a / b [size, time] of the new / the one there
    $dn= is_dir($new);  $dt= is_dir($there);
    $st= fn($p, $d) => $d ? [null, @filemtime($p) ?: null] : [@filesize($p), @filemtime($p) ?: null];
    return ['k' => $key, 'd' => $dn && $dt ? 1 : 0, 'x' => ($dn !== $dt || is_link($there) || ($new !== '' && fe_same($new, $there))) ? 1 : 0,
            'a' => $new === '' ? [$size, $time] : $st($new, $dn), 'b' => $st($there, $dt)];
}

function fe_same($a, $b) { // The same file or folder (also with other upper / lower case on Windows)
    $ra= realpath($a);  $rb= realpath($b);
    return $ra !== false && $rb !== false && strtolower(p2h_norm_path($ra)) === strtolower(p2h_norm_path($rb)) && @fileinode($ra) === @fileinode($rb);
}

function fe_old_away($path) { // Overwrite: the old file into the trash (without a trash: deleted) - false: it stays (a folder, a link, a protected file)
    if (!is_file($path) || is_link($path) || (empty($GLOBALS['fe_unlocked']) && !fe_name_allowed(basename($path))) || fe_program_file($path)) return false;
    $tn= 0;  $ts= 0;  $GLOBALS['fe_trash_big']= false;
    $inTrash= fe_trash_put($path, $tn, $ts);
    if (!$inTrash) @unlink($path);
    if (file_exists($path)) return false;
    if ($inTrash && !empty($GLOBALS['fe_trash_big'])) $GLOBALS['fe_gone'][]= basename($path);     // Bigger than the trash: deleted for good
    else $GLOBALS['fe_over'][]= basename($path);
    return true;
}

function fe_kept($path) { // Skip: the name was taken - the one there is kept (not an error)
    $GLOBALS['fe_kept'][]= basename($path);
}

function fe_zip_name($dir, $base) { // A new ZIP "name.zip" in $dir - never a name upload and rename refuse: a folder "x.php" gave "x.php.zip" (Apache may run it as PHP)
    $n= fe_clean_name($base.'.zip');
    if (!fe_name_allowed($n)) $n= str_replace('.', '_', fe_clean_name($base)).'.zip';
    return fe_unique($dir.'/'.$n);
}

function fe_unique($target) { // Never overwrite: "name (2).ext" - "folder (2)" - a link (also a broken one) counts as there
    if (!file_exists($target) && !is_link($target)) return $target;
    $dir= dirname($target);  $b= pathinfo($target, PATHINFO_FILENAME);  $e= pathinfo($target, PATHINFO_EXTENSION);
    if (is_dir($target)) { $b= basename($target);  $e= ''; }
    for ($n= 2; ; $n++) { $t= $dir.'/'.$b.' ('.$n.')'.($e > '' ? '.'.$e : '');  if (!file_exists($t) && !is_link($t)) return $t; }
}

function fe_renamed($old, $new) { // Remember that a name was taken: "a.pdf" was saved as "a (2).pdf" - shown by fe_renamed_note()
    if (basename($old) !== basename($new)) $GLOBALS['fe_renamed'][]= [basename($old), basename($new)];
}

function fe_renamed_note() { // "<br>The name was taken - saved as: a.pdf &rarr; a (2).pdf, ..." - and what was overwritten / kept - at most 5 of each - empty: none
    $five= fn($l) => implode(', ', array_slice($l, 0, 5)).(count($l) > 5 ? ' (+'.(count($l) - 5).')' : '');
    $out= '';
    if ($r= $GLOBALS['fe_renamed'] ?? [])
        $out.= '<br>'.p2h_enc(lang('@The name was taken - saved as')).': '.$five(array_map(fn($p) => '<b>'.p2h_enc($p[0]).'</b> &rarr; <b>'.p2h_enc($p[1]).'</b>', $r));
    if ($o= $GLOBALS['fe_over'] ?? [])
        $out.= '<br>'.p2h_enc(lang(fe_trash_dir() !== false ? '@Overwritten - the old one is in the trash' : '@Overwritten')).': '.$five(array_map(fn($x) => '<b>'.p2h_enc($x).'</b>', $o));
    if ($g= $GLOBALS['fe_gone'] ?? [])
        $out.= '<br>'.p2h_enc(lang('@Overwritten - the old one was too big for the trash and is deleted')).': '.$five(array_map(fn($x) => '<b>'.p2h_enc($x).'</b>', $g));
    if ($k= $GLOBALS['fe_kept'] ?? [])
        $out.= '<br>'.p2h_enc(lang('@Skipped - the name was taken, the one there is kept')).': '.$five(array_map(fn($x) => '<b>'.p2h_enc($x).'</b>', $k));
    return $out;
}

function fe_copy_tree($src, $dst, &$n, &$skip) { // Copy a file or a folder - only files the user may read - a taken name only in a merged folder ($GLOBALS['fe_merge']: overwrite / skip)
    fe_more_time();
    if (is_link($src)) { $skip++;  return; }            // Never a link: a loop - or files outside the root
    if (is_dir($src)) {
        if ((empty($GLOBALS['fe_unlocked']) && basename($src)[0] === '.') || fe_is_trash($src)) { $skip++;  return; }     // Hidden folders (.git ...) - the trash
        if (is_link($dst) || (!is_dir($dst) && !@mkdir($dst, 0755))) { $skip++;  return; }
        foreach (scandir($src) ?: [] as $f) if ($f !== '.' && $f !== '..') fe_copy_tree($src.'/'.$f, $dst.'/'.$f, $n, $skip);
        return;
    }
    if (!fe_can_read($src)) { $skip++;  return; }
    if (file_exists($dst) || is_link($dst)) {           // In a folder that was there: as chosen for it
        $m= $GLOBALS['fe_merge'] ?? '';
        if ($m === 'skip' && is_file($dst) && !is_link($dst)) { fe_kept($dst);  return; }
        if ($m !== 'overwrite' || !fe_old_away($dst)) { $skip++;  return; }     // A folder, a link, a protected file: never replaced
    }
    if (!@copy($src, $dst)) { $skip++;  return; }
    @touch($dst, filemtime($src));
    $n++;
}

function fe_move_tree($src, $dst, &$n, &$skip) { // Move a folder into a folder of the same name (merged): file by file, a taken name as chosen ($GLOBALS['fe_merge']) - the emptied folders are removed
    fe_more_time();
    if (is_link($src)) { $skip++;  return; }
    if (is_dir($src)) {
        if (empty($GLOBALS['fe_unlocked']) && basename($src)[0] === '.') { $skip++;  return; }     // A hidden folder: stays
        if (is_link($dst) || (!is_dir($dst) && (file_exists($dst) || !@mkdir($dst, 0755)))) { $skip++;  return; }
        foreach (scandir($src) ?: [] as $f) if ($f !== '.' && $f !== '..') fe_move_tree($src.'/'.$f, $dst.'/'.$f, $n, $skip);
        $mt= @filemtime($src);
        if (@rmdir($src) && $mt) @touch($dst, max($mt, (int)@filemtime($dst)));     // Empty now - else something stayed (kept, protected)
        return;
    }
    if ((empty($GLOBALS['fe_unlocked']) && !fe_name_allowed(basename($src))) || fe_program_file($src)) { $skip++;  return; }   // A protected file - or one of the program: stays
    if (file_exists($dst) || is_link($dst)) {
        $m= $GLOBALS['fe_merge'] ?? '';
        if ($m === 'skip' && is_file($dst) && !is_link($dst)) { fe_kept($dst);  return; }     // It stays where it was
        if ($m !== 'overwrite' || !fe_old_away($dst)) { $skip++;  return; }
    }
    if (@rename($src, $dst)) $n++;
    else $skip++;
}

function fe_conflicts_items($items, $dest, $op) { // "Stop and ask": the items whose name is taken in $dest (those fe_transfer() would not skip anyway)
    $c= [];
    foreach ($items as $src) {
        foreach ($items as $o) if ($o !== $src && fe_in_dir($src, $o)) continue 2;
        if (fe_in_dir($dest, $src) || ($op === 'move' && p2h_norm_path(dirname($src)) === $dest)) continue;
        $dst= $dest.'/'.basename($src);
        if (file_exists($dst) || is_link($dst)) $c[]= fe_conflict(basename($src), $src, $dst);
    }
    return $c;
}

function fe_transfer($items, $dest, $op, &$n, &$skip) { // Copy or move files / folders to the folder $dest - returns the moved items
    global $fe_unlocked, $exch_path, $fe_exchange_quota;
    $room= ($exch_path && fe_in_dir($dest, $exch_path) && empty($fe_unlocked)) ? $fe_exchange_quota - fe_dir_size($exch_path) : PHP_INT_MAX;
    $done= [];
    foreach ($items as $src) {
        foreach ($items as $o) if ($o !== $src && fe_in_dir($src, $o)) continue 2;      // Inside another of the items: comes with it
        if (fe_in_dir($dest, $src) || ($op === 'move' && p2h_norm_path(dirname($src)) === $dest)) { $skip++;  continue; }  // Into itself - or moved where it is (a copy there: "name (2)")
        $dst= $dest.'/'.basename($src);  $how= 'new';
        if (file_exists($dst) || is_link($dst)) {       // The name is taken: as chosen - a file against a folder (or a link): only "name (2)"
            $how= fe_choice(basename($src));
            if (is_link($dst) || is_dir($dst) !== is_dir($src) || fe_same($dst, $src) || fe_made($dst)) $how= 'rename';      // (a copy in its own folder: never over itself - nor over what this request made)
            if ($how === 'rename') $dst= fe_unique($dst);
            elseif ($how === 'skip' && !is_dir($src)) { fe_kept($dst);  continue; }
        }
        $merge= is_dir($src) && ($how === 'overwrite' || $how === 'skip');     // Two folders: merged - a taken file name inside as chosen
        $repl=  ($how === 'overwrite' && !$merge && is_file($dst) && !is_link($dst)) ? (int)filesize($dst) : 0;     // Replaced: its bytes leave the folder (into the trash) - the quota counts the difference
        if ($op === 'move' && $room !== PHP_INT_MAX && !fe_in_dir($src, $exch_path)) {     // Into the shared folder from outside it: its quota
            $size= is_dir($src) ? fe_dir_size($src) : filesize($src);
            if ($size - $repl > $room) { $skip++;  continue; }
            $room-= $size - $repl;
        }
        if ($op === 'move' && fe_holds_program($src)) { $skip++;  continue; }     // The program itself: never moved
        $GLOBALS['fe_merge']= $merge ? $how : '';
        if ($op === 'move') {
            if (!fe_can_write(p2h_norm_path(dirname($src))) || (empty($fe_unlocked) && (basename($src)[0] === '.' || (!is_dir($src) && !fe_name_allowed(basename($src))))) || (!is_dir($src) && fe_program_file($src)))
                $skip++;
            elseif ($merge) { fe_move_tree($src, $dst, $n, $skip);  if (!file_exists($src)) $done[]= $src;  fe_made($dst, true); }
            elseif ($how === 'overwrite' && !fe_old_away($dst)) $skip++;            // A protected file there: never replaced
            elseif (@rename($src, $dst)) { $n++;  $done[]= $src;  fe_renamed($src, $dst);  fe_made($dst, true); }
            else $skip++;
        } else {
            $size= is_dir($src) ? fe_dir_size($src) : filesize($src);
            if ($size - $repl > $room) { $skip++;  continue; }
            if ($how === 'overwrite' && !$merge && !fe_old_away($dst)) { $skip++;  continue; }
            fe_copy_tree($src, $dst, $n, $skip);
            if (file_exists($dst)) { fe_renamed($src, $dst);  fe_made($dst, true); }
            $room-= $size - $repl;
        }
        $GLOBALS['fe_merge']= '';
    }
    fe_size_forget($dest);                                          // The tree counts them again
    foreach ($done as $d) fe_size_forget(dirname($d));
    return $done;
}

function fe_sync_tree($src, $dst, &$n, &$skip) { // One way: copy what is missing or newer in $src to $dst - nothing is deleted, an older file is replaced
    fe_more_time();
    if (is_link($src) || is_link($dst)) { $skip++;  return; }       // Never a link: a loop - or files outside the root (also in the destination)
    if (is_dir($src)) {
        if ((empty($GLOBALS['fe_unlocked']) && basename($src)[0] === '.') || fe_is_trash($src)) { $skip++;  return; }     // Hidden folders (.git ...) - the trash
        if (!is_dir($dst) && (file_exists($dst) || !@mkdir($dst, 0755))) { $skip++;  return; }
        foreach (scandir($src) ?: [] as $f) if ($f !== '.' && $f !== '..') fe_sync_tree($src.'/'.$f, $dst.'/'.$f, $n, $skip);
        return;
    }
    if (!fe_can_read($src) || is_dir($dst)) { $skip++;  return; }
    if (is_file($dst)) {
        if (filemtime($dst) >= filemtime($src)) return;                           // The same or newer there: kept
        if ((empty($GLOBALS['fe_unlocked']) && !fe_name_allowed(basename($dst))) || fe_program_file($dst)) { $skip++;  return; }   // Never replace a protected file - or one of the program
    }
    if (@copy($src, $dst)) { @touch($dst, filemtime($src));  $n++; }
    else $skip++;
}

function fe_sync_need($src, $dst) { // The bytes fe_sync_tree() would add: what is missing or newer in $src (for the quota of the shared folder)
    fe_more_time();
    if (is_link($src) || is_link($dst)) return 0;
    if (is_dir($src)) {
        if ((empty($GLOBALS['fe_unlocked']) && basename($src)[0] === '.') || fe_is_trash($src)) return 0;
        $b= 0;
        foreach (scandir($src) ?: [] as $f) if ($f !== '.' && $f !== '..') $b+= fe_sync_need($src.'/'.$f, $dst.'/'.$f);
        return $b;
    }
    if (!fe_can_read($src) || is_dir($dst)) return 0;
    if (is_file($dst)) return filemtime($dst) >= filemtime($src) ? 0 : max(0, (int)filesize($src) - (int)filesize($dst));
    return (int)filesize($src);
}

function fe_delete_tree($path, &$n, &$skip) { // Delete a file, or a folder with its content - a folder with something protected left (.htaccess ...) stays
    fe_more_time();
    if (is_dir($path) && !is_link($path)) {
        if (empty($GLOBALS['fe_unlocked']) && basename($path)[0] === '.') { $skip++;  return; }     // A hidden folder: kept
        foreach (scandir($path) ?: [] as $f) if ($f !== '.' && $f !== '..') fe_delete_tree($path.'/'.$f, $n, $skip);
        if (@rmdir($path)) $n++; else $skip++;
        return;
    }
    if ((!empty($GLOBALS['fe_unlocked']) || fe_name_allowed(basename($path))) && !fe_program_file($path) && @unlink($path)) $n++;
    else $skip++;
}

function fe_program_file($path) { // A file of the program (its PHP files, scripts, texts, accounts, _assets): never overwritten, deleted, moved or synchronized over - also without restrictions
    static $list= null;
    $r= realpath((string)$path);
    if ($r === false) return false;
    $low= fn($p) => strtolower(p2h_norm_path($p));
    if ($list === null) {
        $list= [];
        foreach (array_merge(get_included_files(), glob(__DIR__.'/fe-*.js') ?: [], glob(__DIR__.'/_trans*.json') ?: [], [__DIR__.'/favicon.svg', (string)($GLOBALS['fe_users_file'] ?? ''), (string)($GLOBALS['fe_shares_file'] ?? '')]) as $f)
            if ($f !== '' && ($x= realpath($f)) !== false) $list[$low($x)]= 1;
    }
    return isset($list[$low($r)]) || strpos($low($r).'/', $low(__DIR__.'/_assets').'/') === 0;
}

function fe_holds_program($path) { // The folder of the program - or a folder above it, or the trash: never deleted or moved (the program would be gone)
    $p= p2h_norm_path((string)$path);
    $t= fe_trash_dir();
    return fe_in_dir(p2h_norm_path(__DIR__), $p) || ($t !== false && fe_in_dir($t, $p));
}

function fe_trash_dir() { // The trash folder - created and closed for direct web access - false: no trash
    global $fe_trash_path, $fe_trash_days;
    static $dir= null;
    if ($dir === null) $dir= ((int)$fe_trash_days > 0 && is_string($fe_trash_path) && $fe_trash_path !== '') ? fe_exchange_init($fe_trash_path) : false;
    return $dir;
}

function fe_is_trash($path) { // The trash folder - or below it: never shown, browsed, copied or zipped
    $t= fe_trash_dir();
    return $t !== false && fe_in_dir(p2h_norm_path($path), $t);
}

function fe_rm_all($path) { // Delete a folder of the program (in the trash) with all its content - a link is removed, never followed
    if (is_dir($path) && !is_link($path)) {
        foreach (scandir($path) ?: [] as $f) if ($f !== '.' && $f !== '..') fe_rm_all($path.'/'.$f);
        return @rmdir($path);
    }
    return @unlink($path);
}

function fe_trash_move($path, $dst, &$n, &$skip) { // As fe_delete_tree() - but into the trash ($dst): what is protected stays where it is
    fe_more_time();
    if (is_dir($path) && !is_link($path)) {
        if (empty($GLOBALS['fe_unlocked']) && basename($path)[0] === '.') { $skip++;  return; }     // A hidden folder: kept
        if (!is_dir($dst) && !@mkdir($dst, 0755)) { $skip++;  return; }
        foreach (scandir($path) ?: [] as $f) if ($f !== '.' && $f !== '..') fe_trash_move($path.'/'.$f, $dst.'/'.$f, $n, $skip);
        $mt= @filemtime($path);
        if (@rmdir($path)) { $n++;  if ($mt) @touch($dst, $mt); }
        else $skip++;
        return;
    }
    if ((!empty($GLOBALS['fe_unlocked']) || fe_name_allowed(basename($path))) && !fe_program_file($path) && @rename($path, $dst)) $n++;
    else $skip++;
}

function fe_trash_put($path, &$n, &$skip) { // Delete: into the trash - false: no trash (the caller deletes it for good)
    $t= fe_trash_dir();
    if ($t === false) return false;
    $id= date('Ymd-His').'-'.bin2hex(random_bytes(4));
    if (!@mkdir($t.'/'.$id, 0755)) return false;
    $n0= $n;
    fe_trash_move($path, $t.'/'.$id.'/'.basename($path), $n, $skip);
    if ($n === $n0) { fe_rm_all($t.'/'.$id);  return true; }           // Nothing of it could be moved
    @file_put_contents($t.'/'.$id.'.json', json_encode(['n' => basename($path), 'f' => p2h_norm_path(dirname($path)),
        'd' => is_dir($t.'/'.$id.'/'.basename($path)) ? 1 : 0, 'u' => $_SESSION[p2h_SESSION_ID]['logged'] ?? '', 't' => time(),
        's' => fe_dir_size($t.'/'.$id)], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
    fe_trash_trim();
    if (!is_file($t.'/'.$id.'.json')) $GLOBALS['fe_trash_big']= true;  // Bigger than the trash: deleted for good at once
    return true;
}

function fe_trash_list() { // The trash, the newest first: id => [n name, f from folder, d 1 = a folder, u user, t time, s bytes]
    $t= fe_trash_dir();  $list= [];
    if ($t === false) return $list;
    foreach (glob($t.'/*.json') ?: [] as $j) {
        $id= basename($j, '.json');
        $m=  json_decode((string)@file_get_contents($j), true);
        if (preg_match('/^\d{8}-\d{6}-[0-9a-f]{8}$/', $id) && is_array($m) && isset($m['n'], $m['f'], $m['t']) && is_string($m['n'])
            && !in_array($m['n'], ['', '.', '..'], true) && strpbrk($m['n'], "/\\\0") === false     // One name - any name a file may have (not fe_clean_name(): a:b.txt was lost)
            && file_exists($t.'/'.$id.'/'.$m['n'])) $list[$id]= $m;
    }
    uasort($list, fn($a, $b) => $b['t'] <=> $a['t']);
    return $list;
}

function fe_trash_drop($id) { // Delete one item of the trash for good
    $t= fe_trash_dir();
    if ($t === false || !preg_match('/^\d{8}-\d{6}-[0-9a-f]{8}$/', (string)$id)) return;
    fe_rm_all($t.'/'.$id);  @unlink($t.'/'.$id.'.json');
}

function fe_trash_trim() { // Away with the old ones - and the oldest, while the trash is too big - and what was left by a broken delete
    global $fe_trash_days, $fe_trash_max;
    $t= fe_trash_dir();
    if ($t === false) return;
    $list= fe_trash_list();  $sum= 0;
    foreach ($list as $id => $m) {                      // The newest first
        $sum+= (int)($m['s'] ?? 0);
        if ($m['t'] < time() - (int)$fe_trash_days * 86400 || $sum > $fe_trash_max) { fe_trash_drop($id);  unset($list[$id]); }
    }
    foreach (scandir($t) ?: [] as $f)
        if (preg_match('/^(\d{8}-\d{6}-[0-9a-f]{8})(\.json)?$/', $f, $mm) && !isset($list[$mm[1]]) && @filemtime($t.'/'.$f) < time() - 3600) fe_rm_all($t.'/'.$f);
}

function fe_trash_may($m) { // May the logged user see / restore / delete this item of the trash: administrators all - others what came from their write folders
    return fe_can('w') && (fe_is_admin() || fe_can_write((string)$m['f']));
}

function fe_trash_restore($id, $m) { // Back to its folder (made again, if it is gone) - a name taken there: "name (2)" - [true, new path] or [false, error]
    global $exch_path, $fe_unlocked, $fe_exchange_quota;
    $src= fe_trash_dir().'/'.$id.'/'.$m['n'];
    if (!fe_can_write($m['f']))                                          return [false, lang('@You may not write in the folder').': '.fe_show_path($m['f'])];
    if (empty($fe_unlocked) && empty($m['d']) && !fe_name_allowed($m['n'])) return [false, lang('@This file can not be changed here')];
    if (!is_dir($m['f']) && !@mkdir($m['f'], 0755, true))               return [false, lang('@It could not be done')];
    if ($exch_path && empty($fe_unlocked) && fe_in_dir($m['f'], $exch_path) && fe_dir_size($exch_path) + (int)($m['s'] ?? 0) > $fe_exchange_quota)
                                                                         return [false, lang('@the shared folder is full')];
    $dst= fe_unique($m['f'].'/'.$m['n']);
    if (!@rename($src, $dst))                                            return [false, lang('@It could not be done')];
    fe_trash_drop($id);
    fe_size_forget($m['f']);
    return [true, $dst];
}

function fe_zip_add($z, $path, $local, &$n, &$skip) { // Add a file or a folder to a ZIP - only files the user may read
    fe_more_time();
    if (is_link($path)) { $skip++;  return; }           // Never a link: a loop - or files outside the root
    if ($n + $skip >= 20000) { $skip++;  return; }      // At most 20000 files - the rest is counted as skipped (and told)
    if (is_dir($path)) {
        if ((empty($GLOBALS['fe_unlocked']) && basename($path)[0] === '.') || fe_is_trash($path)) { $skip++;  return; }
        $z->addEmptyDir($local);
        foreach (scandir($path) ?: [] as $f) if ($f !== '.' && $f !== '..') fe_zip_add($z, $path.'/'.$f, $local.'/'.$f, $n, $skip);
    } elseif (fe_can_read($path) && $z->addFile($path, $local)) $n++;
    else $skip++;
}

function fe_unzip($zip, $dest, $room, &$n, &$skip, $check= false) { // Unpack a ZIP in $dest: safe names only, at most $room bytes, a taken name as chosen - $check: only the taken names (nothing made)
    if (!class_exists('ZipArchive')) return $check ? [] : false;
    $z= new ZipArchive();
    if ($z->open($zip, ZipArchive::RDONLY) !== true) return $check ? [] : false;
    $total= 0;  $taken= [];
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
        $repl= 0;                                                               // A file it will overwrite: its bytes leave the folder
        if (!$isDir && $room !== PHP_INT_MAX && ($d0= fe_rel_dir($dest, implode('/', $parts), false)) !== false && is_file($p0= $d0.'/'.fe_clean_name(end($parts)))
            && !is_link($p0) && !fe_made($p0) && fe_choice(ltrim(substr($p0, strlen($dest)), '/')) === 'overwrite') $repl= (int)filesize($p0);
        if (!$isDir && $total + $st['size'] - $repl > $room) { $skip++;  continue; }      // Too large: skipped (not counted - a smaller file after it may fit), no folders made for it
        $dir= fe_rel_dir($dest, implode('/', $parts).($isDir ? '/-' : ''), !$check);
        if ($dir === false) { $skip++;  continue; }
        if ($isDir) continue;
        $target= $dir.'/'.fe_clean_name(end($parts));
        $key=    ltrim(substr($target, strlen($dest)), '/');          // "sub/a.txt" - the same name in the dialog and here
        if ($check) {
            if (file_exists($target) || is_link($target)) $taken[]= fe_conflict($key, '', $target, (int)$st['size'], (int)$st['mtime']);
            continue;
        }
        if (file_exists($target) || is_link($target)) {
            $how= is_file($target) && !is_link($target) && !fe_made($target) ? fe_choice($key) : 'rename';     // A folder or a link of that name - or twice in the ZIP: "name (2)"
            if ($how === 'skip') { fe_kept($target);  continue; }
            if ($how === 'overwrite' && !fe_old_away($target)) { $skip++;  continue; }
            if ($how === 'rename') $target= fe_unique($target);
        }
        $in=  $z->getStream($st['name']);
        $out= $in ? @fopen($target, 'xb') : false;
        if (!$out) { if ($in) fclose($in);  $skip++;  continue; }
        $total+= (int)stream_copy_to_stream($in, $out, $st['size']) - $repl;      // Never more than the ZIP says (a false size in the ZIP)
        fclose($in);  fclose($out);
        @touch($target, $st['mtime']);
        fe_made($target, true);
        $n++;
        fe_renamed(fe_clean_name(end($parts)), $target);
    }
    $z->close();
    return $check ? $taken : true;
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
    $tmp= p2h_norm_path(sys_get_temp_dir());
    fe_log('@Download', p2h_norm_path(dirname($file)) === $tmp ? $name : fe_show_path(p2h_norm_path($file)),     // A ZIP made for it (a temp file): its name
           html_entity_decode(p2h_nice_filesize(filesize($file)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
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
    foreach ([['fez*', 7200], ['fe-login-*.txt', 86400], ['fe-size-*.txt', 86400], ['fe-thumb-*.jpg', 7 * 86400]] as [$pat, $age])
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

function fe_rel_dir($base, $rel, $make= true) { // Create the subfolders of a relative upload path ("photos/2026/a.jpg") below $base - $make false: only the path (the precheck)
    $parts= array_slice(explode('/', str_replace('\\', '/', $rel)), 0, -1);    // Without the file name
    if (count($parts) > 10) return false;
    $dir= $base;
    foreach ($parts as $part) {
        if (substr(trim($part), 0, 1) === '.') return false;   // ".", ".." and hidden folders (.git ...)
        $part= fe_clean_name($part);
        if ($part === '' || $part[0] === '.') return false;
        $dir.= '/'.$part;
        if (is_link($dir) || ($make && !is_dir($dir) && !@mkdir($dir, 0755))) return false;      // Never into a link
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

function fe_msg($html, $status= 'ok') { // Message shown on the next page (already translated and escaped) - and the result in the activity log ($fe_log_act)
    $_SESSION[p2h_SESSION_ID]['message']= $html;
    $_SESSION[p2h_SESSION_ID]['status']=  $status;
    foreach (['currPath', 'dest', 'upDest', 'target'] as $v)     // Where it was done: the sizes of the tree are counted again
        if (is_string($GLOBALS[$v] ?? null)) fe_size_forget(is_dir($GLOBALS[$v]) ? $GLOBALS[$v] : dirname($GLOBALS[$v]));
    if (!empty($GLOBALS['fe_log_act']))
        fe_log($GLOBALS['fe_log_act'], $GLOBALS['fe_log_obj'] ?? (isset($GLOBALS['currPath']) ? fe_show_path($GLOBALS['currPath']) : ''),
               ($status === 'error' ? '✗ ' : '').html_entity_decode(strip_tags(preg_replace(['#\s*-\s*<a\b[^>]*>.*?</a>#s', '#<br\s*/?>#i'], ['', ' - '], $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function fe_log($acti, $targ= '', $note= '', $user= null) { // One event in the activity log (p2h_audit.inc.php) - the logged user by default
    if (function_exists('p2h_audit')) p2h_audit($acti, $targ, $note, $user ?? ($_SESSION[p2h_SESSION_ID]['logged'] ?? ''));
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

function fe_trash_link() { // " - Open the trash" after a message of a delete
    return fe_trash_dir() === false ? '' : ' - <a href="'.p2h_enc(basename(__FILE__).'?show='.urlencode((string)($GLOBALS['currPath'] ?? '')).'&trash=1').'">'.p2h_enc(lang('@Open the trash')).'</a>';
}

function fe_shares_load() { // The share links: token => [p path, d 1 = a folder, u maker, c made, x end (0: none), h password hash ('': none), n downloads]
    $f= (string)($GLOBALS['fe_shares_file'] ?? '');
    if ($f === '' || !is_file($f)) return [];
    try { $l= @include $f; } catch (Throwable $e) { $l= null; }
    return is_array($l) ? $l : [];
}

function fe_shares_save($list) { // Write the share links (fe-shares.php) - the ended ones away - written to a temp file first, then renamed
    $f= $GLOBALS['fe_shares_file'];
    $list= array_filter($list, fn($s) => is_array($s) && (empty($s['x']) || $s['x'] > time()));
    $tmp= $f.'.'.bin2hex(random_bytes(4)).'.tmp';
    $code= "<?php // Folder-explorer share links - written by the program (file menu: Share link - program menu: Shared links).\n".
           'return '.var_export($list, true).";\n";
    if (@file_put_contents($tmp, $code, LOCK_EX) === false) return false;
    @chmod($tmp, 0640);
    if (!@rename($tmp, $f)) { @unlink($tmp);  return false; }
    if (function_exists('opcache_invalidate')) @opcache_invalidate($f, true);
    return true;
}

function fe_share_url($tok) { // The address of a share link
    return p2h_SELF_URL.'?share='.$tok;
}

function fe_share_may($s) { // May the logged user see / delete this share link: administrators all - others their own
    return fe_is_admin() || ($s['u'] ?? '') === ($_SESSION[p2h_SESSION_ID]['logged'] ?? '');
}

function fe_can_read_dir($dir) { // May the logged user download the folder $dir (as a ZIP): in a write folder or the shared folder - not hidden, not the trash
    global $fe_unlocked, $root_path, $exch_path;
    $dir=  p2h_norm_path((string)$dir);
    $real= realpath($dir);
    if ($real === false || !is_dir($real) || !fe_in_dir(p2h_norm_path($real), $root_path) || fe_is_trash($dir)) return false;
    if (!empty($fe_unlocked)) return true;
    $dirs= fe_write_dirs();
    if (fe_can('r') && $exch_path) $dirs[]= $exch_path;
    foreach ($dirs as $d) if (fe_in_dir($dir, $d) && !fe_hidden_below($dir, $d)) return true;
    return false;
}

function fe_share_may_make($path) { // May the logged user make a share link to $path (a normalized real path): an account with w, that may download it - with the restrictions on
    if (!fe_can('w') || (int)($GLOBALS['fe_share_days'] ?? 0) <= 0 || !empty($GLOBALS['fe_unlocked'])) return false;
    return is_dir($path) ? (!is_link($path) && !fe_holds_program($path) && fe_can_read_dir($path))
                         : (is_file($path) && !is_link($path) && fe_can_read($path) && !fe_program_file($path));
}

function fe_share_tries($fail= null) { // Wrong passwords of a share link from this address in the last 15 minutes - $fail true: one more
    $f= sys_get_temp_dir().'/fe-login-s'.md5(__FILE__.'|'.($_SERVER['REMOTE_ADDR'] ?? '').'|'.(string)($_GET['share'] ?? '')).'.txt';
    $n= (is_file($f) && @filemtime($f) > time() - 900) ? (int)@file_get_contents($f) : 0;
    if ($fail === true) @file_put_contents($f, (string)++$n, LOCK_EX);
    return $n;
}

function fe_share_page($tok) { // ?share=<token>: the page of a share link - for everyone, no login - it works only while its maker may still download the file
    global $auth_users, $fe_root_path, $fe_exchange_path, $root_path, $exch_path, $fe_unlocked, $fe_share_days;
    $list= fe_shares_load();
    $s=    preg_match('/^[0-9a-f]{24}$/', $tok) ? ($list[$tok] ?? null) : null;
    $csrf= (string)($_SESSION['csrf_token'] ?? '');
    if (!isset($_SESSION['p2h_lang']) && !empty($GLOBALS['allLang']) &&                 // No language chosen: the first of the browser that has a translation
        preg_match_all('/(?:^|,)\s*([a-z]{2})\b/', strtolower((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '')), $m))
        foreach ($m[1] as $c) {
            if ($c === 'en') break;
            $i= array_search($c, array_column($GLOBALS['allLang'], 'code'), true);
            if ($i !== false && !empty($GLOBALS['allLang'][$i]['translation'])) { $GLOBALS['lang']= $c;  $GLOBALS['natindx']= $i;  break; }
        }
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();     // The session of the visitor is never changed here - below: the maker, in this request only
    $fe_unlocked= false;
    $root_path=   p2h_norm_path(realpath($fe_root_path) ?: $fe_root_path);
    $path= is_array($s) ? realpath((string)($s['p'] ?? '')) : false;
    $ok=   $path !== false && (int)$fe_share_days > 0 && (empty($s['x']) || $s['x'] > time()) && (empty($auth_users) || isset($auth_users[$s['u'] ?? ''][2]));     // (no accounts: no login)
    if ($ok) {
        $_SESSION[p2h_SESSION_ID]['logged']= $s['u'];   // The rights of the maker - as they are now
        $exch_path= fe_exchange_init($fe_exchange_path);
        $path= p2h_norm_path($path);
        $ok=   fe_share_may_make($path) && !empty($s['d']) === is_dir($path);
    }
    $err= '';
    if ($ok && (isset($_GET['get']) || isset($_POST['fe_spw'])) && (($s['h'] ?? '') === '' || isset($_POST['fe_spw']))) {     // With a password: only the form posts it
        if (($s['h'] ?? '') !== '') {
            if (fe_share_tries() >= 10) $err= lang('@Too many wrong passwords - wait 15 minutes and try again');
            elseif (!password_verify((string)($_POST['fe_spw'] ?? ''), (string)$s['h'])) { sleep(1);  fe_share_tries(true);  $err= lang('@Wrong password'); }
        }
        if ($err === '') {
            $now= fe_shares_load();                     // Counted in the file as it is now
            if (isset($now[$tok])) { $now[$tok]['n']= (int)($now[$tok]['n'] ?? 0) + 1;  fe_shares_save($now); }
            if (!is_dir($path)) {
                $_SESSION[p2h_SESSION_ID]['logged']= lang('@share link').' ('.$s['u'].')';     // The activity log: fetched by the link
                fe_send_file($path);
            }
            $err= lang('@It could not be done');
            if (class_exists('ZipArchive')) {
                fe_temp_cleanup();
                $tmp= (string)tempnam(sys_get_temp_dir(), 'fez');  $n= 0;  $skip= 0;
                $z= new ZipArchive();
                if ($tmp !== '' && $z->open($tmp, ZipArchive::OVERWRITE) === true) { fe_zip_add($z, $path, basename($path), $n, $skip);  $z->close(); }
                if ($n > 0 && is_file($tmp) && filesize($tmp) > 0) {
                    register_shutdown_function(function() use ($tmp) { @unlink($tmp); });
                    $_SESSION[p2h_SESSION_ID]['logged']= lang('@share link').' ('.$s['u'].')';
                    fe_send_file($tmp, basename($path).'.zip');
                }
                @unlink($tmp);
                if ($n === 0) $err= lang('@The folder is empty');
            }
        }
    }
    if (!$ok) http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: private, no-store');
    header('X-Robots-Tag: noindex, nofollow');
    header('Referrer-Policy: no-referrer');
    header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'");
    $e= fn($t) => p2h_enc(lang($t));
    echo '<!doctype html><html lang="'.p2h_enc((string)($GLOBALS['lang'] ?? 'en')).'"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'.
         '<meta name="robots" content="noindex, nofollow"><title>'.$e($ok ? '@Shared file' : '@The link does not work').'</title><style>'.
         ':root { color-scheme: light dark; --bg: #e6e8ea; --card: #fff; --text: #222; --muted: #666; --acc: #1a5e8a; --err: #b00020; }'.
         '@media (prefers-color-scheme: dark) { :root { --bg: #15181c; --card: #1f2328; --text: #e3e3e3; --muted: #aeb8c2; --acc: #8ec5ea; --err: #ff7a8a; } }'.
         'body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: var(--bg); color: var(--text); font: 16px/1.5 system-ui, sans-serif; }'.
         'main { box-sizing: border-box; width: min(520px, calc(100vw - 32px)); margin: 16px; padding: 24px 28px; background: var(--card); border-radius: 10px; box-shadow: 0 4px 18px rgba(0,0,0,.18); }'.
         'h1 { margin: 0 0 6px; font-size: 1.25rem; word-break: break-word; } .meta { margin: 0 0 18px; color: var(--muted); font-size: .9rem; }'.
         '.err { color: var(--err); font-weight: 600; } label { display: block; margin-bottom: 10px; } input[type=password] { width: 100%; box-sizing: border-box; padding: 8px; font: inherit; margin-top: 4px; }'.
         '.btn, button { display: inline-block; padding: 9px 22px; border: 0; border-radius: 6px; background: var(--acc); color: #fff; font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; }'.
         '@media (prefers-color-scheme: dark) { .btn, button { color: #10202c; } } footer { margin-top: 22px; font-size: .75rem; color: var(--muted); }'.
         '</style></head><body><main>';
    if (!$ok) echo '<h1>'.$e('@The link does not work').'</h1><p>'.$e('@It has ended or been deleted - or the file is not there any more. Ask the one who sent it for a new link.').'</p>';
    else {
        $isDir= is_dir($path);
        echo '<h1>'.($isDir ? '&#128193;' : '&#128196;').' '.p2h_enc(basename($path)).'</h1>'.
             '<p class="meta">'.($isDir ? $e('@A folder - downloaded as a ZIP file') : p2h_nice_filesize((int)filesize($path))).' &middot; '.$e('@Shared by').' '.p2h_enc((string)$s['u']).' &middot; '.
             (empty($s['x']) ? $e('@No end date') : $e('@Works until').' '.date('Y-m-d H:i', (int)$s['x'])).'</p>';
        if ($err !== '') echo '<p class="err" role="alert">'.p2h_enc($err).'</p>';
        if (($s['h'] ?? '') !== '')
            echo '<form method="post" action="?share='.$tok.'"><input type="hidden" name="csrf_token" value="'.p2h_enc($csrf).'">'.
                 '<label>'.$e('@Password').'<input type="password" name="fe_spw" required autofocus autocomplete="off"></label>'.
                 '<button type="submit">'.$e('@Download').'</button></form>';
        else echo '<p><a class="btn" href="?share='.$tok.'&amp;get=1">'.$e('@Download').'</a></p>';
    }
    echo '<footer>Folder-explorer</footer></main></body></html>';
    exit;
}

const FE_EDIT_MAX= 2 * 1024 * 1024;     // The biggest text file the editor opens (bytes)

function fe_edit_refused($file) { // Why the logged user may not edit the file $file (a normalized real path) in the editor - '' = it may
    if (!is_file($file) || is_link($file)) return lang('@File not found');
    if (!fe_can('w') || !fe_can_write(p2h_norm_path(dirname($file))) || !fe_can_read($file) || !fe_name_allowed(basename($file)) || fe_program_file($file) || fe_is_trash($file))
        return lang('@This file can not be changed here');
    if (fe_preview_kind(basename($file), true) !== 'txt') return lang('@Only text files can be edited');
    if (filesize($file) > FE_EDIT_MAX) return lang('@The file is too big to be edited here (max. 2 MB)');
    return '';
}

function fe_find_hit($txt, $term) { // The first place of $term in the text $txt (upper / lower case alike): [line number, the line around it] - null: not found
    $utf= mb_check_encoding($txt, 'UTF-8');
    $pos= ($utf && preg_match('/[^\x00-\x7F]/', $term)) ? strpos(mb_strtolower($txt, 'UTF-8'), mb_strtolower($term, 'UTF-8')) : stripos($txt, $term);
    if ($pos === false) return null;
    $ls= strrpos(substr($txt, 0, $pos), "\n");  $ls= $ls === false ? 0 : $ls + 1;
    $le= strpos($txt, "\n", $pos);  $line= rtrim(substr($txt, $ls, ($le === false ? strlen($txt) : $le) - $ls), "\r");
    $at= $pos - $ls;
    if (strlen($line) > 140) { $from= max(0, $at - 50);  $line= ($from ? '…' : '').mb_strcut($line, $from, 140, 'UTF-8').'…'; }
    return [substr_count($txt, "\n", 0, $pos) + 1, trim(mb_scrub($line, 'UTF-8'))];
}

function fe_find($dir, $term, $content) { // Search the names below $dir - and the text of text files the user may download ($content) - [hits [path, folder, line, text], searched, stopped]
    $hits= [];  $seen= 0;  $cut= false;  $t0= microtime(true);
    $re=   strpbrk($term, '*?') === false ? null : '/^'.str_replace(['\*', '\?'], ['.*', '.'], preg_quote($term, '/')).'$/iu';     // * and ?: the whole name
    $stack= [$dir];
    while ($stack && !$cut) {
        $d= array_pop($stack);
        $list= @scandir($d);
        if ($list === false) continue;
        foreach ($list as $f) {
            if ($f === '.' || $f === '..' || (empty($GLOBALS['fe_unlocked']) && $f[0] === '.')) continue;     // Hidden files and folders: never
            $p= $d.'/'.$f;
            if (fe_is_trash($p)) continue;
            $isDir= is_dir($p);
            if ($isDir && !is_link($p)) $stack[]= $p;
            $seen++;
            $hit= $re ? preg_match($re, $f) === 1 : mb_stripos($f, $term, 0, 'UTF-8') !== false;
            $in=  null;
            if (!$hit && $content && !$isDir && @filesize($p) <= FE_EDIT_MAX && fe_preview_kind($f, true) === 'txt' && fe_can_read($p)) {
                $txt= @file_get_contents($p);
                if (is_string($txt) && $txt !== '') $in= fe_find_hit($txt, $term);
            }
            if ($hit || $in) $hits[]= [$p, $isDir, $in[0] ?? 0, $in[1] ?? ''];
            if (count($hits) >= 500 || $seen >= 100000 || microtime(true) - $t0 > 20) { $cut= true;  break; }
        }
        fe_more_time();
    }
    usort($hits, fn($a, $b) => strnatcasecmp($a[0], $b[0]));
    return [$hits, $seen, $cut];
}

function fe_thumb($file, $ext) { // A JPEG of at most 240 px of an image (GD) for the gallery - kept a week in the temp folder - null: none (no GD, a type GD can not read, too big)
    $fn= ['jpg'=>'imagecreatefromjpeg', 'jpeg'=>'imagecreatefromjpeg', 'jfif'=>'imagecreatefromjpeg', 'pjpeg'=>'imagecreatefromjpeg', 'png'=>'imagecreatefrompng',
          'apng'=>'imagecreatefrompng', 'gif'=>'imagecreatefromgif', 'webp'=>'imagecreatefromwebp', 'bmp'=>'imagecreatefrombmp', 'avif'=>'imagecreatefromavif'][$ext] ?? '';
    if ($fn === '' || !function_exists($fn) || !function_exists('imagecreatetruecolor')) return null;
    $t= sys_get_temp_dir().'/fe-thumb-'.md5(__FILE__.'|'.$file.'|'.filemtime($file).'|'.filesize($file)).'.jpg';
    if (is_file($t)) { @touch($t);  return $t; }
    $info= @getimagesize($file);
    if (!$info || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 40000000) return null;     // Over 40 megapixels: not read (the memory)
    $need= memory_get_usage() + $info[0] * $info[1] * 5 + 16 * 1048576;                             // What GD may need
    $lim=  fe_ini_bytes((string)ini_get('memory_limit'));
    if ($lim > 0 && $need > $lim) { if ($need > 512 * 1048576 || @ini_set('memory_limit', (string)$need) === false) return null; }
    $im= @$fn($file);
    if (!$im) return null;
    $w= imagesx($im);  $h= imagesy($im);  $k= min(1, 240 / max($w, $h));
    $tw= max(1, (int)round($w * $k));  $th= max(1, (int)round($h * $k));
    $out= imagecreatetruecolor($tw, $th);
    imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));        // A transparent image: on white
    imagecopyresampled($out, $im, 0, 0, 0, 0, $tw, $th, $w, $h);
    $im= null;
    if ($fn === 'imagecreatefromjpeg' && function_exists('exif_read_data')) {     // A photo turned by the camera
        $o= (int)(@exif_read_data($file)['Orientation'] ?? 1);
        if (in_array($o, [3, 6, 8], true)) $out= imagerotate($out, [3 => 180, 6 => -90, 8 => 90][$o], 0) ?: $out;
    }
    $tmp= $t.'.'.bin2hex(random_bytes(3)).'.tmp';
    $ok=  @imagejpeg($out, $tmp, 80);
    $out= null;
    if (!$ok || !@rename($tmp, $t)) { @unlink($tmp);  return null; }
    return $t;
}

function fe_dupes($dir) { // Files with the same content below $dir - only files the user may download: [[[size, [paths]], ...] the most space first, files seen, stopped]
    $bySize= [];  $seen= 0;  $cut= false;  $t0= microtime(true);
    $stack= [$dir];
    while ($stack && !$cut) {
        $d= array_pop($stack);
        $list= @scandir($d);
        if ($list === false) continue;
        foreach ($list as $f) {
            if ($f === '.' || $f === '..' || (empty($GLOBALS['fe_unlocked']) && $f[0] === '.')) continue;
            $p= $d.'/'.$f;
            if (is_link($p) || fe_is_trash($p)) continue;
            if (is_dir($p)) { $stack[]= $p;  continue; }
            if (++$seen > 200000 || microtime(true) - $t0 > 15) { $cut= true;  break; }
            $s= (int)@filesize($p);
            if ($s > 0 && fe_can_read($p)) $bySize[$s][]= $p;
        }
        fe_more_time();
    }
    $groups= [];
    foreach ($bySize as $s => $ps) {        // The same size - then the same first 64 KB - then the same SHA-1 of the whole file
        if (count($ps) < 2) continue;
        if (microtime(true) - $t0 > 40) { $cut= true;  break; }
        $first= [];
        foreach ($ps as $p) { $c= @file_get_contents($p, false, null, 0, 65536);  if (is_string($c)) $first[sha1($c)][]= $p; }
        foreach ($first as $same) {
            if (count($same) < 2) continue;
            $full= [];
            foreach ($same as $p) { $h= $s <= 65536 ? 'x' : @sha1_file($p);  if ($h) $full[$h][]= $p; }
            foreach ($full as $g) if (count($g) > 1) { natcasesort($g);  $groups[]= [(int)$s, array_values($g)]; }
            fe_more_time();
        }
    }
    usort($groups, fn($a, $b) => $b[0] * (count($b[1]) - 1) <=> $a[0] * (count($a[1]) - 1));
    return [$groups, $seen, $cut];
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
    [data-theme="dark"] .feNiceSec h4, [data-theme="dark"] .feKeysWrap h4, [data-theme="dark"] .feManHead { color: #8cc4f0; }
    [data-theme="dark"] .feKeys th { color: #aeb8c2; border-color: #5a6672; }
    [data-theme="dark"] .feKeys td, [data-theme="dark"] .feFacts th { border-color: #3f4852; }
    [data-theme="dark"] .feFacts th { color: #cfd6dd; }
    [data-theme="dark"] .feAccYes { color: #6fd08c; }  [data-theme="dark"] .feAccNo { color: #ff7a8a; }
    [data-theme="dark"] .feKeys kbd { background: #2b3036; border-color: #5a6672; color: #e3e7eb; }
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
    /* Search (card header), Search result, Shared links, the editor */
    .feFindBox { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; padding: 8px; border-bottom: 1px solid #d5dbe1; font-size: 12px; }
    .feFindBox input[type=search] { flex: 1 1 100%; box-sizing: border-box; padding: 4px 6px; border: 1px solid #888; border-radius: 4px; font: inherit; font-size: 13px; }
    .feFindForm { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 6px; }
    .feFindForm input[type=search] { flex: 1 1 220px; max-width: 420px; padding: 4px 7px; border: 1px solid #8a96a3 !important; border-radius: 4px; background: #fff !important; color: #222 !important; font: inherit; }
    [data-theme="dark"] .feFindForm input[type=search] { background: #1d2126 !important; color: #e3e7eb !important; border-color: #5a6672 !important; }
    .feFindSum { margin: 4px 0 8px; }  .feFindCut { color: #b05000; }
    .feFindWrap { max-height: 60vh; overflow: auto; }
    .feFindTbl { width: 100%; }  .feFindTbl td { vertical-align: top; }
    .feFindLine code { white-space: pre-wrap; word-break: break-word; font-size: 12px; }
    .feShareGone td { opacity: .55; }
    .feDlgInp { display: block; width: 100%; box-sizing: border-box; margin-top: 4px; padding: 5px 7px; border: 1px solid #8a96a3 !important; border-radius: 4px;
                background: #fff !important; color: #222 !important; font: inherit; font-weight: normal; }
    [data-theme="dark"] .feDlgInp { background: #1d2126 !important; color: #e3e7eb !important; border-color: #5a6672 !important; }
    .feEdTa { display: block; width: 100%; height: 68vh; box-sizing: border-box; resize: vertical; padding: 8px; tab-size: 4;
              font: 13px/1.45 Consolas, "DejaVu Sans Mono", monospace; border: 1px solid #aab4be; border-radius: 4px; background: #fff; color: #222; }
    .p2hDlgFoot .feEdSt { flex: 1; margin-right: auto; font-size: 12px; color: #555; text-align: left; align-self: center; }
    .p2hDlgFoot .feEdSt.feEdDirty { color: #b05000; font-weight: 600; }
    .p2hDlgFoot .feEdWrap { font-size: 12px; align-self: center; margin-right: 8px; white-space: nowrap; }
    [data-theme="dark"] .feEdTa { background: #1d2126; color: #e3e7eb; border-color: #4a5560; }
    [data-theme="dark"] .feFindBox { border-bottom-color: #3f4852; }
    [data-theme="dark"] .p2hDlgFoot .feEdSt { color: #aeb8c2; }
    [data-theme="dark"] .feFindCut, [data-theme="dark"] .p2hDlgFoot .feEdSt.feEdDirty { color: #ffb066; }
    /* Gallery and Duplicates (card header) */
    .feGalBtn { display: inline-flex; align-items: center; gap: 5px; height: 28px; padding: 0 12px; border: 1px solid #9aa5b1; border-radius: 17px; background: #fff;
                color: #555; font-size: 13px; cursor: pointer; text-decoration: none; white-space: nowrap; box-shadow: none; }
    .feGalBtn[aria-pressed=true] { background: #3c8dbc; border-color: #3c8dbc; color: #fff; }
    .feGal { display: none; }
    .feGalOn > .wrapper { display: none !important; }
    .feGalOn > .feGal { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; padding: 6px 4px 10px; text-align: center; }
    .feTile { position: relative; padding: 6px; border: 1px solid #d0d7de; border-radius: 6px; background: #fff; min-width: 0; }
    .feTile.feTileOn { outline: 2px solid #3c8dbc; background: #eef6fc; }
    .feTileChk { position: absolute; top: 8px; left: 8px; z-index: 1; margin: 0; width: 16px; height: 16px; }
    .feTilePic { display: flex; align-items: center; justify-content: center; height: 118px; overflow: hidden; border-radius: 4px; background: #f3f5f7; cursor: pointer; }
    .feTilePic:focus-visible { outline: 2px solid #3c8dbc; outline-offset: 2px; }
    .feTilePic img { max-width: 100%; max-height: 118px; object-fit: contain; }
    .feTileIc { font-size: 46px; color: #7a8a99; }  .feTileIc.feTileDir { color: #e2b33c; }
    .feTileName { margin-top: 5px; font-size: 12px; line-height: 1.25; max-height: 2.5em; overflow: hidden; word-break: break-word; }
    .feTileMeta { font-size: 11px; color: #777; }
    .feGalNone { grid-column: 1 / -1; color: #777; }
    .feDupHead td { padding-top: 10px !important; border-bottom: 1px solid #d0d7de; }
    [data-theme="dark"] .feGalBtn { background: #2b3036; color: #e3e7eb; border-color: #5a6672; }
    [data-theme="dark"] .feGalBtn[aria-pressed=true] { background: #2d6a96; border-color: #2d6a96; color: #fff; }
    [data-theme="dark"] .feTile { background: #232a31; border-color: #3f4852; }
    [data-theme="dark"] .feTile.feTileOn { background: #1d3447; }
    [data-theme="dark"] .feTilePic { background: #1b2026; }
    [data-theme="dark"] .feTileMeta, [data-theme="dark"] .feGalNone { color: #aeb8c2; }
    [data-theme="dark"] .feDupHead td { border-bottom-color: #3f4852; }
    /* Used space (Analyse): the % text on the middle of the bar */
    .feMeter { position: relative; width: 95%; }
    .feMeter meter { width: 100%; display: block; }
    .feMeter small { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); padding: 0 3px; line-height: 1.15;
                     background: lightyellow; color: #333; opacity: .8; border-radius: 3px; white-space: nowrap; }
    .feSelDest { display: block; margin: 4px 0 2px; color: #555; }
    /* File features for the checked rows: always visible - locked to the bottom of the scrolling file window (the card body) */
    .tableStyle > span:has(#feSelSum) { position: sticky !important; bottom: 0; z-index: 4; display: block !important; padding: 4px 0 6px;
                                        background: var(--CardsBgrd, #fff); border-top: 1px solid #c8d0d8; box-shadow: 0 -4px 6px -4px rgba(0,0,0,.25); }
    .tableStyle > span:has(#feSelSum) fieldset { vertical-align: top; margin-top: 2px; margin-bottom: 2px; }
    [data-theme="dark"] .tableStyle > span:has(#feSelSum) { border-top-color: #3f4852; box-shadow: 0 -4px 6px -4px rgba(0,0,0,.6); }
    .feSelSum { display: block; width: 0; min-width: 100%; margin: 4px 0 0; color: #1a5e8a; line-height: 1.35; }      /* Never wider than the 4 buttons: the facts wrap */
    .feSelSum:empty { display: none; }
    .feSelSum div { display: inline-block; white-space: nowrap; }
    .feSelSum div:first-child { font-weight: 600; }
    .feSelSum div + div:not(.feSelNc)::before { content: "·"; margin: 0 5px; color: #888; }
    .feSelSum div.feSelNc { display: block; white-space: normal; font-size: 11px; opacity: .85; }
    [data-theme="dark"] .feSelSum { color: #8ec5ea; }
    .feNice { columns: 2 340px; column-gap: 22px; font-size: 12.5px; line-height: 1.45; }
    .feNiceSec { break-inside: avoid; margin-bottom: 8px; }
    .feNiceSec h4 { margin: 2px 0 2px; font-size: 13px; color: #1a5276; }
    .feNiceSec ul { margin: 0; padding-left: 16px; }
    .feNiceSec li { margin: 1px 0; }
    .feNiceSec ol { margin: 0; padding-left: 20px; }
    .feFaqA { margin: 0 0 2px; }
    .feKeysWrap { display: flex; flex-wrap: wrap; gap: 8px 26px; font-size: 12.5px; line-height: 1.4; }
    .feKeysWrap > div { flex: 1 1 380px; min-width: 0; }
    .feKeysWrap h4 { margin: 2px 0 4px; font-size: 13px; color: #1a5276; }
    .feKeys { border-collapse: collapse; width: 100%; margin-bottom: 4px; }
    .feKeys th { text-align: left; font-size: 12px; color: #555; padding: 2px 6px; border-bottom: 2px solid #b8c4d0; }
    .feKeys td { padding: 3px 6px; vertical-align: top; border-bottom: 1px solid #d5dde5; }
    .feKeys kbd { display: inline-block; padding: 0 5px; font: 11.5px/1.5 monospace; white-space: nowrap; background: #fff;
                  border: 1px solid #a9b4bf; border-bottom-width: 2px; border-radius: 4px; }
    .feKeysNo { color: #999; }
    .feManHead { margin: 6px 0 6px 2px; font-size: 16px; font-weight: 600; color: #1a5276; text-align: left; }
    .feManHead i { margin-right: 4px; }
    .feFacts th { width: 190px; text-align: left; vertical-align: top; color: #333; font-size: 12.5px; border-bottom: 1px solid #d5dde5; padding: 3px 6px; }
    .feFacts code { font-size: 11.5px; white-space: normal; }
    .feAccYes { color: #1b7a32; }  .feAccNo { color: #b00020; }
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
{ return htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }     // ENT_SUBSTITUTE: a name with invalid UTF-8 (an old Latin-1 name on Linux) gave '' - the row had no name

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
                 new RecursiveCallbackFilterIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                     fn($f) => !($f->isDir() && fe_is_trash($f->getPathname()))),     // Never the trash (the names of deleted files)
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

function fe_size_forget($path) { // A folder was changed: its remembered size - and those of the folders above it - are counted again
    global $root_path;
    if (!is_string($path) || $path === '' || !isset($root_path)) return;
    for ($d= p2h_norm_path($path); fe_in_dir($d, $root_path); $d= p2h_norm_path(dirname($d))) {
        @unlink(sys_get_temp_dir().'/fe-size-'.md5(__FILE__.'|'.$d).'.txt');
        if ($d === $root_path || dirname($d) === $d) break;
    }
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
    $needOwn= $sizes && (!empty($GLOBALS['calc_folder']) || fe_in_dir(p2h_norm_path($dir), $cur) || fe_in_dir($cur, p2h_norm_path($dir)));   // Analyse: every opened folder   // Below the current folder - or on the path to it
    $arrItems = @scandir($dir);
    if (!is_array($arrItems)) return 0;  // Unreadable folder
    $subs= [];  $own= 0;
    fe_more_time();
    foreach ($arrItems as $item) {
        if ($item === '.' || $item === '..') continue;
        if (is_dir($dir.'/'.$item)) { if (!is_link($dir.'/'.$item) && !fe_is_trash($dir.'/'.$item)) $subs[]= $item; }  // Do not follow symlinks (loops) - never the trash
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
            $addUp= $open && ($inCur || $onPath || !empty($GLOBALS['calc_folder']));     // Added up from its rows (Analyse: all opened folders)
            $known= $addUp ? null : fe_size_cache($full);
            $attr=  $addUp ? 'data-own="'.$mine.'"'
                  : ($known !== null && $known >= 0 ? 'data-size="'.$known.'"' : 'data-lazy="'.p2h_enc($full).'"'.($inCur || !empty($GLOBALS['calc_folder']) ? '' : ' data-manual="1"'));   // Analyse: all counted at once
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
            elseif (@is_dir ($pathName) && ($obj != '.') && ($obj != '..') && !fe_is_trash($pathName)) { $folders[] = $obj; }     // Never the trash
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
                 'g'=>basename(__FILE__).'?show='.urlencode($file_path), 'b'=>p2h_norm_path($file_path), 'l'=>fe_share_may_make(p2h_norm_path($file_path)) ? 1 : 0,
                 'k'=>in_array(p2h_norm_path($file_path), $basket ?? [], true) ? 1 : 0], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)).'" hidden></span></div>';
        $rec['ext']= $ext;
        $rec['size']= $sortsize.'<div style="font-family:monospace; color:green;">'.fe_hint($showsize, number_format((float)$foldsize, 0,',',' ').' Bytes').'</div>';
        $rec['modifyed']= $sorttime.$modif;
        $rec['perms']= $perms;
        $rec['owner']= p2h_enc($owner['name']);      //$rec['group']= $group['name'];
        $rec['access']= '-';
        $rec['space']= !$calc_folder ? '' : '<div class="feMeter"><meter id="currFold'.$ix.'" low="0.15" optimum="0.30" high="0.60" max="1" value="'.$meter.'">'.$meter.'</meter><small>'.number_format($meter*100,2).' %</small></div>';
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
        // Where the user may read: Download in the file menu - where the user may write: rename, copy ... in the file menu
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
              'd'=>$can_dl ? $dlUrl : '',
              'x'=>$wr_file && $filesize_raw <= FE_EDIT_MAX && fe_preview_kind($fld, true) === 'txt' && !fe_program_file($file_path) ? 1 : 0,   // Edit (a text file)
              'l'=>!$is_link && fe_share_may_make(p2h_norm_path($file_path)) ? 1 : 0];                                                 // Share link
        $nm= '<span class="feBp" data-bp="'.p2h_enc(p2h_norm_path($file_path)).'" hidden></span><span class="feFileName" tabindex="0" role="button" aria-haspopup="menu" data-fm="'.p2h_enc(json_encode($fm, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)).'">'.p2h_enc($fld).'</span>';
        if ($calc_folder)
            $nameFld= '<div class="file-name-div">'.$ic.'&nbsp;'. $nm. ($ix < 1200 ? '<div class="live-preview-img">'.$fileInfo.'</div>' : ''). '</div>';  // Max 200 on one page ($ix starts at 1000)
        else
            $nameFld= $ic.'&nbsp;'. $nm;
        if ($ext>'') $ext = '.'.p2h_enc($ext);
        $rec['ix']= $ix;
        $rec['name']= $nameFld;
        $rec['ext']= $ext;
        $rec['size']= $sortsize.'<div>'.fe_hint($showsize, number_format((float)$filesize_raw, 0,',',' ').' Bytes').'</div>';
        $rec['modifyed']= $sorttime.$modif;
        $rec['perms']= $perms;
        $rec['owner']= p2h_enc($owner['name']);      //$rec['group']= $group['name'];
        $rec['access']= $accetime.$acces;
        $rec['space']= !$calc_folder ? '' : '<div class="feMeter"><meter id="currFile'.$ix.'" low="0.15" optimum="0.30" high="0.60" max="1" value="'.$meter.'">'.$meter.'</meter><small>'.number_format($meter*100,2).' %</small></div>';
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
    htm_Input($labl= 'Search', plho: '@Enter...', icon: '', hint: '@Search for files and folders in this folder and all folders below it - by name, or in the text of text files',
              vrnt: $type= 'html', name: 'fsearch',
              valu: '<div><i class="fa fa-search"></i> '.($calc_folder== true ? ($GLOBALS['totFiles'] ?? count($arrNames)).' '.lang('@Files in subfolders') : lang('@Search in subfolders')).'</div>',
              form: '', wdth: '200px', algn: 'left',attr: 'onclick="ToggShow()" class="dropbtn" ',rtrn: true,
              unit: '', disa: false, rows: '0', step: '', list: [], llgn: 'R', bord: '', ftop: '')
              . '
    <div id="FileSearch" class="dropdown-content" 
        >
        <div class="feFindBox" role="search">'.      // The server searches this folder and all below it (feFindGo) - a field, not a form: this is inside the form of the card
            '<input type="search" id="feFind" maxlength="100" placeholder="'.p2h_enc(lang('@Name or part of it - * and ? allowed')).'" aria-label="'.p2h_enc(lang('@Search for')).'" '.
                'onkeydown="if (event.key === &quot;Enter&quot;) { event.preventDefault();  feFindGo(); }">'.
            '<label><input type="checkbox" id="feFindC"> '.p2h_enc(lang('@also in the text of text files')).'</label>'.
            '<button type="button" class="txtbutt feXfBtn" onclick="feFindGo()"><i class="fas fa-search"></i> '.p2h_enc(lang('@Search')).'</button>'.
        '</div>'.($calc_folder ? '
        <input type="text" placeholder="'.p2h_enc(lang('@Part of a file or folder name...')).'" id="searchInput" onkeyup="filterFunction()" z-index:202;
            aria-label="'. p2h_enc(lang('@Type a search string: Part of the file- / folder-name')).'"
            style="width:200px; text-align:left; font-family:arial; border: 1px solid black;">
        <div style="font-size: 11px; color: gray; text-align: left; padding: 2px 6px;">'. lang('@Click to open folder containing this file ') .
            (($GLOBALS['totFiles'] ?? 0) > count($arrNames) ? '<br>'.p2h_enc(lang('@The search list has the first')).' '.count($arrNames).' - '.p2h_enc(lang('@go to a sub folder to search the rest')) : '').'</div>' : '');
        $name_list= ''; // List to search for file names in sub-folders 
        foreach($arrNames as $file) {
            $pos= strrpos($file,'/')+1;
            $dir= substr($file,0,$pos);
            $fil= substr($file,$pos);
            $shown= fe_show_path(rtrim($dir, '/')).'/';
            $name_list .= '<a href="'. basename(__FILE__). '?show='.urlencode(rtrim($dir,'/')).'" >'.
                        '<span class="colrgray">'.p2h_enc(mb_strlen($shown) > 32 ? '...'.mb_substr($shown,-32) : $shown).':</span> '. p2h_enc($fil).'</a>';
            }
            if ($calc_folder) $result.= '<div style= "text-align:left;">'.$name_list.'</div>';      // Analyse mode: the names counted - filtered while you type
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
    if (isset($_SESSION[p2h_SESSION_ID]['logged'])) fe_log('@Log out');
    unset($_SESSION[p2h_SESSION_ID]['logged'], $_SESSION[p2h_SESSION_ID]['unlocked'], $_SESSION[p2h_SESSION_ID]['basket'], $_SESSION[p2h_SESSION_ID]['fdl']);
    header('Location: '.basename(__FILE__));   // A clean address: the login form must not post ?logout again
    exit;
}

if (is_string($_GET['share'] ?? null)) fe_share_page($_GET['share']);     // A share link: for everyone - no login (it exits)

if ($use_auth) {
    $logged= $_SESSION[p2h_SESSION_ID]['logged'] ?? null;
    if ($logged !== null && isset($auth_users[$logged])) {
        // Logged:
    } elseif (isset($_POST['p2h_usr'], $_POST['p2h_pwd']) && fe_login_tries() >= 10) {
        unset($_SESSION[p2h_SESSION_ID]['logged']);     // Too many failed logins from this address - also when the password is right
        fe_log('@Login blocked - too many failed logins', '', '', mb_substr((string)$_POST['p2h_usr'], 0, 64));
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
            fe_log('@Log in');
        } else {
            if (!isset($auth_users[$usr][0])) password_verify($pwd, '$2y$10$kDgQcpPzD8aMOLCS/fb74.P/19oWozxK99H1enPpvLdVOhR64u9Du');  // The same time
            fe_login_tries(true);
            fe_log('@Login failed', '', isset($auth_users[$usr][0]) ? lang('@wrong password') : lang('@unknown account'), mb_substr($usr, 0, 64));
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
    $fe_log_act= ($_POST['fe_unlock'] ?? '') === '1' ? '@Remove restrictions' : '@Restore restrictions';  $fe_log_obj= '';
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
$currPath= p2h_safe_path(is_string($s= $_POST['show'] ?? $_GET['show'] ?? '') ? $s : '', $root_path);  // Never outside $root_path - (?show[]=x was a fatal error)
if (fe_is_trash($currPath)) $currPath= $root_path;                             // The trash is only seen in its card (program menu: Trash)

##### RIGHTS: write in the account's write folders - read (download, content) there and in the shared folder ($fe_exchange_path)
$exch_path=      $is_logged ? fe_exchange_init($fe_exchange_path) : false;
if ($is_logged && mt_rand(1, 50) === 1) fe_trash_trim();       // Now and then: away with what is older than $fe_trash_days
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

if ($is_logged && isset($_GET['thumb'])) {             // A small picture of an image for the gallery - where the preview may show it
    session_write_close();                              // Many at the same time: never one after the other
    $file= realpath((string)$_GET['thumb']);
    $kind= ($file !== false && is_file($file) && fe_in_dir(p2h_norm_path($file), $root_path)) ? fe_preview_kind(basename($file), fe_can_read($file), fe_media_ok($file)) : '';
    while (ob_get_level()) ob_end_clean();
    if ($kind !== 'img') { http_response_code(404);  exit; }
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=86400');
    $ext= strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (($t= fe_thumb($file, $ext)) !== null) {
        header('Content-Type: image/jpeg');
        header('Content-Length: '.filesize($t));
        readfile($t);
        exit;
    }
    if (filesize($file) <= 3 * 1024 * 1024) {           // No GD, an SVG, an icon ...: the image itself (not a big one)
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");
        fe_send_inline($file, FE_PV_MEDIA[$ext][1] ?? 'application/octet-stream');
    }
    http_response_code(404);
    exit;
}

if ($is_logged && isset($_GET['edt'])) {               // The text of a file for the editor (file menu: Edit)
    $file= realpath((string)$_GET['edt']);
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: private, no-store');
    $why= $file === false ? lang('@File not found') : fe_edit_refused(p2h_norm_path($file));
    if ($why !== '') exit(json_encode(['err' => $why], JSON_UNESCAPED_UNICODE));
    $raw= (string)file_get_contents($file);
    if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) $raw= substr($raw, 3);        // The byte order mark: kept when it is saved
    if (!mb_check_encoding($raw, 'UTF-8')) exit(json_encode(['err' => lang('@The file is not UTF-8 text - it can not be edited here')], JSON_UNESCAPED_UNICODE));
    exit(json_encode(['t' => $raw, 'm' => sha1_file($file), 'n' => basename($file)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

if ($is_logged && fe_can('w')) fe_conf_init();          // A taken name: as chosen (copy, move, unpack, upload)
$fe_check= ($_POST['fe_check'] ?? '') === '1';           // "Stop and ask": first only the taken names (fe_check_answer) - then the real request with the answers

if ($is_logged && isset($_POST['fe_upcheck'])) {      // "Stop and ask" before an upload: the taken names of the chosen files - nothing is sent or made
    $c= [];
    $upDest= fe_resolve_dest($_POST['fe_dest'] ?? '');       // A new destination folder: nothing is taken there
    if (fe_can('w') && csrf_verify() && $upDest !== false && fe_can_write($upDest)) {
        $files= json_decode((string)($_POST['fe_files'] ?? ''), true);       // [[name, relative path, size, time], ...] - one field: max_input_vars cut a list of fields (250 files)
        foreach (array_slice(is_array($files) ? $files : [], 0, 20000) as $f) {
            if (!is_array($f) || !is_string($f[0] ?? null)) continue;
            $name= fe_clean_name($f[0]);
            if ($name === '' || substr(trim($f[0]), 0, 1) === '.' || !fe_name_allowed($name)) continue;      // Refused anyway (a hidden file)
            $dir= fe_rel_dir($upDest, is_string($f[1] ?? null) ? $f[1] : '', false);
            if ($dir === false || (!file_exists($dir.'/'.$name) && !is_link($dir.'/'.$name))) continue;
            $c[]= fe_conflict(ltrim(substr($dir.'/'.$name, strlen($upDest)), '/'), '', $dir.'/'.$name, (int)($f[2] ?? 0), (int)($f[3] ?? 0) ?: null);
        }
    }
    fe_check_answer($c);
}

if ($is_logged && isset($_FILES['fe_upload'])) {     // Upload files from the user's PC to the current folder (form, or batches from fe-upload.js)
    $ajax= ($_POST['fe_ajax'] ?? '') === '1';   // fe-upload.js wants a JSON answer per batch
    $done= [];  $fail= [];  $kept= [];  $error= '';     // done: [index, saved path]   fail: [index, name, reason]   kept: [index, the file there] (skipped: the name was taken)
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
            if     ($err !== UPLOAD_ERR_OK)          $why= lang('@upload error').' '.$err.($err <= 2 ? ' - '.lang('@the file is too large') : '');
            elseif (!fe_name_allowed($name))         $why= lang('@this file type is not allowed');
            elseif ($size > $fe_max_upload && !$fe_unlocked) $why= lang('@the file is too large');
            elseif ($used + $size - ($repl= $quota_up ? fe_up_repl($upDest, is_string($rels[$i] ?? null) ? $rels[$i] : '', $name) : 0) > $quota)
                                                     $why= lang('@the shared folder is full');     // A file it overwrites: the difference
            elseif (($dir= fe_rel_dir($upDest, is_string($rels[$i] ?? null) ? $rels[$i] : '')) === false)       // Folders made only for an accepted file
                                                     $why= lang('@the folder name is not allowed');
            else {
                $target= $dir.'/'.$name;    $how= 'new';
                if (file_exists($target) || is_link($target)) {     // The name is taken: as chosen - a folder or a link of that name: "name (2)"
                    $how= is_file($target) && !is_link($target) && !fe_made($target) ? fe_choice(ltrim(substr($target, strlen($upDest)), '/')) : 'rename';     // Twice in this upload: "name (2)"
                    if ($how === 'skip') { $kept[]= [$i, fe_show_path($target)];  fe_kept($target);  continue; }
                    $g0= count($GLOBALS['fe_gone'] ?? []);
                    if ($how === 'overwrite' && !fe_old_away($target)) { $fail[]= [$i, $name, lang('@the file there can not be replaced')];  continue; }
                    if ($how === 'overwrite' && count($GLOBALS['fe_gone'] ?? []) > $g0) $how= 'gone';     // Too big for the trash: deleted for good
                    if ($how === 'rename') $target= fe_unique($target);
                }
                if (move_uploaded_file($up['tmp_name'][$i], $target)) {
                    @chmod($target, 0644);  $used+= $size - ($how === 'overwrite' || $how === 'gone' ? $repl : 0);  fe_made($target, true);
                    $done[]= [$i, fe_show_path($target), $how === 'overwrite' ? 1 : ($how === 'gone' ? 2 : 0)];  continue;     // 1: the old file was replaced (into the trash) - 2: deleted for good
                }
                $why= lang('@could not be saved');
            }
            $fail[]= [$i, $name ?: (string)$orig, $why];
        }
    }
    fe_log('@Upload', is_string($upDest ?? null) ? fe_show_path($upDest) : '',
           ($error > '' ? '✗ '.$error : count($done).' '.lang('@files').($done ? ': '.implode(', ', array_map(fn($d) => basename($d[1]), $done)) : '')).
           ($fail ? ' - '.lang('@Not uploaded').': '.implode(', ', array_map(fn($f) => $f[1].' ('.$f[2].')', $fail)) : '').
           (!empty($fe_over) || !empty($fe_gone) ? ' - '.lang('@Overwritten').': '.implode(', ', array_merge($fe_over ?? [], $fe_gone ?? [])) : '').
           ($kept ? ' - '.lang('@Skipped - the name was taken, the one there is kept').': '.implode(', ', array_map(fn($k) => basename($k[1]), $kept)) : ''));
    if (is_string($upDest ?? null)) fe_size_forget($upDest);       // (the JSON answer has no fe_msg)
    if ($ajax) {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $error, 'done' => $done, 'fail' => $fail, 'kept' => $kept, 'trash' => fe_trash_dir() !== false ? 1 : 0,
                          'show' => basename(__FILE__).'?show='.urlencode(is_string($upDest ?? null) ? $upDest : $currPath)], JSON_INVALID_UTF8_SUBSTITUTE);   // The destination
        exit;
    }
    $failTxt= implode(', ', array_map(fn($f) => p2h_enc($f[1]).' ('.p2h_enc($f[2]).')', $fail));
    if ($error)    fe_msg(p2h_enc($error), 'error');
    elseif ($done) fe_msg(p2h_enc(lang('@Uploaded')).': <b>'.implode(', ', array_map(fn($d) => p2h_enc($d[1]), $done)).'</b>'.
                          ($fail ? '<br>'.p2h_enc(lang('@Not uploaded')).': '.$failTxt : '').fe_renamed_note(), $fail ? 'error' : 'ok');
    elseif ($kept && !$fail) fe_msg(p2h_enc(lang('@Not uploaded')).':'.fe_renamed_note());
    else           fe_msg(p2h_enc(lang('@Not uploaded')).': '.($fail ? $failTxt : p2h_enc(lang('@no file selected'))).fe_renamed_note(), 'error');
    fe_redirect($currPath);
}

if ($is_logged && isset($_POST['fe_delete'])) {      // Delete a file in the current folder
    $fe_log_act= '@Delete file';
    $name= fe_given_name($_POST['fe_delete']);
    $file= realpath($currPath.'/'.$name);
    if (!fe_can('w') || !$can_write_here) fe_msg(p2h_enc(lang('@You may not write in this folder')), 'error');
    elseif (!csrf_verify()) fe_msg(p2h_enc(lang('@The form has expired - reload the page and try again')), 'error');
    elseif ($file === false || !is_file($file) || p2h_norm_path(dirname(p2h_norm_path($file))) !== $currPath || !fe_name_allowed($name))
                            fe_msg(p2h_enc(lang('@File not found')).': '.p2h_enc($name), 'error');
    elseif (($inTrash= fe_trash_put($file, $tn, $ts)) && $tn)
                            fe_msg(p2h_enc(lang(empty($fe_trash_big) ? '@Moved to the trash' : '@Deleted - too big for the trash')).': <b>'.p2h_enc($name).'</b>'.fe_trash_link());
    elseif (!$inTrash && !fe_program_file($file) && @unlink($file)) fe_msg(p2h_enc(lang('@Deleted')).': <b>'.p2h_enc($name).'</b>');
    else                    fe_msg(p2h_enc(lang('@Could not delete')).': '.p2h_enc($name), 'error');
    fe_redirect($currPath);
}
if ($is_logged && isset($_POST['fe_edit'])) {      // Save the text of the editor (file menu: Edit) - the old version into the trash - a JSON answer
    $name= fe_given_name($_POST['fe_edit']);
    $file= $name === '' ? false : realpath($currPath.'/'.$name);
    $text= str_replace(["\r\n", "\r"], "\n", is_string($_POST['fe_text'] ?? null) ? $_POST['fe_text'] : '');
    $res=  [];
    clearstatcache();
    if (!csrf_verify()) $res['err']= lang('@The form has expired - reload the page and try again');
    elseif ($file === false || p2h_norm_path(dirname(p2h_norm_path($file))) !== $currPath) $res['err']= lang('@File not found').': '.$name;
    elseif (($why= fe_edit_refused(p2h_norm_path($file))) !== '') $res['err']= $why;
    elseif (!mb_check_encoding($text, 'UTF-8') || strlen($text) > FE_EDIT_MAX) $res['err']= lang('@The file is too big to be edited here (max. 2 MB)');
    elseif (($_POST['fe_force'] ?? '') !== '1' && !hash_equals((string)sha1_file($file), (string)($_POST['fe_ver'] ?? '')))     // The text as it was opened (a checksum: also within the same second)
        $res= ['err' => lang('@The file was changed by someone else after you opened it'), 'conflict' => 1];
    else {
        $old= (string)file_get_contents($file);
        if (strpos($old, "\r\n") !== false) $text= str_replace("\n", "\r\n", $text);     // The line ends of the file are kept - and its byte order mark
        $new= (strncmp($old, "\xEF\xBB\xBF", 3) === 0 ? "\xEF\xBB\xBF" : '').$text;
        if ($new === $old) $res= ['ok' => 1, 'm' => sha1($old), 'same' => 1];
        elseif ($quota_here && strlen($new) - strlen($old) > $fe_exchange_quota - fe_dir_size($exch_path)) $res['err']= lang('@the shared folder is full');
        else {
            $tmp=  $currPath.'/.fe-edit-'.bin2hex(random_bytes(4)).'.tmp';     // Hidden until it is renamed
            $perm= fileperms($file) & 0777;
            if (@file_put_contents($tmp, $new, LOCK_EX) !== strlen($new)) { @unlink($tmp);  $res['err']= lang('@It could not be saved'); }
            else {
                @chmod($tmp, $perm);
                if (!fe_old_away($file))           { @unlink($tmp);  $res['err']= lang('@It could not be saved'); }
                elseif (!@rename($tmp, $file))     { @unlink($tmp);  $res['err']= lang('@It could not be saved').' - '.lang('@the old version is in the trash'); }
                else {
                    clearstatcache();
                    $res= ['ok' => 1, 'm' => sha1_file($file)];
                    fe_size_forget($currPath);
                    fe_log('@Edit file', fe_show_path(p2h_norm_path($file)), html_entity_decode(p2h_nice_filesize(strlen($old)).' → '.p2h_nice_filesize(strlen($new)), ENT_QUOTES | ENT_HTML5, 'UTF-8').
                           (fe_trash_dir() !== false ? ' - '.lang('@the old version is in the trash') : ''));
                }
            }
        }
    }
    if (isset($res['err'])) fe_log('@Edit file', $file ? fe_show_path(p2h_norm_path($file)) : $name, '✗ '.$res['err']);
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    exit(json_encode($res, JSON_UNESCAPED_UNICODE));
}
if ($is_logged && isset($_POST['fe_dupdel'])) {    // Duplicates (card): one file into the trash - it may be in a folder below this one
    $fe_log_act= '@Delete file';
    $file= realpath(is_string($_POST['fe_dupdel']) ? $_POST['fe_dupdel'] : '');
    $fe_log_obj= $file === false ? '' : fe_show_path(p2h_norm_path($file));
    if (!fe_can('w') || $file === false || !is_file($file) || is_link($file) || !fe_in_dir(p2h_norm_path($file), $currPath) || !fe_can_write(p2h_norm_path(dirname($file)))
        || !fe_name_allowed(basename($file)) || fe_program_file($file) || fe_is_trash($file))
                            fe_msg(p2h_enc(lang('@This file can not be changed here')), 'error');
    elseif (!csrf_verify()) fe_msg(p2h_enc(lang('@The form has expired - reload the page and try again')), 'error');
    elseif (($inTrash= fe_trash_put($file, $tn, $ts)) && $tn)
                            fe_msg(p2h_enc(lang(empty($fe_trash_big) ? '@Moved to the trash' : '@Deleted - too big for the trash')).': <b>'.p2h_enc(basename($file)).'</b>'.fe_trash_link());
    elseif (!$inTrash && @unlink($file)) fe_msg(p2h_enc(lang('@Deleted')).': <b>'.p2h_enc(basename($file)).'</b>');
    else                    fe_msg(p2h_enc(lang('@Could not delete')).': '.p2h_enc(basename($file)), 'error');
    header('Location: '.basename(__FILE__).'?show='.urlencode($currPath).'&dupl=1', true, 303);
    exit;
}
if ($is_logged && isset($_POST['fe_act'])) {      // File / folder menu: rename / move, copy, ZIP, unpack, new folder - in the current folder
    $act=  (string)$_POST['fe_act'];
    if ($fe_check) {                                  // "Stop and ask": only Unpack here asks - the rest has a name of its own
        $f= ($_POST['fe_name'] ?? '') === '' ? false : realpath($currPath.'/'.fe_given_name($_POST['fe_name']));  $cn= 0;  $cs= 0;
        fe_check_answer($act === 'unzip' && fe_can('w') && $can_write_here && csrf_verify() && $f !== false && is_file($f) && !fe_is_trash($f)
                        && p2h_norm_path(dirname(p2h_norm_path($f))) === $currPath ? fe_unzip($f, $currPath, PHP_INT_MAX, $cn, $cs, true) : []);
    }
    $fe_log_act= ['move' => '@Rename / move', 'copy' => '@Copy', 'zip' => '@Create ZIP', 'unzip' => '@Unpack here', 'mkdir' => '@New folder',
                  'sync' => '@Synchronize'][$act] ?? '@It could not be done';
    $name= fe_given_name($_POST['fe_name'] ?? '');
    $to=   trim(str_replace('\\', '/', (string)($_POST['fe_to'] ?? '')), '/ ');
    $file= $name === '' ? false : realpath($currPath.'/'.$name);
    if ($file !== false && fe_is_trash($file)) $file= false;      // Never the trash itself
    if ($file !== false && $act === 'move' && (is_dir($file) ? fe_holds_program($file) : fe_program_file($file))) $file= false;    // Nor the folder or a file of the program (renamed: the program is gone)
    $isDir= $file !== false && is_dir($file);
    $err=  '';  $target= '';  $n= 0;  $skip= 0;
    $room= $quota_here ? $fe_exchange_quota - fe_dir_size($exch_path) : PHP_INT_MAX;
    if (!fe_can('w') || (!$can_write_here && $act !== 'sync')) $err= lang('@You may not write in this folder');
    elseif (!csrf_verify()) $err= lang('@The form has expired - reload the page and try again');
    elseif ($act === 'sync') {              // Synchronize a folder of this folder to a destination folder (it writes only there)
        $dest= $to === '' ? '' : (string)fe_resolve_dest($to);     // As the other destinations: "sub", "root/sub" (as shown) or a whole path - never the trash
        if (!$isDir || p2h_norm_path(dirname(p2h_norm_path($file))) !== $currPath) $err= lang('@File not found').': '.$name;
        elseif ($dest === '')                                     $err= lang('@The destination folder is not found').': '.$to;
        elseif (!fe_can_write($dest))                             $err= lang('@You may not write in the destination folder').': '.$to;
        elseif (fe_in_dir($dest, p2h_norm_path($file)) || $dest === p2h_norm_path($file)) $err= lang('@Not an allowed name or folder').': '.$to;
        elseif ($exch_path && fe_in_dir($dest, $exch_path) && !$fe_unlocked && fe_sync_need(p2h_norm_path($file), $dest) > $fe_exchange_quota - fe_dir_size($exch_path))   // What is copied (a second sync: little) - was the whole folder
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
        $target= fe_zip_name($currPath, $isDir ? $name : preg_replace('/\.[^.]*$/', '', $name));
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
    if ($err === '' && ($act === 'move' || $act === 'copy'))      // The destination decides: "sub/name" may be in the shared folder - a move inside it adds nothing
        $room= $exch_path && !$fe_unlocked && fe_in_dir($dirReal, $exch_path) && !($act === 'move' && fe_in_dir(p2h_norm_path($file), $exch_path))
             ? $fe_exchange_quota - fe_dir_size($exch_path) : PHP_INT_MAX;
    if ($err === '' && in_array($act, ['copy', 'zip', 'move'], true) && $room < PHP_INT_MAX && ($isDir ? fe_dir_size($file) : filesize($file)) > $room)
        $err= lang('@the shared folder is full');
    if ($err === '') {
        if ($act === 'sync')      { fe_sync_tree($file, $target, $n, $skip);  $ok= true; }
        elseif ($act === 'mkdir') $ok= @mkdir($target, 0755);
        elseif ($act === 'move')  $ok= @rename($file, $target);
        elseif ($act === 'copy')  { if ($isDir) { fe_copy_tree($file, $target, $n, $skip);  $ok= is_dir($target); } else $ok= @copy($file, $target); }
        elseif ($act === 'unzip') $ok= fe_unzip($file, $currPath, $room, $n, $skip) && ($n > 0 || !empty($fe_kept) || !empty($fe_over));     // All skipped as chosen: told, not an error
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
                                         ($skip ? ' - '.$skip.' '.p2h_enc(lang('@skipped (not allowed names, too large, or more than 20000)')) : '').fe_renamed_note(), $skip ? 'error' : 'ok');
        else                      fe_msg(p2h_enc(lang(['mkdir'=>'@Folder created', 'move'=>'@Renamed / moved to', 'copy'=>'@Copied to', 'zip'=>'@ZIP file created'][$act])).
                                         ': <b>'.p2h_enc($rel).'</b>'.$skipTxt, $skip ? 'error' : 'ok');
    } else fe_msg(p2h_enc($err), 'error');
    fe_redirect($currPath);
}

##### SHARE LINKS (file menu: Share link - program menu: Shared links): make one (a JSON answer) - delete one
if ($is_logged && isset($_POST['fe_share'])) {
    $op=   (string)$_POST['fe_share'];
    $ajax= ($_POST['fe_ajax'] ?? '') === '1';
    $err=  '';  $res= [];
    if (!fe_can('w') || (int)$fe_share_days <= 0) $err= lang('@Your account may not make share links');
    elseif (!csrf_verify())                       $err= lang('@The form has expired - reload the page and try again');
    elseif ($op === 'create') {
        $p=    realpath(is_string($_POST['fe_path'] ?? null) ? $_POST['fe_path'] : '');
        $days= (int)($_POST['fe_days'] ?? 7);
        $pw=   is_string($_POST['fe_spw'] ?? null) ? $_POST['fe_spw'] : '';
        if ($fe_unlocked) $err= lang('@Restore the restrictions first - a share link follows them');
        elseif ($p === false || !fe_share_may_make($p= p2h_norm_path($p))) $err= lang('@You may not share this');
        elseif ($days < 0 || $days > (int)$fe_share_days || ($days === 0 && !fe_is_admin())) $err= lang('@Not an allowed end date');
        else {
            $tok=  bin2hex(random_bytes(12));
            $list= fe_shares_load();
            $list[$tok]= ['p' => $p, 'd' => is_dir($p) ? 1 : 0, 'u' => $_SESSION[p2h_SESSION_ID]['logged'], 'c' => time(), 'x' => $days ? time() + $days * 86400 : 0,
                          'h' => $pw === '' ? '' : password_hash($pw, PASSWORD_DEFAULT), 'n' => 0];
            if (!fe_shares_save($list)) $err= lang('@It could not be saved').': '.basename($fe_shares_file);
            else {
                $res= ['url' => fe_share_url($tok), 'x' => $days ? date('Y-m-d H:i', $list[$tok]['x']) : ''];
                fe_log('@Share link', fe_show_path($p), ($days ? lang('@Works until').' '.date('Y-m-d H:i', $list[$tok]['x']) : lang('@No end date')).($pw !== '' ? ' - '.lang('@with password') : ''));
            }
        }
    } elseif (strpos($op, 'delete:') === 0) {
        $tok=  substr($op, 7);
        $list= fe_shares_load();
        if (!isset($list[$tok]) || !fe_share_may($list[$tok])) $err= lang('@The link is not found');
        else {
            $was= fe_show_path((string)$list[$tok]['p']);
            unset($list[$tok]);
            if (!fe_shares_save($list)) $err= lang('@It could not be saved').': '.basename($fe_shares_file);
            else fe_log('@Delete share link', $was);
        }
    } else $err= lang('@It could not be done');
    if ($err !== '') fe_log($op === 'create' ? '@Share link' : '@Delete share link', '', '✗ '.$err);
    if ($ajax) {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        exit(json_encode($err !== '' ? ['err' => $err] : $res + ['ok' => 1], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
    $_SESSION[p2h_SESSION_ID]['message']= p2h_enc($err !== '' ? $err : lang('@The share link is deleted'));
    $_SESSION[p2h_SESSION_ID]['status']=  $err !== '' ? 'error' : 'ok';
    header('Location: '.basename(__FILE__).'?show='.urlencode($currPath).'&shares=1', true, 303);
    exit;
}

##### TRANSLATIONS (program menu): the changed texts of one language - administrators only
if ($is_logged && isset($_POST['p2h_trans_lang'])) {
    $fe_log_act= '@Translations';  $fe_log_obj= is_string($_POST['p2h_trans_lang']) ? $_POST['p2h_trans_lang'] : '';
    if (!fe_is_admin()) fe_msg(p2h_enc(lang('@Only an administrator can change the translations')), 'error');
    else { [$ok, $m]= p2h_trans_handle();  fe_msg(p2h_enc($m), $ok ? 'ok' : 'error'); }
    header('Location: '.basename(__FILE__).'?show='.urlencode($currPath).'&trans=1&tl='.urlencode(preg_replace('/[^A-Za-z-]/', '', $fe_log_obj)), true, 303);
    exit;
}

##### ACCOUNTS: the users (admins) - and the own password (everybody but a read only account)
if ($is_logged && (isset($_POST['fe_users']) || isset($_POST['fe_pw']))) {
    $me= $_SESSION[p2h_SESSION_ID]['logged'] ?? '';
    $fe_log_act= isset($_POST['fe_users']) ? '@Users' : '@Change my password';  $fe_log_obj= '';
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
    if ($op === 'copy' || $op === 'move') $fe_log_act= $op === 'copy' ? '@Basket: copy' : '@Basket: move';
    $paths= array_filter((array)($_POST['fe_bp'] ?? []), 'is_string');
    $n= 0;  $skip= 0;
    if ($fe_check) {                                  // "Stop and ask": the taken names in the destination (a new destination folder: none)
        $d= fe_resolve_dest($_POST['fe_dest'] ?? '');
        fe_check_answer(($op === 'copy' || $op === 'move') && csrf_verify() && $d !== false && fe_can('w') && fe_can_write($d) ? fe_conflicts_items($basket, $d, $op) : []);
    }
    if (!csrf_verify()) fe_msg(p2h_enc(lang('@The form has expired - reload the page and try again')), 'error');
    elseif ($op === 'add') {
        foreach ($paths as $pth) {
            $r= realpath($pth);  $r= $r === false ? '' : p2h_norm_path($r);
            if ($r !== '' && $r !== $root_path && fe_in_dir($r, $root_path) && !fe_is_trash($r) && !in_array($r, $basket, true)) { $basket[]= $r;  $n++; }
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
                   ($skip ? ' - '.$skip.' '.p2h_enc(lang('@skipped (not allowed, no right to move, too large, or already here)')) : '').fe_renamed_note(), $skip ? 'error' : 'ok');
        }
    }
    $_SESSION[p2h_SESSION_ID]['basket']= $basket;
    fe_redirect($currPath);
}

##### FILE FEATURES below the table: copy, move, ZIP, delete, download - the checked rows of the current folder
if ($is_logged && isset($_POST['fe_sel'])) {
    $op=    (string)$_POST['fe_sel'];
    $fe_log_act= ['copy' => '@Checked rows: copy', 'move' => '@Checked rows: move', 'zip' => '@Checked rows: create ZIP',
                  'delete' => '@Checked rows: delete', 'download' => '@Checked rows: download'][$op] ?? '';
    $items= [];
    foreach (array_filter((array)($_POST['fe_bp'] ?? []), 'is_string') as $pth) {
        $r= realpath($pth);  $r= $r === false ? '' : p2h_norm_path($r);
        if ($r !== '' && p2h_norm_path(dirname($r)) === $currPath && !fe_is_trash($r) && !in_array($r, $items, true)) $items[]= $r;      // Only rows of this folder - never the trash
    }
    if ($fe_check) {
        $d= fe_resolve_dest($_POST['fe_dest'] ?? '');
        fe_check_answer(($op === 'copy' || $op === 'move') && csrf_verify() && $d !== false && fe_can('w') && fe_can_write($d) ? fe_conflicts_items($items, $d, $op) : []);
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
            fe_msg(p2h_enc(lang($op === 'move' ? '@Moved to' : '@Copied to')).' <b>'.p2h_enc($destRel).'</b>: '.$n.' '.p2h_enc(lang($op === 'move' ? '@files / folders' : '@files')).$skipTxt().fe_renamed_note(), $skip ? 'error' : 'ok');
        } elseif (!class_exists('ZipArchive')) fe_msg(p2h_enc(lang('@It could not be done')), 'error');
        else {
            $one= count($items) === 1 ? (is_dir($items[0]) ? basename($items[0]) : pathinfo($items[0], PATHINFO_FILENAME)) : '';    // A folder "my.photos": not "my"
            $target= fe_zip_name($dest, $one > '' ? $one : basename($currPath).' - '.lang('@selection'));
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
            $inTrash= false;
            foreach ($items as $it)
                if (fe_holds_program($it)) $skip++;                                    // The folder of the program: never deleted
                elseif (fe_trash_put($it, $n, $skip)) $inTrash= true;  else fe_delete_tree($it, $n, $skip);
            fe_msg(p2h_enc(lang(!$inTrash ? '@Deleted' : (empty($fe_trash_big) ? '@Moved to the trash' : '@Deleted - too big for the trash'))).': '.$n.' '.p2h_enc(lang('@files / folders')).
                   ($inTrash ? fe_trash_link() : '').
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
if ($is_logged && isset($_POST['fe_trash'])) {     // The trash (program menu): restore - delete for good - empty
    [$op, $id]= array_pad(explode(':', (string)$_POST['fe_trash'], 2), 2, '');
    $list= array_filter(fe_trash_list(), 'fe_trash_may');
    $fe_log_act= ['restore' => '@Restore from the trash', 'drop' => '@Delete for good', 'empty' => '@Empty the trash'][$op] ?? '';
    $fe_log_obj= isset($list[$id]) ? fe_show_path($list[$id]['f'].'/'.$list[$id]['n']) : '';
    if (!csrf_verify())        fe_msg(p2h_enc(lang('@The form has expired - reload the page and try again')), 'error');
    elseif ($op === 'empty') { foreach ($list as $i => $m) fe_trash_drop($i);
                               fe_msg(p2h_enc(lang('@The trash is emptied')).': '.count($list).' '.p2h_enc(lang('@files / folders'))); }
    elseif (!isset($list[$id])) fe_msg(p2h_enc(lang('@Not found in the trash')), 'error');
    elseif ($op === 'drop')   { fe_trash_drop($id);  fe_msg(p2h_enc(lang('@Deleted for good')).': <b>'.p2h_enc($list[$id]['n']).'</b>'); }
    elseif ($op === 'restore') {
        [$ok, $res]= fe_trash_restore($id, $list[$id]);
        if ($ok) fe_msg(p2h_enc(lang('@Restored')).': <b>'.p2h_enc(fe_show_path($res)).'</b>');
        else     fe_msg(p2h_enc($res), 'error');
    }
    header('Location: '.basename(__FILE__).'?show='.urlencode($currPath).'&trash=1', true, 303);
    exit;
}

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
htm_Page_(titl:'@Log in to Folder-explorer', hint:lang(''), info:lang(''), inis:$p2h_Style, 
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
    htm_Card_end(labl:'@Log in', icon:'', hint:'@Login with the given data', name:'butt', form:'signup', subm:true, attr:'', akey:'', kind:'navi', simu:false);
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
    htm_Caption(labl:'Folder-explorer',icon:'',hint:'@The advanced file explorer with focus on folders and the space the files take up.',
                algn:'center',styl:'color:'.$gbl_TitleColr.'; font-weight:600; font-size: 18px; padding: 4px;' );
    htm_nl(1);
    htm_Caption(labl:lang('@Built with php2html').' v'.P2H_VERSION,icon:'',hint:'@The shortcut to structured and compact code<br>Created by EV-soft.',
                algn:'center',styl:'color: gray;; font-weight:400; font-size: 12px;');
    p2h_show_message();
    htm_nl(1);
    htm_Card_(capt:'@Folder tree',icon:'fas fa-sitemap',hint: '',form:'',acti:'',clas:'cardW800',vhgh:'360px',    // The card body scrolls: the size grip can show the whole tree
                wdth:'800px',styl:'background-color: white; ',attr:'text-align: left; padding:8px; background-color: white;',show:true, head:'');
        // Card tool: show the used space of every folder in the tree (on/off for this session)
        if (isset($_GET['treesize'])) $_SESSION[p2h_SESSION_ID]['treesize']= ($_GET['treesize'] === '1');
        $fe_treesize= $calc_folder || !empty($_SESSION[p2h_SESSION_ID]['treesize']);     // Analyse mode: always - Quick: when it is switched on here
        $tsHint= $calc_folder ? lang('@Analyse mode: the used space of every folder is shown - click to go back to Quick')
               : ($fe_treesize ? lang('@ANALYSE: Click to hide the used space of the folders')
                               : lang('@ANALYSE: Click to show the used space of every folder (can take time in big folders)'));
        echo '<abbr class="hint p2hCtrlHint" id="feSizeTool">'.
                '<a href="'.basename(__FILE__).'?show='.urlencode($currPath).'&amp;treesize='.($fe_treesize && !$calc_folder ? '0' : ($calc_folder ? (empty($_SESSION[p2h_SESSION_ID]['treesize']) ? '0' : '1') : '1')).'" '.
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
                  form:'',acti:'',clas:'cardW800',wdth:'800px',styl:'background-color: white;',attr:'text-align: left; padding:8px; background-color: white;',show:true, head:'background-color: white;');
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
    if (isset($_GET['trash']) && fe_can('w') && fe_trash_dir() !== false) {     // The trash
        fe_trash_trim();
        $list= array_filter(fe_trash_list(), 'fe_trash_may');
        $act=  basename(__FILE__).'?show='.urlencode($currPath);
        htm_Card_(capt:'@Trash',icon:'fas fa-trash-restore',hint:'@Deleted files and folders - restore them to their folder, or delete them for good',
                  form:'',acti:'',clas:'cardW800',wdth:'800px',styl:'background-color: white;',attr:'text-align: left; padding:8px; background-color: white;',show:true, head:'background-color: white;');
            echo '<div class="feUsers"><form id="feTrashForm" method="post" action="'.p2h_enc($act.'&trash=1').'">'.csrf_field(true).'</form>';
            if (!$list) echo '<p><i>'.p2h_enc(lang('@The trash is empty')).'</i></p>';
            else {
                echo '<table class="feUsrTbl feTrashTbl"><tr><th>'.p2h_enc(lang('@Name')).'</th><th>'.p2h_enc(lang('@From the folder')).'</th><th>'.p2h_enc(lang('@Size')).'</th>'.
                     '<th>'.p2h_enc(lang('@Deleted')).'</th><th>'.p2h_enc(lang('@By')).'</th><th></th></tr>';
                foreach ($list as $id => $m)
                    echo '<tr><td><i class="far fa-'.(empty($m['d']) ? 'file' : 'folder').'"></i> '.p2h_enc($m['n']).'</td><td>'.p2h_enc(fe_show_path($m['f'])).'</td>'.
                         '<td style="white-space:nowrap;">'.p2h_nice_filesize((int)($m['s'] ?? 0)).'</td><td style="white-space:nowrap;">'.date('Y-m-d H:i', (int)$m['t']).'</td>'.
                         '<td>'.p2h_enc((string)($m['u'] ?? '')).'</td><td style="white-space:nowrap;">'.
                         [$mayRe= fe_can_write((string)$m['f']),
                          fe_hint('<button type="submit" form="feTrashForm" name="fe_trash" value="restore:'.p2h_enc($id).'" class="txtbutt feXfBtn"'.($mayRe ? '' : ' disabled').' aria-label="'.p2h_enc(lang('@Restore')).'">'.
                                  '<i class="fas fa-undo"></i></button>', p2h_enc(lang($mayRe ? '@Restore - back to its folder (a name taken there: name (2))'
                                                                                         : '@You may not write in its folder - an administrator can remove the restrictions first')))][1].' '.
                         fe_hint('<button type="submit" form="feTrashForm" name="fe_trash" value="drop:'.p2h_enc($id).'" class="txtbutt feXfBtn feUsrDel" aria-label="'.p2h_enc(lang('@Delete for good')).'" '.
                                 'onclick="return feConfirmSubmit(this, '.p2h_enc(p2h_js(lang('@Delete for good').': '.$m['n'].' ?')).')"><i class="fas fa-times"></i></button>', p2h_enc(lang('@Delete for good'))).
                         '</td></tr>';
                echo '</table>';
            }
            echo '<small>'.p2h_enc(lang('@Deleted for good after').' '.(int)$fe_trash_days.' '.lang('@days').' - '.lang('@and the oldest first, when the trash holds more than')).' '.
                 p2h_nice_filesize($fe_trash_max).'</small> &nbsp; '.
                 ($list ? '<button type="submit" form="feTrashForm" name="fe_trash" value="empty:" class="txtbutt feXfBtn feUsrDel" '.
                          'onclick="return feConfirmSubmit(this, '.p2h_enc(p2h_js(lang('@Empty the trash').' ('.count($list).') ?')).')"><i class="fas fa-trash-alt"></i> '.p2h_enc(lang('@Empty the trash')).'</button> ' : '').
                 '<a href="'.p2h_enc($act).'" class="txtbutt feXfBtn"><i class="fas fa-times"></i> '.p2h_enc(lang('@Close')).'</a></div>';
        htm_Card_end(labl:'', icon:'', hint:'', name:'', form:'', subm:false, attr:'', akey:'', kind:'save', simu:false);
        htm_nl(1);
    }
    if (isset($_GET['trans']) && fe_is_admin()) {       // The translations - the texts of this program and its library are searched for the missing ones
        htm_TransEditor(capt:'@Translations', icon:'fas fa-language', hint:'@The texts of the program in other languages - the missing first. Saved in _trans.sys.json',
                        scan:[__FILE__, __DIR__.'/php2html.lib.php', __DIR__.'/p2h_audit.inc.php', __DIR__.'/p2h_trans.inc.php'],
                        acti:basename(__FILE__).'?show='.urlencode($currPath).'&trans=1', prog:'Folder-explorer, a web file manager (folders, upload, download, basket, trash)');
        htm_nl(1);
    }
    if (isset($_GET['log']) && fe_is_admin()) {         // The activity log
        htm_AuditLog(capt:'@Activity log', icon:'fas fa-clipboard-list', hint:'@Who did what, when and from where - the newest first', max:1000, vhgh:'500px');
        htm_nl(1);
    }
    if (isset($_GET['pw']) && fe_may_change_pw($me)) {
        htm_Card_(capt:'@Change my password',icon:'fas fa-key',hint:'',form:'',acti:'',clas:'cardW800',wdth:'800px',
                  styl:'background-color: white;',attr:'text-align: left; padding:8px; background-color: white;',show:true, head:'background-color: white;');
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

    if (isset($_GET['shares']) && fe_can('w') && (int)$fe_share_days > 0) {     // The share links: the own - administrators all
        $act=  basename(__FILE__).'?show='.urlencode($currPath);
        $list= array_filter(fe_shares_load(), fn($s) => is_array($s) && fe_share_may($s) && (empty($s['x']) || $s['x'] > time()));
        uasort($list, fn($a, $b) => (int)$b['c'] <=> (int)$a['c']);
        htm_Card_(capt:'@Shared links',icon:'fas fa-link',hint:'@Links to files and folders for people without an account - everyone with the link can download, until its end date',
                  form:'',acti:'',clas:'cardW800',wdth:'800px',styl:'background-color: white;',attr:'text-align: left; padding:8px; background-color: white;',show:true, head:'background-color: white;');
            echo '<div class="feUsers"><form id="feShareForm" method="post" action="'.p2h_enc($act).'">'.csrf_field(true).'</form>';
            if (!$list) echo '<p><i>'.p2h_enc(lang('@No share links - make one in the file menu: Share link')).'</i></p>';
            else {
                echo '<table class="feUsrTbl feShareTbl"><tr><th>'.p2h_enc(lang('@Name')).'</th><th>'.p2h_enc(lang('@Folder')).'</th>'.(fe_is_admin() ? '<th>'.p2h_enc(lang('@By')).'</th>' : '').
                     '<th>'.p2h_enc(lang('@Made')).'</th><th>'.p2h_enc(lang('@Works until')).'</th><th>'.p2h_enc(lang('@Downloads')).'</th><th></th></tr>';
                foreach ($list as $tok => $s) {
                    $gone= !file_exists((string)$s['p']);
                    echo '<tr'.($gone ? ' class="feShareGone"' : '').'><td><i class="far fa-'.(empty($s['d']) ? 'file' : 'folder').'"></i> '.p2h_enc(basename((string)$s['p'])).
                         ($s['h'] !== '' ? ' '.fe_hint('<i class="fas fa-key" aria-label="'.p2h_enc(lang('@with password')).'"></i>', p2h_enc(lang('@with password'))) : '').
                         ($gone ? ' <small>('.p2h_enc(lang('@not there any more')).')</small>' : '').'</td>'.
                         '<td>'.p2h_enc(fe_show_path(p2h_norm_path(dirname((string)$s['p'])))).'</td>'.(fe_is_admin() ? '<td>'.p2h_enc((string)$s['u']).'</td>' : '').
                         '<td style="white-space:nowrap;">'.date('Y-m-d H:i', (int)$s['c']).'</td>'.
                         '<td style="white-space:nowrap;">'.(empty($s['x']) ? p2h_enc(lang('@No end date')) : date('Y-m-d H:i', (int)$s['x'])).'</td>'.
                         '<td style="text-align:center;">'.(int)($s['n'] ?? 0).'</td><td style="white-space:nowrap;">'.
                         fe_hint('<button type="button" class="txtbutt feXfBtn" data-url="'.p2h_enc(fe_share_url($tok)).'" onclick="feCopyLink(this)" aria-label="'.p2h_enc(lang('@Copy the link')).'">'.
                                 '<i class="fas fa-copy"></i></button>', p2h_enc(lang('@Copy the link'))).' '.
                         fe_hint('<button type="submit" form="feShareForm" name="fe_share" value="delete:'.p2h_enc($tok).'" class="txtbutt feXfBtn feUsrDel" aria-label="'.p2h_enc(lang('@Delete the link')).'" '.
                                 'onclick="return feConfirmSubmit(this, '.p2h_enc(p2h_js(lang('@Delete the link - it stops working').': '.basename((string)$s['p']).' ?')).')"><i class="fas fa-times"></i></button>', p2h_enc(lang('@Delete the link'))).
                         '</td></tr>';
                }
                echo '</table>';
            }
            echo '<small>'.p2h_enc(lang('@A link stops working at its end date, when it is deleted - and when its maker may not download the file any more')).'</small> &nbsp; '.
                 '<a href="'.p2h_enc($act).'" class="txtbutt feXfBtn"><i class="fas fa-times"></i> '.p2h_enc(lang('@Close')).'</a></div>';
        htm_Card_end(labl:'', icon:'', hint:'', name:'', form:'', subm:false, attr:'', akey:'', kind:'save', simu:false);
        htm_nl(1);
    }
    if (is_string($_GET['find'] ?? null) && trim($_GET['find']) !== '') {     // Search in this folder and all below it: names - and the text of text files you may download
        $q=   mb_substr(trim($_GET['find']), 0, 100);
        $qc=  !empty($_GET['fc']);
        [$hits, $seen, $cut]= fe_find($currPath, $q, $qc);
        $act= basename(__FILE__).'?show='.urlencode($currPath);
        htm_Card_(capt:'@Search result',icon:'fas fa-search',hint:'@Files and folders in this folder and all folders below it - the name, and maybe the text of text files you may download',
                  form:'',acti:'',clas:'cardWmax',wdth:'800px',styl:'background-color: white;',attr:'text-align: left; padding:8px; background-color: white;',show:true, head:'background-color: white;');
            echo '<div class="feUsers"><form class="feFindForm" method="get" action="'.p2h_enc(basename(__FILE__)).'" role="search">'.
                 '<input type="hidden" name="show" value="'.p2h_enc($currPath).'">'.
                 '<input type="search" name="find" value="'.p2h_enc($q).'" maxlength="100" required aria-label="'.p2h_enc(lang('@Search for')).'"> '.
                 '<label><input type="checkbox" name="fc" value="1"'.($qc ? ' checked' : '').'> '.p2h_enc(lang('@also in the text of text files')).'</label> '.
                 '<button type="submit" class="txtbutt feXfBtn"><i class="fas fa-search"></i> '.p2h_enc(lang('@Search')).'</button> '.
                 '<a href="'.p2h_enc($act).'" class="txtbutt feXfBtn"><i class="fas fa-times"></i> '.p2h_enc(lang('@Close')).'</a></form>';
            echo '<p class="feFindSum" role="status"><b>'.count($hits).'</b> '.p2h_enc(lang(count($hits) === 1 ? '@hit for' : '@hits for')).' <b>'.p2h_enc($q).'</b> '.
                 p2h_enc(lang('@in')).' <b>'.p2h_enc(fe_show_path($currPath)).'</b> '.p2h_enc(lang('@and below')).' - '.$seen.' '.p2h_enc(lang('@files and folders searched')).
                 ($cut ? ' - <span class="feFindCut">'.p2h_enc(lang('@stopped early (500 hits, 100000 names or 20 seconds) - search in a folder further down')).'</span>' : '').'</p>';
            if ($hits) {
                echo '<div class="feFindWrap"><table class="feUsrTbl feFindTbl"><tr><th>'.p2h_enc(lang('@Name')).'</th><th>'.p2h_enc(lang('@Folder')).'</th><th>'.p2h_enc(lang('@Size')).'</th>'.
                     '<th>'.p2h_enc(lang('@Modified')).'</th>'.($qc ? '<th>'.p2h_enc(lang('@Found in the text')).'</th>' : '').'</tr>';
                foreach ($hits as [$p, $isDir, $ln, $snip]) {
                    $dir= p2h_norm_path(dirname($p));
                    $go=  basename(__FILE__).'?show='.urlencode($isDir ? $p : $dir);
                    echo '<tr><td><a href="'.p2h_enc($go).'"><i class="far fa-'.($isDir ? 'folder' : 'file').'"></i> '.p2h_enc(basename($p)).'</a>'.
                         (!$isDir && fe_can_read($p) ? ' '.fe_hint('<a href="'.p2h_enc(basename(__FILE__).'?dl='.rawurlencode($p)).'" aria-label="'.p2h_enc(lang('@Download')).'"><i class="fas fa-download"></i></a>', p2h_enc(lang('@Download'))) : '').'</td>'.
                         '<td><a href="'.p2h_enc(basename(__FILE__).'?show='.urlencode($dir)).'">'.p2h_enc(ltrim(substr($dir, strlen($currPath)), '/') ?: lang('@(this folder)')).'</a></td>'.
                         '<td style="white-space:nowrap; text-align:right;">'.($isDir ? '' : p2h_nice_filesize((int)@filesize($p))).'</td>'.
                         '<td style="white-space:nowrap;">'.date('Y-m-d H:i', (int)@filemtime($p)).'</td>'.
                         ($qc ? '<td class="feFindLine">'.($ln ? '<small>'.p2h_enc(lang('@line')).' '.$ln.':</small> <code>'.p2h_enc($snip).'</code>' : '').'</td>' : '').'</tr>';
                }
                echo '</table></div>';
            }
            echo '<small>'.p2h_enc(lang('@Tip: * and ? search the whole name (*.pdf, report-202?.txt) - else a part of the name. The text: only text files you may download, up to 2 MB.')).'</small></div>';
        htm_Card_end(labl:'', icon:'', hint:'', name:'', form:'', subm:false, attr:'', akey:'', kind:'save', simu:false);
        htm_nl(1);
    }

    if (isset($_GET['dupl'])) {         // Duplicates: files with the same content in this folder and below it - the most space first
        [$groups, $seen, $cut]= fe_dupes($currPath);
        $act=   basename(__FILE__).'?show='.urlencode($currPath);
        $waste= array_sum(array_map(fn($g) => $g[0] * (count($g[1]) - 1), $groups));
        htm_Card_(capt:'@Duplicates',icon:'fas fa-clone',hint:'@Files with the same content in this folder and all folders below it - only files you may download. Keep one of each.',
                  form:'',acti:'',clas:'cardWmax',wdth:'800px',styl:'background-color: white;',attr:'text-align: left; padding:8px; background-color: white;',show:true, head:'background-color: white;');
            echo '<div class="feUsers"><form id="feDupForm" method="post" action="'.p2h_enc($act).'">'.csrf_field(true).'</form>';
            echo '<p class="feFindSum" role="status">'.($groups
                    ? '<b>'.count($groups).'</b> '.p2h_enc(lang('@groups of equal files')).' - <b>'.array_sum(array_map(fn($g) => count($g[1]) - 1, $groups)).'</b> '.
                      p2h_enc(lang('@copies too many')).' - '.p2h_enc(lang('@space to save')).': <b>'.p2h_nice_filesize($waste).'</b>'
                    : p2h_enc(lang('@No duplicates found'))).
                 ' - '.$seen.' '.p2h_enc(lang('@files compared in')).' <b>'.p2h_enc(fe_show_path($currPath)).'</b> '.p2h_enc(lang('@and below')).
                 ($cut ? ' - <span class="feFindCut">'.p2h_enc(lang('@stopped early (200000 files or the time) - look in a folder further down')).'</span>' : '').'</p>';
            if ($groups) {
                echo '<div class="feFindWrap"><table class="feUsrTbl feDupTbl"><tr><th>'.p2h_enc(lang('@Name')).'</th><th>'.p2h_enc(lang('@Folder')).'</th><th>'.p2h_enc(lang('@Modified')).'</th><th></th></tr>';
                foreach (array_slice($groups, 0, 300) as $gi => [$size, $paths]) {
                    echo '<tr class="feDupHead"><td colspan="4"><b>'.count($paths).' &times; '.p2h_nice_filesize($size).'</b> <small>('.p2h_enc(lang('@space to save')).': '.p2h_nice_filesize($size * (count($paths) - 1)).')</small></td></tr>';
                    foreach ($paths as $p) {
                        $dir= p2h_norm_path(dirname($p));
                        $mayDel= fe_can('w') && fe_can_write($dir) && fe_name_allowed(basename($p)) && !fe_program_file($p);
                        echo '<tr><td><i class="far fa-file"></i> '.p2h_enc(basename($p)).'</td>'.
                             '<td><a href="'.p2h_enc(basename(__FILE__).'?show='.urlencode($dir)).'">'.p2h_enc(ltrim(substr($dir, strlen($currPath)), '/') ?: lang('@(this folder)')).'</a></td>'.
                             '<td style="white-space:nowrap;">'.date('Y-m-d H:i', (int)@filemtime($p)).'</td><td style="white-space:nowrap;">'.
                             fe_hint('<a class="txtbutt feXfBtn" href="'.p2h_enc(basename(__FILE__).'?dl='.rawurlencode($p)).'" aria-label="'.p2h_enc(lang('@Download')).'"><i class="fas fa-download"></i></a>', p2h_enc(lang('@Download'))).
                             ($mayDel ? ' '.fe_hint('<button type="submit" form="feDupForm" name="fe_dupdel" value="'.p2h_enc($p).'" class="txtbutt feXfBtn feUsrDel" aria-label="'.p2h_enc(lang('@Move to the trash')).'" '.
                                        'onclick="return feConfirmSubmit(this, '.p2h_enc(p2h_js(lang('@Move the file to the trash').': '.basename($p).' ?')).')"><i class="fas fa-trash-alt"></i></button>', p2h_enc(lang('@Move to the trash'))) : '').
                             '</td></tr>';
                    }
                }
                echo '</table></div>'.(count($groups) > 300 ? '<p><small>'.p2h_enc(lang('@The first 300 groups are shown')).'</small></p>' : '');
            }
            echo '<small>'.p2h_enc(lang('@The same size, then the same start, then the same SHA-1 checksum of the whole file - the name does not matter.')).'</small> &nbsp; '.
                 '<a href="'.p2h_enc($act).'" class="txtbutt feXfBtn"><i class="fas fa-times"></i> '.p2h_enc(lang('@Close')).'</a></div>';
        htm_Card_end(labl:'', icon:'', hint:'', name:'', form:'', subm:false, attr:'', akey:'', kind:'save', simu:false);
        htm_nl(1);
    }

    htm_Card_(capt:'@Folder content',icon:'fas fa-folder-open',hint: '',form:'xxx',acti:'',clas:'cardWmax',vhgh:'780px',   // The file window: 600px was low with the file features locked to its bottom
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
                fe_hint('<button type="button" id="feGalBtn" class="feGalBtn" aria-pressed="false" onclick="feGalToggle()"><i class="fas fa-th-large"></i> '.lang('@Gallery').'</button>',
                        lang('@The files as tiles - images as small pictures. Click a tile: the file menu - double-click: view. Click again: the table.')).
                ($calc_folder ? fe_hint('<a class="feGalBtn" href="'.p2h_enc(basename(__FILE__).'?show='.urlencode($currPath).'&dupl=1').'"><i class="fas fa-clone"></i> '.lang('@Duplicates').'</a>',
                                        lang('@Find files with the same content in this folder and all folders below it')) : '').
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
            ['@Used space','20%', ($calc_folder == true ? 'html' : 'hidd'),
                                          '', ['left'  ,'','',$sort=false], 'space', '@Used space related to<br>Sum of all files in current folder and subfolders.']
        ),
        $RowSuff= array(), // tabl_row.ix
        $TblNote= '<small>'.lang('@Filtering/Searching: Hold mouse over the colored row below the column headers.').'</small><br>'.
                  htm_Fieldset(capt:'@File features - for the checked rows:',icon:'', hint:'',wdth:'',marg:'',attr:'',rtrn:true).
                      htm_Fieldset(capt:'@Select:',icon:'', hint:'',wdth:'',marg:'',attr:'',rtrn:true).
                      htm_AcceptButt(labl:'@All',  icon:'', hint:'@All files and folders', form:'', wdth:'', attr:'', akey:'', kind:'spc2', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feSel("all")', idix:'').
                      htm_AcceptButt(labl:'@None', icon:'', hint:'@Deselect all', form:'', wdth:'', attr:'', akey:'', kind:'spc2', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feSel("none")', idix:'').
                      htm_AcceptButt(labl:'@Inverse', icon:'', hint:'@Reverse all', form:'', wdth:'', attr:'', akey:'', kind:'spc2', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feSel("inverse")', idix:'').
                      htm_AcceptButt(labl:'@Basket', icon:'fas fa-shopping-basket', hint:'@Put the checked rows in the basket - to copy or move them to a folder somewhere else', form:'', wdth:'', attr:'', akey:'', kind:'spc2', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feBasketChecked()', idix:'').
                      '<span id="feSelSum" class="feSelSum" aria-live="polite"></span>'.     // The checked rows: number and total size (feSelSum)
                      htm_Fieldset_end(rtrn:true).

                      htm_Fieldset(capt:'@With selected do:',icon:'', hint:'',wdth:'',marg:'',attr:'',rtrn:true).
                      htm_AcceptButt(labl:'@COPY',       icon:'', hint:'@Copy the checked files / folders to the destination folder (below) - never over an existing file', form:'', wdth:'100px;', attr:$dis, akey:'', kind:'',     rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feDo("copy")', idix:'').
                      htm_AcceptButt(labl:'@MOVE',       icon:'', hint:'@Move the checked files / folders to the destination folder (below)',       form:'', wdth:'100px;', attr:$dis, akey:'', kind:'crea', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feDo("move")', idix:'').
                      htm_AcceptButt(labl:'@Create ZIP', icon:'', hint:'@One ZIP file with the checked files / folders - in the destination folder (below)', form:'', wdth:'100px;', attr:$dis, akey:'', kind:'',     rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feDo("zip")', idix:'').
                      htm_AcceptButt(labl:'@DELETE',     icon:'', hint:fe_trash_dir() !== false ? '@Move the checked files / folders to the trash (program menu: Trash) - you are asked first' : '@Delete the checked files / folders (folders with their content) - you are asked first', form:'', wdth:'100px;', attr:$dis, akey:'', kind:'eras', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feDo("delete")', idix:'').
                      htm_AcceptButt(labl:'@Property',   icon:'', hint:'@Number and size of the checked files and folders',  form:'', wdth:'100px;', attr:$dis, akey:'', kind:'spc1', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feDo("prop")', idix:'').
                      htm_AcceptButt(labl:'@DOWNLOAD',   icon:'', hint:'@Download the checked files / folders to your PC - more than one file as a ZIP', form:'', wdth:'100px;', attr:$dis, akey:'', kind:'get_', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feDo("download")', idix:'').
                      htm_Fieldset_end(rtrn:true).
                      htm_AcceptButt(labl:'@UPLOAD',     icon:'', hint:'@Upload files from your PC to this folder (the Transfer card)', form:'', wdth:'100px;', attr:'', akey:'', kind:'get_', rtrn:true, tplc:'LblTip_text', tsty:'', acti:'feDo("upload")', idix:'').
                      '<small class="feSelDest">'.p2h_enc(lang('@COPY / MOVE / Create ZIP go to the destination of the Transfer card - without one: this folder')).'</small>'.
                  htm_Fieldset_end(rtrn:true),
        data: $tabldata, 
        filt: $FilterOn= true,
        sort: $SorterOn= true,        # Folders and files sort together (tested 2026-10-20 - the old FIXIT is solved)
        crea: $CreateRec=false,
        modi: $ModifyRec=false,
        vhgh: $ViewHeight= '650px',
        styl: $TblStyle= 'width:98%; cursor: alias; ',
        from: $CalledFrom= __FILE__ ,
        list: $MultiList= ['',''],
        expo: $ExportTo= ''           # Never from the request: POST csvFile= wrote a file of any name in the program folder (also folder-explorer.php) - for any account
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
    function feCopyText(txt, btn, done) {     /* Copy a text to the clipboard - the button says so for a moment */
        var ok= function() { if (!btn) return;  var o= btn.innerHTML;  btn.textContent= done || "✓";  setTimeout(function() { btn.innerHTML= o; }, 1500); };
        if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(txt).then(ok, function() { sysPrompt("", function() {}, txt); });
        else { var t= document.createElement("textarea");  t.value= txt;  t.style.cssText= "position: fixed; opacity: 0;";  document.body.appendChild(t);  t.select();
               var r= false;  try { r= document.execCommand("copy"); } catch (e) {}  t.remove();  if (r) ok();  else sysPrompt("", function() {}, txt); }
    }
    function feCopyLink(btn) { feCopyText(btn.getAttribute("data-url"), btn, "✓"); }     /* Shared links: copy a link */
    function feFindGo() {      /* Search in this folder and below it (the search field of the card header) */
        var q= document.getElementById("feFind"), c= document.getElementById("feFindC");
        if (!q || !q.value.trim()) { if (q) q.focus();  return; }
        location.href= '.p2h_js(basename(__FILE__).'?show='.urlencode($currPath).'&find=').' + encodeURIComponent(q.value.trim()) + (c && c.checked ? "&fc=1" : "");
    }
    /* Gallery (the button in the card header): the rows of the table as tiles - images as small pictures (?thumb=) - remembered in this browser */
    function feGalOn() { try { return localStorage.getItem("feGallery") === "1"; } catch (e) { return false; } }
    function feGalSpan() { var s= document.querySelector("[id^=tblSpan]");  return s && s.querySelector(":scope > .wrapper") ? s : null; }
    function feGalBuild() {
        var span= feGalSpan();  if (!span) return;
        var wrap= span.querySelector(":scope > .wrapper"), g= document.getElementById("feGal"), tb= wrap.querySelector("table");
        if (!g) { g= document.createElement("div");  g.id= "feGal";  g.className= "feGal";  g.setAttribute("role", "list");  wrap.parentNode.insertBefore(g, wrap); }
        g.innerHTML= "";
        var mk= function(tag, cls, txt) { var e= document.createElement(tag);  if (cls) e.className= cls;  if (txt !== undefined) e.textContent= txt;  return e; };
        Array.prototype.forEach.call(tb && tb.tBodies[0] ? tb.tBodies[0].rows : [], function(tr) {
            if (tr.style.display === "none" || tr.classList.contains("filtered")) return;      /* Hidden by the filter row */
            var nm= tr.querySelector(".feFileName"), src= nm || tr.querySelector(".feBp[data-fm]"), f;
            if (!src) return;
            try { f= JSON.parse(src.dataset.fm); } catch (e) { return; }
            var chk= tr.querySelector("input[type=checkbox]"), ic= tr.querySelector(".feFileIc");
            var tile= mk("div", "feTile" + (chk && chk.checked ? " feTileOn" : "")), pic= mk("div", "feTilePic");
            tile.setAttribute("role", "listitem");  pic.tabIndex= 0;  pic.setAttribute("role", "button");  pic.setAttribute("aria-label", f.n);  pic.title= f.n;
            var icon= function() { return mk("i", (f.dir ? "fas fa-folder feTileDir" : (ic ? ic.className.replace("feFileIc", "") : "far fa-file")) + " feTileIc"); };
            if (ic && ic.dataset.pk === "img" && ic.dataset.pv) {
                var im= new Image();  im.alt= "";  im.loading= "lazy";  im.decoding= "async";
                im.onerror= function() { im.replaceWith(icon()); };
                im.src= ic.dataset.pv.replace("?pv=", "?thumb=");  pic.appendChild(im);
            } else pic.appendChild(icon());
            if (chk) {
                var cb= mk("input", "feTileChk");  cb.type= "checkbox";  cb.checked= chk.checked;  cb.setAttribute("aria-label", f.n);
                cb.onchange= function() { chk.checked= cb.checked;  tile.classList.toggle("feTileOn", cb.checked);  chk.dispatchEvent(new Event("change", {bubbles: true})); };
                tile._chk= [cb, chk];  tile.appendChild(cb);
            }
            var open= function() {
                if (f.dir) { location.href= f.g;  return; }
                var r= pic.getBoundingClientRect();     /* The file menu - as a right-click of the row */
                nm.dispatchEvent(new MouseEvent("contextmenu", {bubbles: true, cancelable: true, clientX: r.left + 10, clientY: r.bottom - 6}));
            };
            pic.addEventListener("click", function(e) { e.stopPropagation();  open(); });
            pic.addEventListener("dblclick", function() { if (!f.dir && f.o) window.open(f.o, "_blank", "noopener"); });
            pic.addEventListener("keydown", function(e) {
                if (e.key === "Enter") { e.preventDefault();  open(); }
                else if (e.key === " " && cb) { e.preventDefault();  cb.click(); }
            });
            tile.appendChild(pic);  tile.appendChild(mk("div", "feTileName", f.n));
            tile.appendChild(mk("div", "feTileMeta", f.dir ? "" : (ic ? ic.dataset.ps : "")));
            g.appendChild(tile);
        });
        if (!g.children.length) g.appendChild(mk("p", "feGalNone", '.p2h_js(lang('@Nothing to show')).'));
    }
    function feGalSync() {          /* The check boxes of the table changed (All / None / Inverse): the tiles too */
        document.querySelectorAll("#feGal .feTile").forEach(function(t) { if (t._chk) { t._chk[0].checked= t._chk[1].checked;  t.classList.toggle("feTileOn", t._chk[1].checked); } });
    }
    function feGalShow() {
        var span= feGalSpan(), on= feGalOn(), b= document.getElementById("feGalBtn");
        if (b) b.setAttribute("aria-pressed", on ? "true" : "false");
        if (!span) return;
        span.classList.toggle("feGalOn", on);
        if (on) feGalBuild();
    }
    function feGalToggle() { try { localStorage.setItem("feGallery", feGalOn() ? "0" : "1"); } catch (e) {}  feGalShow(); }
    addEventListener("load", function() {
        setTimeout(feGalShow, 50);
        if (window.jQuery) jQuery("[id^=tblSpan] table").on("filterEnd sortEnd", function() { if (feGalOn()) feGalBuild(); });
    });
    document.addEventListener("change", function(e) { if (e.target.matches && e.target.matches("[id^=tblSpan] table input[type=checkbox]")) feGalSync(); });
    var feT= JSON.parse('.p2h_js(json_encode(['title'=>lang('@The name is taken'), 'q'=>lang('@These names are taken in the destination - what shall be done?'),
                 'name'=>lang('@Name'), 'new'=>lang('@New'), 'there'=>lang('@There now'), 'do'=>lang('@Do'), 'all'=>lang('@All'), 'folder'=>lang('@folder'),
                 'rename'=>lang('@New name - name (2)'), 'over'=>lang(fe_trash_dir() !== false ? '@Overwrite - the old one into the trash' : '@Overwrite'),
                 'skip'=>lang('@Skip - keep the one there'), 'mOver'=>lang('@Merge - replace files of the same name'), 'mSkip'=>lang('@Merge - keep files there'),
                 'only'=>lang('@a file against a folder: only a new name'), 'ok'=>lang('@OK'), 'cancel'=>lang('@Cancel'), 'fail'=>lang('@It could not be done'),
                 'more'=>lang('@more not shown - they get the choice of All')])).');
    function feConf() { var s= document.getElementById("feConf");  return s ? s.value : "rename"; }    /* A taken name: as chosen on the Transfer card */
    function feAsk(fields, go, url, stop) {   /* "Stop and ask": the server names the taken names first (nothing is done) - then go(the fields of the choice) - Cancel: stop() */
        var m= feConf();
        if (m !== "ask" || !window.fetch || !window.URLSearchParams || typeof HTMLDialogElement !== "function") { go([["fe_conf", m === "ask" ? "rename" : m]]);  return; }
        var fd= new URLSearchParams();
        fields.concat([["fe_check", "1"], ["csrf_token", '.p2h_js($_SESSION['csrf_token'] ?? '').']]).forEach(function(v) { fd.append(v[0], v[1]); });
        document.body.style.cursor= "progress";
        fetch(url || '.p2h_js(basename(__FILE__).'?show='.urlencode($currPath)).', {method: "POST", body: fd, credentials: "same-origin"})
            .then(function(r) { return r.json(); }).then(function(j) {
                document.body.style.cursor= "";
                var c= (j && j.c) || [];
                if (!c.length) go([["fe_conf", "rename"]]);       /* Nothing taken */
                else feAskDlg(c, function(ch, a) { go([["fe_conf", "ask"], ["fe_ch", JSON.stringify(ch)], ["fe_chall", a]]); }, stop);
            }).catch(function() { document.body.style.cursor= "";  sysAlert(feT.fail);  if (stop) stop(); });
    }
    function feAskDlg(c, ok, no) {   /* The taken names: the new one and the one there (size, date) - a choice per name, and one for all */
        var hum= function(n) { if (n === null || n === undefined) return "";  var u= ["B", "KB", "MB", "GB", "TB"], i= 0;  n= +n;  while (n >= 1024 && i < 4) { n/= 1024;  i++; }  return (i ? n.toFixed(1) : n) + " " + u[i]; };
        var day= function(t) { return t ? new Date(t * 1000).toLocaleString([], {dateStyle: "short", timeStyle: "short"}) : ""; };
        var el= function(tag, txt, css) { var e= document.createElement(tag);  if (txt !== undefined) e.textContent= txt;  if (css) e.style.cssText= css;  return e; };
        var opts= function(r) { return r.x ? [["rename", feT.only]] : [["rename", feT.rename], ["overwrite", r.d ? feT.mOver : feT.over], ["skip", r.d ? feT.mSkip : feT.skip]]; };
        var sel= function(list, v) { var s= el("select");  list.forEach(function(o) { var op= el("option", o[1]);  op.value= o[0];  s.appendChild(op); });  s.value= v;  return s; };
        var d= el("dialog");  d.className= "p2hDlg";  d.style.maxWidth= "min(860px, 96vw)";
        var h= el("div", feT.title);  h.className= "p2hDlgHead";
        var b= el("div", undefined, "white-space: normal;");  b.className= "p2hDlgBody";
        b.appendChild(el("p", feT.q, "margin: 0 0 8px;"));
        var all= sel([["", "—"], ["rename", feT.rename], ["overwrite", feT.over], ["skip", feT.skip]], "");
        var al= el("label", feT.all + ": ", "display: block; margin-bottom: 8px; font-weight: 600;");  al.appendChild(all);  b.appendChild(al);
        all.setAttribute("aria-label", feT.all);
        var wrap= el("div", undefined, "max-height: 50vh; overflow: auto;"), t= el("table", undefined, "border-collapse: collapse; width: 100%; font-size: 13px;");
        var hr= el("tr");  [feT.name, feT["new"], feT.there, feT["do"]].forEach(function(x) { hr.appendChild(el("th", x, "text-align: left; padding: 3px 6px; border-bottom: 1px solid var(--grayColor);")); });
        t.appendChild(hr);
        var rows= [], shown= c.slice(0, 200);
        shown.forEach(function(r) {
            var tr= el("tr"), cell= function(x, css) { var td= el("td", x, "padding: 3px 6px; vertical-align: top;" + (css || ""));  tr.appendChild(td);  return td; };
            cell(r.k + (r.d ? "/" : ""), "word-break: break-all;");
            var newer= (r.a[1] || 0) > (r.b[1] || 0), info= function(s, w, bold) { var td= cell(w ? feT.folder : hum(s[0]), "white-space: nowrap;");
                td.appendChild(el("br"));  td.appendChild(el("small", day(s[1]), bold ? "font-weight: 700;" : ""));  return td; };
            info(r.a, r.d, newer);  info(r.b, r.d, !newer && (r.b[1] || 0) > (r.a[1] || 0));
            var s= sel(opts(r), "rename");  if (r.x) s.disabled= true;
            s.setAttribute("aria-label", feT["do"] + ": " + r.k);     /* A screen reader names the row */
            cell("").appendChild(s);
            rows.push([r, s]);  t.appendChild(tr);
        });
        wrap.appendChild(t);  b.appendChild(wrap);
        if (c.length > shown.length) b.appendChild(el("p", "+ " + (c.length - shown.length) + " " + feT.more, "margin: 6px 0 0;"));
        all.onchange= function() { if (all.value) rows.forEach(function(p) { if (!p[0].x) p[1].value= all.value; }); };
        var f= el("div");  f.className= "p2hDlgFoot";
        var bc= el("button", feT.cancel);  bc.type= "button";
        var bo= el("button", feT.ok);  bo.type= "button";  bo.className= "p2hDlgOk";
        f.appendChild(bc);  f.appendChild(bo);
        d.appendChild(h);  d.appendChild(b);  d.appendChild(f);
        var close= function(yes) {
            d.close();  d.remove();
            if (!yes) { if (no) no();  return; }
            var ch= {};
            rows.forEach(function(p) { ch[p[0].k]= p[1].value; });
            ok(ch, all.value || "rename");     /* The names not shown (and those the server did not list): the choice of All */
        };
        bc.onclick= function() { close(false); };  bo.onclick= function() { close(true); };
        d.addEventListener("cancel", function(e) { e.preventDefault();  close(false); });     /* Esc: nothing is done */
        document.body.appendChild(d);  d.showModal();  bo.focus();
    }
    function feBasket(op, paths) {      /* add, remove, clear, copy, move, zip */
        var de= document.getElementById("feDest");
        var fields= [["fe_bk", op], ["fe_dest", de ? de.value.trim() : ""]].concat((paths || []).map(function(p) { return ["fe_bp[]", p]; }));
        if (op === "copy" || op === "move") feAsk(fields, function(x) { fePost(fields.concat(x)); });
        else fePost(fields, op === "zip");
    }
    function feRows() {     /* The check boxes of the table rows (each row has its path in .feBp) */
        return Array.prototype.slice.call(document.querySelectorAll("table input[type=checkbox]")).filter(function(c) {
            var tr= c.closest("tr");  return tr && tr.querySelector(".feBp");
        });
    }
    function feSel(how) {      /* All / Inverse: only the rows the filter shows (a hidden row was checked too - and DELETE took it) */
        feRows().forEach(function(c) {
            var tr= c.closest("tr"), shown= tr.offsetParent !== null && !tr.classList.contains("filtered");
            if (how === "none") c.checked= false;  else if (shown) c.checked= how === "all" ? true : !c.checked;
        });
        feSelSum();
        if (typeof feGalSync === "function") feGalSync();
    }
    function feSelSum() {      /* Below the table: how many rows are checked - and the total size of the checked files (folders: when Analyse has counted them) */
        var out= document.getElementById("feSelSum");  if (!out) return;
        var S= JSON.parse('.p2h_js(json_encode(['chk'=>lang('@checked'), 'files'=>lang('@files'), 'file'=>lang('@file'), 'folders'=>lang('@folders'), 'folder'=>lang('@folder'),
                                     'nc'=>lang('@size of the folders not counted - Analyse counts it')])).');
        var nf= 0, nd= 0, nu= 0, b= 0;
        feRows().forEach(function(c) {
            if (!c.checked) return;
            var tr= c.closest("tr"), fn= tr.querySelector(".feFileName"), f= null;
            try { f= JSON.parse((fn || tr.querySelector(".feBp[data-fm]") || {dataset: {}}).dataset.fm || "null"); } catch (e) {}
            var n= f ? parseInt(String(f.s || "").replace(/\D/g, ""), 10) : NaN;
            if (fn) nf++;  else { nd++;  if (isNaN(n)) nu++; }
            if (!isNaN(n)) b+= n;
        });
        if (!nf && !nd) { out.replaceChildren();  return; }
        var u= ["B", "KB", "MB", "GB", "TB"], i= 0, v= b;  while (v >= 1024 && i < 4) { v/= 1024;  i++; }
        var L= [(nf + nd) + " " + S.chk];      /* Below the 4 buttons, as wide as they are: the facts wrap */
        if (nf) L.push(nf + " " + (nf === 1 ? S.file : S.files));
        if (nd) L.push(nd + " " + (nd === 1 ? S.folder : S.folders));
        L.push((i ? v.toLocaleString(undefined, {maximumFractionDigits: 2}) : v) + " " + u[i]);
        if (nu) L.push("(" + S.nc + ")");
        out.replaceChildren.apply(out, L.map(function(t, k) { var d= document.createElement("div");  d.textContent= t;  if (nu && k === L.length - 1) d.className= "feSelNc";  return d; }));
        out.title= b.toLocaleString() + " B";
    }
    document.addEventListener("change", function(e) { if (e.target.matches && e.target.matches("table input[type=checkbox]")) feSelSum(); });
    addEventListener("pageshow", feSelSum);     /* Rows the browser kept checked after Back / reload */
    function feDo(op) {     /* File features below the table */
        var T= '.p2h_js(json_encode(['check'=>lang('@Check one or more rows in the table first'), 'noUp'=>lang('@You may not upload to this folder'),
                                     'delQ'=>lang(fe_trash_dir() !== false ? '@Move the checked files and folders to the trash?' : '@Delete the checked files and folders (folders with all their content)?'), 'title'=>lang('@The checked rows'),
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
        var fields= [["fe_sel", op], ["fe_dest", de ? de.value.trim() : ""]].concat(ps.map(function(p) { return ["fe_bp[]", p]; }));
        var go= function() { fePost(fields, op === "download"); };
        if (op === "delete") sysConfirm(T.delQ + " (" + ps.length + ")", go);
        else if (op === "copy" || op === "move") feAsk(fields, function(x) { fePost(fields.concat(x)); });
        else go();
    }
    function feNewFolder() {     /* Program menu: a new folder in this folder */
        var ask= typeof sysPrompt === "function" ? sysPrompt : function(q, fn) { var v= prompt(q);  if (v !== null) fn(v); };
        ask('.p2h_js(lang('@Name of the new folder:')).', function(v) { if (v && String(v).trim()) fePost([["fe_act", "mkdir"], ["fe_name", ""], ["fe_to", String(v).trim()]]); });
    }
    function feHelp() {          /* Program menu: the tab Nice to know in the card Manual - the card opened first */
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
    var feDT= JSON.parse('.p2h_js(json_encode(['cancel'=>lang('@Cancel'), 'close'=>lang('@Close'), 'fail'=>lang('@It could not be done'),
                 'sTitle'=>lang('@Share link'), 'sDays'=>lang('@The link works for'), 'sNever'=>lang('@No end date'), 'sDay'=>lang('@day'), 'sDayN'=>lang('@days'),
                 'sPw'=>lang('@Password (optional)'), 'sPwH'=>lang('@The one who gets the link must type it'), 'sMake'=>lang('@Make the link'),
                 'sNote'=>lang('@Everyone with the link can download it - without an account. Program menu - Shared links: see and delete your links.'),
                 'sNoteD'=>lang('@A folder is downloaded as one ZIP file with what is in it now.'),
                 'sMade'=>lang('@The link is made - copy it and send it:'), 'sUntil'=>lang('@Works until'), 'sCopy'=>lang('@Copy'), 'sCopied'=>lang('@Copied'),
                 'eTitle'=>lang('@Edit'), 'eSave'=>lang('@Save'), 'eSaved'=>lang('@Saved'), 'eChanged'=>lang('@Changed - not saved'), 'eLines'=>lang('@lines'),
                 'eWrap'=>lang('@Wrap long lines'), 'eLose'=>lang('@Close without saving the changes?'),
                 'eForce'=>lang('@The file was changed by someone else after you opened it. Save your version anyway? (The other version goes into the trash.)'),
                 'eTrash'=>fe_trash_dir() !== false ? lang('@Each save puts the old version into the trash') : '', 'eKeys'=>lang('@Ctrl+S saves')])).');
    var feDays= '.json_encode(array_values(array_merge(array_filter(array_unique([1, 7, 30, (int)$fe_share_days]), fn($d) => $d > 0 && $d <= (int)$fe_share_days), fe_is_admin() ? [0] : []))).';
    function feDlg(title, wide) {   /* An empty dialog of the program: [dialog, body, foot] */
        var mk= function(tag, cls) { var e= document.createElement(tag);  if (cls) e.className= cls;  return e; };
        var d= mk("dialog", "p2hDlg"), h= mk("div", "p2hDlgHead"), b= mk("div", "p2hDlgBody"), f= mk("div", "p2hDlgFoot");
        d.style.maxWidth= wide ? "min(1100px, 96vw)" : "min(560px, 96vw)";  if (wide) d.style.width= "96vw";
        h.textContent= title;  b.style.whiteSpace= "normal";  b.style.textAlign= "left";
        d.appendChild(h);  d.appendChild(b);  d.appendChild(f);  document.body.appendChild(d);
        return [d, b, f];
    }
    function feBtn(foot, txt, ok) { var b= document.createElement("button");  b.type= "button";  b.textContent= txt;  if (ok) b.className= "p2hDlgOk";  foot.appendChild(b);  return b; }
    function feJson(fields) {       /* POST to this folder with the CSRF token - the answer as JSON */
        var fd= new URLSearchParams();
        fields.concat([["fe_ajax", "1"], ["csrf_token", '.p2h_js($_SESSION['csrf_token'] ?? '').']]).forEach(function(v) { fd.append(v[0], v[1]); });
        return fetch('.p2h_js(basename(__FILE__).'?show='.urlencode($currPath)).', {method: "POST", body: fd, credentials: "same-origin"}).then(function(r) { return r.json(); });
    }
    function feShare(f) {           /* File menu: Share link - how long it works, maybe a password - then the link to copy */
        var x= feDlg(feDT.sTitle + ": " + f.n), d= x[0], b= x[1], ft= x[2];
        var lab= function(txt, input, hint) { var l= document.createElement("label");  l.style.cssText= "display: block; margin: 0 0 10px; font-weight: 600;";
            l.appendChild(document.createTextNode(txt));  input.classList.add("feDlgInp");  l.appendChild(input);
            if (hint) { var s= document.createElement("small");  s.style.fontWeight= "normal";  s.textContent= hint;  l.appendChild(s); }
            b.appendChild(l); };
        var sel= document.createElement("select");
        feDays.forEach(function(n) { var o= document.createElement("option");  o.value= n;  o.textContent= n ? n + " " + (n === 1 ? feDT.sDay : feDT.sDayN) : feDT.sNever;  sel.appendChild(o); });
        sel.value= feDays.indexOf(7) >= 0 ? "7" : String(feDays[0]);
        var pw= document.createElement("input");  pw.type= "text";  pw.autocomplete= "off";  pw.maxLength= 200;  pw.spellcheck= false;
        lab(feDT.sDays, sel);  lab(feDT.sPw, pw, feDT.sPwH);
        var p= document.createElement("p");  p.style.cssText= "margin: 4px 0 0; font-size: 12.5px;";  p.textContent= feDT.sNote + (f.dir ? " " + feDT.sNoteD : "");  b.appendChild(p);
        var bc= feBtn(ft, feDT.cancel), bo= feBtn(ft, feDT.sMake, true);
        var done= function() { d.close();  d.remove(); };
        bc.onclick= done;  d.addEventListener("cancel", function(e) { e.preventDefault();  done(); });
        bo.onclick= function() {
            bo.disabled= true;
            feJson([["fe_share", "create"], ["fe_path", f.b], ["fe_days", sel.value], ["fe_spw", pw.value]]).then(function(j) {
                if (j.err) { bo.disabled= false;  sysAlert(j.err);  return; }
                b.innerHTML= "";  ft.innerHTML= "";
                var t= document.createElement("p");  t.style.margin= "0 0 6px";  t.textContent= feDT.sMade;  b.appendChild(t);
                var row= document.createElement("div");  row.style.cssText= "display: flex; gap: 6px;";
                var u= document.createElement("input");  u.type= "text";  u.readOnly= true;  u.value= j.url;  u.className= "feDlgInp";  u.style.cssText= "flex: 1; min-width: 0; margin: 0; font-family: monospace;";
                u.onfocus= function() { u.select(); };  u.setAttribute("aria-label", feDT.sTitle);
                var cp= feBtn(row, feDT.sCopy, true);  row.insertBefore(u, cp);  b.appendChild(row);
                cp.onclick= function() { feCopyText(j.url, cp, feDT.sCopied); };
                var w= document.createElement("p");  w.style.cssText= "margin: 8px 0 0; font-size: 12.5px;";
                w.textContent= (j.x ? feDT.sUntil + " " + j.x : feDT.sNever) + (pw.value ? " - " + feDT.sPw.replace(/\s*\(.*\)/, "") + ": " + pw.value : "");  b.appendChild(w);
                feBtn(ft, feDT.close).onclick= done;
                u.focus();
            }).catch(function() { bo.disabled= false;  sysAlert(feDT.fail); });
        };
        d.showModal();  sel.focus();
    }
    function feEdit(f) {            /* File menu: Edit a text file - the text is fetched, saved with the time it had (someone else changed it: asked) */
        document.body.style.cursor= "progress";
        fetch('.p2h_js(basename(__FILE__).'?edt=').' + encodeURIComponent(f.b), {credentials: "same-origin"}).then(function(r) { return r.json(); }).then(function(j) {
            document.body.style.cursor= "";
            if (j.err) { sysAlert(j.err);  return; }
            var x= feDlg(feDT.eTitle + ": " + j.n, true), d= x[0], b= x[1], ft= x[2], mt= j.m, any= false;
            var ta= document.createElement("textarea");  ta.className= "feEdTa";  ta.spellcheck= false;  ta.value= j.t;  ta.setAttribute("aria-label", feDT.eTitle + ": " + j.n);
            ta.wrap= "off";  b.appendChild(ta);
            var saved= ta.value;
            var st= document.createElement("span");  st.className= "feEdSt";  st.setAttribute("aria-live", "polite");
            var wl= document.createElement("label");  wl.className= "feEdWrap";  var wc= document.createElement("input");  wc.type= "checkbox";
            wl.appendChild(wc);  wl.appendChild(document.createTextNode(" " + feDT.eWrap));
            wc.onchange= function() { ta.wrap= wc.checked ? "soft" : "off"; };
            ft.appendChild(st);  ft.appendChild(wl);
            var bc= feBtn(ft, feDT.close), bs= feBtn(ft, feDT.eSave, true), at= "";
            var show= function() {
                var dirty= ta.value !== saved;
                st.textContent= ta.value.split("\n").length + " " + feDT.eLines + "  ·  " + (dirty ? feDT.eChanged : (at ? feDT.eSaved + " " + at : feDT.eKeys)) +
                                (feDT.eTrash && !dirty && !at ? "  ·  " + feDT.eTrash : "");
                st.classList.toggle("feEdDirty", dirty);  bs.disabled= !dirty;
            };
            var save= function(force) {
                bs.disabled= true;
                var txt= ta.value;
                feJson([["fe_edit", f.n], ["fe_text", txt], ["fe_ver", String(mt)], ["fe_force", force ? "1" : ""]]).then(function(r) {
                    if (r.conflict) { show();  sysConfirm(feDT.eForce, function() { save(true); });  return; }
                    if (r.err) { show();  sysAlert(r.err);  return; }
                    mt= r.m;  saved= txt;  any= any || !r.same;
                    at= new Date().toLocaleTimeString([], {hour: "2-digit", minute: "2-digit"});  show();
                }).catch(function() { show();  sysAlert(feDT.fail); });
            };
            var close= function() { d.close();  d.remove();  if (any) location.reload(); };
            var ask= function() { if (ta.value !== saved) sysConfirm(feDT.eLose, close);  else close(); };
            ta.addEventListener("input", show);
            d.addEventListener("keydown", function(e) { if ((e.ctrlKey || e.metaKey) && (e.key === "s" || e.key === "S")) { e.preventDefault();  if (ta.value !== saved) save(false); } });
            d.addEventListener("cancel", function(e) { e.preventDefault();  ask(); });
            bc.onclick= ask;  bs.onclick= function() { save(false); };
            show();  d.showModal();  ta.focus();  ta.setSelectionRange(0, 0);  ta.scrollTop= 0;
        }).catch(function() { document.body.style.cursor= "";  sysAlert(feDT.fail); });
    }
    function feAct(act, name, to) {     // File menu: move / copy / zip / unzip in the current folder
        var fields= [["fe_act", act], ["fe_name", name], ["fe_to", to || ""]];
        if (act === "unzip") feAsk(fields, function(x) { fePost(fields.concat(x)); });     // A taken name: as chosen on the Transfer card
        else fePost(fields);
    }
    function feDelete(name) {
        var q= '.p2h_js(lang(fe_trash_dir() !== false ? '@Move the file to the trash' : '@Delete the file').': ').' + name + " ?", go= function() { fePost([["fe_delete", name]]); };
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
    'share'=>lang('@Share link ...'), 'edit'=>lang('@Edit'),
    'bkIn'=>lang('@Put in the basket'), 'bkOut'=>lang('@Take out of the basket'), 'unzip'=>lang('@Unpack here'),
    'prog'=>['title'=>'Folder-explorer', 'admin'=>fe_is_admin() ? 1 : 0, 'pw'=>fe_may_change_pw($_SESSION[p2h_SESSION_ID]['logged'] ?? '') ? 1 : 0,
             'unl'=>$fe_unlocked ? 1 : 0, 'out'=>$use_auth ? basename(__FILE__).'?logout=1&t='.urlencode((string)($_SESSION['csrf_token'] ?? '')) : '',
             'urlUsers'=>basename(__FILE__).'?show='.urlencode($currPath).'&users=1', 'urlLog'=>basename(__FILE__).'?show='.urlencode($currPath).'&log=1', 'logT'=>lang('@Activity log'), 'urlTrans'=>basename(__FILE__).'?show='.urlencode($currPath).'&trans=1', 'transT'=>lang('@Translations'),
             'trash'=>fe_can('w') && fe_trash_dir() !== false ? 1 : 0, 'urlTrash'=>basename(__FILE__).'?show='.urlencode($currPath).'&trash=1', 'trashT'=>lang('@Trash'),
             'shares'=>fe_can('w') && (int)$fe_share_days > 0 ? 1 : 0, 'urlShares'=>basename(__FILE__).'?show='.urlencode($currPath).'&shares=1', 'sharesT'=>lang('@Shared links'), 'urlPw'=>basename(__FILE__).'?show='.urlencode($currPath).'&pw=1',
             'users'=>lang('@Users ...'), 'pwT'=>lang('@Change my password'), 'lock'=>lang('@Remove restrictions'), 'unlock'=>lang('@Restore restrictions'),
             'lockQ'=>lang('@Remove the restrictions for this session?'), 'help'=>lang('@Help'), 'about'=>lang('@About Folder-explorer'), 'out_'=>lang('@Log out'),
             'aboutTxt'=>'Folder-explorer '.FE_VERSION.' ('.FE_DATE.")\n".'PHP2HTML / Clever-Html-Engine '.P2H_VERSION."\n".(fe_is_admin() ? 'PHP '.PHP_VERSION."\n" : '').
                         lang('@User').': '.($_SESSION[p2h_SESSION_ID]['logged'] ?? '')."\n\n".'© 2019-2026 EV-soft - github.com/EV-soft/folder-explorer'],
    'goto'=>lang('@Open the folder'), 'dirZip'=>lang('@Download as ZIP'), 'dirProp'=>lang('@Folder properties'), 'moveD'=>lang('@Rename / move folder'),
    'copyD'=>lang('@Copy folder'), 'delD'=>lang('@Delete folder'), 'delDQ'=>lang(fe_trash_dir() !== false ? '@Move the folder with all its content to the trash?' : '@Delete the folder with all its content?'),
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

    echo '</small>';
    htm_Card_end(labl:'', icon:'', hint:'', name:'', form:'', subm:false, attr:'', akey:'', kind:'save', simu:false);     // Folder content: the table - the transfer and the manual are cards of their own below it
    htm_nl(1);

    ### TRANSFER: the folders the user may write in, the basket (source -> destination), upload from the PC
    if ($is_logged) {
        $rootRel=  function($d) use ($root_path) { $r= ltrim(substr($d, strlen($root_path)), '/');  return $r === '' ? '/' : $r; };
        $maxFile=  min($fe_unlocked ? PHP_INT_MAX : $fe_max_upload, fe_ini_bytes(ini_get('upload_max_filesize')) ?: PHP_INT_MAX,
                       (fe_ini_bytes(ini_get('post_max_size')) ?: PHP_INT_MAX) - 65536);     // post_max_size: the whole request - room for the other fields (as fe-upload.js)
        htm_Card_(capt:'@Transfer',icon:'fas fa-exchange-alt',hint:'@Collect files and folders in the basket, and copy or move them to another folder - upload from your PC and download to it',
                  form:'',acti:'',clas:'cardW800',wdth:'800px',styl:'background-color: white;',attr:'text-align: left; padding:8px; background-color: white;',show:true, head:'background-color: white;');
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
            echo '<div class="feXfRow"><abbr class="hint"><b>'.p2h_enc(lang('@You may write in')).':</b><data-hint>'.p2h_enc(lang('@The folders your account may write in: upload, copy / move to, create ZIP and new folders - click one to open it')).'</data-hint></abbr> ';
            if (!$wd) echo '<i>'.p2h_enc(lang('@nothing - your account can only read')).'</i>';
            foreach ($wd as $d)
                echo '<a href="'.basename(__FILE__).'?show='.urlencode($d).'" class="txtbutt feXfDir"><i class="far fa-folder colr0"></i> '.p2h_enc($rootRel($d)).'</a> ';
            echo $can_write_here ? ' <abbr class="hint"><small class="feXfHere"><i class="fas fa-check"></i> '.p2h_enc(lang('@also here')).'</small><data-hint>'.p2h_enc(lang('@You may also write in the folder you are in now - it is the destination, unless you choose another')).'</data-hint></abbr>' : '';
            echo '</div>';
            if ($wd && fe_can('w')) {      // Where uploads and the basket go - typed or chosen, it need not be the folder you are in
                $opts= array_unique(array_merge($can_write_here ? [$currPath] : [], $wd));
                echo '<div class="feXfRow feXfDestRow"><label for="feDest"><i class="fas fa-arrow-right"></i> <b>'.p2h_enc(lang('@Destination')).'</b>:</label> '.
                     '<input type="text" id="feDest" list="feDestOpts" autocomplete="off" value="'.p2h_enc(fe_show_path($destDef)).'" '.
                        'aria-describedby="feDestTip" placeholder="'.p2h_enc(lang('@a folder you may write in')).'"> '.
                     '<datalist id="feDestOpts">'.implode('', array_map(fn($d) => '<option value="'.p2h_enc(fe_show_path($d)).'">', $opts)).'</datalist>'.
                     ' <small id="feDestTip">'.p2h_enc(lang('@for upload and the basket - choose or type a folder you may write in')).'</small></div>';
                $confOpts= ['ask' => '@Stop and ask', 'rename' => '@New name - name (2)',
                            'overwrite' => fe_trash_dir() !== false ? '@Overwrite - the old one into the trash' : '@Overwrite', 'skip' => '@Skip - keep the one there'];
                echo '<div class="feXfRow"><label for="feConf"><i class="far fa-clone"></i> <b>'.p2h_enc(lang('@A taken name')).'</b>:</label> '.
                     '<select id="feConf" aria-describedby="feConfTip">'.implode('', array_map(fn($k, $l) => '<option value="'.$k.'">'.p2h_enc(lang($l)).'</option>', array_keys($confOpts), $confOpts)).'</select>'.
                     ' <small id="feConfTip">'.p2h_enc(lang('@when copy, move, unpack or upload meets a name that is there already - a folder is merged')).'</small></div>'.
                     '<script>(function(){ var s= document.getElementById("feConf"), v= "ask";'.      // Remembered in this browser - "Stop and ask" first
                        'try { v= localStorage.getItem("feConf") || "ask"; } catch (e) {}'.
                        'if (s.querySelector("option[value=\'" + v + "\']")) s.value= v;'.
                        's.addEventListener("change", function(){ try { localStorage.setItem("feConf", s.value); } catch (e) {} });'.
                     '})();</script>';
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
                        'kept'    => '@skipped - the name was taken, the one there is kept',
                        'over'    => '@overwritten',
                        'overT'   => '@overwritten - the old one is in the trash',
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

    ### MANUAL: a card of its own at the bottom of the page
    htm_Card_(capt:'@Manual',icon:'fas fa-book',hint:'@How to use Folder-explorer - nice to know, step by step, questions, mouse and keys, your account, limits',
              form:'',acti:'',clas:'cardWmax',wdth:'800px',styl:'background-color: white;',attr:'text-align: left;',show:true, head:'background-color: white;');
    echo '<small>';

    $nice= [     // Nice to know: [heading, [lines]] - the texts are translated (_trans.sys.json)
        ['Menus - how to open them', [
            '<b>File menu:</b> rest the mouse on a file name - or click it (also on a touch screen) - or right-click the row.',
            '<b>Folder menu:</b> right-click the row of a folder. A click on the folder name opens the folder.',
            '<b>Program menu:</b> right-click the table outside a row: trash, shared links, your password, help, about, log out - administrators also: users, activity log, translations, restrictions.',
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
            '<b>Keyboard:</b> Ctrl+A checks all rows, Ctrl+Shift+A none - Delete deletes the checked rows (none checked: the row you are on) - F2 renames the row you are on (or the one checked row).',
            '<b>Trash:</b> what you delete goes to the trash (program menu: Trash) - restore it there, or delete it for good. After some time it is deleted for good.',
        ]],
        ['Transfer', [
            '<b>Basket:</b> collect files and folders from any folder, choose the <b>destination</b>, then copy or move them there - or download them as one ZIP.',
            '<b>Upload:</b> drag files or whole folders from your PC onto the drop zone - they go to the destination. A ZIP file is unpacked with the file menu: Unpack here.',
            '<b>A taken name:</b> choose in the field A taken name - Stop and ask (you see the names and choose for each), a new name (name (2)), overwrite (the old file goes to the trash) or skip. Two folders are merged. The message tells what was done.',
        ]],
        ['Share, edit, search, gallery', [
            '<b>Share link:</b> in the file or folder menu - a link for people without an account, with an end date and maybe a password. A folder is fetched as one ZIP file. Program menu - <b>Shared links</b>: copy or delete your links.',
            'A link works only while you may still download the file - and stops at its end date or when you delete it.',
            '<b>Edit:</b> in the file menu of a text file (up to 2 MB) in a folder you may write in. Ctrl+S saves - the old version goes to the trash. If someone else changed the file meanwhile, you are asked.',
            '<b>Search:</b> the search field in the heading of Folder content searches this folder and all folders below it - by name (* and ? for the whole name), and if you like in the text of text files.',
            '<b>Gallery</b> (heading of Folder content): the files as tiles, images as small pictures - click a tile for the file menu, double-click to view, check the box to select. Click Gallery again for the table.',
            '<b>Duplicates</b> (Analyse mode, heading of Folder content): files with the same content in this folder and below it - the most space first. Move the copies you do not need to the trash.',
        ]],
        ['Folder tree', [
            'The current folder is bold, with an open folder. The number at the left is the level.',
            'The card tool <b>hdd</b> shows the used space: the current folder is counted at once - other folders with their calculator, or <b>Count all</b>. The bars show the share of the parent folder or of BASE.',
            'In <b>Analyse</b> mode the used space is always shown - and all folders are counted at once.',
        ]],
        ['Quick and Analyse', [
            '<b>Quick</b> lists the folder at once. <b>Analyse</b> also counts the files and sizes in all sub folders, and lists their names to filter while you type. Quick is used again when you change folder.',
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
    // Your account (tab): what the logged user may do - and where. The facts of this account and this folder.
    $me=    $_SESSION[p2h_SESSION_ID]['logged'] ?? '';
    $yes=   fn($ok) => '<i class="fas fa-'.($ok ? 'check feAccYes' : 'times feAccNo').'" aria-hidden="true"></i> ';
    $tt=    fn($k) => p2h_enc(lang('@'.$k));
    $wdl=   fe_write_dirs();
    $usedX= $exch_path !== false ? (isset($used) && is_int($used) ? $used : fe_dir_size($exch_path)) : 0;
    $readHere= $fe_unlocked || $can_write_here || (fe_can('r') && $exch_path !== false && fe_in_dir($currPath, $exch_path) && !fe_hidden_below($currPath, $exch_path));
    $trashN= fe_trash_dir() !== false && fe_can('w') ? count(array_filter(fe_trash_list(), 'fe_trash_may')) : 0;
    $acc= [
        ['Account', p2h_enc($me !== '' ? $me : lang('@(no login)')).(fe_is_admin() ? ' - <b>'.$tt('administrator').'</b>' : '')],
        ['Read', $yes(fe_can('r')).$tt(fe_can('r') ? 'Download and preview - in your write folders and the shared folder' : 'Your account may not download or preview files')],
        ['Write', $yes(fe_can('w')).$tt(fe_can('w') ? 'Upload, copy, move, rename, ZIP, unpack and delete - in your write folders' : 'Your account can only read')],
        ['Write folders', $fe_unlocked ? $tt('Everything - the restrictions are removed') :
                          ($wdl ? implode(', ', array_map(fn($d) => '<a href="'.p2h_enc(basename(__FILE__).'?show='.urlencode($d)).'"><i class="far fa-folder"></i> '.p2h_enc(fe_show_path($d)).'</a>', $wdl))
                                : '<i>'.$tt('none').'</i>')],
        ['Shared folder', $exch_path === false ? '-' : p2h_enc(fe_show_path($exch_path)).': '.p2h_nice_filesize($usedX).' '.$tt('of').' '.
                          ($fe_unlocked ? $tt('no limit (restrictions removed)') : p2h_nice_filesize($fe_exchange_quota)).' - '.
                          $tt(fe_can('w') && fe_can_write($exch_path) ? 'you may read and write in it' : (fe_can('r') ? 'you may read in it' : 'you may only see the names'))],
        ['This folder', p2h_enc(fe_show_path($currPath)).': '.($can_write_here ? $yes(true).$tt('You may write here')
                          : ($readHere ? $yes(true).$tt('You may download here - not write') : $yes(false).$tt('You may only see the names here')))],
        ['Your password', $yes(fe_may_change_pw($me)).$tt(fe_may_change_pw($me) ? 'You may change it: program menu - Change my password' : 'This account may not change its password')],
        ['Restrictions', fe_is_admin() ? ($fe_unlocked ? $tt('Removed - for you, in this session') : $tt('In force - you may remove them: the lock icon of the Transfer card'))
                                       : $tt('In force - only administrators can remove them (for themselves)')],
        ['Trash', fe_trash_dir() === false ? $tt('No trash - deleted at once') :
                  (!fe_can('w') ? '-' : $tt(fe_is_admin() ? 'You see everything that was deleted' : 'You see what was deleted in your write folders').
                                        ' - '.$trashN.' '.$tt('files / folders').' - <a href="'.p2h_enc(basename(__FILE__).'?show='.urlencode($currPath).'&trash=1').'">'.$tt('Open the trash').'</a>')],
        ['Logged in', ($gbl_SessionKeep ?? true) ? $tt('You stay logged in while a page of the program is open')
                  : $tt('Logged out after').' '.max(1, round((int)ini_get('session.gc_maxlifetime') / 60)).' '.$tt('minutes without activity')],
    ];
    $tbl2= fn($rows) => '<table class="feKeys feFacts">'.implode('', array_map(fn($r) => '<tr><th scope="row">'.p2h_enc(lang('@'.$r[0])).'</th><td>'.$r[1].'</td></tr>', $rows)).'</table>';
    $body4= '<div class="feKeysWrap"><div>'.$tbl2($acc).'<small>'.$tt('An administrator gives the rights: program menu - Users.').'</small></div></div>';

    // Limits (tab): the numbers of this installation - the settings of the program and of PHP
    $iniUp=   fe_ini_bytes(ini_get('upload_max_filesize')) ?: PHP_INT_MAX;
    $iniPost= fe_ini_bytes(ini_get('post_max_size')) ?: PHP_INT_MAX;
    $maxF=    min($fe_unlocked ? PHP_INT_MAX : $fe_max_upload, $iniUp, $iniPost < PHP_INT_MAX ? $iniPost - 65536 : $iniPost);
    $nice=    fn($b) => $b >= PHP_INT_MAX ? $tt('no limit') : p2h_nice_filesize($b);
    preg_match('/\(([^)]*)\)/', FE_DENY_EXT, $denyM);
    $deny=    implode(' ', array_map(fn($e) => '.'.($e === 'php\d*' ? 'php*' : $e), explode('|', $denyM[1] ?? '')));
    $lim= [
        ['Max size per file (upload)', '<b>'.$nice($maxF).'</b> <small>('.$tt('the program').': '.$nice($fe_unlocked ? PHP_INT_MAX : $fe_max_upload).' - PHP upload_max_filesize: '.$nice($iniUp).
                                       ' - post_max_size: '.$nice($iniPost).')</small>'],
        ['Files per upload', $tt('Any number - they are sent').' '.max(1, (int)ini_get('max_file_uploads') ?: 20).' '.$tt('at a time')],
        ['Folders in an upload', $tt('At most 10 levels below the destination')],
        ['File types not allowed', $fe_unlocked ? $tt('Hidden files (.htaccess ...) - all other types are allowed: the restrictions are removed')
                                                : '<code>'.p2h_enc($deny).'</code> - '.$tt('and hidden files (.htaccess ...) and index.html')],
        ['Shared folder', $exch_path === false ? '-' : '<b>'.$nice($fe_unlocked ? PHP_INT_MAX : $fe_exchange_quota).'</b> - '.$tt('used').' '.p2h_nice_filesize($usedX).' - '.$tt('the other write folders have no limit of their own')],
        ['ZIP', $tt('At most 20000 files in a ZIP - when it is made, and when it is unpacked')],
        ['Unpack', $tt('Only safe names - a taken name as chosen in the field A taken name - in the shared folder: at most its free space')],
        ['Names', $tt('A taken name: as chosen on the Transfer card - Stop and ask, name (2), overwrite (the old file into the trash) or skip - a protected file is never replaced')],
        ['Long work', $tt('At most').' '.(int)$fe_max_time.' '.$tt('seconds (copy, ZIP, the sizes of big folders) - the web server may have its own limit')],
        ['Trash', fe_trash_dir() === false ? $tt('No trash - deleted at once')
                  : $tt('Deleted for good after').' '.(int)$fe_trash_days.' '.$tt('days').' - '.$tt('and the oldest first, when the trash holds more than').' '.p2h_nice_filesize($fe_trash_max)],
        ['Failed logins', $tt('At most 10 per address and account in 15 minutes - then wait')],
        ['Preview', $tt('Text: the first 40 lines - the content only where you may download the file')],
    ];
    $body5= '<div class="feKeysWrap"><div>'.$tbl2($lim).'<small>'.$tt('Set in folder-explorer.php (see README) and in the PHP settings of the web hotel.').'</small></div></div>';

    // Step by step (tab): what people want to do - [heading, [steps]]
    $steps= [
        ['Copy or move files to another folder', [
            'Check the rows in the table (or Ctrl+A).',
            'In the card Transfer: choose the <b>Destination</b> - a folder you may write in.',
            'Below the table: <b>COPY</b> or <b>MOVE</b>.',
        ]],
        ['Collect files from several folders', [
            'Rest the mouse on a file name: <b>Put in the basket</b> - or check rows and click <b>Put the checked rows in the basket</b>.',
            'Go to the next folder and do the same - the basket keeps them.',
            'Choose the Destination, then <b>Copy to the destination</b> or <b>Move to the destination</b> - or <b>Download as ZIP</b>.',
        ]],
        ['Download a whole folder', [
            'Right-click the row of the folder: <b>Download as ZIP</b>.',
            'Several files and folders: check them, then <b>DOWNLOAD</b> below the table - more than one file comes as one ZIP.',
        ]],
        ['Upload files or a whole folder from your PC', [
            'Choose the Destination in the card Transfer.',
            'Drag files or folders onto the drop zone - or <b>Choose files</b> / <b>Choose a folder</b>.',
            'Click <b>Upload</b>. The sub folders are made as they were on your PC.',
        ]],
        ['Unpack a ZIP file', [
            'Rest the mouse on the name of the ZIP file: <b>Unpack here</b> - it is unpacked in the folder you are in.',
        ]],
        ['Rename a file or a folder', [
            'Click the row and press <b>F2</b> - or the file menu: <b>Rename / move</b>.',
            'A path moves it too: <code>sub/new name.txt</code>.',
        ]],
        ['Get a deleted file back', [
            'Program menu (right-click the table outside a row): <b>Trash</b>.',
            '<b>Restore</b> - it goes back to its folder.',
        ]],
        ['Add a new language (administrators)', [
            'Program menu: <b>Translations</b> - choose the language (those without translations are at the bottom).',
            '<b>Copy for AI</b> - paste it in an AI chat (ChatGPT, Claude ...) - <b>Paste the answer</b> - <b>Save the changes</b>.',
            'Reload the page: the language is in the language selector of the view bar - with its flag, or its code.',
        ]],
        ['Find a file in the sub folders', [
            'Choose <b>Analyse</b> - the switch in the header of the card Folder content.',
            'Type in the search field next to it: the files of all sub folders are listed.',
        ]],
    ];
    $body6= '<div class="feNice">'.implode('', array_map(fn($s) => '<div class="feNiceSec"><h4>'.p2h_enc(lang('@'.$s[0])).'</h4><ol>'.
            implode('', array_map(fn($l) => '<li>'.lang('@'.$l).'</li>', $s[1])).'</ol></div>', $steps)).'</div>';

    // Questions and answers (tab): [question, answer]
    $faq= [
        ['Why are Rename and Delete grey?', 'You may only change files in your write folders. See the tab Your account - This folder.'],
        ['Where did my deleted file go?', 'Into the trash: program menu - Trash. There you can restore it.'],
        ['Why is my file called name (2)?', 'A file with the same name was already there, and the choice was New name (the field A taken name on the Transfer card). The message after the copy, move, unpack or upload lists the files that got a new name.'],
        ['Can a file be overwritten?', 'Yes - choose Overwrite in the field A taken name on the Transfer card, or answer Overwrite in the dialog of Stop and ask. The old file goes to the trash, so it can be restored.'],
        ['Why can I not upload a .php file?', 'Server scripts and hidden files are never uploaded or written - see the tab Limits.'],
        ['The upload of a big file fails', 'A file may be at most the size in the tab Limits - and the shared folder has a quota.'],
        ['Why are the folder sizes missing or slow?', 'Quick lists the folder at once. Analyse - or the calculator in the tree - counts the sizes, and big folders take time.'],
        ['I can not log in', 'After 10 failed logins from your address the account is blocked for 15 minutes. Wait - or ask an administrator.'],
        ['Can others see my files?', 'Everybody who is logged in sees the names of the folders and files. Download and preview only where the account may - see the tab Your account.'],
        ['The preview shows no content', 'The content is only shown where you may download the file - and some file types have no preview: see the tab File-viewers.'],
        ['Who sees what I do?', 'Administrators see an activity log: who did what, when and from where.'],
    ];
    $body7= '<div class="feNice">'.implode('', array_map(fn($q) => '<div class="feNiceSec"><h4>'.p2h_enc(lang('@'.$q[0])).'</h4><p class="feFaqA">'.p2h_enc(lang('@'.$q[1])).'</p></div>', $faq)).'</div>';

    // About (tab): the versions - and for administrators the server
    $about= [
        ['Folder-explorer', p2h_enc(FE_VERSION.' ('.FE_DATE.')')],
        ['php2html', p2h_enc(P2H_VERSION).' - Clever-Html-Engine'],
        ['Source code', '<a href="https://github.com/EV-soft/folder-explorer" target="_blank" rel="noopener">github.com/EV-soft/folder-explorer</a>'],
        ['Report a problem', '<a href="https://github.com/EV-soft/folder-explorer/issues" target="_blank" rel="noopener">'.$tt('GitHub - Issues').'</a>'],
        ['Made by', 'EV-soft - © 2019-2026'],
    ];
    if (fe_is_admin()) $about= array_merge($about, [
        ['PHP', p2h_enc(PHP_VERSION.' ('.PHP_SAPI.')')],
        ['Web server', p2h_enc((string)($_SERVER['SERVER_SOFTWARE'] ?? '-'))],
        ['PHP extensions', implode(' &nbsp; ', array_map(fn($x) => $yes(extension_loaded($x)).p2h_enc($x), ['zip', 'mbstring', 'fileinfo', 'json', 'openssl']))],
        ['Time limit of PHP', p2h_enc((string)ini_get('max_execution_time')).' s - '.$tt('extended up to').' '.(int)$fe_max_time.' s'],
    ]);
    $body8= '<div class="feKeysWrap"><div>'.$tbl2($about).(fe_is_admin() ? '<small>'.$tt('The server facts are only shown to administrators.').'</small>' : '').'</div></div>';

    $mouse= [   // Mouse and keys: [where, click, right-click / long press] - translated (_trans.sys.json)
        ['File name', 'File menu - resting the mouse on it opens it too', 'File menu'],
        ['File icon', 'Rest the mouse on it: preview of the file', 'File menu'],
        ['Folder name', 'Opens the folder', 'Folder menu'],
        ['Check box (Select)', 'Checks / unchecks the row', 'Menu of the row'],
        ['Size', 'Rest the mouse on it: the size in bytes', 'Menu of the row'],
        ['The table outside the rows', '', 'Program menu: trash, password, help, about, log out - administrators also: users, activity log, translations, restrictions'],
        ['Column heading', 'Sorts by the column', ''],
        ['Folder in the tree', 'Opens the folder - the calculator counts its size', ''],
        ['Card header', 'Folds / opens the card', 'Card menu: applies to all cards'],
        ['Grip in the card header', 'Drag: moves the card', ''],
        ['Corner of the card', 'Drag: the size - double-click: the original size', ''],
    ];
    $keys= [    // [keys, where, what]
        ['Ctrl+A', 'Table', 'Checks all rows'],
        ['Ctrl+Shift+A', 'Table', 'Unchecks all rows'],
        ['Delete', 'Table', 'Deletes the checked rows (none checked: the row you are on) - into the trash'],
        ['F2', 'Table', 'Renames the row you are on (or the one checked row)'],
        ['Enter | Space', 'File name, card header', 'Opens the file menu - folds / opens the card'],
        ['Menu key | Shift+F10', 'Row - outside the rows', 'Menu of the row - the program menu'],
        ['Arrow up / down', 'Menu', 'The next / previous item'],
        ['Esc', 'Menu, preview', 'Closes it (Tab closes a menu too)'],
        ['Alt+Arrow up / down', 'In a card', 'Moves the card up / down'],
        ['Arrows (Shift: 50 px), Home', 'Corner of the card', 'Changes the size - Home: the original size'],
        ['F11', 'Everywhere', 'Full screen on / off'],
    ];
    $cell= fn($t) => $t === '' ? '<span class="feKeysNo">-</span>' : p2h_enc(lang('@'.$t));
    $body3= '<div class="feKeysWrap"><div><h4>'.p2h_enc(lang('@Mouse')).'</h4><table class="feKeys"><tr><th>'.p2h_enc(lang('@Where')).'</th><th>'.p2h_enc(lang('@Click')).'</th>'.
            '<th>'.p2h_enc(lang('@Right-click / long press')).'</th></tr>'.
            implode('', array_map(fn($r) => '<tr><td><b>'.$cell($r[0]).'</b></td><td>'.$cell($r[1]).'</td><td>'.$cell($r[2]).'</td></tr>', $mouse)).'</table>'.
            '<small>'.p2h_enc(lang('@Touch screens: a tap is a click - a long press is a right-click.')).'</small></div>'.
            '<div><h4>'.p2h_enc(lang('@Shortcut keys')).'</h4><table class="feKeys"><tr><th>'.p2h_enc(lang('@Keys')).'</th><th>'.p2h_enc(lang('@Where')).'</th><th>'.p2h_enc(lang('@What')).'</th></tr>'.
            implode('', array_map(fn($r) => '<tr><td>'.implode(' / ', array_map(fn($k) => '<kbd>'.p2h_enc(preg_match('/^(Space|Menu key|Arrow|Alt\+Arrow|Arrows)/', trim($k)) ? lang('@'.trim($k)) : trim($k)).'</kbd>', explode(' | ', $r[0]))).'</td><td>'.$cell($r[1]).'</td><td>'.$cell($r[2]).'</td></tr>', $keys)).'</table>'.
            '<small>'.p2h_enc(lang('@Not while you type in a field - and not while a dialog is open.')).'</small></div></div>';
    htm_Tabs_(head:'', styl:'display: block;', rtrn:false);     // (the card caption says Manual)
    // The tabs of the manual - in the order people need them: [label, body, name, colour, colour in the dark theme] - one lightness, eight hues
    $manTabs= [
        ['@Nice to know:',   $body1, 'nice',  '#fdf6d8', '#38331f'],
        ['@Step by step:',   $body6, 'steps', '#e6f1fc', '#1f3140'],
        ['@Questions:',      $body7, 'faq',   '#e5f5ec', '#1f3629'],
        ['@Mouse and keys:', $body3, 'keys',  '#efeafb', '#2d2940'],
        ['@Your account:',   $body4, 'acc',   '#e2f3f3', '#1c3536'],
        ['@Limits:',         $body5, 'lim',   '#fbeee2', '#3b2d21'],
        ['@File-viewers:',   $body2, 'view',  '#f7e9f0', '#392a32'],
        ['@About:',          $body8, 'about', '#eceff2', '#2a2f36'],
    ];
    foreach ($manTabs as [$lb, $bd, $nm, $c])
        htm_Tab(labl:$lb, body:$bd, name:$nm, styl:'text-align: left; box-shadow: 3px 3px 6px 0px #ccc; padding: 5px; background-color: '.$c.';', bclr:$c.';', dflt:$nm === 'nice');
    echo '<style>'.implode('', array_map(fn($t) => '[data-theme="dark"] #tab_'.$t[2].', [data-theme="dark"] #'.$t[2].' { background-color: '.$t[4].' !important; border-bottom-color: '.$t[4].' !important; }', $manTabs)).
         '[data-theme="dark"] .p2hTabs .tabcontent { box-shadow: 3px 3px 6px 0px #000 !important; }'.
         '[data-theme="dark"] .p2hTabs .tab { background-color: #1b1f24; }</style>';
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
    htm_Page_Sect(body:FE_FOOTER);
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
        
