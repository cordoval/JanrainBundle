<?php

namespace Evario\JanrainBundle\Security\Authentication\Provider;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;

use Evario\JanrainBundle\Security\Authentication\Token\JanrainUserToken;

class JanrainProvider implements AuthenticationProviderInterface
{
    protected $userProvider;
    protected $userChecker;

    public function __construct(UserProviderInterface $userProvider = null, UserCheckerInterface $userChecker = null)
    {
        if (null !== $userProvider && null === $userChecker) {
            throw new \InvalidArgumentException('$userChecker cannot be null, if $userProvider is not null.');
        }

        $this->userProvider = $userProvider;
        $this->userChecker = $userChecker;
    }

    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return null;
        }

        try {
            if ($uid = $this->facebook->getUser()) {
                return $this->createAuthenticatedToken($uid);
            }
        } catch (AuthenticationException $failed) {
            throw $failed;
        } catch (\Exception $failed) {
            throw new AuthenticationException('Unknown error', $failed->getMessage(), $failed->getCode(), $failed);
        }

        throw new AuthenticationException('The Social Media user could not be retrieved from the session.');
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof JanrainUserToken;
    }

    protected function createAuthenticatedToken($uid)
    {
        if (null === $this->userProvider) {
            return new JanrainUserToken($uid);
        }

        $user = $this->userProvider->loadUserByUsername($uid);
        if (!$user instanceof UserInterface) {
            throw new \RuntimeException('User provider did not return an implementation of user interface.');
        }

        $this->userChecker->checkPreAuth($user);
        $this->userChecker->checkPostAuth($user);

        return new JanrainUserToken($user, $user->getRoles());
    }
}