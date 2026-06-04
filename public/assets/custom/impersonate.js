(function () {
    var searchTimeout;

    function loadUsers($list, query) {
        $list.html('<div class="text-sm text-zinc-400 text-center py-2"><i class="fa-solid fa-spinner fa-spin mr-1.5 text-xs"></i>Searching...</div>');

        $.ajax({
            url: window.impersonateUsersUrl,
            type: 'GET',
            data: { q: query || '' },
            dataType: 'json',
            success: function (users) {
                if (users.length === 0) {
                    $list.html('<div class="text-sm text-zinc-400 text-center py-2">No users found</div>');
                    return;
                }

                var html = '';
                users.forEach(function (user) {
                    var takeUrl = window.impersonateTakeUrl.replace('__ID__', user.id);
                    html += '<a href="' + takeUrl + '" class="dropdown-item impersonate-take-btn flex justify-between items-center py-2" data-user-name="' + $('<span>').text(user.name).html() + '">';
                    html += '<div><strong class="block text-sm text-zinc-900">' + $('<span>').text(user.name).html() + '</strong>';
                    html += '<small class="text-xs text-zinc-500">' + $('<span>').text(user.email).html() + '</small></div>';
                    html += '<i class="fa-solid fa-arrow-right-to-bracket text-zinc-400 text-xs"></i>';
                    html += '</a>';
                });
                $list.html(html);
            },
            error: function () {
                $list.html('<div class="text-sm text-red-500 text-center py-2">Search failed</div>');
            },
        });
    }

    // Debounced AJAX search
    $(document).on('keyup', '.impersonate-search-input', function () {
        var query = $(this).val();
        var $list = $(this).closest('.dropdown-menu, [id*=impersonate]').find('.impersonate-user-list');

        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(function () {
            loadUsers($list, query);
        }, 300);
    });

    // Confirm before impersonating
    $(document).on('click', '.impersonate-take-btn', function (e) {
        e.preventDefault();
        var url = $(this).attr('href');
        var name = $(this).data('user-name');

        if (typeof erpConfirm === 'function') {
            erpConfirm({
                title: 'Impersonate User',
                message: 'Are you sure you want to impersonate "' + name + '"?',
                confirmText: 'Yes, impersonate',
                cancelText: 'Cancel',
                type: 'default'
            }).then(function (confirmed) {
                if (confirmed) {
                    window.location.href = url;
                }
            });
        } else if (confirm('Are you sure you want to impersonate "' + name + '"?')) {
            window.location.href = url;
        }
    });

    // Auto-focus search input and load default users on dropdown open
    $(document).on('shown.bs.dropdown', '#impersonate-dropdown', function () {
        var $this = $(this);
        $this.find('.impersonate-search-input').val('').focus();
        loadUsers($this.find('.impersonate-user-list'), '');
    });

    // For Tailwind theme: load default users when impersonate dropdown becomes visible
    $(document).on('click', '#impersonate-dropdown > button', function () {
        var $dropdown = $(this).parent();
        var $list = $dropdown.find('.impersonate-user-list');
        if ($list.length && $list.find('.impersonate-take-btn').length === 0) {
            setTimeout(function () {
                $dropdown.find('.impersonate-search-input').val('').focus();
                loadUsers($list, '');
            }, 50);
        }
    });
})();
