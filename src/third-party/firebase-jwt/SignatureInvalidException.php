<?php

namespace Firebase\JWT;

if (!class_exists('Firebase\JWT\SignatureInvalidException')) {
    class SignatureInvalidException extends \UnexpectedValueException
    {
    }
}
