<?php

namespace Webbuilders\Facebook\Extension;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBVarchar;

class FacebookMemberExtension extends DataExtension
{
    private static $db = [
        "FacebookID" => DBVarchar::class,
    ];

    public function FBProfilePicture()
    {
        return 'https://graph.facebook.com/' . $this->owner->FacebookID . '/picture?width=80&height=80';
    }
}
