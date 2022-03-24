var $j = jQuery.noConflict();
$j(document).ready(function() {
    checkServerSupportPhpVersion();
});

function checkServerSupportPhpVersion() {

    $j.get('/modules/microweber/index.php/phpupgradewizard/checkServerSupportPhpVersion', function(data) {
        if (data.supported) {
            $j('.js-next-step').show();
            $j('.js-check-server-support-php-version').html('<div class="pul-alert pul-alert--success pul-status-message pul-status-message--success">Success! Your server meet the following requirements.</div>');
        } else {
            $j('.js-upgrade-php').show();
            $j('.js-check-server-support-php-version').html('<div class="pul-alert pul-alert--danger pul-status-message pul-status-message--danger">Failed! Your server doesn\'t meet the following requirements. Please update your PHP versions of the server.</div>');
        }
    })

}

