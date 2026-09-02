document.addEventListener('DOMContentLoaded', function () {
    // Modal
    document.querySelectorAll('[data-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-modal');
            var modal = document.getElementById(id);
            if (modal) modal.classList.add('active');
        });
    });

    document.querySelectorAll('.modal-close, .modal-overlay').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (e.target === el) {
                el.closest('.modal-overlay').classList.remove('active');
            }
        });
    });

    // Menu tree toggle
    document.querySelectorAll('.menu-tree-item-header').forEach(function (header) {
        header.addEventListener('click', function (e) {
            if (e.target.closest('a')) return;
            var children = header.parentElement.querySelector('.menu-tree-children');
            if (children) children.classList.toggle('collapsed');
        });
    });

    // Auto-dismiss alerts
    document.querySelectorAll('.alert').forEach(function (alert) {
        setTimeout(function () {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity .3s';
            setTimeout(function () { alert.remove(); }, 300);
        }, 4000);
    });

    // Confirm delete
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // Tabs
    document.querySelectorAll('.tabs').forEach(function (tabBar) {
        tabBar.querySelectorAll('.tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                var target = tab.getAttribute('data-tab');
                tabBar.querySelectorAll('.tab').forEach(function (t) { t.classList.remove('active'); });
                tab.classList.add('active');
                document.querySelectorAll('.tab-panel').forEach(function (panel) {
                    panel.style.display = panel.id === target ? 'block' : 'none';
                });
            });
        });
    });
});
