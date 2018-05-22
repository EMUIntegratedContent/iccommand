<?php
namespace App\Event;

use LdapTools\Bundle\LdapToolsBundle\Event\LoadUserEvent;

class LoadUserListener
{
    public function beforeLoadUser(LoadUserEvent $event)
    {
      /*
        // Get the username to be loaded...
        $username = $event->getUsername();
        // Get the domain for the username...
        $domain = $event->getDomain();

        // Do something with the username/domain before it hits the user provider...
        */
    }

    public function afterLoadUser(LoadUserEvent $event)
    {
      /*
        // Get the username that was loaded...
        $username = $event->getUsername();
        // Get the domain for the username...
        $domain = $event->getDomain();
        // Get the actual user instance...
        $user = $event->getUser();
        // Get the LDAP object the user was loaded from...
        $ldapObject = $event->getLdapObject();

        // Do something with the user/username/domain/LDAP attributes before it is authenticated...
        foreach($ldapObject->toArray() as $attribute => $value) {
            # ...
        }
        */
    }
}
