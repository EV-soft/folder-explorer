/* fe-tree.js v:1.0.0 d:2026-10-19 i:evs  -  Folder-explorer: the used space in the folder tree (card tool "Show used space")
 * The page is shown at once: the server sends what it knows without counting -
 *   span.feSizeCol data-own  = the bytes of the files directly in an opened folder (its sub folders are rows below it)
 *   span.feSizeCol data-size = the size of a closed folder, remembered by the server (15 minutes)
 *   span.feSizeCol data-lazy = a closed folder not counted yet: fetched here from ?fsz=..., two at a time
 *   data-manual              = outside the current folder: counted only when asked (its calculator, or "Count all")
 * The sizes of the opened folders, the shares (of the parent folder or of BASE) and the total are added up here.
 */
(function () {
    'use strict';
    var me = document.currentScript, T = {};
    try { T = JSON.parse(me.getAttribute('data-t')); } catch (e) {}
    var top = document.querySelector('ul.feTree'), head = document.getElementById('feTreeTotal'), sw = document.querySelector('.feRelSw');
    if (!head) return;
    var rel = 'parent', busy = 0, queue = [], timer = null;
    try { rel = localStorage.getItem('feTreeRel') || 'parent'; } catch (e) {}

    function nice(n, fix) {      // Like p2h_nice_filesize(): 2 decimals in the column
        var u = [' B', 'KB', 'MB', 'GB', 'TB', 'PB'], i = 0;
        while (n >= 1024 && i < u.length - 1) { n /= 1024;  i++; }
        return (i ? (fix ? n.toFixed(2) : String(Math.round(n * 100) / 100)) : String(n)) + ' ' + u[i];
    }
    function bytesTxt(n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' '); }
    function col(li) { return li.querySelector(':scope > .feSizeCol'); }
    function kids(li) { var ul = li.querySelector(':scope > ul.feTree');  return ul ? Array.prototype.slice.call(ul.children) : []; }

    // The size of a row: {b: bytes, p: true while a part is not known yet}
    function size(li) {
        var c = col(li);
        if (!c) return {b: 0, p: false};
        if (c.dataset.lazy) return {b: 0, p: true};
        if (c.dataset.size !== undefined && c.dataset.size !== '') return {b: +c.dataset.size, p: false};
        if (c.dataset.err) return {b: 0, p: true};
        var r = {b: +(c.dataset.own || 0), p: false};
        kids(li).forEach(function (k) { var s = size(k);  r.b += s.b;  r.p = r.p || s.p; });
        return r;
    }
    function show(li, parentBytes, baseBytes, inManual) {     // inManual: inside a folder not counted yet - its rows stay empty
        var c = col(li), s = size(li);
        if (c) {
            var pct = parentBytes > 0 ? s.b / parentBytes * 100 : 0, pb = baseBytes > 0 ? s.b / baseBytes * 100 : 0;
            var p = rel === 'base' ? pb : pct, num = c.querySelector('.feTreeNum');
            c.setAttribute('data-bytes', s.b);  c.setAttribute('data-pp', pct.toFixed(2));
            if (c.dataset.manual) num.innerHTML = inManual ? '' : '<i class="fas fa-calculator feCount"></i>';
            else if (c.dataset.lazy && !s.b) num.textContent = '…';
            else num.textContent = (c.dataset.err ? '? ' : '') + nice(s.b, true) + (s.p ? '+' : '');
            c.querySelector('.feTreeBar > span').style.width = p.toFixed(1) + '%';
            var b = c.querySelector('.feTreeB');   if (b) b.textContent = c.dataset.manual ? T.manual : c.dataset.lazy ? T.counting : (c.dataset.err ? T.err : bytesTxt(s.b) + (s.p ? ' +' : ''));
            var t = c.querySelector('.feTreePct'); if (t) t.textContent = (p < 1 && p > 0 ? p.toFixed(2) : Math.round(p)) + ' % ' + (rel === 'base' ? T.ofBase : T.ofParent);
            c.classList.toggle('fePending', !!(c.dataset.lazy || s.p));
        }
        kids(li).forEach(function (k) { show(k, s.b, baseBytes, inManual || !!(c && c.dataset.manual)); });
    }
    function render() {
        timer = null;
        var rows = top ? Array.prototype.slice.call(top.children) : [], tot = {b: +(head.dataset.own || 0), p: false};
        rows.forEach(function (li) { var s = size(li);  tot.b += s.b;  tot.p = tot.p || s.p; });
        rows.forEach(function (li) { show(li, tot.b, tot.b, false); });
        head.querySelector('b').textContent = nice(tot.b) + (tot.p ? ' +' : '');
        var manual = Array.prototype.filter.call(document.querySelectorAll('ul.feTree .feSizeCol[data-manual]'), needed).length;
        head.querySelector('small').textContent = '(' + bytesTxt(tot.b) + ' Bytes' + (tot.p && !manual ? ' - ' + T.counting : '') + ')';
        allBtn.hidden = !manual;
    }
    function needed(c) {        // Part of the sums: no folder above it is counted as a whole (or waits for it)
        for (var li = c.closest('li').parentElement.closest('li'); li; li = li.parentElement.closest('li')) {
            var pc = col(li);  if (pc && (pc.dataset.manual || pc.dataset.lazy || (pc.dataset.size !== undefined && pc.dataset.size !== ''))) return false;
        }
        return true;
    }
    function ask(c) {           // Count a folder outside the current folder
        if (!c.dataset.manual) return;
        delete c.dataset.manual;
        queue.push(c);
    }
    var allBtn = document.createElement('button');      // "Count all": the folders outside the current folder too
    allBtn.type = 'button';  allBtn.className = 'feCountAll';  allBtn.hidden = true;
    allBtn.innerHTML = '<i class="fas fa-calculator"></i> ';  allBtn.appendChild(document.createTextNode(T.all || 'Count all'));
    allBtn.setAttribute('aria-label', T.allHint || '');
    allBtn.addEventListener('click', function () {
        Array.prototype.filter.call(document.querySelectorAll('ul.feTree .feSizeCol[data-manual]'), needed).forEach(ask);
        render();  next();
    });
    head.appendChild(allBtn);
    if (top) top.addEventListener('click', function (e) {     // The calculator of one folder
        var i = e.target.closest('.feCount'), c = i && i.closest('.feSizeCol');
        if (!c) return;
        e.preventDefault();  ask(c);  render();  next();
    });
    function later() { if (!timer) timer = setTimeout(render, 150); }

    function next() {           // Two folders at a time - the server counts the files of each
        while (busy < 2 && queue.length) {
            var c = queue.shift();
            busy++;
            fetch(T.url + encodeURIComponent(c.dataset.lazy), {credentials: 'same-origin'})
                .then(function (r) { if (!r.ok) throw r.status;  return r.json(); })
                .then(function (c) { return function (j) {
                          if (j.busy) { setTimeout(function () { queue.push(c);  next(); }, 15000);  return; }    // Counted for another page view: ask again
                          if (j.err || j.b === undefined) throw 0;
                          c.dataset.size = j.b;  delete c.dataset.lazy; }; }(c))
                .then(null,
                      function (c) { return function () { c.dataset.err = 1;  delete c.dataset.lazy; }; }(c))
                .then(function () { busy--;  later();  next(); });
        }
    }
    if (sw) {                   // The bars: share of the parent folder or of BASE (remembered in the browser)
        sw.addEventListener('click', function (e) {
            var b = e.target.closest('button[data-rel]');
            if (!b) return;
            rel = b.getAttribute('data-rel');
            try { localStorage.setItem('feTreeRel', rel); } catch (err) {}
            sw.querySelectorAll('button').forEach(function (x) { x.setAttribute('aria-pressed', x.getAttribute('data-rel') === rel ? 'true' : 'false'); });
            render();
        });
        sw.querySelectorAll('button').forEach(function (x) { x.setAttribute('aria-pressed', x.getAttribute('data-rel') === rel ? 'true' : 'false'); });
        sw.hidden = false;
    }
    queue = Array.prototype.slice.call(document.querySelectorAll('ul.feTree .feSizeCol[data-lazy]:not([data-manual])'));
    render();
    if (top && window.IntersectionObserver) {        // Only when the tree is shown - its card is folded at the start
        var io = new IntersectionObserver(function (es) {
            if (es.some(function (x) { return x.isIntersecting; })) { io.disconnect();  next(); }
        });
        io.observe(top);
    } else next();
})();
