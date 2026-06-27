<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Internal;

use CardTechie\TradingCardApiSdk\Internal\Resources\AuditLog;
use CardTechie\TradingCardApiSdk\Internal\Resources\Workflow;
use GuzzleHttp\Client;

/**
 * InternalClient — exposes workflow and audit-log resources that live behind
 * the `/internal/*` API routes and require the `internal` OAuth scope.
 *
 * **Not part of the public SDK contract.** This class and its resources may
 * change without semver guarantees. It is intended for internal callers
 * (admin tooling, tradingcardapi-mcp, tradingcardapi-tools) whose credentials
 * carry the `internal` OAuth scope.
 */
class InternalClient
{
    /**
     * Authentication type ('oauth2' or 'pat')
     */
    private string $authType;

    /**
     * Personal Access Token (for PAT auth mode)
     */
    private ?string $personalAccessToken;

    /**
     * OAuth2 Client ID
     */
    private ?string $clientId;

    /**
     * OAuth2 Client Secret
     */
    private ?string $clientSecret;

    /**
     * OAuth2 Scope
     */
    private ?string $scope;

    public function __construct(
        private Client $client,
        string $authType = 'oauth2',
        ?string $personalAccessToken = null,
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?string $scope = null
    ) {
        $this->authType = $authType;
        $this->personalAccessToken = $personalAccessToken;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->scope = $scope;
    }

    /**
     * Create a resource instance with auth information
     *
     * @template T of object
     *
     * @param  class-string<T>  $resourceClass
     * @return T
     */
    private function createResource(string $resourceClass): object
    {
        $resource = new $resourceClass($this->client);

        if (method_exists($resource, 'setAuthInfo')) {
            $resource->setAuthInfo(
                $this->authType,
                $this->personalAccessToken,
                $this->clientId,
                $this->clientSecret,
                $this->scope
            );
        }

        return $resource;
    }

    /**
     * Retrieve the internal workflow resource.
     */
    public function workflow(): Workflow
    {
        return $this->createResource(Workflow::class);
    }

    /**
     * Retrieve the internal audit log resource.
     */
    public function auditLog(): AuditLog
    {
        return $this->createResource(AuditLog::class);
    }
}
