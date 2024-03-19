var $j = jQuery.noConflict();
$j(document).ready(function() {

    function getLongTaskStatus(callback = null) {
        $j.get('/modules/microweber/index.php/task/taskstatuses', function (data) {
            let showTemplateUpToDate = true;
            if (data.tasks.templates_download) {
                if (data.tasks.templates_download.status == 'running') {
                    $j('.js-check-for-update').attr('disabled','disabled');
                    $j('.js-check-for-update').html('Installing templates...');
                    $j('.js-templates-up-to-date').hide();
                    showTemplateUpToDate = false;
                }
            }
            if (data.tasks.app_download) {
                if (data.tasks.app_download.status == 'running') {
                    $j('.js-check-for-update').attr('disabled', 'disabled');
                    $j('.js-check-for-update').html('Installing app...');
                }
            }
            if (data.app_installed && showTemplateUpToDate) {
                $j('.js-templates-up-to-date').show();
                $j('.js-check-for-update').removeAttr('disabled');
                $j('.js-check-for-update').html('Check for updates');
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

    $j('body').on('click', '.js-check-for-update', function() {

        $j('.js-check-for-update').attr('disabled','disabled');

        let updateLink = $j('.js-check-for-update').data('update-link');
        $j.get(updateLink, function(data) {
            window.location.reload();
        });

    });

});
