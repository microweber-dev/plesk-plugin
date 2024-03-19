var $j = jQuery.noConflict();
$j(document).ready(function() {

    function getLongTaskStatus(callback = null) {
        $j.get('/modules/microweber/index.php/task/taskstatuses', function (data) {

            let templatesDownloadStatus = false;
            let appDownloadStatus = false;
            if (data.tasks.templates_download) {
                if (data.tasks.templates_download.status == 'running') {
                    $j('.js-download-app').attr('disabled','disabled');
                    $j('.js-download-app').html('Installing templates...');
                    templatesDownloadStatus = 'running';
                }
            }
            if (data.tasks.app_download) {
                if (data.tasks.app_download.status == 'running') {
                    $j('.js-download-app').attr('disabled', 'disabled');
                    $j('.js-download-app').html('Installing app...');
                    appDownloadStatus = 'running';
                }
            }
            if (data.app_installed && data.tasks.app_download.status == 'done') {
                window.location.href = '/modules/microweber/index.php/index';
            }
            callback({
                templatesDownloadStatus: templatesDownloadStatus,
                appDownloadStatus: appDownloadStatus,
            });
        });
    }

    getLongTaskStatus((getLongTaskStatuses)=> {
        if (getLongTaskStatuses) {
            // Some tasks are running
            if (
                (getLongTaskStatuses.templatesDownloadStatus == 'running') ||
                (getLongTaskStatuses.appDownloadStatus == 'running')
            ) {
                let longTaskStatusInterval = setInterval(function () {
                    getLongTaskStatus(checkStatus => {
                        if (checkStatus.templatesDownloadStatus !== 'running'
                            && checkStatus.appDownloadStatus !== 'running') {
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
            window.location.reload();
        });

    });

});
