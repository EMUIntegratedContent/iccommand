<?php
namespace App\Event;

use LdapTools\Bundle\LdapToolsBundle\Event\LdapLoginEvent;

class LdapLoginListener
{
    public function onLdapLoginSuccess(LdapLoginEvent $event)
    {
        $hr = "I like bacon";
        var_dump($hr);
        die();
        // Do something with the user/password combo...
    }
}
