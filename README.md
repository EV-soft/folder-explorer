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

### What is new in 2.1.0:
- **Transfer with a basket (source -> destination)** replaces the exchange area card: collect files and folders from any folder in
  the basket, go to the destination folder, and *Copy here* / *Move here* - or *Download as ZIP* to your PC. Upload from your PC goes to
  the folder you are in, and a ZIP file is unpacked there with *Unpack here*. See *Transfer* below.
- **File features below the table work now** (were *Future file features*): select all / none / inverse, put the checked rows in the
  basket, and for the checked rows: COPY / MOVE / Create ZIP to the *Destination* folder (empty = this folder; the folders you may
  write in are suggested), DELETE (folders with their content - protected files and the folders they are in are kept), Property
  (number and size) and DOWNLOAD (one file directly, more as a ZIP). UPLOAD opens the file chooser of the Transfer card.
- **Write folders per account:** the 4th field of `$auth_users` lists the folders an account may write in.
- **File preview:** rest the mouse on a file icon in *Folder-Details*:
  - images, video (plays muted), audio (length), fonts (sample text) - everywhere;
  - code and text with syntax colours and line numbers (the first 40 lines), PDF, the file list of a ZIP and the text of Word / ODT -
    only where you may read the content (your write folders and the shared folder). Elsewhere the preview says so.
  - tables: CSV / TSV, Excel (xlsx) and Calc (ods) - the first 40 rows; the text of PowerPoint (pptx), Impress (odp), RTF and e-books (epub);
    the file list of tar, tar.gz / tgz (and a single .gz: its text); any other file you may read: its first 512 bytes (hex) and its type.
  - In Analyse mode the file information is shown below the preview. `fe-preview.js` has no external library.
- **File menu:** rest the mouse on a file name: View, Download, File properties, Put in / take out of the basket - and in a folder
  you may write in: Rename / move (also into a subfolder: `sub/name.ext`), Copy file, Create ZIP, Unpack here (a ZIP) and Delete file.
  Actions you may not do are greyed, with the reason below. Nothing is ever overwritten.
- **Program menu** (right-click the table outside a row): Users ... (administrators), Change my password, Remove / restore restrictions
  (administrators), Help, About, Log out.
- **Users** (administrators): create, change and delete accounts - right (read / read + write), write folders, administrator, password.
  Saved in `fe-users.php` next to the program (password hashes only; a PHP file, it shows nothing in the browser). When it exists it replaces
  `$auth_users` and `$fe_admins` in `folder-explorer.php`. You can not delete yourself or remove the last administrator.
- **Change my password:** every account that may write, and administrators (not a read only demo account).
- **Right-click a row:** the same menu - for a file, and for a folder: Open the folder, Download as ZIP, Folder properties, basket,
  Rename / move folder, Copy folder, Create ZIP, Synchronize to ..., Delete folder - and New folder where you may write.
  (The old demo menu and the two demo buttons beside a folder name in Analyse mode are removed.)
  *Synchronize to ...* copies what is missing or newer in the folder to a destination folder; an older file there is replaced, nothing is deleted.
- **Folder sizes in the tree are shown at once:** closed folders are counted afterwards in the browser, two at a time
  (`fe-tree.js`, `?fsz=`), and remembered 15 minutes - a whole disk no longer holds the page back.
  Only the current folder and its sub folders are counted at once; the rest of the tree is counted when you click its calculator
  icon, or *Count all* (at BASE everything is counted as before).
- **Long work is not stopped after 30 s:** Analyse, the folder sizes of the tree and copy / ZIP of big folders extend PHP's time
  limit while they work - up to `$fe_max_time` (default 300 s). The web server may still have its own limit.
- **No session timeout:** an open page keeps the login alive by itself (`$gbl_SessionKeep= true`, the default).
  You are logged out when you log out, or when no page has been open for the session lifetime.
  Set `$gbl_SessionKeep= false;` to get the old behaviour: logged out after `session.gc_maxlifetime` without activity, with a warning dialog.
