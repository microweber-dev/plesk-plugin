function openPhpUpgradeWizard() {

    domainSetupPopup = new Jsw.PopupForm({
        singleRowButtons: true
    });

    domainSetupPopup.setBoxType('form-box');
    domainSetupPopup.setTitle('PHP Upgrade Wizard');

    closeButtonHtml = '<button class="btn pull-right" onclick="domainSetupPopup.hide();" type="button" value="" style="width:200px;margin-top:15px;height:40px;">Close</button>';
    domainSetupPopup.setHint('<div style="height:250px;">Loading...</div>' + closeButtonHtml);



   /* var xhr = new XMLHttpRequest();
    xhr.open('get', '/modules/microweber/index.php/index/index')
    xhr.send();

    xhr.onreadystatechange = function () {
        if (this.readyState == 4 && this.status == 200) {

            domainSetupPopup.setHint(this.responseText);
        }
    }*/

}