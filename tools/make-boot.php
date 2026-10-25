<?php   // tools/make-boot.php - makes the boot file: dist/folder-explorer-<version>-boot.zip with one file, folder-explorer-install.php
// The installer (tools/fe-install.tpl.php) gets the program files packed at its end: every file in git but .gitignore and tools/.
// Run it in the repository after a release:   php tools/make-boot.php   - then upload both ZIPs to the release:
// gh release upload v<version> dist/folder-explorer-<version>-boot.zip dist/folder-explorer-boot.zip
// Open folder-explorer-install.php on a web server: it unpacks the program in a new folder and makes the first administrator.
declare(strict_types=1);
chdir(dirname(__DIR__));

$git= trim((string)shell_exec('git rev-parse --show-toplevel 2>'.(DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null')));
if ($git === '') exit("Not a git repository\n");
if (!preg_match("/\\\$DocVer='([0-9.]+)'/", (string)file_get_contents('folder-explorer.php'), $m)) exit("No \$DocVer in folder-explorer.php\n");
$ver= $m[1];
$dirty= trim((string)shell_exec('git status --porcelain --untracked-files=no'));
if ($dirty !== '') echo "Note: changes not committed - the files as they are now are packed:\n$dirty\n";

$files= ['files' => [], 'sha1' => [], 'version' => $ver];
$bytes= 0;
foreach (preg_split('/\R/', trim((string)shell_exec('git ls-files'))) as $f) {
    if ($f === '' || $f === '.gitignore' || str_starts_with($f, 'tools/') || !is_file($f)) continue;
    $c= (string)file_get_contents($f);
    $files['files'][$f]= gzdeflate($c, 9);
    $files['sha1'][$f]=  sha1($c);
    $bytes+= strlen($c);
}
$tpl= (string)file_get_contents('tools/fe-install.tpl.php');
if (!str_ends_with(rtrim($tpl), '__halt_compiler();')) exit("The template must end with __halt_compiler();\n");
$php= str_replace('{{VERSION}}', $ver, rtrim($tpl))."\n".serialize($files);

@mkdir('dist');
$out= 'dist/folder-explorer-install.php';
file_put_contents($out, $php);
$lint= shell_exec('"'.PHP_BINARY.'" -l '.escapeshellarg($out).' 2>&1');
if (!str_contains((string)$lint, 'No syntax errors')) exit("The installer has an error:\n$lint");

$zipName= 'dist/folder-explorer-'.$ver.'-boot.zip';
@unlink($zipName);
$z= new ZipArchive();
if ($z->open($zipName, ZipArchive::CREATE) !== true) exit("Could not make $zipName\n");
$z->addFile($out, 'folder-explorer-install.php');
$z->close();
copy($zipName, 'dist/folder-explorer-boot.zip');     // The same with a fixed name: README links to releases/latest/download/folder-explorer-boot.zip - upload both to the release

printf("Folder-explorer %s: %d files (%.1f MB) packed - %s %.1f MB, %s %.1f MB\n", $ver, count($files['files']), $bytes / 1048576,
       $out, filesize($out) / 1048576, $zipName, filesize($zipName) / 1048576);
