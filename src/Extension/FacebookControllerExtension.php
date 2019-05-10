<?php

namespace Webbuilders\Facebook\Extension;

use Facebook\Facebook;
use Facebook\SignedRequest;
use SilverStripe\Security\Member;
use Facebook\GraphNodes\GraphUser;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\IdentityStore;
use TractorCow\Fluent\State\FluentState;

class FacebookControllerExtension extends DataExtension
{

    protected $facebook = null;

    private static $allowed_actions = [
        "fbauth",
    ];

    public function onAfterInit()
    {
        $appID = Config::inst()->get(FacebookControllerExtension::class, "fb_app_id");
        $this->facebook = new Facebook([
            'app_id' => $appID,
            'app_secret' => Config::inst()->get(FacebookControllerExtension::class, "fb_app_secret"),
            'default_graph_version' => "v3.3",
        ]);
        Requirements::set_force_js_to_bottom(true);

        Requirements::customScript(<<<JS
            var FBID = $appID;
JS
        );

        Requirements::javascript("resources/vendor/webbuilders/facebook/javascript/facebooklogin.js");
    }

    /**
     * Access the login with facebook link
     *
     * @return string
     */
    public function FBLoginLink()
    {
        $fb = $this->facebook;
        $helper = $fb->getRedirectLoginHelper();
        $permissions = ['email'];

        $loginUrl = $helper->getLoginUrl($this->owner->Link('authenticate'), $permissions);

        return $loginUrl;
    }

    /**
     * Authenticate a FB Login
     * @param  HTTPRequest $request
     * @return void
     */
    public function fbauth(HTTPRequest $request)
    {

        $sr = new SignedRequest($this->facebook->getApp(), $request->getVar('signed_request'));
        $member = Member::get()->filter(array('FacebookID' => $sr->getUserId()))->First();

        // checks to see if this user already is tracked in the system
        if ($member) {
            $creds = ["RememberMe" => false];
            $this->login($member, $creds, $request);
        } else {
            // if it's not make the user
            $resp = $this->facebook->sendRequest(
                'GET',
                $sr->getUserId(),
                [
                    "fields" => "first_name,last_name,email",
                ],
                $this->facebook->getApp()->getAccessToken()
            );

            $user = $resp->getGraphObject(GraphUser::class);

            $member = $this->registerMember($user);

            $identityStore = Injector::inst()->get(IdentityStore::class);
            $identityStore->logIn($member);

        }
        return $this->owner->redirect($request->getVar("backURL"));

    }

    /**
     * Register a member with data
     * @param  array $data
     * @param  object $form
     * @return Memeber
     */
    private function registerMember(GraphUser $user)
    {
        $member = new Member();

        $member->FirstName = $user->getFirstName();
        $member->Surname = $user->getLastName();
        if (class_exists(FluentState::class)) {
            $member->Language = FluentState::singleton()->getLocale();
        }

        $member->FacebookID = $user->getId();
        $member->Email = $user->getEmail();
        $member->write();

        return $member;
    }

    private function login(Member $member, array $credentials, HTTPRequest $request)
    {
        /** IdentityStore */
        $rememberMe = (isset($credentials['Remember']) && Security::config()->get('autologin_enabled'));
        /** @var IdentityStore $identityStore */
        $identityStore = Injector::inst()->get(IdentityStore::class);
        $identityStore->logIn($member, $rememberMe, $request);
    }
}
