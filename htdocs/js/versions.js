var $j = jQuery.noConflict();
$j(document).ready(function() {

    $j('body').on('click', '.js-check-for-update', function() {

        $j('.js-check-for-update').attr('disabled','disabled');
        $j('.js-check-for-update').html('Loading...');

        redirectToUpdateLink = $j('.js-check-for-update').data('update-link');

        setTimeout(function () {
            $j('.js-check-for-update').html('Checking free disk space...');
        }, 2000);

        $j.get('/modules/microweber/index.php/index/checkServerDiskSpace', function(data) {
            if (data.is_ok) {
                window.location.href = redirectToUpdateLink;
            } else {
                $j('.js-check-for-update').html('No disk space. Can\'t download the app.');
            }
        })

    });

});
