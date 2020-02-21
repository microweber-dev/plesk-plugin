var $j = jQuery.noConflict();
$j(document).ready(function() {

    $j('.js-microweber-install-form').show();
    $j('#installation_password').attr('type', 'password');

    $j(document).keyup('#installation_folder', function() {
        if ($j('#installation_folder').val().match(/[^a-zA-Z0-9 ]/g)) {
            $j('#installation_folder').val($j('#installation_folder').val().replace(/[^a-zA-Z0-9 ]/g, ''));
        }
        // console.log($j('#installation_folder').val());
    });

    $j('#installation_domain').after(' / <input type="text" name="installation_folder" id="installation_folder" value="microweber" class="input-text">');


    var show_password = '&nbsp; <a class="toggle-password">Show</a>';
    $j('#installation_password').after(show_password);

    $j('.toggle-password').click(function() {
        if ($j(".toggle-password").text() == 'Show') {
            $j('.toggle-password').html('Hide');
            $j('#installation_password').attr('type', 'text');
        } else {
            $j('.toggle-password').html('Show');
            $j('#installation_password').attr('type', 'password');
        }
    });

    $j('#installation_domain-form-row').after('<div class="domain-message"></div>');

    $j('#installation_folder').keyup(function() {
        checkInstallPath();
    });

    $j('#installation_domain').change(function() {
        checkInstallPath();
    });

    checkInstallPath();
});

function checkInstallPath() {

    $j('#pm-form-simple').find('.domain-message').html('');

    $j.ajax({
        url:"check_install_path",
        data: $j("#pm-form-simple").serialize(),
        dataType: "json"
    }).done(function(result) {

        warning_html = false;

        if (result.found_app) {
            warning_html = ''+
                '<div  style="background: #fff7eb;padding: 15px;">'+
                '<span>'+
                'Microweber is already installed on the target. <br />If you proceed with installing, '+
                'this Microweber installation will be overwritten. Overwrite instance on target?'+
                '</span>'+
                '</div>';
        } else if (result.found_thirdparty_app) {
            warning_html = ''+
                '<div  style="background: #fff7eb;padding: 15px;">'+
                '<span>'+
                'Some files exists on the target. <br />If you proceed with installing, '+
                'this Microweber installation will be overwritten. Overwrite instance on target?'+
                '</span>'+
                '</div>';
        }

        if (warning_html) {
            $j('#pm-form-simple').find('.domain-message').html('<div class="form-row"><div class="field-name"></div><div class="field-value">'+warning_html+'</div></div>');
        }
    });
}