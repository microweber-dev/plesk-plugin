var $j = jQuery.noConflict();
$j(document).ready(function() {
    checkHostingPlansSupportPhpVersion();
});

function checkHostingPlansSupportPhpVersion() {

    $j.get('/modules/microweber/index.php/phpupgradewizard/checkHostingPlansSupportPhpVersion', function(plansData) {

        $j('.js-check-hosting-plans-support-php-version').html('');

        $j.each(plansData.hosting_plans, function(iPlan, planItem) {

            var html = '<div class="pul-switches-panel-item pul-switches-panel__item" style="width: 100%">' +
                '<h5 class="pul-switches-panel-item__title">'+planItem['name']+' - <b>PHP'+planItem['php-handler']['version']+'</b></h5>' +
                '<div class="pul-switches-panel-item__actions">' +

                '</div>' +
                '</div>';

            $j('.js-check-hosting-plans-support-php-version').append(html);
        });

    })

}

