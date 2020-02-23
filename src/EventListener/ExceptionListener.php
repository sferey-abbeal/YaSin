<?php

namespace App\EventListener;

use Doctrine\DBAL\DBALException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionListener implements EventSubscriberInterface
{
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();

        $message = $exception->getMessage();
        if ($exception instanceof DBALException) {
            $statusCode = 500;
            $response = new JsonResponse(
                [
                    'status' => $statusCode,
                    'message' => 'Data Base problem',
                ],
                $statusCode
            );
        } elseif ($exception instanceof NotFoundHttpException) {
            $statusCode = 404;
            $response = new JsonResponse(
                [
                    'status' => $statusCode,
                    'message' => 'Not Found',
                ],
                $statusCode
            );
        } else {
            $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;


            $response = new JsonResponse(
                [
                    'status' => $statusCode,
                    'message' => $message,
                ],
                $statusCode
            );
        }
        $response->headers->set('Content-Type', 'application/problem+json');
        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return array(
            KernelEvents::EXCEPTION => 'onKernelException'
        );
    }
}
