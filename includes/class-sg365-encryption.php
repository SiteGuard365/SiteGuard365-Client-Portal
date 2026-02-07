<?php
/**
 * Encryption helpers.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SG365_Encryption {
	private $cipher = 'aes-256-cbc';

	private function get_key() {
		$raw = AUTH_SALT;
		return hash( 'sha256', $raw, true );
	}

	public function encrypt( $value ) {
		if ( '' === $value || null === $value ) {
			return '';
		}

		$iv = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $this->cipher ) );
		$encrypted = openssl_encrypt( $value, $this->cipher, $this->get_key(), 0, $iv );
		if ( false === $encrypted ) {
			return '';
		}

		return base64_encode( $iv . '::' . $encrypted );
	}

	public function decrypt( $payload ) {
		if ( '' === $payload || null === $payload ) {
			return '';
		}

		$decoded = base64_decode( $payload, true );
		if ( false === $decoded ) {
			return '';
		}

		$parts = explode( '::', $decoded, 2 );
		if ( 2 !== count( $parts ) ) {
			return '';
		}

		return openssl_decrypt( $parts[1], $this->cipher, $this->get_key(), 0, $parts[0] );
	}
}
