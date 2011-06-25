<?php

namespace Evario\JanrainBundle\Security\User\Provider;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class JanrainUserProvider implements UserProviderInterface
{
    protected $userManager;
    protected $validator;
    protected $options;
    protected $container;

    public function __construct($userManager, $validator, $options, $container)
    {
        $this->userManager = $userManager;
        $this->validator = $validator;
        $this->options = new ParameterBag($options);
        $this->container = $container;
    }

    public function supportsClass($class)
    {
        return $this->userManager->supportsClass($class);
    }

    public function extractJanrainInfo($token)
    {
        // TODO: Move apiKey to config file and reference it.
        /* STEP 1: Extract token POST parameter */
        $post_data = array('token'  => $token,
                           'apiKey' => $this->options->get('api_key'),
                           'format' => 'json');

        /* STEP 2: Use the token to make the auth_info API call */
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, 'https://rpxnow.com/api/v2/auth_info');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $raw_json = curl_exec($curl);
        curl_close($curl);

        /* STEP 3: Parse the JSON auth_info response */
        return json_decode($raw_json, true);
    }

    public function loadUserByUsername($username)
    {
        // Check by PK
        $user = $this->userManager->findUserBy(array('id' => $username));

        // Check by username
        if (!$user)
            $user = $this->userManager->findUserBy(array('username' => $username));

        // Check by janrainId
        if (!$user)
            $user = $this->userManager->findUserBy(array('janrainId' => $username));

        if (isset($_POST['token']))
        {
            $auth_info = $this->extractJanrainInfo($_POST['token']);
        }
        else
        {
            $auth_info = null;
        }

        if (!$user && $auth_info && $auth_info['stat'] == 'ok') {
            
            /* STEP 3 Continued: Extract the 'identifier' from the response */
            $profile = $auth_info['profile'];
            $identifier = $profile['identifier'];
            $email = $profile['email'];
            $primaryKey = isset($profile['primaryKey']) ? $profile['primaryKey'] : null;

            // This will store whether we need to create a new mapping for this openID.
            $new_map = false;

            // Do we have an account for them yet?
            $user = $this->userManager->findUserBy(array('janrainId' => $identifier));
            
            // If not, check the email.
            if (!$user)
            {
                $user = $this->userManager->findUserBy(array('email' => $email));
            }

            // If we still have not found a user, we need to create a new one.
            if (!$user)
            {
                $user = $this->userManager->createUser();
                $user->setEnabled(true);
                $user->setPassword('');
                $user->setAlgorithm('');

                $username = preg_replace('/\W+/', '_', $profile['preferredUsername']);
                $username = substr(strtolower(trim($username, '_')), 0, 16);
                $user->setUsername($username);

                $user->setJanrainId($identifier);
            }

            // Have we linked this account with Janrain yet? If so, is the link correct?
            if (!$primaryKey || ($user && $primaryKey && $user->getId() != $primaryKey))
            {
                $new_map = true;
                if ($user)
                {
                    $user->setJanrainId($identifier);
                }
            }

            if (count($this->validator->validate($user, 'Janrain'))) {
                // TODO: the user was found obviously, but doesnt match our expectations, do something smart
                throw new UsernameNotFoundException('The social media user could not be stored');
            }

            $this->userManager->updateUser($user);
        }

        if (!isset($user) || !$user) {
            throw new UsernameNotFoundException('The user is not authenticated.');
        }

        return $user;
    }

    public function loadUser(UserInterface $user)
    {
        if (!$this->supportsClass(get_class($user))) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getId());
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof UserInterface) {
            throw new UnsupportedUserException('Account is not supported.');
        }

        return $this->loadUserByUsername($user->getUsername());
    }
}