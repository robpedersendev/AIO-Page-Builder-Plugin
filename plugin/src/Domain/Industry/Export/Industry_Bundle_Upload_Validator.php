<?php
/**
 * Validates industry bundle file uploads: size limit, extension, and MIME type (SPR-001).
 * Used before reading or parsing uploads; does not weaken nonce/capability checks.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Export;

defined( 'ABSPATH' ) || exit;

/**
 * Validates uploaded file for industry bundle preview/import. Enforces size, extension, and MIME.
 */
final class Industry_Bundle_Upload_Validator {

	/** Maximum allowed file size in bytes (10 MB). */
	public const MAX_BYTES = 10 * 1024 * 1024;

	/** Allowed file extensions (lowercase). */
	private const ALLOWED_EXTENSIONS = array( 'json' );

	/** Allowed MIME types from finfo (server-side detection). */
	private const ALLOWED_MIME_TYPES = array( 'application/json', 'text/json', 'text/plain' );

	/** Log reason: file exceeds size limit. */
	public const LOG_REASON_TOO_LARGE = 'file_too_large';

	/** Log reason: file extension not allowed. */
	public const LOG_REASON_EXTENSION = 'invalid_extension';

	/** Log reason: detected MIME not allowed. */
	public const LOG_REASON_MIME = 'invalid_mime';

	/** Log reason: not an uploaded file. */
	public const LOG_REASON_NOT_UPLOADED = 'not_uploaded';

	/** Log reason: upload error or missing tmp_name. */
	public const LOG_REASON_UPLOAD_ERROR = 'upload_error';

	/**
	 * Validates $_FILES-style entry: uploaded file, size, extension, and MIME. Does not read body for JSON.
	 *
	 * @param array{ tmp_name?: string, size?: int, name?: string, error?: int } $file $_FILES['aio_industry_bundle_file'].
	 * @return array{ ok: bool, user_message: string, log_reason: string, tmp_path: string, file_size: int } tmp_path and file_size only meaningful when ok is true.
	 */
	public static function validate_upload( array $file ): array {
		$tmp_name = isset( $file['tmp_name'] ) && is_string( $file['tmp_name'] ) ? $file['tmp_name'] : '';
		$name     = isset( $file['name'] ) && is_string( $file['name'] ) ? $file['name'] : '';
		$size     = isset( $file['size'] ) && is_int( $file['size'] ) ? $file['size'] : 0;
		$error    = isset( $file['error'] ) && is_int( $file['error'] ) ? $file['error'] : \UPLOAD_ERR_NO_FILE;

		if ( $tmp_name === '' || ! is_uploaded_file( $tmp_name ) ) {
			return array(
				'ok'          => false,
				'user_message' => __( 'No file uploaded.', 'aio-page-builder' ),
				'log_reason'  => self::LOG_REASON_NOT_UPLOADED,
				'tmp_path'    => '',
				'file_size'   => 0,
			);
		}
		if ( $error !== \UPLOAD_ERR_OK ) {
			return array(
				'ok'           => false,
				'user_message' => __( 'Upload failed. Please try again.', 'aio-page-builder' ),
				'log_reason'   => self::LOG_REASON_UPLOAD_ERROR,
				'tmp_path'     => '',
				'file_size'    => 0,
			);
		}
		if ( $size > self::MAX_BYTES ) {
			$max_mb = (int) ( self::MAX_BYTES / ( 1024 * 1024 ) );
			return array(
				'ok'           => false,
				'user_message' => sprintf(
					/* translators: %d: maximum file size in megabytes */
					__( 'File is too large. Maximum size is %d MB.', 'aio-page-builder' ),
					$max_mb
				),
				'log_reason'   => self::LOG_REASON_TOO_LARGE,
				'tmp_path'     => '',
				'file_size'    => 0,
			);
		}
		$ext = strtolower( (string) pathinfo( $name, \PATHINFO_EXTENSION ) );
		if ( $ext === '' || ! in_array( $ext, self::ALLOWED_EXTENSIONS, true ) ) {
			return array(
				'ok'           => false,
				'user_message' => __( 'Invalid file type. Please upload a JSON file.', 'aio-page-builder' ),
				'log_reason'   => self::LOG_REASON_EXTENSION,
				'tmp_path'     => '',
				'file_size'    => 0,
			);
		}
		$finfo = \finfo_open( \FILEINFO_MIME_TYPE );
		if ( $finfo === false ) {
			return array(
				'ok'           => false,
				'user_message' => __( 'Invalid file type. Please upload a JSON file.', 'aio-page-builder' ),
				'log_reason'   => self::LOG_REASON_MIME,
				'tmp_path'     => '',
				'file_size'    => 0,
			);
		}
		$detected_mime = \finfo_file( $finfo, $tmp_name );
		\finfo_close( $finfo );
		if ( $detected_mime === false || ! in_array( $detected_mime, self::ALLOWED_MIME_TYPES, true ) ) {
			return array(
				'ok'           => false,
				'user_message' => __( 'Invalid file type. Please upload a JSON file.', 'aio-page-builder' ),
				'log_reason'   => self::LOG_REASON_MIME,
				'tmp_path'     => '',
				'file_size'    => 0,
			);
		}
		return array(
			'ok'           => true,
			'user_message' => '',
			'log_reason'   => '',
			'tmp_path'     => $tmp_name,
			'file_size'    => $size,
		);
	}

