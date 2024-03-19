var $j = jQuery.noConflict();
$j(document).ready(function() {

    function getLongTaskStatus(callback = null) {
        $j.get('/modules/microweber/index.php/task/taskstatuses', function (data) {
            if (data.tasks.templates_download) {
                if (data.tasks.templates_download.status == 'running') {
                    $j('.js-download-app').attr('disabled','disabled');
                    $j('.js-download-app').html('Installing templates...');
                }
            }
            if (data.tasks.app_download) {
                if (data.tasks.app_download.status == 'running') {
                    $j('.js-download-app').attr('disabled', 'disabled');
                    $j('.js-download-app').html('Installing app...');
                }
            }
            if (data.app_installed && data.tasks.app_download.status == 'done') {
                window.location.href = '/modules/microweber/index.php/index';
            }
            if (callback) {
                callback(data);
            }
        });
    }

    getLongTaskStatus((getLongTaskStatuses)=> {
        if (getLongTaskStatuses) {
            // Some tasks are running
            if (
                getLongTaskStatuses.tasks.templates_download.status == 'running' ||
                getLongTaskStatuses.tasks.app_download.status == 'running'
            ) {
                let longTaskStatusInterval = setInterval(function() {
                    getLongTaskStatus(checkStatus => {
                        if (checkStatus.tasks.templates_download.status !== 'running'
                            && checkStatus.tasks.app_download.status !== 'running') {
                            clearInterval(longTaskStatusInterval);
                        }
                    });
                }, 3000);
            }
        }
    });

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
