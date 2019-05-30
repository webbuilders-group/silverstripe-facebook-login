jQuery(document).ready(function ($) {

    /**
     * Set up the Facebook APIs
     */
    window.fbAsyncInit = function () {
        FB.init({
            appId: FBID,
            xfbml: true,
            version: 'v3.3'
        });
    };

    (function (d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) { return; }
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_US/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
    // *****************************************************

    // FB Login link on the login dialog
    $('#fb_login').click(function (e) {
        e.preventDefault();
        FB.login(function (response) {
            if (response.authResponse && response.status == 'connected') {
                window.location.href = (CurrentLang === "fr_CA" ? "fr/home/" : "home/") + "fbauth?signed_request=" + response.authResponse.signedRequest + "&backURL=" + e.target.getAttribute("data-reg-url");
            }
        }, { scope: 'email' });
        return false;
    });

    // registration via FB
    $("#fbconnect").on('click', function (e) {
        e.preventDefault();

        FB.login(function (response) {
            if (response.authResponse) {
                if (response.status == 'connected') {
                    window.location.href = (CurrentLang === "fr_CA" ? "fr/home/" : "home/") + "fbauth?signed_request=" + response.authResponse.signedRequest + "&backURL=" + e.target.getAttribute("data-reg-url");
                }
            } else {
                // console.log('User cancelled login or did not fully authorize.');
            }
        }, { scope: 'public_profile,email' });
        return false;
    });

});