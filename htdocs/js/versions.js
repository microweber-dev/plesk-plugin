var $j = jQuery.noConflict();
$j(document).ready(function() {

    function getLongTaskStatus() {
        $j.get('/modules/microweber/index.php/task/taskstatuses', function (data) {
            if (data.tasks.templates_download) {
                if (data.tasks.templates_download.status == 'running') {
                    $j('.js-check-for-update').attr('disabled','disabled');
                    $j('.js-check-for-update').html('Installing templates...');
                }
            }
        });
    }

    getLongTaskStatus();

    $j('body').on('click', '.js-check-for-update', function() {

        $j('.js-check-for-update').attr('disabled','disabled');

        let updateLink = $j('.js-check-for-update').data('update-link');

        $j.get(updateLink, function(data) {
            $j('.js-check-for-update').html('Installing...');

            setInterval(function() {
                getLongTaskStatus();
            }, 3000);

        });

    });

});
