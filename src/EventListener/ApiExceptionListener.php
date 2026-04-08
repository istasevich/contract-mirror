<?php

namespace App\EventListener;

use App\Shared\Exception\ReportLockedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

final class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if ($exception instanceof ReportLockedException && !$this->isApi($request)) {
            $event->setResponse(new Response(
                '<h1>🔒 Report is locked</h1><p>Please unlock to download PDF.</p>',
                403
            ));
            return;
        }

        if ($exception instanceof ReportLockedException) {
            $event->setResponse(new JsonResponse([
                'error' => 'report_locked',
                'message' => $exception->getMessage(),
            ], 403));
        }
    }

    private function isApi($request): bool
    {
        return str_contains($request->headers->get('Accept'), 'application/json');
    }
}
