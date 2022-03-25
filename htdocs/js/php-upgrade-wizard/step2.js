var $j = jQuery.noConflict();
$j(document).ready(function() {
    checkHostingPlansSupportPhpVersion();

    $j('body').on('click', '.js-upgrade-hosting-plans', function() {
        upgradeHostingPlans();
    });
});

function upgradeHostingPlans() {

    var selectedPhpHandler = $j("input[type='radio'][name='php_version_id']:checked").val();
    if ((selectedPhpHandler == '') || (typeof selectedPhpHandler === 'undefined')) {
        alert('Please, select PHP version for hosting plans.');
        return;
    }

    hostingPlanIds = [];
    getHostingPlanIds = $j("input[name='hosting_plan_ids[]']").serializeArray();
    $j.each(getHostingPlanIds, function (i, item) {
        hostingPlanIds.push(item.value);
    });

    $j('.js-upgrade-hosting-plans').attr('disabled', 'disabled');
    $j('.js-upgrade-hosting-plans').html('Updating hosting plans...');

    setTimeout(function () {
        $j('.js-upgrade-hosting-plans').append('<span>This maybe can take a time, so please dont refresh the page...</span>');
    }, 5000);

    $j.post('/modules/microweber/index.php/phpupgradewizard/updateHostingPlansPhpVersion',{
        php_handler_id:selectedPhpHandler,
        hosting_plan_ids:hostingPlanIds,
    }, function (data) {
        if (data.updated) {
            window.location.href = window.location.href;
        }
        $j('.js-upgrade-hosting-plans').hide();
        $j('.js-next-step').show();
    });

}

function checkHostingPlansSupportPhpVersion() {

    $j.get('/modules/microweber/index.php/phpupgradewizard/checkHostingPlansSupportPhpVersion', function(data) {

        $j('.js-check-hosting-plans-support-php-version').html('<div>Your hosting plans</div><br />');

        $j.each(data.hosting_plans, function(iPlan, planItem) {

            var html = '<div class="pul-switches-panel-item pul-switches-panel__item" style="width: 100%">' +
                '<h5 class="pul-switches-panel-item__title">'+planItem['name']+' - <b>PHP'+planItem['php-handler']['version']+' (' + planItem['php-handler']['id'] + ')</b></h5>' +
                '<input type="hidden" name="hosting_plan_ids[]" value="'+planItem['id']+'">'+
                '</div>';

            $j('.js-check-hosting-plans-support-php-version').append(html);
        });


        var html = '<div style="padding-left:15px;margin-top:20px;">';
        $j.each(data.supported_php_versions, function(iPhpVersion, phpVersionItem) {
            html += '<p>' +
                '<label for="radio-' + phpVersionItem.id + '" class="pul-radio">' +
                '<input class="pul-radio__input" type="radio" name="php_version_id" value="' + phpVersionItem.id + '" id="radio-' + phpVersionItem.id + '">' +
                '<span class="pul-radio__indicator"></span>' +
                '<span class="pul-radio__text">PHP' + phpVersionItem.version + ' (' + phpVersionItem.id + ')</span>' +
                '</label>' +
                '</p>';
        });
        html += '</div>';

        $j('.js-check-hosting-plans-support-php-version').append(html);
        $j('.js-upgrade-hosting-plans').show();

    });

}

