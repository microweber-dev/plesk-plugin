var $j = jQuery.noConflict();
$j(document).ready(function() {
    showOutdatedDomains();
});

function showOutdatedDomains() {

    $j.get('/modules/microweber/index.php/phpupgradewizard/getOutdatedDomains', function(data) {
        $j('.js-show-outdated-domains').html('');
        $j.each(data.outdated_domains, function(i, item) {
            $j('.js-show-outdated-domains').append('<b><span class="pul-text pul-text--warning">Outdated:</span> ' + item + '</b> <br />');
        });
        $j('.js-upgrade-websites').removeAttr('disabled','disabled');
        $j('.js-upgrade-websites').show();
    });

}
