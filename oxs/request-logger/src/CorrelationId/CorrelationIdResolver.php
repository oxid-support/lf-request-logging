<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\CorrelationId;

class CorrelationIdResolver implements CorrelationIdResolverInterface
{
    public function __construct(
        private CorrelationIdValidatorInterface $validator,
        //private string $headerName,
        private string $cookieName,
    ) {}

    public function resolve(): ?string
    {
        /*
         * @todo
         * Optional: also check as HTTP header (e.g., for APIs, proxies, distributed tracing)
         * in case an api has must be observed.
         */
        /*
        $id = $_SERVER[$this->headerName] ?? null;
        if (is_string($id) && $this->validator->isValid($id)) {
            self::emit($id);
            return $id;
        }
        */

        $id = $_COOKIE[$this->cookieName] ?? null;

        return (is_string($id) && $this->validator->isValid($id)) ? $id : null;
    }
}
