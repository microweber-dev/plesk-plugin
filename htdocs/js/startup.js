var $j = jQuery.noConflict();
$j(document).ready(function() {

    $j('body').on('click', '.js-download-app', function() {

        $j('.js-download-app').attr('disabled','disabled');

        let updateLink = $j('.js-download-app').data('update-link');

        $j.get(updateLink, function(data) {
            $j('.js-download-app').html('Installing...');
        });

    });

});
