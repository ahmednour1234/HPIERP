<script>
    (function () {
        var add = document.getElementById('add-link');
        var wrap = document.getElementById('links-wrapper');

        if (!add || !wrap) { return; }

        add.addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'link-row';

            var input = document.createElement('input');
            input.type = 'url';
            input.name = 'links[]';
            input.className = 'form-control';
            input.placeholder = 'https://example.com';

            var del = document.createElement('button');
            del.type = 'button';
            del.className = 'btn btn-sm btn-outline-danger';
            del.innerHTML = '<i class="tio-clear"></i>';
            // الصفّ المضاف يُزال بنفسه؛ الأول يبقى فارغًا بلا ضرر.
            del.addEventListener('click', function () { row.remove(); });

            row.appendChild(input);
            row.appendChild(del);
            wrap.appendChild(row);
            input.focus();
        });
    })();
</script>
