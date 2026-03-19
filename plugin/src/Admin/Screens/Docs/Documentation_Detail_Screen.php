<?php
/**
 * Documentation detail screen for helper docs and one-pagers (spec §15–§16).
 *
 * Observational only. Renders documentation objects loaded from file-based registries.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Docs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Docs\Documentation_Registry;
use AIOPageBuilder\Domain\Registries\Documentation\Documentation_Schema;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

final class Documentation_Detail_Screen {

	/** @var Service_Container|null */
	private ?Service_Container $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public const SLUG = 'aio-page-builder-documentation-detail';

	public function get_title(): string {
		return __( 'Documentation', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::MANAGE_SECTION_TEMPLATES;
	}

	public function render(): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to view this page.', 'aio-page-builder' ), 403 );
		}

		$doc_id      = isset( $_GET['doc_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['doc_id'] ) ) : '';
		$section_key = isset( $_GET['section'] ) ? \sanitize_key( (string) $_GET['section'] ) : '';

		$registry = new Documentation_Registry();
		$doc      = null;
		if ( $doc_id !== '' ) {
			$doc = $registry->get_by_id( $doc_id );
		} elseif ( $section_key !== '' ) {
			$doc = $registry->get_by_section_key( $section_key );
		}

		$title = $this->get_title();
		if ( \is_array( $doc ) ) {
			$title = (string) ( $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? $title );
		}

		?>
		<div class="wrap aio-page-builder-screen aio-documentation-detail" role="main" aria-label="<?php echo \esc_attr( $title ); ?>">
			<h1 class="wp-heading-inline"><?php echo \esc_html( $title ); ?></h1>
			<hr class="wp-header-end" />
			<p>
				<a class="button button-secondary" href="<?php echo \esc_url( \admin_url( 'admin.php?page=aio-page-builder-section-templates' ) ); ?>">
					<?php \esc_html_e( 'Back to Section Templates', 'aio-page-builder' ); ?>
				</a>
			</p>

			<?php if ( ! \is_array( $doc ) ) : ?>
				<div class="notice notice-error"><p><?php \esc_html_e( 'Documentation not found.', 'aio-page-builder' ); ?></p></div>
			<?php else : ?>
				<?php
				$body = (string) ( $doc[ Documentation_Schema::FIELD_CONTENT_BODY ] ?? '' );
				if ( $body === '' ) :
					?>
					<div class="notice notice-warning"><p><?php \esc_html_e( 'Documentation body is empty.', 'aio-page-builder' ); ?></p></div>
				<?php else : ?>
					<div class="aio-documentation-body">
						<?php echo \wp_kses_post( $body ); ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}
}

