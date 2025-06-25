<?php

namespace Firebase\JWT;

if (!class_exists('Firebase\JWT\ExpiredException')) {
    class ExpiredException extends \UnexpectedValueException implements JWTExceptionWithPayloadInterface
    {
        private object $payload;

        public function setPayload(object $payload): void
        {
            $this->payload = $payload;
        }

        public function getPayload(): object
        {
            return $this->payload;
        }
    }
}