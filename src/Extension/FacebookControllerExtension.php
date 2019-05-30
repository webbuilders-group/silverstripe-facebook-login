<?php

namespace Webbuilders\Facebook\Extension;

use Facebook\Facebook;
use Facebook\SignedRequest;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Member;
use Facebook\GraphNodes\GraphUser;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
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
        "FBRegisterForm",
        "registerFBMember",
    ];

    public function onAfterInit()
    {
        $appID = Config::inst()->get(self::class, "fb_app_id");
        $this->facebook = new Facebook([
            'app_id' => $appID,
            'app_secret' => Config::inst()->get(self::class, "fb_app_secret"),
            'default_graph_version' => "v3.3",
        ]);
        Requirements::set_force_js_to_bottom(true);

        Requirements::customScript(
            <<<JS
            var FBID = $appID;
JS
        );

        Requirements::javascript("resources/vendor/webbuilders-group/silverstripe-facebook-login/javascript/facebooklogin.js");
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
            $this->loginMember($member, $creds, $request);
            return $this->owner->redirect($request->getVar("backURL"));
        }

        // If the user doesn't exist send them to the registration form
        return $this->owner->redirect($request->getVar("backURL") . "?signed_request=" . $request->getVar('signed_request') . "&backURL=" . $request->getVar("backURL"));
    }

    public function FBRegisterForm(HTTPRequest $request)
    {
        $fields = FieldList::create(
            $first = TextField::create("FirstName"),
            $last = TextField::create("Surname", "Last Name"),
            $email = EmailField::create("Email"),
            $fb = HiddenField::create("FacebookID"),
            HiddenField::create("backURL")->setValue($request->getVar("backURL"))
        );

        if (array_key_exists("signed_request", $request->getVars())) {
            $request->getSession()->set('signed_request', $request->getVar('signed_request'));
            $sr = new SignedRequest($this->facebook->getApp(), $request->getVar('signed_request'));
            $resp = $this->facebook->sendRequest(
                'GET',
                $sr->getUserId(),
                [
                    "fields" => "first_name,last_name,email",
                ],
                $this->facebook->getApp()->getAccessToken()
            );

            $user = $resp->getGraphObject(GraphUser::class);

            $first->setValue($user->getFirstName());
            $last->setValue($user->getLastName());
            $email->setValue($user->getEmail());
            $fb->setValue($user->getId());
        }

        $form = Form::create(
            $this->owner,
            "FBRegisterForm",
            $fields,
            FieldList::create(
                FormAction::create("registerFBMember", "Sign Up")
            )
        );

        $this->owner->extend("updateFBRegisterForm", $form);

        return $form;
    }

    /**
     * Register a member with data
     * @param  array $data
     * @param  object $form
     * @return Member
     */
    public function registerFBMember($data, Form $form)
    {
        $valid = true;

        $results = new \stdClass();
        $this->owner->extend("validateFBRegister", $data, $form, $results);

        if (!$results->valid) {
            $this->owner->request->getSession()->set('FormInfo.Form_FBRegistrationForm.data', $data);
            return $this->owner->redirect($results->redirectTo);
        }

        $member = new Member();

        $form->saveInto($member);

        if (class_exists(FluentState::class)) {
            $member->Language = FluentState::singleton()->getLocale();
        }

        $this->owner->extend("onBeforeFBRegister", $member);

        $member->write();

        $this->owner->extend("onAfterFBRegister", $member);

        return $this->owner->redirect($data['backURL']);
    }

    private function loginMember(Member $member, array $credentials, HTTPRequest $request)
    {
        /** IdentityStore */
        $rememberMe = (isset($credentials['Remember']) && Security::config()->get('autologin_enabled'));
        /** @var IdentityStore $identityStore */
        $identityStore = Injector::inst()->get(IdentityStore::class);
        $identityStore->logIn($member, $rememberMe, $request);
    }
}
