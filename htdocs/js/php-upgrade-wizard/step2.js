var $j = jQuery.noConflict();
$j(document).ready(function() {
    checkHostingPlansSupportPhpVersion();
});

function checkHostingPlansSupportPhpVersion() {

    $j.get('/modules/microweber/index.php/phpupgradewizard/checkHostingPlansSupportPhpVersion', function(data) {

        $j('.js-check-hosting-plans-support-php-version').html('');

        $j.each(data.hosting_plans, function(i, item) {

            /*$j.each(data.su, function(i, item) {
                var html = '<p>' +
                    '<label for="radio-16" class="pul-radio">' +
                    '<input class="pul-radio__input" type="radio" name="group2" id="radio-16">' +
                    '<span class="pul-radio__indicator"></span>' +
                    '<span class="pul-radio__text">' + item[''] + '</span>' +
                    '</label>' +
                    '</p>';
            });*/

            var html = '<div class="pul-switches-panel-item pul-switches-panel__item">' +
                '<h5 class="pul-switches-panel-item__title">'+item['name']+' - Current version of PHP'+item['php-handler']['version']+' </h5>' +
                '<div class="pul-switches-panel-item__actions">' +


                '</div>' +
                '</div>';

            $j('.js-check-hosting-plans-support-php-version').append(html);
        });

    })

}

