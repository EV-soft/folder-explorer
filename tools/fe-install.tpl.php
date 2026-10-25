<?php   // folder-explorer-install.php - Folder-explorer {{VERSION}}: unpacks the program in a new folder and makes the first administrator
// Made by tools/make-boot.php - the program files are packed at the end of this file (after __halt_compiler).
// Open it in the browser, fill in the form - and it deletes itself when the program is installed.
declare(strict_types=1);
const FE_BOOT_VERSION= '{{VERSION}}';

session_start();
if (empty($_SESSION['fe_boot_t'])) $_SESSION['fe_boot_t']= bin2hex(random_bytes(16));
$enc= fn($s) => htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$here= str_replace('\\', '/', __DIR__);

// The packed files: [version, files => [path => deflated content], sha1 => [path => sha1 of the content]]
function fe_boot_pack(): array {
    static $p= null;
    if ($p === null) {
        $raw= file_get_contents(__FILE__, false, null, __COMPILER_HALT_OFFSET__);
        $p=   is_string($raw) ? @unserialize(ltrim($raw, "\r\n"), ['allowed_classes' => false]) : false;
        if (!is_array($p) || !isset($p['files'])) $p= [];
    }
    return $p;
}

// The checks before anything is done
$checks= [
    ['PHP 8.1 or newer', PHP_VERSION_ID >= 80100, 'PHP '.PHP_VERSION],
    ['mbstring (required)', extension_loaded('mbstring'), extension_loaded('mbstring') ? 'yes' : 'missing - ask your web host'],
    ['zlib (to unpack this file)', function_exists('gzinflate'), function_exists('gzinflate') ? 'yes' : 'missing'],
    ['The folder may be written', is_writable(__DIR__), is_writable(__DIR__) ? $here : 'no write access in '.$here],
    ['The program is in this file', count(fe_boot_pack()['files'] ?? []) > 0, count(fe_boot_pack()['files'] ?? []).' files'],
    ['zip (optional: ZIP in the program)', true, extension_loaded('zip') ? 'yes' : 'missing - the ZIP functions are not shown'],
    ['fileinfo (optional)', true, extension_loaded('fileinfo') ? 'yes' : 'missing'],
];
$ok= !in_array(false, array_column($checks, 1), true);