- **Login and safety:** at most 10 failed logins per address and account in 15 minutes, the slider is checked on the server,
  a password change logs out the other sessions, log out needs the session token, a new CSRF token at login, and after login
  you are back in the folder you asked for.
- **Keyboard, touch and dark theme:** all menus by keyboard (Enter, menu key, Shift+F10, arrows, Esc), by a tap or a long press
  on touch screens; every part of the program follows the light / dark theme.
- **More file types in the preview:** tables (CSV, TSV, Excel, Calc - with dates), PowerPoint, Impress, RTF, e-books, tar archives,
  UTF-16 text, and the first bytes (hex) of any other file you may read.
- **Hex view and Develop cards removed;** the demo leftovers (program menu items, "forgotten password", e-mail field) are gone or work.
- The unused *Base-path* field on the login page and the setting `$locked_path` are removed.
- Bundled php2html (Clever-Html-Engine) **1.7.5**.

### Updating from 2.0.0:
1. Copy all the files over the old ones, also the new `fe-preview.js`, `fe-tree.js` and `_trans.sys.json`. Keep your `folder-explorer.php` settings.
2. `$auth_users` got a 4th field: the write folders of each account (`['']` = everything, missing = the shared folder `_exchange`).
   Or make the accounts in the program: program menu (right-click the table) - Users. They are then saved in `fe-users.php`.
3. The Exchange area card is now the *Transfer* card (basket, destination, upload).

### What is new in 2.0.0 (update from 1.4.0):
- **Security:** browsing locked to `$fe_root_path`, login before any data is read, escaped output, CSRF token on every POST.
- **Exchange area** for upload / download / delete between the server and your PC (drag and drop, folders, batches).
- **Remove restrictions** for administrators (`$fe_admins`).
- **Folder tree** with levels, connector lines and the used space of every folder.
- **Quick / Analyse** in one toggle button (were *Simple / Advanced*); search and mode in the card header.
- **View bar** with the logged in user and logout (the old Language and green Logout buttons are removed).
- Library hints instead of browser tooltips everywhere; sizes in columns with 2 decimals.
- Bundled php2html upgraded from 1.4.2 to 1.7.4. PHP 8.1 or newer is now required.

### Updating from 1.4.0:
1. Keep a copy of your `folder-explorer.php` (your `$auth_users`).
2. Copy all the new files over the old ones, also the folder `_assets/` and the new `fe-upload.js`, `favicon.svg`, `_trans.sys.json`.
3. Put your `$auth_users` lines back into the new `folder-explorer.php`. The 1.4 setting `$locked_path` is not used -
   use `$fe_root_path` (see *Security / configuration*).
4. The folders `_cache/` and `_exchange/` are created automatically - the web server must be able to write them.
5. 2.1: add the write folders as the 4th field of each account in `$auth_users` (see *Transfer*).

### Transfer - basket, write folders, upload and download:
- **Write folders:** the 4th field of an account in `$auth_users` lists the folders it may write in, below `$fe_root_path`
  (`''` = all of it). Default: `fe-admin` everything, `fe-user` `_exchange`, `visitor` nothing (read only).
  An account with `w` without the 4th field writes in the shared folder `$fe_exchange_path` (`_exchange/`).
  A write folder that does not exist is created with a `.htaccess` that blocks direct web access. An existing folder is used as it is -
  files uploaded to a folder the web server shows can be opened directly in the browser, so choose such folders with care.
- **Read (download, preview of the content):** in the account's write folders, and in the shared folder for accounts with `r`.
  Server scripts, server config and hidden files are never served (except to an administrator without restrictions).
- **Basket:** put files and folders in it from any folder you can see - with the file menu, or check rows and click
  *Put the checked rows in the basket*. Then choose the destination:
  - *Copy here* - everything you may read is copied (folders with their content); the basket is kept.
  - *Move here* - from folders you may write in; moved items leave the basket.
  - *Download as ZIP* - one ZIP file with everything in the basket you may read.
  - Nothing is ever overwritten: `name (2).ext`, `folder (2)`.
