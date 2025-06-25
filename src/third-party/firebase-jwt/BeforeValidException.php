<?php

namespace Firebase\JWT;

if (!class_exists('Firebase\JWT\BeforeValidException')) {
    class BeforeValidException extends \UnexpectedValueException implements JWTExceptionWithPayloadInterface
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