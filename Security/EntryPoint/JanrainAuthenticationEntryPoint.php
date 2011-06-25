<?php

namespace Evario\JanrainBundle\Security\EntryPoint;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JanrainAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{

    /**
     * Constructor
     *
     * @param Facebook $facebook
     * @param array    $options
     */
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        // Not quite sure on this.
        return;
    }
}