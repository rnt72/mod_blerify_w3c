define([], function() {

    return {
        init: function() {
            var links = document.querySelectorAll('.blerify-delete-config');
            links.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    var message = link.getAttribute('data-confirm') || '';
                    if (!window.confirm(message)) {
                        e.preventDefault();
                    }
                });
            });
        }
    };
});
