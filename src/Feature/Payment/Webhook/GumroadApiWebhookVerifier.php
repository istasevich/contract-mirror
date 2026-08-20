<?php

declare(strict_types=1);

namespace App\Feature\Payment\Webhook;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GumroadApiWebhookVerifier implements WebhookVerifierInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly ?string $sellerId,
        private readonly ?string $accessToken,
    ) {
    }

    public function verify(array $payload): WebhookVerificationResult
    {
        $saleId = $payload['sale_id'] ?? $payload['id'] ?? null;
        $publicId = $payload['url_params']['custom'] ?? null;

        if (!is_string($saleId) || trim($saleId) === '') {
            return WebhookVerificationResult::invalid('missing_sale_id');
        }

        if (!is_string($publicId) || trim($publicId) === '') {
            return WebhookVerificationResult::invalid('missing_public_id');
        }

        if ($this->sellerId === null || trim($this->sellerId) === '' || $this->accessToken === null || trim($this->accessToken) === '') {
            return WebhookVerificationResult::invalid('provider_verification_not_configured');
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api.gumroad.com/v2/sales/' . rawurlencode($saleId), [
                'query' => [
                    'access_token' => $this->accessToken,
                ],
                'timeout' => 10,
                'max_duration' => 15,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getContent(false);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->warning('Gumroad verification request failed.', [
                'sale_id_hash' => hash('sha256', $saleId),
                'error_type' => $exception::class,
            ]);

            return WebhookVerificationResult::invalid('provider_unavailable');
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            return WebhookVerificationResult::invalid('provider_rejected_sale');
        }

        try {
            $verified = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return WebhookVerificationResult::invalid('provider_invalid_response');
        }

        if (!is_array($verified) || ($verified['success'] ?? false) !== true || !isset($verified['sale']) || !is_array($verified['sale'])) {
            return WebhookVerificationResult::invalid('provider_sale_not_found');
        }

        $sale = $verified['sale'];
        $verifiedSellerId = $sale['seller_id'] ?? null;

        if (!is_string($verifiedSellerId) || !hash_equals($this->sellerId, $verifiedSellerId)) {
            return WebhookVerificationResult::invalid('seller_mismatch');
        }

        if (($sale['refunded'] ?? false) === true || ($sale['disputed'] ?? false) === true) {
            return WebhookVerificationResult::invalid('sale_not_payable');
        }

        return new WebhookVerificationResult(
            isValid: true,
            publicId: $publicId,
            providerEventId: $saleId,
        );
    }
}
