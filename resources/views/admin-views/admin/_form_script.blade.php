<script>
    (function () {
        var opts = Array.prototype.slice.call(document.querySelectorAll('.role-opt input'));

        if (!opts.length) { return; }

        var warn = document.getElementById('noRoleWarn');

        /* التحذير يُشتق من الصناديق نفسها، فلا يتناقض معها. */
        function refresh() {
            var any = false;

            opts.forEach(function (box) {
                box.closest('.role-opt').classList.toggle('is-on', box.checked);
                if (box.checked) { any = true; }
            });

            if (warn) { warn.hidden = any; }
        }

        opts.forEach(function (box) { box.addEventListener('change', refresh); });

        refresh();
    })();
</script>
