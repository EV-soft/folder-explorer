# folder-explorer

A browser that focuses on the disk space that folders takes up.

Developed with the [PHP2HTML library.](https://github.com/EV-soft/ev-soft.github.io)

The Folder Explorer project is built with the PHP2HTML library and shows how html output is generated, especially with regard to the advanced function htm_table ()

Look / try a [demo here](https://ev-soft.dk/foldexpl/folder-explorer.php) It runs on PHP 8.3


### Description:
With Folder Explorer, you can browse files and folders on a web server and see used space.

The file list are displayed in a table, where you can sort and filter the data.

In advanced mode: content in subfolders is analyzed, so that file-data for the entire subtree can be displayed.

For example, you can search for a filename in the subtrees.

File/Folder space is also displayed graphically.

In simple mode: it speeds up and works like a typical file-browser without analyzing subfolder content.

Recently added context menus that you can activate by right-clicking on a file or folder row in the table (still demo).

### Exchange area - files between the server and your PC:
- The folder `_exchange/` (set `$fe_exchange_path` to change it) is the **only** place where files can be
  uploaded from your PC, downloaded to your PC and deleted. Everything else is read-only.
- Upload: **drag files or whole folders** from your PC onto the drop zone, or use *Choose files* / *Choose a folder*.
  A list shows every file with a check box; files the server would refuse are marked with the reason and not sent.
  Folder structure is kept, and large selections are sent in batches that fit PHP's `max_file_uploads` / `post_max_size`
  (`fe-upload.js`; without JavaScript the plain file field still works).
- It is created automatically with a `.htaccess` that blocks direct web access: files are only served
  through `folder-explorer.php?dl=...`, for logged-in users.
- Rights per account in `$auth_users` (3rd field): `r` = download, `w` = upload and delete.
  The public demo account `visitor` has `r` only.
- Server scripts (`.php`, `.phtml`, `.phar`, `.cgi`, `.py`, `.sh` ..., also as a double extension like `x.php.jpg`),
  server config and hidden files are refused. Existing files are never overwritten (`name (2).ext`).
- Limits: `$fe_max_upload` per file (20 MB) and `$fe_exchange_quota` in total (200 MB) -
  PHP's `upload_max_filesize` and `post_max_size` also apply.

### Requirements:
- PHP 8.1 or newer. Tested on PHP 8.3.
- `short_open_tag` is **not** required.
- Built with [Clever-Html-Engine / PHP2HTML](https://github.com/EV-soft/Clever-Html-Engine) **1.7.3** (bundled).

### Installation:
1. Copy the files to a folder on your web server.
2. Open `folder-explorer.php` in the browser.
3. The bundled `php2html.lib.php` (1.7.3) is always used.
4. The web server must be able to write the folder `_cache/` (created automatically) - the library caches its CSS/JS there.

### Security / configuration:
- Browsing is restricted to `$fe_root_path` (default: the folder containing `folder-explorer.php`)
  and its subfolders. Paths outside it (also via `../`) fall back to the root.
  Set `$fe_root_path` near the top of `folder-explorer.php` to change it.
- Accounts are defined in `$auth_users` as bcrypt hashes. To create a new account,
  try to log in with the wanted name and password: the error message shows a
  `name: hash` line that you can copy into `$auth_users`.
- Change or remove the demo accounts before you put the program on a public server.
- All POST forms carry a CSRF token (php2html 1.7) - a POST without a valid token is rejected with 403.
- The full variable dump in the "Develop" card is only shown for `fe-admin` with `?debug` in the URL.
