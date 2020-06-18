var $j = jQuery.noConflict();
$j(document).ready(function() {

    var installPassword = $j('#installation_password').val();
    var installPasswordMessage = checkStrength(installPassword);
    $j('#installation_password').after('<div id="installation_password_message"></div>');
    $j('#installation_password_message').html(installPasswordMessage);

    $j(document).keyup('#installation_password', function() {
        installPasswordMessage = checkStrength($j('#installation_password').val());
        $j('#installation_password_message').html(installPasswordMessage);
    });

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

function checkStrength(password) {
    var strength = 0
    if (password.length < 6) {
        $j('#btn-send').attr('disabled', 'disabled');
        return '<i style="color:red;">The password is too short.</i>';
    }
    if (password.length > 7) strength += 1
    // If password contains both lower and uppercase characters, increase strength value.
    if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) strength += 1
    // If it has numbers and characters, increase strength value.
    if (password.match(/([a-zA-Z])/) && password.match(/([0-9])/)) strength += 1
    // If it has one special character, increase strength value.
    if (password.match(/([!,%,&,@,#,$,^,*,?,_,~])/)) strength += 1
    // If it has two special characters, increase strength value.
    if (password.match(/(.*[!,%,&,@,#,$,^,*,?,_,~].*[!,%,&,@,#,$,^,*,?,_,~])/)) strength += 1
    // Calculated strength value, we can return messages
    // If value is less than 2
    if (strength < 2) {
        $j('#btn-send').attr('disabled', 'disabled');
        return '<i style="color:red;">The password is too weak.</i>';
    } else if (strength == 2) {
        j('#btn-send').removeAttr('disabled');
        return '<i style="color:yellowgreen;">The password is good.</i>';
    } else {
        $j('#btn-send').removeAttr('disabled');
        return '<i style="color:green;">The password is strong.</i>';
    }
}

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