$err= [];  $done= null;
$v= ['dir' => 'folder-explorer', 'user' => 'admin', 'assets' => '', 'del' => '1'];
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    foreach ($v as $k => $d) $v[$k]= is_string($_POST[$k] ?? null) ? trim($_POST[$k]) : '';
    $pw= is_string($_POST['pw'] ?? null) ? $_POST['pw'] : '';  $pw2= is_string($_POST['pw2'] ?? null) ? $_POST['pw2'] : '';
    if (!hash_equals($_SESSION['fe_boot_t'], (string)($_POST['t'] ?? '')))  $err[]= 'The form has expired - load the page again.';
    elseif (!$ok)                                                         $err[]= 'A check above failed.';
    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$/', $v['dir']))     $err[]= 'The folder name: letters, digits . _ - (not first: . _ -)';
    if (!preg_match('/^[A-Za-z0-9._-]{2,32}$/', $v['user']))               $err[]= 'The user name: 2-32 letters, digits . _ -';
    if (mb_strlen($pw) < 8)                                                $err[]= 'The password must have at least 8 characters.';
    elseif ($pw !== $pw2)                                                  $err[]= 'The two passwords are not the same.';
    $target= __DIR__.'/'.$v['dir'];
    if (!$err && file_exists($target) && (!is_dir($target) || count(scandir($target)) > 2)) $err[]= 'The folder "'.$v['dir'].'" is there already (and not empty) - choose another name.';
    if (!$err) {
        if (!is_dir($target) && !@mkdir($target, 0755)) $err[]= 'The folder could not be made.';
        $pack= fe_boot_pack();  $n= 0;
        foreach ($err ? [] : $pack['files'] as $path => $data) {
            if (!is_string($path) || $path === '' || $path[0] === '/' || str_contains($path, '..') || str_contains($path, '\\')) continue;   // Only plain relative paths
            if (str_starts_with($path, '_assets/') && $v['assets'] !== '1') continue;                                              // The local libraries: only when asked
            $content= gzinflate($data);
            if ($content === false || sha1($content) !== ($pack['sha1'][$path] ?? '')) { $err[]= 'Damaged in this file: '.$path;  break; }
            if (!is_dir(dirname($target.'/'.$path)) && !@mkdir(dirname($target.'/'.$path), 0755, true)) { $err[]= 'Could not make the folder of '.$path;  break; }
            if (@file_put_contents($target.'/'.$path, $content, LOCK_EX) === false) { $err[]= 'Could not write '.$path;  break; }
            $n++;
        }
        if (!$err && $v['assets'] === '1') {        // Local libraries: no CDN (no internet access)
            $fe= $target.'/folder-explorer.php';  $src= (string)file_get_contents($fe);
            $req= "require_once (__DIR__.'/php2html.lib.php');";
            if (str_contains($src, $req)) file_put_contents($fe, preg_replace('/'.preg_quote($req, '/').'/', "\$gbl_LibSource= '1';      // Local libraries (_assets) - set by the installer\n".$req, $src, 1));
        }
        if (!$err) {                                // The first administrator - write folder '' = everything below the program folder
            $users= ['users' => [$v['user'] => [password_hash($pw, PASSWORD_DEFAULT), 'root', 'rwx', ['']]], 'admins' => [$v['user']]];
            $code=  "<?php // Folder-explorer accounts - written by the installer. Password hashes only.\nreturn ".var_export($users, true).";\n";
            if (@file_put_contents($target.'/fe-users.php', $code, LOCK_EX) === false) $err[]= 'Could not write fe-users.php';
        }
        if (!$err) {
            $gone= $v['del'] === '1' && @unlink(__FILE__);
            $done= ['n' => $n, 'dir' => $v['dir'], 'gone' => $gone];
            $_SESSION['fe_boot_t']= bin2hex(random_bytes(16));
        }
    }
}
$url= rawurlencode($v['dir']).'/folder-explorer.php';
?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Install Folder-explorer <?= $enc(FE_BOOT_VERSION) ?></title>
<style>
  :root { color-scheme: light dark; --bg: #f4f6f8; --card: #fff; --line: #d0d7de; --txt: #1f2328; --ok: #1a7f37; --bad: #b00020; --acc: #0b5cad; }
  @media (prefers-color-scheme: dark) { :root { --bg: #15181c; --card: #1f2328; --line: #3a4048; --txt: #e6edf3; --ok: #4ac26b; --bad: #ff6b81; --acc: #6cb6ff; } }
  body { margin: 0; padding: 16px; background: var(--bg); color: var(--txt); font: 15px/1.5 system-ui, sans-serif; }
  main { max-width: 640px; margin: 24px auto; background: var(--card); border: 1px solid var(--line); border-radius: 10px; padding: 20px 24px; }
  h1 { font-size: 22px; margin: 0 0 4px; }  h2 { font-size: 16px; margin: 20px 0 6px; }  small { opacity: .75; }
  table { border-collapse: collapse; width: 100%; }  td { padding: 3px 6px; border-bottom: 1px solid var(--line); vertical-align: top; }
  .ok { color: var(--ok); font-weight: 600; }  .bad { color: var(--bad); font-weight: 600; }
  label { display: block; margin: 10px 0 2px; font-weight: 600; }
  input[type=text], input[type=password] { width: 100%; box-sizing: border-box; padding: 7px; border: 1px solid var(--line); border-radius: 6px; background: var(--bg); color: var(--txt); font: inherit; }
  .chk { font-weight: normal; }  button, .btn { margin-top: 16px; padding: 8px 18px; border: 0; border-radius: 6px; background: var(--acc); color: #fff; font: inherit; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
  .msg { padding: 10px 12px; border-radius: 6px; margin: 12px 0; border: 1px solid; }  .msg.bad { border-color: var(--bad); }  .msg.ok { border-color: var(--ok); }
</style></head><body><main>
<h1>Install Folder-explorer <?= $enc(FE_BOOT_VERSION) ?></h1>
<small>A web file manager - <a href="https://github.com/EV-soft/folder-explorer">github.com/EV-soft/folder-explorer</a></small>
<?php if ($done): ?>
  <div class="msg ok"><b>Installed:</b> <?= (int)$done['n'] ?> files in the folder <b><?= $enc($done['dir']) ?></b> - and the administrator <b><?= $enc($v['user']) ?></b>.<br>
  <?= $done['gone'] ? 'This installer has deleted itself.' : '<span class="bad">This installer is still here - delete <code>'.$enc(basename(__FILE__)).'</code> from the server now: anybody could use it to make an account of their own.</span>' ?></div>
  <a class="btn" href="<?= $enc($url) ?>">Open Folder-explorer</a>
  <h2>Next</h2>
  <ul><li>Log in as <b><?= $enc($v['user']) ?></b> with your password.</li>
      <li>More accounts: right-click the table - <i>Users</i>.</li>
      <li>The program shows the folder <b><?= $enc($done['dir']) ?></b> and its sub folders. Another folder: set <code>$fe_root_path</code> near the top of <code>folder-explorer.php</code>.</li>
      <li>On nginx: deny direct access to <code>_exchange/</code>, <code>.fe-trash/</code> and the write folders in the server configuration (the <code>.htaccess</code> files only work on Apache / LiteSpeed).</li></ul>
<?php else: ?>
  <h2>1. Checks</h2>
  <table><?php foreach ($checks as [$what, $good, $info]) echo '<tr><td>'.$enc($what).'</td><td class="'.($good ? 'ok' : 'bad').'">'.($good ? '&#10003;' : '&#10007;').'</td><td>'.$enc($info).'</td></tr>'; ?></table>
  <?php if ($err) echo '<div class="msg bad">'.implode('<br>', array_map($enc, $err)).'</div>'; ?>
  <?php if ($ok): ?>
  <h2>2. Install</h2>
  <form method="post" autocomplete="off">
    <input type="hidden" name="t" value="<?= $enc($_SESSION['fe_boot_t']) ?>">
    <label for="dir">New folder (next to this file)</label>
    <input type="text" id="dir" name="dir" value="<?= $enc($v['dir']) ?>" required pattern="[A-Za-z0-9][A-Za-z0-9._\-]{0,63}">
    <small><?= $enc($here) ?>/<b>&hellip;</b> - it must not exist, or be empty</small>
    <label for="user">The administrator</label>
    <input type="text" id="user" name="user" value="<?= $enc($v['user']) ?>" required pattern="[A-Za-z0-9._\-]{2,32}" autocomplete="username">
    <label for="pw">Password (at least 8 characters)</label>
    <input type="password" id="pw" name="pw" required minlength="8" autocomplete="new-password">
    <label for="pw2">The password again</label>
    <input type="password" id="pw2" name="pw2" required minlength="8" autocomplete="new-password">
    <label class="chk"><input type="checkbox" name="assets" value="1"<?= $v['assets'] === '1' ? ' checked' : '' ?>> Local libraries (<code>_assets</code>, 2.4 MB) - only when the server or the users have no internet access</label>
    <label class="chk"><input type="checkbox" name="del" value="1"<?= $v['del'] === '1' || $_SERVER['REQUEST_METHOD'] !== 'POST' ? ' checked' : '' ?>> Delete this installer when it is done (recommended)</label>
    <button type="submit">Install</button>
  </form>
  <?php endif; ?>
<?php endif; ?>
</main></body></html>
<?php __halt_compiler();