	/**
	 * Reads temp file (up to max_bytes), decodes JSON, and validates bundle structure. Call only after validate_upload succeeded.
	 *
	 * @param string $tmp_path Path to uploaded temp file.
	 * @param int    $max_bytes Maximum bytes to read (use Industry_Bundle_Upload_Validator::MAX_BYTES).
	 * @return array{ bundle: array<string, mixed>|null, user_message: string, log_reason: string } log_reason for server log only.
	 */
	public static function read_parse_validate_bundle( string $tmp_path, int $max_bytes = self::MAX_BYTES ): array {
		if ( ! is_file( $tmp_path ) || ! is_readable( $tmp_path ) ) {
			return array(
				'bundle'      => null,
				'user_message' => __( 'Could not read file.', 'aio-page-builder' ),
				'log_reason'  => 'read_error',
			);
		}
		$size = filesize( $tmp_path );
		if ( $size === false || $size > $max_bytes ) {
			return array(
				'bundle'      => null,
				'user_message' => sprintf(
					/* translators: %d: max size in MB */
					__( 'File is too large. Maximum size is %d MB.', 'aio-page-builder' ),
					(int) ( $max_bytes / ( 1024 * 1024 ) )
				),
				'log_reason'  => self::LOG_REASON_TOO_LARGE,
			);
		}
		$raw = file_get_contents( $tmp_path, false, null, 0, $max_bytes );
		if ( $raw === false ) {
			return array(
				'bundle'      => null,
				'user_message' => __( 'Could not read file.', 'aio-page-builder' ),
				'log_reason'  => 'read_error',
			);
		}
		$bundle = json_decode( $raw, true );
		if ( ! is_array( $bundle ) ) {
			return array(
				'bundle'      => null,
				'user_message' => __( 'Invalid JSON.', 'aio-page-builder' ),
				'log_reason'  => 'invalid_json',
			);
		}
		$bundle_service = new Industry_Pack_Bundle_Service();
		$errors = $bundle_service->validate_bundle( $bundle );
		if ( $errors !== array() ) {
			return array(
				'bundle'      => null,
				'user_message' => __( 'Invalid bundle structure. Please use an export from this plugin.', 'aio-page-builder' ),
				'log_reason'  => 'invalid_bundle_structure',
			);
		}
		return array(
			'bundle'      => $bundle,
			'user_message' => '',
			'log_reason'   => '',
		);
	}
}
