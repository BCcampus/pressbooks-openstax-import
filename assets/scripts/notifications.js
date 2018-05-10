(function ($) {

    $(document).ready(function () {

        // admin notice to nudge installation on toggle
        var import_form = $('#pb-import-form select');
        import_form.on('change', function () {
            var poi = import_form.attr('value');
            if (poi === 'zip' && settings.active == 0) {
                $('#wpbody').prepend('<div data-dismissible="install-notice-forever" id="message" class="notice notice-info"><p class="poi-notify">Using equations in your book? Consider activating <a href="plugins.php">WP QuickLaTeX</a> for improved equation rendering.</p></div>');

            }         // admin notice if plugin active
            else if (poi === 'zip' && settings.active == 1) {
                $('#wpbody').prepend('<div data-dismissible="install-notice-forever" id="message" class="notice notice-success"><p class="poi-notify">With WP QuickLaTeX enabled, expect slower loading the first time a page is accessed due to caching.</p></div>');
            }
            else {
                $('div').detach('.notice');
            }
        })
    });

})(jQuery);