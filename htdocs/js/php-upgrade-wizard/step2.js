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

    $j.post('/modules/microweber/index.php/phpupgradewizard/updateHostingPlansPhpVersion',{php_handler_id:selectedPhpHandler}, function (data) {
        $j('.js-upgrade-hosting-plans').hide();
        $j('.js-next-step').show();
    });

}

function checkHostingPlansSupportPhpVersion() {

    $j.get('/modules/microweber/index.php/phpupgradewizard/checkHostingPlansSupportPhpVersion', function(data) {

        $j('.js-check-hosting-plans-support-php-version').html('<div>Your hosting plans</div><br />');

        $j.each(data.hosting_plans, function(iPlan, planItem) {

            var html = '<div class="pul-switches-panel-item pul-switches-panel__item" style="width: 100%">' +
                '<h5 class="pul-switches-panel-item__title">'+planItem['name']+' - <b>PHP'+planItem['php-handler']['version']+'</b></h5>' +
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

