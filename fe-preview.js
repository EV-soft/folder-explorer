/* fe-preview.js v:1.0.0 d:2026-10-19 i:evs  -  Folder-explorer: preview of a file when the mouse rests on its icon in Folder-Details
 * The icon (i.feFileIc) carries: data-pk = kind, data-pv = preview address (?pv=...), data-pn = name, data-ps = size, data-pm = modified
 * Kinds: img, video, audio, font (shown everywhere) - txt, pdf, zip, doc (only where the file may be downloaded) - lock (text without that right)
 * Code is shown with syntax colours and line numbers (a small built-in highlighter - no external library).
 */
(function () {
    'use strict';
    var me = document.currentScript, T = {};
    try { T = JSON.parse(me.getAttribute('data-t')); } catch (e) { T = {}; }
    var box = null, cur = null, timer = null, cache = {}, fontNo = 0;
    var LINES = 40;

    function el(tag, cls, txt) {
        var e = document.createElement(tag);
        if (cls) e.className = cls;
        if (txt != null) e.textContent = txt;
        return e;
    }
    function esc(s) { return s.replace(/[&<>]/g, function (c) { return c === '&' ? '&amp;' : c === '<' ? '&lt;' : '&gt;'; }); }
    function ext(name) { var m = /\.([^.\/]+)$/.exec(name || ''); return m ? m[1].toLowerCase() : ''; }
    function time(s) {
        if (!isFinite(s)) return '';
        s = Math.round(s);
        var h = Math.floor(s / 3600), m = Math.floor(s / 60) % 60, x = s % 60;
        return (h ? h + ':' + (m < 10 ? '0' : '') : '') + m + ':' + (x < 10 ? '0' : '') + x;
    }

    // ---------- Syntax colours ----------
    function words(list, flags) { return new RegExp('\\b(?:' + list.split(' ').join('|') + ')\\b', 'y' + (flags || '')); }
    var STR  = [['s', /"(?:[^"\\\n]|\\.)*"?/y], ['s', /'(?:[^'\\\n]|\\.)*'?/y]];
    var NUM  = ['n', /\b(?:0x[\da-f]+|\d+(?:\.\d+)?(?:e[+-]?\d+)?)\b/iy];
    var KW_C = words('abstract and array as async await break case catch class clone const continue declare default delete do echo else elseif ' +
               'enum export extends false final finally fn for foreach from func function global goto if implements import in include ' +
               'include_once instanceof insteadof interface is isset let match namespace new null or package print private protected ' +
               'public readonly require require_once return self static struct super switch this throw trait true try type typeof ' +
               'unset use var void while with yield int float string bool boolean char double long short unsigned mixed undefined NaN');
    var KW_H = words('if then else elif fi for while do done case esac in function return local export def class import from as pass ' +
               'lambda try except finally raise with yield not and or is None True False end begin module require unless until elsif ' +
               'sub my our use print echo set unset true false null yes no on off');
    var KW_S = words('select from where insert into values update set delete create table alter drop index primary key foreign ' +
               'references not null default and or in is like join left right inner outer on as group by order having limit offset ' +
               'union all distinct case when then else end if exists integer int varchar text date datetime decimal begin commit ' +
               'rollback trigger view auto_increment unique check count sum avg min max engine charset collate', 'i');
    var LANG = {
        c:    [['c', /\/\*[\s\S]*?(?:\*\/|$)/y], ['c', /\/\/.*/y], STR[0], STR[1], ['s', /`(?:[^`\\]|\\.)*`?/y],
               ['v', /\$[a-z_]\w*/iy], ['k', KW_C], NUM],
        css:  [['c', /\/\*[\s\S]*?(?:\*\/|$)/y], ['c', /\/\/.*/y], STR[0], STR[1], ['n', /#[\da-f]{3,8}\b/iy],
               ['p', /[\w-]+(?=\s*:[^:])/y], ['k', /@[\w-]+/y], ['v', /\$[\w-]+|--[\w-]+/y],
               ['n', /-?\b\d+(?:\.\d+)?(?:px|em|rem|%|vh|vw|s|ms|deg|fr)?/y]],
        hash: [['c', /#.*/y], STR[0], STR[1], ['v', /\$\{?[\w]+\}?/y], ['k', KW_H], NUM],
        sql:  [['c', /--.*/y], ['c', /#.*/y], ['c', /\/\*[\s\S]*?(?:\*\/|$)/y], STR[0], STR[1], ['s', /`[^`\n]*`?/y], ['k', KW_S], NUM],
        ini:  [['c', /^[ \t]*[;#].*/my], ['k', /^[ \t]*\[[^\]\n]*\]/my], ['p', /^[ \t]*[\w.\-]+(?=[ \t]*[=:])/my], STR[0], STR[1], NUM],
        bat:  [['c', /^[ \t]*(?:rem\b|::).*/imy], ['v', /%[\w~]+%?/y], ['k', words('echo set if else goto call exit for in do not exist errorlevel off on', 'i')], STR[0]],
        json: [['p', /"(?:[^"\\\n]|\\.)*"(?=\s*:)/y], STR[0], ['k', /\b(?:true|false|null)\b/y], NUM],
        xml:  [['c', /<!--[\s\S]*?(?:-->|$)/y], ['k', /<!\[CDATA\[|\]\]>|<![\w-]+|<\?[\w-]*|\?>/y], ['t', /<\/?[\w:.-]+/y],
               ['a', /[\w:.-]+(?==)/y], STR[0], STR[1], ['t', /\/?>/y], ['v', /&[#\w]+;/y]],
        md:   [['h', /^#{1,6}[ \t].*/my], ['c', /^>.*/my], ['s', /`[^`\n]+`/y], ['k', /\*\*[^*\n]+\*\*|__[^_\n]+__/y],
               ['a', /\[[^\]\n]*\]\([^)\n]*\)/y], ['n', /^[ \t]*(?:[-*+]|\d+\.)(?=[ \t])/my]],
        diff: [['h', /^@@.*/my], ['s', /^\+.*/my], ['k', /^-.*/my]]
    };
    LANG.php = [['c', /#(?!\[).*/y]].concat(LANG.c);
    var EXT = {
        php: 'php php3 php4 php5 php7 php8 phtml inc', c: 'js mjs cjs ts tsx jsx java kt kts swift go rs c h cpp hpp cc cxx cs dart scala vue svelte groovy gradle',
        css: 'css scss sass less', hash: 'py rb sh bash zsh pl pm r yml yaml toml conf cfg properties ps1 mk makefile dockerfile',
        sql: 'sql', ini: 'ini cnf env', bat: 'bat cmd', json: 'json jsonc map webmanifest',
        xml: 'html htm xhtml shtml xml xsl xslt svg twig tpl mustache vtt', md: 'md markdown', diff: 'diff patch'
    };
    var BYEXT = {};
    Object.keys(EXT).forEach(function (k) { EXT[k].split(' ').forEach(function (x) { BYEXT[x] = k; }); });

    function highlight(text, lang) {
        var rules = LANG[lang], out = '', plain = '', i = 0, n = text.length;
        if (!rules) return esc(text);
        while (i < n) {
            var hit = null;
            for (var r = 0; r < rules.length; r++) {
                var re = rules[r][1];
                re.lastIndex = i;
                var m = re.exec(text);
                if (m && m[0].length) { hit = [rules[r][0], m[0]]; break; }
            }
            if (hit) {
                if (plain) { out += esc(plain); plain = ''; }
                out += '<span class="hl-' + hit[0] + '">' + esc(hit[1]) + '</span>';
                i += hit[1].length;
            } else {
                var c = text.charAt(i);
                if (/\w/.test(c)) {                             // A whole word, so that a keyword is never found inside a name
                    var w = /\w+/y;  w.lastIndex = i;  c = w.exec(text)[0];
                }
                plain += c;  i += c.length;
            }
        }
        return out + esc(plain);
    }
    function code(text, name, kind) {
        var lines = text.replace(/\r\n?/g, '\n').replace(/\n+$/, '').split('\n'), more = lines.length > LINES || text.length >= 16000;
        lines = lines.slice(0, LINES);
        if (more) lines.push('…');
        var lang = kind === 'txt' ? (BYEXT[ext(name)] || '') : '';
        var wrap = el('div', 'fePvCode'), num = el('pre', 'fePvLn'), src = el('pre');
        num.textContent = lines.map(function (l, i) { return more && i === lines.length - 1 ? '' : i + 1; }).join('\n');
        src.innerHTML = highlight(lines.join('\n'), lang);
        wrap.appendChild(num);  wrap.appendChild(src);
        return wrap;
    }

    // ---------- The preview box ----------
    function place() {
        if (!box || !cur) return;
        var r = cur.getBoundingClientRect(), w = box.offsetWidth, h = box.offsetHeight, x = r.right + 12, y = r.top - 8;
        if (x + w > innerWidth - 8) x = Math.max(8, r.left - w - 12);
        if (y + h > innerHeight - 8) y = Math.max(8, innerHeight - h - 8);
        box.style.left = x + 'px';  box.style.top = y + 'px';
    }
    function stopMedia() {
        if (!box) return;
        box.querySelectorAll('video, audio').forEach(function (m) { try { m.pause(); m.removeAttribute('src'); m.load(); } catch (e) {} });
        box.querySelectorAll('iframe').forEach(function (f) { f.src = 'about:blank'; });
    }
    function hide() {
        clearTimeout(timer);  cur = null;
        if (box) { stopMedia();  box.innerHTML = '';  box.hidden = true; }
    }
    function note(bd, txt) { bd.innerHTML = '';  bd.appendChild(el('div', 'fePvNote', txt));  place(); }
    function addInfo(hd, txt) { hd.lastChild.textContent += '  ·  ' + txt; }
    function load(u, as) {
        var key = as + u;
        return cache[key] = cache[key] || fetch(u, {credentials: 'same-origin'}).then(function (r) {
            if (!r.ok) throw r.status;
            return as === 'buf' ? r.arrayBuffer() : (as === 'json' ? r.json() : r.text());
        }).catch(function (e) { delete cache[key]; throw e; });
    }

    function show(ic) {
        if (!box) {
            box = el('div', 'fePv');  box.id = 'fePv';  box.setAttribute('role', 'tooltip');
            document.body.appendChild(box);
        }
        stopMedia();
        cur = ic;  box.innerHTML = '';
        var k = ic.dataset.pk, u = ic.dataset.pv, name = ic.dataset.pn;
        var hd = el('div', 'fePvHead', name + '  '), bd = el('div', 'fePvBody fePv-' + (k || 'none'));
        hd.appendChild(el('small', '', ic.dataset.ps + '  ·  ' + ic.dataset.pm));
        box.appendChild(hd);  box.appendChild(bd);

        if (k === 'img') {
            var im = new Image();  im.alt = '';
            im.onload = function () { addInfo(hd, im.naturalWidth + ' x ' + im.naturalHeight + ' px');  place(); };
            im.onerror = function () { note(bd, T.none); };
            im.src = u;  bd.appendChild(im);
        } else if (k === 'video') {
            var v = el('video');  v.muted = true;  v.autoplay = true;  v.loop = true;  v.playsInline = true;  v.preload = 'metadata';
            v.onloadedmetadata = function () { addInfo(hd, time(v.duration) + (v.videoWidth ? '  ·  ' + v.videoWidth + ' x ' + v.videoHeight + ' px' : ''));  place(); };
            v.onerror = function () { if (cur === ic) note(bd, T.play); };
            v.src = u;  bd.appendChild(v);
        } else if (k === 'audio') {
            bd.appendChild(el('div', 'fePvBig', '♫'));
            var a = new Audio();  a.preload = 'metadata';
            a.onloadedmetadata = function () { addInfo(hd, time(a.duration));  a.removeAttribute('src');  a.load(); };
            a.src = u;
        } else if (k === 'font') {
            bd.appendChild(el('div', 'fePvNote', T.load));
            load(u, 'buf').then(function (buf) {
                var fam = 'fePvFont' + (++fontNo), ff = new FontFace(fam, buf);
                return ff.load().then(function (f) {
                    document.fonts.add(f);
                    if (cur !== ic) return;
                    bd.innerHTML = '';
                    [34, 20, 13].forEach(function (px, i) {
                        var d = el('div', 'fePvFontS', i ? 'The quick brown fox jumps over the lazy dog  æøå ÆØÅ  0123456789' : 'AaBbCc ÆØÅ 123');
                        d.style.fontFamily = '"' + fam + '", monospace';  d.style.fontSize = px + 'px';
                        bd.appendChild(d);
                    });
                    place();
                });
            }).catch(function () { if (cur === ic) note(bd, T.none); });
        } else if (k === 'pdf') {
            var f = el('iframe', 'fePvPdf');  f.setAttribute('tabindex', '-1');  f.title = name;
            f.src = u + '#toolbar=0&navpanes=0&scrollbar=0&view=FitH';
            bd.appendChild(f);
        } else if (k === 'table') {                    // CSV / TSV, Excel, Calc: the first rows as a table
            bd.appendChild(el('div', 'fePvNote', T.load));
            load(u, 'json').then(function (j) {
                if (cur !== ic) return;
                bd.innerHTML = '';
                if (!j.rows || !j.rows.length) { bd.appendChild(el('div', 'fePvNote', T.empty));  place();  return; }
                if (j.sheet) addInfo(hd, j.sheet);
                var w = el('div', 'fePvTblWrap'), tb = el('table', 'fePvTbl');
                j.rows.forEach(function (r) { var tr = el('tr');  r.forEach(function (c) { tr.appendChild(el('td', '', c)); });  tb.appendChild(tr); });
                w.appendChild(tb);  bd.appendChild(w);
                if (j.more) bd.appendChild(el('div', 'fePvNote', '\u2026'));
                place();
            }, function () { if (cur === ic) note(bd, T.none); });
        } else if (k === 'txt' || k === 'zip' || k === 'doc' || k === 'arch' || k === 'hex') {
            bd.appendChild(el('pre', 'fePvNote', T.load));
            load(u, 'txt').then(function (t) {
                if (cur !== ic) return;
                bd.innerHTML = '';
                if (!t.trim()) bd.appendChild(el('div', 'fePvNote', T.empty));
                else bd.appendChild(code(t, name, k));
                place();
            }, function () { if (cur === ic) note(bd, T.none); });
        } else bd.appendChild(el('div', 'fePvNote', k === 'lock' ? T.lock : T.none));

        var inf = ic.closest('.file-name-div');  inf = inf && inf.querySelector('.live-preview-img');
        if (inf) {                                      // Analyse mode: the file information below the preview
            var d = el('div', 'fePvInfo');  d.innerHTML = inf.innerHTML;
            d.querySelectorAll('a').forEach(function (a) { a.remove(); });     // The links can not be clicked here
            box.appendChild(d);
        }
        box.hidden = false;  place();
    }

    document.addEventListener('mouseover', function (e) {
        var ic = e.target.closest && e.target.closest('.feFileIc');
        if (!ic || ic === cur) return;
        clearTimeout(timer);  timer = setTimeout(function () { show(ic); }, 250);
    });
    document.addEventListener('mouseout', function (e) {
        var ic = e.target.closest && e.target.closest('.feFileIc');
        if (ic && !(e.relatedTarget && ic.contains(e.relatedTarget))) hide();
    });
    addEventListener('scroll', hide, true);
    addEventListener('keydown', function (e) { if (e.key === 'Escape') hide(); });

    // ---------- Menu on the file name: actions on the file (span.feFileName data-fm = the file facts and what the user may do) ----------
    // n name, r path, t type, s bytes, m modified, a accessed, p permissions, u owner, o view address, d download address,
    // e: change the file (rename / copy / ZIP / unpack / delete) 1 = yes, 2 = the account may not, 3 = a protected file, 0 = not a write folder
    // b: the path (basket), k: 1 = in the basket
    var menu = null, mName = null, mShow = null, mHide = null;

    function properties(f) {
        var rows = [[T.pName, f.n], [T.pPath, f.r], [T.pType, f.t], [T.pSize, f.s ? f.s + ' ' + T.bytes : '-'], [T.pMod, f.m],
                    [T.pAcc, f.a], [T.pPerm, f.p], [T.pOwn, f.u]].filter(function (r) { return r[1] !== ''; });
        var txt = rows.map(function (r) { return r[0] + ':  ' + r[1]; }).join('\n');
        if (typeof window.sysAlert !== 'function') { alert(txt); return; }
        window.sysAlert(txt, f.dir ? T.dirProp : T.prop);
        var ds = document.querySelectorAll('dialog.p2hDlg .p2hDlgBody > span');     // Keep the lines
        if (ds.length) { ds[ds.length - 1].style.whiteSpace = 'pre-line';  ds[ds.length - 1].style.textAlign = 'left'; }
    }
    function confirmDo(q, fn) { if (typeof window.sysConfirm === 'function') window.sysConfirm(q, fn); else if (confirm(q)) fn(); }
    function post(fields, download) { window.fePost(fields, download); }     // A download goes into a hidden frame (see fePost)
    function ask(q, def, fn) {
        if (typeof window.sysPrompt === 'function') window.sysPrompt(q, function (v) { if (v && String(v).trim()) fn(String(v).trim()); }, def);
        else { var v = prompt(q, def);  if (v && v.trim()) fn(v.trim()); }
    }
    function copyName(n) { var m = /^(.*?)(\.[^.]*)?$/.exec(n);  return m[1] + ' - ' + T.copyN + (m[2] || ''); }
    function hideMenu() {
        clearTimeout(mShow);  clearTimeout(mHide);
        if (mName) mName.classList.remove('feOn');
        mName = null;  mCtx = false;
        if (menu) menu.hidden = true;
    }
    var mCtx = false;                                   // Opened by a right-click: stays until a click outside, Esc or scroll
    function makeMenu() {       // The one menu box: file / folder menu and program menu
        if (menu) return;
        menu = el('div', 'fePm');  menu.setAttribute('role', 'menu');
        menu.addEventListener('focusout', function (e) {     // The keyboard left the menu (Tab): it closes
            if (e.relatedTarget && !menu.contains(e.relatedTarget)) hideMenu();
        });
        menu.addEventListener('mouseover', function () { clearTimeout(mHide); });
        menu.addEventListener('mouseout', function (e) {
            if (mCtx) return;
            if (!(e.relatedTarget && (menu.contains(e.relatedTarget) || (mName && mName.contains(e.relatedTarget))))) mHide = setTimeout(hideMenu, 300);
        });
        document.body.appendChild(menu);
    }
    function showMenu(nm, at) {
        var f;
        try { f = JSON.parse(nm.dataset.fm); } catch (e) { return; }
        makeMenu();
        hide();                                         // Not the icon preview at the same time
        if (mName) mName.classList.remove('feOn');
        mName = nm;  mCtx = !!at;  nm.classList.add('feOn');
        menu.innerHTML = '';
        function item(icon, label, fn, cls, off) {
            var b = el('button', 'fePmItem' + (cls ? ' ' + cls : ''));
            b.type = 'button';  b.setAttribute('role', 'menuitem');  b.disabled = !!off;
            b.appendChild(el('i', icon));  b.appendChild(document.createTextNode(label));
            b.addEventListener('click', function (e) { e.preventDefault();  e.stopPropagation();  hideMenu();  fn(); });
            menu.appendChild(b);
        }
        var off = f.e !== 1 || typeof window.feAct !== 'function';
        var head = el('div', 'fePmHead');  head.appendChild(el('i', f.dir ? 'far fa-folder' : 'far fa-file'));
        head.appendChild(document.createTextNode(' ' + f.n));  menu.appendChild(head);
        if (f.dir) {                                    // A folder (right-click its row)
            item('fas fa-folder-open', T.goto, function () { location.href = f.g; });
            item('fas fa-download', T.dirZip, function () { post([['fe_sel', 'download'], ['fe_bp[]', f.b]], true); }, '', typeof window.fePost !== 'function');
            item('fa-solid fa-binoculars', T.dirProp, function () { properties(f); });
        } else {
            item('far fa-eye', T.open, function () { window.open(f.o, '_blank', 'noopener'); }, '', !f.o);
            item('fas fa-download', T.down, function () { location.href = f.d; }, '', !f.d);
            item('fa-solid fa-binoculars', T.prop, function () { properties(f); });
        }
        if (typeof window.feBasket === 'function')
            item('fas fa-shopping-basket', f.k ? T.bkOut : T.bkIn, function () { window.feBasket(f.k ? 'remove' : 'add', [f.b]); });
        menu.appendChild(el('div', 'fePmSep'));
        item('fas fa-pen-square', f.dir ? T.moveD : T.move, function () { ask(T.moveQ, f.n, function (to) { if (to !== f.n) window.feAct('move', f.n, to); }); }, '', off);
        item('fas fa-copy', f.dir ? T.copyD : T.copy, function () { ask(T.copyQ, f.dir ? f.n + ' - ' + T.copyN : copyName(f.n), function (to) { window.feAct('copy', f.n, to); }); }, '', off);
        item('far fa-file-archive', T.zip, function () { window.feAct('zip', f.n); }, '', off || (!f.dir && /\.zip$/i.test(f.n)));
        if (f.dir) item('fas fa-sync', T.sync, function () { ask(T.syncQ, (T.dests || [])[0] || '', function (to) { window.feAct('sync', f.n, to); }); },
                        '', f.e === 2 || typeof window.feAct !== 'function' || !(T.dests || []).length);
        if (!f.dir && /\.zip$/i.test(f.n)) item('fas fa-box-open', T.unzip, function () { window.feAct('unzip', f.n); }, '', off);
        if (f.dir) item('fas fa-trash-alt', T.delD, function () { confirmDo(T.delDQ + '\n' + f.n, function () { post([['fe_sel', 'delete'], ['fe_bp[]', f.b]]); }); }, 'fePmDel', off);
        else       item('fas fa-trash-alt', T.del, function () { window.feDelete(f.n); }, 'fePmDel', off || typeof window.feDelete !== 'function');
        if (f.w) {
            menu.appendChild(el('div', 'fePmSep'));
            item('fas fa-folder-plus', T.newD, function () { ask(T.newQ, '', function (to) { window.feAct('mkdir', '', to); }); });
        }
        if (f.e !== 1) menu.appendChild(el('div', 'fePmFoot', f.e === 0 ? T.onlyEx : f.e === 2 ? T.noRight : T.locked));
        menu.hidden = false;
        var w = menu.offsetWidth, h = menu.offsetHeight, x, y;
        if (at) { x = at.x + 2;  y = at.y + 2; }
        else { var r = nm.getBoundingClientRect();  x = r.left;  y = r.bottom + 2; }
        if (x + w > innerWidth - 8) x = Math.max(8, innerWidth - w - 8);
        if (y + h > innerHeight - 8) y = Math.max(8, (at ? at.y : nm.getBoundingClientRect().top) - h - 2);
        menu.style.left = x + 'px';  menu.style.top = y + 'px';
    }
    function showProg(at) {     // The program menu: right-click the table outside a row - the same look as the file menu
        var P = T.prog || {};
        hide();  hideMenu();
        makeMenu();
        menu.innerHTML = '';
        var head = el('div', 'fePmHead');  head.appendChild(el('i', 'fas fa-wrench'));  head.appendChild(document.createTextNode(' ' + (P.title || '')));
        menu.appendChild(head);
        function item(icon, label, fn, off) {
            var b = el('button', 'fePmItem');  b.type = 'button';  b.setAttribute('role', 'menuitem');  b.disabled = !!off;
            b.appendChild(el('i', icon));  b.appendChild(document.createTextNode(label));
            b.addEventListener('click', function (ev) { ev.preventDefault();  ev.stopPropagation();  hideMenu();  fn(); });
            menu.appendChild(b);
        }
        if (P.admin) item('fas fa-users', P.users, function () { location.href = P.urlUsers; });
        if (P.admin && P.urlLog) item('fas fa-clipboard-list', P.logT, function () { location.href = P.urlLog; });
        item('fas fa-key', P.pwT, function () { location.href = P.urlPw; }, !P.pw);
        if (P.admin) item(P.unl ? 'fas fa-lock' : 'fas fa-unlock', P.unl ? P.unlock : P.lock, function () {
            var f = document.getElementById('feUnlockForm');
            if (!f) return;
            if (P.unl) f.submit();
            else if (typeof window.sysConfirm === 'function') window.sysConfirm(P.lockQ, function () { f.submit(); });
            else if (confirm(P.lockQ)) f.submit();
        });
        menu.appendChild(el('div', 'fePmSep'));
        item('fa fa-question', P.help, function () { if (typeof window.feHelp === 'function') window.feHelp(); });
        item('fas fa-info-circle', P.about, function () {
            if (typeof window.sysAlert !== 'function') { alert(P.aboutTxt);  return; }
            window.sysAlert(P.aboutTxt, P.about);
            var ds = document.querySelectorAll('dialog.p2hDlg .p2hDlgBody > span');
            if (ds.length) { ds[ds.length - 1].style.whiteSpace = 'pre-line';  ds[ds.length - 1].style.textAlign = 'left'; }
        });
        if (P.out) { menu.appendChild(el('div', 'fePmSep'));  item('fas fa-sign-out-alt', P.out_, function () { location.href = P.out; }); }
        mCtx = true;  menu.hidden = false;
        var w = menu.offsetWidth, h = menu.offsetHeight, x = at.x + 2, y = at.y + 2;
        if (x + w > innerWidth - 8) x = Math.max(8, innerWidth - w - 8);
        if (y + h > innerHeight - 8) y = Math.max(8, at.y - h - 2);
        menu.style.left = x + 'px';  menu.style.top = y + 'px';
    }
    var lpTimer = null, lpFired = false, lpXY = null;     // Long press (touch screens - iOS has no right-click): the same menus
    document.addEventListener('touchstart', function (e) {
        if (e.touches.length !== 1 || !e.target.closest || !e.target.closest('[id^=tblSpan]') || e.target.closest('input, textarea, select')) return;
        var t = e.touches[0], tr = e.target.closest('tr'), nm = tr && (tr.querySelector('.feFileName') || tr.querySelector('.feBp[data-fm]'));
        lpFired = false;  lpXY = {x: t.clientX, y: t.clientY};
        clearTimeout(lpTimer);
        lpTimer = setTimeout(function () {
            lpFired = true;
            if (nm) showMenu(nm, lpXY);  else showProg(lpXY);
        }, 550);
    }, {passive: true});
    document.addEventListener('touchmove', function (e) {
        if (lpXY && e.touches[0] && Math.abs(e.touches[0].clientX - lpXY.x) + Math.abs(e.touches[0].clientY - lpXY.y) > 10) clearTimeout(lpTimer);
    }, {passive: true});
    document.addEventListener('touchend', function (e) {
        clearTimeout(lpTimer);
        if (lpFired) { e.preventDefault();  lpFired = false; }       // Not the click (a folder link) after the long press
    });
    document.addEventListener('touchcancel', function () { clearTimeout(lpTimer); });
    document.addEventListener('contextmenu', function (e) {     // Right-click a row of the table: the same menu - elsewhere in the table: the program menu
        if (!e.target.closest || e.target.closest('a[href]:not(.txtbutt), input, textarea, select')) return;
        var tr = e.target.closest('tr');
        var nm = tr && (tr.querySelector('.feFileName') || tr.querySelector('.feBp[data-fm]'));
        if (!nm && !e.target.closest('[id^=tblSpan]')) return;
        e.preventDefault();  e.stopPropagation();
        clearTimeout(mShow);  clearTimeout(mHide);
        if (nm) showMenu(nm, {x: e.clientX, y: e.clientY});
        else showProg({x: e.clientX, y: e.clientY});
    }, true);
    document.addEventListener('mouseover', function (e) {
        var nm = e.target.closest && e.target.closest('.feFileName');
        if (!nm) return;
        clearTimeout(mHide);
        if (nm === mName || mCtx) return;
        clearTimeout(mShow);  mShow = setTimeout(function () { showMenu(nm); }, 400);
    });
    document.addEventListener('mouseout', function (e) {
        var nm = e.target.closest && e.target.closest('.feFileName');
        if (!nm) return;
        var to = e.relatedTarget;
        if (to && (nm.contains(to) || (menu && menu.contains(to)))) return;
        clearTimeout(mShow);
        if (nm === mName && !mCtx) mHide = setTimeout(hideMenu, 300);
    });
    document.addEventListener('click', function (e) {          // A click (or a tap) on a file name: the menu - for touch screens and the keyboard
        var nm = e.target.closest && e.target.closest('.feFileName');
        if (!nm) return;
        e.preventDefault();
        if (nm === mName && mCtx) { hideMenu();  return; }
        clearTimeout(mShow);  clearTimeout(mHide);
        var r = nm.getBoundingClientRect();
        showMenu(nm, {x: r.left, y: r.bottom});
        var first = menu.querySelector('.fePmItem:not(:disabled)');  if (first && e.detail === 0) first.focus();   // Keyboard: into the menu
    });
    document.addEventListener('keydown', function (e) {
        var key = e.key === 'ContextMenu' || (e.key === 'F10' && e.shiftKey);
        if (menu && !menu.hidden && menu.contains(document.activeElement) && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {     // Up / down in the menu
            var its = Array.prototype.slice.call(menu.querySelectorAll('.fePmItem:not(:disabled)')), i = its.indexOf(document.activeElement);
            its[(i + (e.key === 'ArrowDown' ? 1 : its.length - 1)) % its.length].focus();  e.preventDefault();  return;
        }
        var t = document.activeElement, tr = t && t.closest && t.closest('tr');
        var nm = (t && t.classList && t.classList.contains('feFileName') && (e.key === 'Enter' || e.key === ' ' || key)) ? t
               : (key && tr ? (tr.querySelector('.feFileName') || tr.querySelector('.feBp[data-fm]')) : null);
        if (!nm && key && t && t.closest && t.closest('[id^=tblSpan]')) {        // In the table, not on a row: the program menu
            e.preventDefault();
            var rr = t.getBoundingClientRect();  showProg({x: rr.left, y: rr.bottom});
            var f1 = menu.querySelector('.fePmItem:not(:disabled)');  if (f1) f1.focus();
            return;
        }
        if (!nm) return;
        e.preventDefault();
        var r = (t.getBoundingClientRect ? t : nm).getBoundingClientRect();
        showMenu(nm, {x: r.left, y: r.bottom});
        var first = menu.querySelector('.fePmItem:not(:disabled)');  if (first) first.focus();
    });
    addEventListener('scroll', function (e) { if (!(menu && menu.contains(e.target))) hideMenu(); }, true);
    addEventListener('keydown', function (e) { if (e.key === 'Escape') hideMenu(); });
    document.addEventListener('pointerdown', function (e) { if (menu && !menu.hidden && !menu.contains(e.target)) hideMenu(); });
})();
