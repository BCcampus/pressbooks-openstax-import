(function ($) {

    $(document).ready(function () {

        // admin notice to nudge installation on toggle
        var import_form = $('#pb-import-form select');
        import_form.on('change', function () {
            var poi = import_form.attr('value');
            if (poi === 'zip' && settings.active == 0) {
                $('#wpbody').prepend('<div id="message" class="notice notice-info"><p class="poi-notify">Using equations in your book? Consider activating <a href="plugins.php">WP QuickLaTeX</a> for improved equation rendering.</p></div>');

            }         // admin notice if plugin active
            else if (poi === 'zip' && settings.active == 1) {
                $('#wpbody').prepend('<div id="message" class="notice notice-success"><p class="poi-notify">With WP QuickLaTeX enabled, expect slower loading the first time a page is accessed due to caching.</p></div>');
            }
            else {
                $('div').detach('.notice');
            }

            if (poi === 'zip') {
                $('#wpbody-content').before('<div id="message" class="notice notice-info">' +
                    '<p>If this book is a large file the import routine may take longer than expected. Make sure not to close down your browser during this process.</p></div>');
                    // '<div id="message" class="notice notice-warning"><p>The following PHP settings may need to be increased if the import fails.</p>' +
                    // '<ul><li><b>post_max_size</b>: '+ settings.post_max + '</li>' +
                    // '<li><b>upload_max_filesize</b>: ' + settings.upload_max + '</li>' +
                    // '<li><b>memory_limit</b>: ' + settings.memory_max + '</li></ul>' +
                    // '</div>');
            }

        })

    });

})(jQuery);