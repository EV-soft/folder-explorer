# folder-explorer

A browser that focuses on the disk space that folders takes up.

Developed with the [PHP2HTML library.](https://github.com/EV-soft/ev-soft.github.io)

The Folder Explorer project is built with the PHP2HTML library and shows how html output is generated, especially with regard to the advanced function htm_table ()

Look / try a [demo here](https://ev-soft.dk/foldexpl/folder-explorer.php) It runs on PHP 8.3


### Description:
With Folder Explorer, you can browse files and folders on a web server and see used space.

The file list are displayed in a table, where you can sort and filter the data.

**Analyse** mode: content in subfolders is analyzed, so that file-data for the entire subtree can be displayed.

For example, you can search for a filename in the subtrees.

File/Folder space is also displayed graphically.

**Quick** mode: it speeds up and works like a typical file-browser without analyzing subfolder content.
One button in the header of the *Folder-Details* card switches between the two modes (the search field is beside it).

Context menus can be activated by right-clicking on a file or folder row in the table (still demo).

### Folder tree:
- Drawn connector lines and folder icons; the level number of each folder at the far left and a level axis below the tree.
- Only the current folder is shown in bold with an open folder icon.
- The card tool *hdd* icon shows the used space of every folder as a column: size, a share bar and the total above the tree.
  The bars can show the share of the parent folder or of BASE.
- The card can be dragged to its full height with the size grip.

### What is new in 2.0.0 (update from 1.4.0):
- **Security:** browsing locked to `$fe_root_path`, login before any data is read, escaped output, CSRF token on every POST.
- **Exchange area** for upload / download / delete between the server and your PC (drag and drop, folders, batches).
- **Remove restrictions** for administrators (`$fe_admins`).
- **Folder tree** with levels, connector lines and the used space of every folder.
- **Quick / Analyse** in one toggle button (were *Simple / Advanced*); search and mode in the card header.
- **View bar** with the logged in user and logout (the old Language and green Logout buttons are removed).
- Library hints instead of browser tooltips everywhere; sizes in columns with 2 decimals.
- Bundled php2html upgraded from 1.4.2 to 1.7.4 (+ fixes that come in 1.7.5). PHP 8.1 or newer is now required.

### Updating from 1.4.0:
1. Keep a copy of your `folder-explorer.php` (your `$auth_users`).
2. Copy all the new files over the old ones, also the folder `_assets/` and the new `fe-upload.js`, `favicon.svg`, `_trans.sys.json`.
3. Put your `$auth_users` lines back into the new `folder-explorer.php`. The 1.4 setting `$locked_path` is gone -
   use `$fe_root_path` (see *Security / configuration*).
4. The folders `_cache/` and `_exchange/` are created automatically - the web server must be able to write them.

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

### Remove restrictions (administrators only):
- Accounts in `$fe_admins` (default `fe-admin`) get a button **Remove restrictions**. For that session only:
  browse and download everything below `$fe_unlocked_root` (default: the folder above the web root, e.g. `/home/<account>`),
  upload any file type to the exchange area, and no quota or size limit of its own (PHP's limits still apply).
- Still refused: hidden files (`.htaccess`, `.user.ini` ...) - the `.htaccess` of the exchange area is what keeps an
  uploaded script from being run from the web. Upload and delete stay inside the exchange area.
- A red banner shows while the restrictions are removed; *Restore restrictions*, logout or a new login ends it.

### Requirements:
- PHP 8.1 or newer. Tested on PHP 8.3.
- `short_open_tag` is **not** required.
- Built with [Clever-Html-Engine / PHP2HTML](https://github.com/EV-soft/Clever-Html-Engine) **1.7.4** (bundled).

### Installation:
1. Copy the files to a folder on your web server.
2. Open `folder-explorer.php` in the browser.
3. The bundled `php2html.lib.php` (1.7.4) is always used.
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
