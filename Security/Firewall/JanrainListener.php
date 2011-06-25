<?php

namespace Evario\JanrainBundle\Security\Firewall;

use Evario\JanrainBundle\Security\Authentication\Token\JanrainUserToken;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\HttpFoundation\Request;

/**
 * Janrain authentication listener.
 */
class JanrainListener extends AbstractAuthenticationListener
{
    protected function attemptAuthentication(Request $request)
    {
        return $this->authenticationManager->authenticate(new JanrainUserToken());
    }
}