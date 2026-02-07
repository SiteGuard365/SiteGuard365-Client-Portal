<?php
/**
 * Encryption helper.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SG365_Encryption {
	/**
	 * Encrypt a value.
	 *
	 * @param string $value Plain text.
	 * @return string Encrypted base64 string.
	 */
	public static function encrypt( $value ) {
		if ( '' === $value ) {
			return '';
		}

		$key = hash( 'sha256', AUTH_SALT );
		$iv  = substr( hash( 'sha256', SECURE_AUTH_SALT ), 0, 16 );
		$raw = openssl_encrypt( $value, 'AES-256-CBC', $key, 0, $iv );

		return $raw ? base64_encode( $raw ) : '';
	}

	/**
	 * Decrypt a value.
	 *
	 * @param string $value Base64 string.
	 * @return string Decrypted value.
	 */
	public static function decrypt( $value ) {
		if ( '' === $value ) {
			return '';
		}

		$key = hash( 'sha256', AUTH_SALT );
		$iv  = substr( hash( 'sha256', SECURE_AUTH_SALT ), 0, 16 );
		$raw = base64_decode( $value );

		if ( false === $raw ) {
			return '';
		}

		$decrypted = openssl_decrypt( $raw, 'AES-256-CBC', $key, 0, $iv );

		return $decrypted ? $decrypted : '';
	}
}
