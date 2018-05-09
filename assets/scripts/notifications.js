(function ($) {

    $(document).ready(function () {
        var import_form = $('#pb-import-form select');
        import_form.on('change', function () {
            var poi = import_form.attr('value');
            if (poi === 'zip') {
                $('#pb-file').append('<h2 class="poi-notify">Importing a book with equations? Consider activating <a href="plugins.php">WP QuickLaTeX</a> for improved equation rendering.</h2>');

            } else {
                $('h2').detach('.poi-notify');
            }
        })

    });

})(jQuery);