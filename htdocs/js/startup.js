var $j = jQuery.noConflict();
$j(document).ready(function() {

    $j('body').on('click', '.js-download-app', function() {

        $j('.js-download-app').attr('disabled','disabled');

        redirectToUpdateLink = $j('.js-download-app').data('update-link');

        $j('.js-update-messages').html('Checking free disk space...');

        $j.get('/modules/microweber/index.php/index/checkServerDiskSpace', function(data) {
            if (data.is_ok) {
                window.location.href = redirectToUpdateLink;
            } else {
                $j('.js-update-messages').html('' +
                    '<div class="pul-alert pul-alert--danger pul-status-message pul-status-message--danger">' +
                    'No enough disk space. Can\'t download the app.' +
                    '<br />Your available disk space is <b>'+data.available_disk_space+'GB</b>, but required disk space is <b>'+data.required_disk_space+'</b>'+
                    '</div>');
            }
        })

    });

});
