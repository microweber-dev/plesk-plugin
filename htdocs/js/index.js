HTMLElement.prototype.decodeForm = function () {
    var obj = {};
    var elements = this.querySelectorAll("input, hidden, select, textarea");
    for (var i = 0; i < elements.length; ++i) {
        var element = elements[i];
        var name = element.name;
        var value = element.value;

        if (name) {
            obj[name] = value;
        }
    }
    return obj;
}

function openSetupForm(instance) {

    domainSetupPopup = new Jsw.PopupForm({
        singleRowButtons: true
    });

    domainSetupPopup.setBoxType('form-box');
    domainSetupPopup.setTitle('General Settings');

    closeButtonHtml = '<button class="btn pull-right" onclick="domainSetupPopup.hide();" type="button" value="" style="width:200px;margin-top:15px;height:40px;">Close</button>';

    domainSetupPopup.setHint('<div style="height:250px;">Loading...</div>' + closeButtonHtml);

    var domainFormData = instance.parentNode.decodeForm();

    var xhr = new XMLHttpRequest();
    xhr.open('post', '/modules/microweber/index.php/index/domaindetails')
    xhr.send(new FormData(instance.parentNode));

    xhr.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {

            var responseDataJson = JSON.parse(this.responseText);

            var html_form_filed = '<form method="post">';

            html_form_filed += '<div class="js-mw-form-messages"></div>';

            html_form_filed += '<div class="mtop15">';
            html_form_filed += '<small>Admin url *</small><br />';
            html_form_filed += '<input type="text" value="'+responseDataJson.admin_url+'" name="admin_url" class="form-control" style="width:100%">';
            html_form_filed += '</div>';

            html_form_filed += '<div class="mtop15">';
            html_form_filed += '<small>Administrator</small> <br />';
            html_form_filed +=  responseDataJson.admin_username;
            html_form_filed += '</div>';

            html_form_filed += '<div class="mtop15">';
            html_form_filed += '<small>Current password</small> <br />';
            html_form_filed += '<input type="password" value="' + responseDataJson.admin_password + '" class="js-mw-current-password" style="width:140px;box-shadow:none !important;padding:0px;border:0px">';
            html_form_filed += '<button type="button"  onclick="toggleTextPassword(this);" class="btn btn-info">show</button>';
            html_form_filed += '</div>';

            html_form_filed += '<div class="mtop15">';
            html_form_filed += '<small>New password</small><br />';
            html_form_filed += '<input type="password" value="'+responseDataJson.admin_password+'" name="admin_password" class="form-control js-mw-admin-password" style="width:325px">';
            html_form_filed += '<button type="button" onclick="togglePassword(this);" class="btn btn-info">show</button>';
            html_form_filed += '<button type="button" onclick="generatePassword();" class="btn btn-info">Generate Password</button>';
            html_form_filed += '</div>';

            html_form_filed += '<div class="mtop15">';
            html_form_filed += '<small>Administrator\'s email *</small><br />';
            html_form_filed += '<input type="text" value="'+responseDataJson.admin_email+'" name="admin_email" class="form-control" style="width:100%">';
            html_form_filed += '</div>';


            html_form_filed += '<div class="mtop15 mbottom25">';
            html_form_filed += '<small>Website Language</small><br />';
            html_form_filed += '<select type="text" name="website_language" class="form-control" style="width:100%">';

            for (let [key, value] of Object.entries(responseDataJson.languages)) {
                if (responseDataJson.language == key) {
                    html_form_filed += '<option value="'+key+'" selected="selected">'+value+'</option>';
                } else {
                    html_form_filed += '<option value="'+key+'">'+value+'</option>';
                }
            }

            html_form_filed += '</select>';
            html_form_filed += '</div>';

            html_form_filed += '<button class="btn action" onclick="submitDomainChanges(this)" type="button" value="" style="width:200px;margin-top:15px;height:40px;">Change</button>';
            html_form_filed +=  closeButtonHtml;
            html_form_filed += '<input type="hidden" value="'+ responseDataJson.admin_username + '" name="admin_username">';
            html_form_filed += '<input type="hidden" value="'+ responseDataJson.domain_id + '" name="domain_id">';
            html_form_filed += '<input type="hidden" value="'+ domainFormData.website_url + '" name="website_url">';
            html_form_filed += '<input type="hidden" value="'+ domainFormData.document_root + '" name="document_root">';

            html_form_filed += '<a onclick="removeDomainAppInstallation(this)" style="color:#c62d2d;margin-top: 15px;display: block;">Uninstall app</a>';
            html_form_filed += '</form>';

            domainSetupPopup.setHint(html_form_filed);
        }
    }

}

function toggleTextPassword(instance) {

    var getPasswordInput = document.getElementsByClassName("js-mw-current-password")[0];
    if (getPasswordInput.getAttribute('type') == 'password') {
        instance.innerHTML = 'hide';
        getPasswordInput.setAttribute('type','text')
    } else {
        instance.innerHTML = 'show';
        getPasswordInput.setAttribute('type','password')
    }
}

function togglePassword(instance) {

    var getPasswordInput = document.getElementsByClassName("js-mw-admin-password")[0];
    if (getPasswordInput.getAttribute('type') == 'password') {
        instance.innerHTML = 'hide';
        getPasswordInput.setAttribute('type','text')
    } else {
        instance.innerHTML = 'show';
        getPasswordInput.setAttribute('type','password')
    }

}

function generatePassword() {

    var length = 9,
        charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%^&*()_+~`|}{[]\:;?><,./-=",
        retVal = "";
    for (var i = 0, n = charset.length; i < length; ++i) {
        retVal += charset.charAt(Math.floor(Math.random() * n));
    }

    var getPasswordInput = document.getElementsByClassName("js-mw-admin-password")[0];
    getPasswordInput.setAttribute('value', retVal);

}

function removeDomainAppInstallation(instance) {
    if (confirm('Are you sure you want to uninstall the app from this website?')) {
        var xhr = new XMLHttpRequest();
        xhr.open('post', '/modules/microweber/index.php/index/domainappuninstall')
        xhr.send(new FormData(instance.parentNode));

        xhr.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                var responseDataJson = JSON.parse(this.responseText);
                if (responseDataJson.status == 'success') {

                }
            }
        };
    }
}

function submitDomainChanges(instance) {

    instance.setAttribute('disabled', 'disabled');
    instance.innerHTML = 'Changing...';

    var xhr = new XMLHttpRequest();
    xhr.open('post', '/modules/microweber/index.php/index/domainupdate')
    xhr.send(new FormData(instance.parentNode));

    xhr.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {

            var responseDataJson = JSON.parse(this.responseText);
            var messageBox = document.getElementsByClassName('js-mw-form-messages')[0];

            if (responseDataJson.status == 'success') {

                instance.removeAttribute('disabled');
                instance.innerHTML = 'Change';

                messageBox.innerHTML = '<div class="msg-box msg-info">\n' +
                    '<div class="msg-content">\n' +
                    responseDataJson.message +
                    '</div>\n' +
                    '</div>';
            } else {

                instance.removeAttribute('disabled');
                instance.innerHTML = 'Change';

                messageBox.innerHTML = '<div class="msg-box msg-error">\n' +
                    '<div class="msg-content">\n' +
                    responseDataJson.message +
                    '</div>\n' +
                    '</div>';
            }
        }
    }
}