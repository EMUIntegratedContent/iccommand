<?php
namespace App\Event;

use LdapTools\Bundle\LdapToolsBundle\Event\LdapLoginEvent;

class LdapLoginListener
{
    public function onLdapLoginSuccess(LdapLoginEvent $event)
    {
      /*
        $user = $event->getUser();
        // Get the credentials they used for the login...
        $password = $event->getToken()->getCredentials();
        // Do something with the user/password combo...
        */
    }
}
