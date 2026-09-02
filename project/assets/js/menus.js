document.addEventListener('DOMContentLoaded', function () {
    var wrap = document.querySelector('.menu-structure-wrap');
    if (!wrap) {
        return;
    }

    wrap.addEventListener('click', function (e) {
        var btn = e.target.closest('.menu-org-toggle');
        if (!btn) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        var node = btn.closest('.menu-org-node');
        if (!node) {
            return;
        }
        var expanded = node.classList.toggle('expanded');
        node.classList.toggle('collapsed', !expanded);
        btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        btn.textContent = expanded ? '▾' : '▸';
    });

    var expandAll = document.getElementById('menuTreeExpandAll');
    var collapseAll = document.getElementById('menuTreeCollapseAll');

    if (expandAll) {
        expandAll.addEventListener('click', function () {
            wrap.querySelectorAll('.menu-org-node.has-children').forEach(function (node) {
                node.classList.add('expanded');
                node.classList.remove('collapsed');
                var btn = node.querySelector('.menu-org-toggle');
                if (btn) {
                    btn.setAttribute('aria-expanded', 'true');
                    btn.textContent = '▾';
                }
            });
        });
    }

    if (collapseAll) {
        collapseAll.addEventListener('click', function () {
            wrap.querySelectorAll('.menu-org-node.has-children').forEach(function (node) {
                node.classList.remove('expanded');
                node.classList.add('collapsed');
                var btn = node.querySelector('.menu-org-toggle');
                if (btn) {
                    btn.setAttribute('aria-expanded', 'false');
                    btn.textContent = '▸';
                }
            });
        });
    }
});
