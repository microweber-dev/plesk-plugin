var $j = jQuery.noConflict();
$j(document).ready(function() {

    function getLongTaskStatus() {
        $j.get('/modules/microweber/index.php/task/taskstatuses', function (data) {
            if (data.tasks.templates_download) {
                if (data.tasks.templates_download.status == 'running') {
                    $j('.js-download-app').attr('disabled','disabled');
                    $j('.js-download-app').html('Installing templates...');
                }
            }
            if (data.app_installed) {
                window.location.href = '/modules/microweber/index.php/index';
            }
        });
    }

    getLongTaskStatus();

    $j('body').on('click', '.js-download-app', function() {

        $j('.js-download-app').attr('disabled','disabled');

        let updateLink = $j('.js-download-app').data('update-link');

        $j.get(updateLink, function(data) {
            $j('.js-download-app').html('Installing...');

            setInterval(function() {
                getLongTaskStatus();
            }, 3000);

        });

    });

});
