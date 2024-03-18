var $j = jQuery.noConflict();
$j(document).ready(function() {

    function getLongTaskStatus() {
        $j.get('/modules/microweber/index.php/task/taskstatuses', function (data) {
            console.log(data);
            if (data.app_download) {
                if (data.app_download.status == 'done') {
                    window.location.href = '/modules/microweber/index.php/index';
                }
            }
            if (data.templates_download) {
                if (data.templates_download.status == 'running') {
                    $j('.js-download-app').attr('disabled','disabled');
                    $j('.js-download-app').html('Installing templates...');
                }
                if (data.templates_download.status == 'done') {
                    window.location.href = '/modules/microweber/index.php/index';
                }
            }
        });
    }

    getLongTaskStatus();

    $j('body').on('click', '.js-download-app', function() {

        $j('.js-download-app').attr('disabled','disabled');

        let updateLink = $j('.js-download-app').data('update-link');

        $j.get(updateLink, function(data) {
            $j('.js-download-app').html('Installing...');
        });

    });

});
