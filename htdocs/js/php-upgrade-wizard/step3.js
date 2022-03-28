var $j = jQuery.noConflict();
$j(document).ready(function() {

    showOutdatedDomains();

    $j('body').on('click', '.js-upgrade-websites-btn', function() {

        domainIds = [];
        getDomainIds = $j("input[name='domain_ids[]']").serializeArray();
        $j.each(getDomainIds, function (i, item) {
            domainIds.push(item.value);
        });

        $j('.js-upgrade-websites-btn').attr('disabled', 'disabled');
        $j('.js-upgrade-websites-btn').html('Updating websites...');

        setTimeout(function () {
            $j('.js-messages').html('<span>This maybe can take a time, so please don\'t refresh the page...</span>');
        }, 5000);

        $j.post('/modules/microweber/index.php/phpupgradewizard/updateWebsitesPhpVersion',{
            domain_ids:domainIds,
        }, function (data) {
            $j('.js-upgrade-websites-btn').hide();
            $j('.js-next-step-btn').show();
        });
    });

});

function showOutdatedDomains() {

    $j.get('/modules/microweber/index.php/phpupgradewizard/getOutdatedDomains', function(data) {
        $j('.js-show-outdated-domains').html('');
        $j.each(data.outdated_domains, function(i, item) {
            $j('.js-show-outdated-domains').append('<b><span class="pul-text pul-text--warning">Outdated:</span> ' + item + '</b> <br />');
        });
        $j.each(data.outdated_domains_ids, function(i, outdatedDomainId) {
            $j('.js-show-outdated-domains').append('<input type="hidden" name="domain_ids[]" value="'+outdatedDomainId+'" />');
        });
        $j('.js-upgrade-websites-btn').removeAttr('disabled','disabled');
        $j('.js-upgrade-websites-btn').show();
    });

}