- **Destination:** one field in the Transfer card for upload and the basket - the folder you are in when you may write there,
  else your first write folder. Choose one of your write folders, or type a folder below it (`folder-explorer/_exchange/photos`).
- **Upload:** to the destination, **drag files or whole folders** from your PC onto the drop zone, or use *Choose files* /
  *Choose a folder*. A list shows every file with a check box; files the server would refuse are marked with the reason and not sent.
  Folder structure is kept, and large selections are sent in batches that fit PHP's `max_file_uploads` / `post_max_size`
  (`fe-upload.js`; without JavaScript the plain file field still works).
- **Unpack a ZIP:** file menu *Unpack here* - in the folder of the ZIP. Refused: `../` and absolute paths, hidden files and folders,
  server scripts; at most 20000 files; never over an existing file.
- Server scripts (`.php`, `.phtml`, `.phar`, `.cgi`, `.py`, `.sh` ..., also as a double extension like `x.php.jpg`),
  server config and hidden files are refused everywhere.
- Limits: `$fe_max_upload` per file (20 MB); `$fe_exchange_quota` (200 MB) for the shared folder only -
  PHP's `upload_max_filesize` and `post_max_size` also apply.

### Settings (near the top of `folder-explorer.php` - each can also be set before the file is included):
| Setting | Default | Meaning |
|---|---|---|
| `$fe_root_path` | the program folder | browsing never goes above it |
| `$fe_exchange_path` | `_exchange` | the shared folder (closed for direct web access) |
| `$fe_max_upload` | 20 MB | max size per uploaded file (PHP's own limits also apply) |
| `$fe_exchange_quota` | 200 MB | max total size of the shared folder |
| `$fe_admins` | `['fe-admin']` | administrators (replaced by `fe-users.php` when it exists) |
| `$fe_unlocked_root` | the folder above the web root | what an administrator sees without restrictions |
| `$fe_users_file` | `fe-users.php` | the accounts made in the program (program menu - Users) |
| `$fe_demo_login` | `['visitor', 'xxxxxxxx']` | the login form suggests this demo account - only while its password is still this one |
| `$fe_max_time` | 300 s | long work (Analyse, sizes, copy, ZIP) may extend PHP's time limit up to this |
| `$gbl_SessionKeep` | `true` | an open page keeps the login alive |

### Login and safety:
- At most 10 failed logins per address and account in 15 minutes; the slider *Are you human?* is checked on the server too.
- A password change logs out the other sessions of the account. Log out needs the token of the session (a link on another site can not log you out).
- Accounts and passwords: program menu (right-click the table) - Users / Change my password.

### Keyboard, touch, theme:
- Tab to a file name and Enter, or the menu key / Shift+F10 on a row: the file / folder menu; outside a row: the program menu.
  Arrow up / down in the menu, Esc or Tab closes it.
- Touch screens: tap a file name, or press long on a row (or the table) - the same menus.
- The light / dark theme of the view bar applies to all parts of the program.

### Remove restrictions (administrators only):
- Accounts in `$fe_admins` (default `fe-admin`) get a lock icon in the *Transfer* card. For that session only:
  browse, download and write everywhere below `$fe_unlocked_root` (default: the folder above the web root, e.g. `/home/<account>`),
  any file type, and no quota or size limit of its own (PHP's limits still apply).
- Still refused: hidden files (`.htaccess`, `.user.ini` ...).
- A red banner shows while the restrictions are removed; *Restore restrictions*, logout or a new login ends it.

### Requirements:
- PHP 8.1 or newer. Tested on PHP 8.3.
- `short_open_tag` is **not** required.
- Built with [Clever-Html-Engine / PHP2HTML](https://github.com/EV-soft/Clever-Html-Engine) **1.7.5** (bundled).

### Installation:
1. Copy the files to a folder on your web server.
2. Open `folder-explorer.php` in the browser.
3. The bundled `php2html.lib.php` (1.7.5) is always used.
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
- `$gbl_SessionKeep` (default `true`): an open page keeps the session alive. On a shared computer, set it to `false`.
