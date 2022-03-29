var $j = jQuery.noConflict();
$j(document).ready(function() {

    $j('body').on('click', '.js-check-for-update', function() {

        $j('.js-check-for-update').attr('disabled','disabled');
        $j('.js-check-for-update').html('Loading...');

        setTimeout(function () {
            $j('.js-check-for-update').html('Checking free disk space...');
        }, 5000);

        $j.get('/modules/microweber/index.php/index/checkServerDiskSpace', function(data) {

        })

    });

});
