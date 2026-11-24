<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FirstConnectionBlocker implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private RouterInterface $router
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 100],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return;
        }

        // Non-admin users must change their password before accessing the application.
        if ($user->isFirstConnection() === true && !in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $routeName = $event->getRequest()->attributes->get('_route');
            $allowedRoutes = ['app_change_password', 'app_logout'];

            // If trying to access any other route, logout
            if (!in_array($routeName, $allowedRoutes)) {
                $this->tokenStorage->setToken(null);
                $event->getRequest()->getSession()->invalidate();
                
                $loginUrl = $this->router->generate('admin_login');
                $event->setController(function() use ($loginUrl) {
                    return new RedirectResponse($loginUrl);
                });
            }
        }
    }
}
