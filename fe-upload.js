/* fe-upload.js v:1.1.0 d:2026-10-25 i:evs  -  Folder-explorer: upload to the current folder (a folder the user may write in)
 * - a list of the chosen files with check boxes; files that the server would refuse are marked and not sent
 * - drag and drop of files and folders, and "Choose a folder" (the folder structure is kept)
 * - sends the files in batches that fit PHP's max_file_uploads and post_max_size, with a progress bar
 * - a taken name as chosen on the Transfer card - "Stop and ask": the server names them first, then a dialog (window.feAsk)
 * Without JavaScript the form is a plain file field + submit, handled by folder-explorer.php as well.
 */
(function () {
    'use strict';
    var form = document.getElementById('feUpload');
    if (!form || !window.FormData || !window.XMLHttpRequest) return;
    var cfg  = JSON.parse(form.getAttribute('data-cfg'));
    var txt  = cfg.txt;
    var deny = new RegExp(cfg.deny, 'i');
    var drop = document.getElementById('feDrop'),   list = document.getElementById('feList'),
        send = document.getElementById('feSend'),   sum  = document.getElementById('feSum'),
        prog = document.getElementById('feProg'),   files = document.getElementById('feFiles'),
        folder = document.getElementById('feFolder');
    var items = [];     // {file, rel, why, chk, status, row}
    var emptyDirs = []; // Empty folders of a dropped folder: made on the server with the first batch
    var busy  = false;
    var showUrl = form.action;      // "Show the folder" after the upload: the destination (the server says which)
    form.classList.add('fe-ready');
    files.removeAttribute('name');      // The files are sent by this script - not by the form

    function human(n) {
        var u = ['B', 'KB', 'MB', 'GB'], i = 0;
        while (+n.toFixed(1) >= 1024 && i < u.length - 1) { n /= 1024; i++; }      // 1023.99 KB: 1.0 MB
        return (i ? n.toFixed(1) : n) + ' ' + u[i];
    }
    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) { return '&#' + c.charCodeAt(0) + ';'; });
    }
    function check(f, rel) {    // The same rules as fe_name_allowed() / fe_rel_dir() in folder-explorer.php
        var n = f.name;
        if (!n || n.charAt(0) === '.' || (cfg.strict && n.toLowerCase() === 'index.html') || deny.test(n)) return txt.type;
        var dirs = rel ? rel.split('/').slice(0, -1) : [];
        if (dirs.length > 10) return txt.folder;
        for (var i = 0; i < dirs.length; i++)
            if (!dirs[i].trim() || dirs[i].charAt(0) === '.') return txt.folder;
        if (f.size > cfg.maxFile) return txt.big + ' (' + human(f.size) + ' > ' + human(cfg.maxFile) + ')';
        return '';
    }
    function add(f, rel) {
        rel = (rel || '').replace(/^\/+/, '');
        for (var i = 0; i < items.length; i++)     // The same file twice: keep one
            if (items[i].rel === rel && items[i].file.name === f.name && items[i].file.size === f.size) return;
        var why = check(f, rel);
        items.push({file: f, rel: rel, why: why, chk: !why, status: why ? '' : txt.ready});
    }

    function render() {
        if (!items.length) { list.innerHTML = '<small><i>' + esc(txt.none) + '</i></small>'; summary(); return; }
        var h = '<div class="fe-wrap"><table><tr><th><abbr class="hint feHint"><input type="checkbox" id="feAll" aria-label="' + esc(txt.all) + '"><data-hint>' + esc(txt.all) + '</data-hint></abbr></th>' +
                '<th>' + esc(txt.name) + '</th><th>' + esc(txt.size) + '</th><th>' + esc(txt.status) + '</th></tr>';
        items.forEach(function (it, i) {
            h += '<tr><td><input type="checkbox" data-i="' + i + '"' + (it.chk ? ' checked' : '') + (it.why || it.sent ? ' disabled' : '') +
                 ' aria-label="' + esc(it.rel || it.file.name) + '"></td>' +
                 '<td>' + esc(it.rel || it.file.name) + '</td><td class="fe-size">' + human(it.file.size) + '</td>' +
                 '<td class="' + (it.why || it.failed ? 'fe-bad' : (it.sent ? 'fe-good' : '')) + '" id="feSt' + i + '">' + esc(it.why || it.status) + '</td></tr>';
        });
        list.innerHTML = h + '</table></div>';
        summary();
    }
    function chosen() { return items.filter(function (it) { return it.chk && !it.why && !it.sent; }); }
    function summary() {
        var sel = chosen(), total = 0;
        sel.forEach(function (it) { total += it.file.size; });
        var full = total > cfg.quotaLeft;
        sum.innerHTML = sel.length + ' ' + esc(txt.files) + ', ' + human(total) +
                        (full ? ' - <b class="fe-bad" style="color:#b00020;">' + esc(txt.quota) + ' (' + human(cfg.quotaLeft) + ')</b>' : '');
        send.disabled = busy || !sel.length || full;
        var all = document.getElementById('feAll');
        if (all) all.checked = sel.length > 0 && sel.length === items.filter(function (it) { return !it.why && !it.sent; }).length;
    }

    list.addEventListener('change', function (e) {
        var t = e.target;
        if (t.id === 'feAll') {
            items.forEach(function (it) { if (!it.why && !it.sent) it.chk = t.checked; });
            render();
        } else if (t.hasAttribute('data-i')) {
            items[+t.getAttribute('data-i')].chk = t.checked;
            summary();
        }
    });

    files.addEventListener('change', function () {
        Array.prototype.forEach.call(files.files, function (f) { add(f, ''); });
        files.value = '';   render();
    });
    folder.addEventListener('change', function () {
        Array.prototype.forEach.call(folder.files, function (f) { add(f, f.webkitRelativePath || ''); });
        folder.value = '';  render();
    });
    document.getElementById('feClear').addEventListener('click', function () {
        if (busy) return;
        items = [];  emptyDirs = [];  render();
    });

    // Drag and drop: files, and folders (read recursively)
    function readEntry(entry, path) {
        return new Promise(function (resolve) {
            if (entry.isFile) {
                entry.file(function (f) { add(f, path ? path + '/' + f.name : ''); resolve(); }, function () { resolve(); });
            } else if (entry.isDirectory) {
                var reader = entry.createReader(), all = [];
                var dir = path ? path + '/' + entry.name : entry.name;
                (function more() {      // readEntries returns at most 100 entries per call
                    reader.readEntries(function (ents) {
                        if (!ents.length) {
                            if (!all.length && emptyDirs.indexOf(dir) < 0) emptyDirs.push(dir);     // An empty folder
                            Promise.all(all.map(function (en) { return readEntry(en, dir); })).then(resolve);
                        } else { all = all.concat(Array.prototype.slice.call(ents)); more(); }
                    }, function () { resolve(); });
                })();
            } else resolve();
        });
    }
    ['dragenter', 'dragover'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('fe-over'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('fe-over'); });
    });
    drop.addEventListener('drop', function (e) {
        if (busy) return;
        var dt = e.dataTransfer, jobs = [];
        if (dt.items && dt.items.length && dt.items[0].webkitGetAsEntry) {
            Array.prototype.forEach.call(dt.items, function (item) {
                var en = item.kind === 'file' && item.webkitGetAsEntry();
                if (en) jobs.push(readEntry(en, ''));
            });
        }
        if (!jobs.length) Array.prototype.forEach.call(dt.files || [], function (f) { add(f, ''); });     // No folder support: plain files
        Promise.all(jobs).then(render);
    });
    window.addEventListener('dragenter', function (e) {     // Files dragged onto the page while the card is folded: it opens (else nothing happened)
        var types = e.dataTransfer ? Array.prototype.slice.call(e.dataTransfer.types || []) : [];
        if (drop.offsetParent !== null || types.indexOf('Files') < 0) return;
        var card = form.closest('[id^=card]'), open = card && window['CardMaximize' + card.id.slice(4)];
        if (typeof open === 'function') { open();  card.scrollIntoView({block: 'center'}); }
    });
    window.addEventListener('dragover', function (e) { e.preventDefault(); });     // A file dropped beside the zone
    window.addEventListener('drop',     function (e) { e.preventDefault(); });     // must not open it in the browser

    // Upload in batches
    function batches(sel) {
        var out = [], cur = [], size = 0, room = cfg.maxPost - 65536;     // Room for the other form fields
        sel.forEach(function (it) {
            if (cur.length && (cur.length >= cfg.maxCount || size + it.file.size > room)) { out.push(cur); cur = []; size = 0; }
            cur.push(it);  size += it.file.size;
        });
        if (cur.length) out.push(cur);
        return out;
    }
    function setStatus(it, text, bad) {
        it.status = text;  it.failed = !!bad;
        var i = items.indexOf(it), td = document.getElementById('feSt' + i);
        if (td) { td.textContent = text;  td.className = bad ? 'fe-bad' : (it.sent ? 'fe-good' : ''); }
    }
    function sendBatch(batch, doneBytes, totalBytes, extra) {
        return new Promise(function (resolve) {
            var fd = new FormData();
            fd.append('csrf_token', form.querySelector('input[name="csrf_token"]').value);
            fd.append('fe_ajax', '1');
            var de = document.getElementById('feDest');      // The destination of the Transfer card (else: the hidden default)
            fd.append('fe_dest', de ? de.value.trim() : ((form.querySelector('input[name="fe_dest"]') || {}).value || ''));
            emptyDirs.forEach(function (d) { fd.append('fe_mkdir[]', d); });     // (with the first batch)
            emptyDirs = [];
            (extra || []).forEach(function (x) { fd.append(x[0], x[1]); });     // The choice for a taken name
            batch.forEach(function (it) {
                fd.append('fe_upload[]', it.file, it.file.name);
                fd.append('fe_relpath[]', it.rel);
                setStatus(it, txt.sending);
            });
            var xhr = new XMLHttpRequest();
            xhr.open('POST', form.action);
            xhr.upload.onprogress = function (e) {
                if (e.lengthComputable) prog.value = Math.round((doneBytes + e.loaded * (batchBytes(batch) / e.total)) / totalBytes * 100);
            };
            xhr.onload = function () {
                var r = null;
                try { r = JSON.parse(xhr.responseText); } catch (err) { r = null; }
                if (!r || xhr.status !== 200 || r.error) {
                    batch.forEach(function (it) { setStatus(it, (r && r.error) || (txt.server + ' (' + xhr.status + ')'), true); });
                } else {
                    if (r.show) showUrl = r.show;     // The destination - for "Show the folder"
                    (r.done || []).forEach(function (d) { var it = batch[d[0]]; if (it) { it.sent = true; it.chk = false; cfg.quotaLeft -= it.file.size; setStatus(it, txt.saved + ' ' + d[1] + (d[2] ? ' - ' + (d[2] === 1 && r.trash ? txt.overT : txt.over) : '')); } });
                    (r.kept || []).forEach(function (k) { var it = batch[k[0]]; if (it) { it.sent = true; it.chk = false; it.kept = true;  setStatus(it, txt.kept); } });     // Skipped: the name was taken
                    (r.fail || []).forEach(function (f) { var it = batch[f[0]]; if (it) setStatus(it, f[2], true); });
                }
                resolve();
            };
            xhr.onerror = function () { batch.forEach(function (it) { setStatus(it, txt.server, true); }); resolve(); };
            xhr.send(fd);
        });
    }
    function batchBytes(batch) { var s = 0; batch.forEach(function (it) { s += it.file.size; }); return s || 1; }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var sel = chosen();
        if (busy || !sel.length) return;
        if (typeof window.feAsk !== 'function') { start(sel, []); return; }
        var de = document.getElementById('feDest');     // "Stop and ask": the names first - no file is sent (one field: PHP's max_input_vars)
        var f = [['fe_upcheck', '1'], ['fe_dest', de ? de.value.trim() : ''],
                 ['fe_files', JSON.stringify(sel.map(function (it) { return [it.file.name, it.rel, it.file.size, Math.round((it.file.lastModified || 0) / 1000)]; }))]];
        busy = true;  send.disabled = true;
        window.feAsk(f, function (extra) { start(sel, extra); }, form.action, function () { busy = false;  summary(); });     // Cancel: nothing is sent
    });
    function start(sel, extra) {
        busy = true;  send.disabled = true;  prog.hidden = false;  prog.value = 0;
        var list2 = batches(sel), total = 0, done = 0;
        sel.forEach(function (it) { total += it.file.size; });
        total = total || 1;
        var chain = Promise.resolve();
        list2.forEach(function (b) {
            chain = chain.then(function () { return sendBatch(b, done, total, extra); }).then(function () { done += batchBytes(b); });
        });
        chain.then(function () {
            busy = false;  prog.value = 100;
            var ok = items.filter(function (it) { return it.sent && !it.kept; }).length;
            render();
            sum.innerHTML = '<b>' + esc(txt.done) + '</b>: ' + ok + ' ' + esc(txt.uploaded) +
                            ' &nbsp; <a href="' + esc(showUrl) + '" class="txtbutt">' + esc(txt.show) + '</a>';
        });
    }

    render();
})();
