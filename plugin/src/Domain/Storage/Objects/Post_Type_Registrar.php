<?php
/**
 * Registers plugin CPTs with stable keys, labels, and capability mapping (spec §9.1, §10).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Objects;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Capabilities;

/**
 * Registers section template, page template, composition, build plan, AI run, AI chat session,
 * prompt pack, documentation, and version snapshot CPTs. No metaboxes, columns, or REST mutation.
 * Built pages remain standard WordPress pages; they are not registered here.
 */
final class Post_Type_Registrar {

	/**
	 * Registers all plugin object CPTs on init. Call once via init hook.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->register_section_template();
		$this->register_page_template();
		$this->register_composition();
		$this->register_build_plan();
		$this->register_ai_run();
		$this->register_ai_chat_session();
		$this->register_prompt_pack();
		$this->register_documentation();
		$this->register_version_snapshot();
	}

	/**
	 * Builds capabilities array mapping all meta caps to a single plugin capability.
	 *
	 * @param string $cap Single capability name (e.g. Capabilities::MANAGE_SECTION_TEMPLATES).
	 * @return array<string, string>
	 */
	private function cap_map( string $cap ): array {
		return array(
			'edit_post'              => $cap,
			'read_post'              => $cap,
			'delete_post'            => $cap,
			'edit_posts'             => $cap,
			'edit_others_posts'      => $cap,
			'publish_posts'          => $cap,
			'read_private_posts'     => $cap,
			'delete_posts'           => $cap,
			'delete_private_posts'   => $cap,
			'delete_published_posts' => $cap,
			'delete_others_posts'    => $cap,
			'edit_private_posts'     => $cap,
			'edit_published_posts'   => $cap,
		);
	}

	/**
	 * Common args: not public, not queryable on front, no default menu; map_meta_cap for proper checks.
	 *
	 * @param string                $post_type Object_Type_Keys constant.
	 * @param array<string, string> $labels Labels array.
	 * @param string                $cap       Single capability for all operations.
	 * @param array<string>         $supports Support flags.
	 * @return array<string, mixed>
	 */
	private function base_args( string $post_type, array $labels, string $cap, array $supports = array( 'title' ) ): array {
		return array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'exclude_from_search' => true,
			'capability_type'     => 'post',
			'capabilities'        => $this->cap_map( $cap ),
			'map_meta_cap'        => true,
			'supports'            => $supports,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
		);
	}

	private function register_section_template(): void {
		$key = Object_Type_Keys::SECTION_TEMPLATE;
		\register_post_type(
			$key,
			$this->base_args(
				$key,
				array(
					'name'               => _x( 'Section Templates', 'post type general name', 'aio-page-builder' ),
					'singular_name'      => _x( 'Section Template', 'post type singular name', 'aio-page-builder' ),
					'menu_name'          => _x( 'Section Templates', 'admin menu', 'aio-page-builder' ),
					'add_new'            => _x( 'Add New', 'section template', 'aio-page-builder' ),
					'add_new_item'       => __( 'Add New Section Template', 'aio-page-builder' ),
					'edit_item'          => __( 'Edit Section Template', 'aio-page-builder' ),
					'new_item'           => __( 'New Section Template', 'aio-page-builder' ),
					'view_item'          => __( 'View Section Template', 'aio-page-builder' ),
					'view_items'         => __( 'View Section Templates', 'aio-page-builder' ),
					'search_items'       => __( 'Search Section Templates', 'aio-page-builder' ),
					'not_found'          => __( 'No section templates found.', 'aio-page-builder' ),
					'not_found_in_trash' => __( 'No section templates found in Trash.', 'aio-page-builder' ),
					'all_items'          => __( 'Section Templates', 'aio-page-builder' ),
					'item_published'     => __( 'Section template published.', 'aio-page-builder' ),
					'item_updated'       => __( 'Section template updated.', 'aio-page-builder' ),
				),
				Capabilities::MANAGE_SECTION_TEMPLATES,
				array( 'title', 'custom-fields', 'revisions' )
			)
		);
	}

	private function register_page_template(): void {
		$key = Object_Type_Keys::PAGE_TEMPLATE;
		\register_post_type(
			$key,
			$this->base_args(
				$key,
				array(
					'name'               => _x( 'Page Templates', 'post type general name', 'aio-page-builder' ),
					'singular_name'      => _x( 'Page Template', 'post type singular name', 'aio-page-builder' ),
					'menu_name'          => _x( 'Page Templates', 'admin menu', 'aio-page-builder' ),
					'add_new'            => _x( 'Add New', 'page template', 'aio-page-builder' ),
					'add_new_item'       => __( 'Add New Page Template', 'aio-page-builder' ),
					'edit_item'          => __( 'Edit Page Template', 'aio-page-builder' ),
					'new_item'           => __( 'New Page Template', 'aio-page-builder' ),
					'view_item'          => __( 'View Page Template', 'aio-page-builder' ),
					'view_items'         => __( 'View Page Templates', 'aio-page-builder' ),
					'search_items'       => __( 'Search Page Templates', 'aio-page-builder' ),
					'not_found'          => __( 'No page templates found.', 'aio-page-builder' ),
					'not_found_in_trash' => __( 'No page templates found in Trash.', 'aio-page-builder' ),
					'all_items'          => __( 'Page Templates', 'aio-page-builder' ),
					'item_published'     => __( 'Page template published.', 'aio-page-builder' ),
					'item_updated'       => __( 'Page template updated.', 'aio-page-builder' ),
				),
				Capabilities::MANAGE_PAGE_TEMPLATES,
				array( 'title', 'custom-fields', 'revisions' )
			)
		);
	}

	private function register_composition(): void {
		$key = Object_Type_Keys::COMPOSITION;
		\register_post_type(
			$key,
			$this->base_args(
				$key,
				array(
					'name'               => _x( 'Compositions', 'post type general name', 'aio-page-builder' ),
					'singular_name'      => _x( 'Composition', 'post type singular name', 'aio-page-builder' ),
					'menu_name'          => _x( 'Compositions', 'admin menu', 'aio-page-builder' ),
					'add_new'            => _x( 'Add New', 'composition', 'aio-page-builder' ),
					'add_new_item'       => __( 'Add New Composition', 'aio-page-builder' ),
					'edit_item'          => __( 'Edit Composition', 'aio-page-builder' ),
					'new_item'           => __( 'New Composition', 'aio-page-builder' ),
					'view_item'          => __( 'View Composition', 'aio-page-builder' ),
					'view_items'         => __( 'View Compositions', 'aio-page-builder' ),
					'search_items'       => __( 'Search Compositions', 'aio-page-builder' ),
					'not_found'          => __( 'No compositions found.', 'aio-page-builder' ),
					'not_found_in_trash' => __( 'No compositions found in Trash.', 'aio-page-builder' ),
					'all_items'          => __( 'Compositions', 'aio-page-builder' ),
					'item_published'     => __( 'Composition published.', 'aio-page-builder' ),
					'item_updated'       => __( 'Composition updated.', 'aio-page-builder' ),
				),
				Capabilities::MANAGE_COMPOSITIONS,
				array( 'title', 'custom-fields', 'revisions' )
			)
		);
	}

	private function register_build_plan(): void {
		$key = Object_Type_Keys::BUILD_PLAN;
		\register_post_type(
			$key,
			$this->base_args(
				$key,
				array(
					'name'               => _x( 'Build Plans', 'post type general name', 'aio-page-builder' ),
					'singular_name'      => _x( 'Build Plan', 'post type singular name', 'aio-page-builder' ),
					'menu_name'          => _x( 'Build Plans', 'admin menu', 'aio-page-builder' ),
					'add_new'            => _x( 'Add New', 'build plan', 'aio-page-builder' ),
					'add_new_item'       => __( 'Add New Build Plan', 'aio-page-builder' ),
					'edit_item'          => __( 'Edit Build Plan', 'aio-page-builder' ),
					'new_item'           => __( 'New Build Plan', 'aio-page-builder' ),
					'view_item'          => __( 'View Build Plan', 'aio-page-builder' ),
					'view_items'         => __( 'View Build Plans', 'aio-page-builder' ),
					'search_items'       => __( 'Search Build Plans', 'aio-page-builder' ),
					'not_found'          => __( 'No build plans found.', 'aio-page-builder' ),
					'not_found_in_trash' => __( 'No build plans found in Trash.', 'aio-page-builder' ),
					'all_items'          => __( 'Build Plans', 'aio-page-builder' ),
					'item_published'     => __( 'Build plan published.', 'aio-page-builder' ),
					'item_updated'       => __( 'Build plan updated.', 'aio-page-builder' ),
				),
				Capabilities::VIEW_BUILD_PLANS,
				array( 'title', 'custom-fields', 'revisions' )
			)
		);
	}

	private function register_ai_run(): void {
		$key = Object_Type_Keys::AI_RUN;
		\register_post_type(
			$key,
			$this->base_args(
				$key,
				array(
					'name'               => _x( 'AI Runs', 'post type general name', 'aio-page-builder' ),
					'singular_name'      => _x( 'AI Run', 'post type singular name', 'aio-page-builder' ),
					'menu_name'          => _x( 'AI Runs', 'admin menu', 'aio-page-builder' ),
					'add_new'            => _x( 'Add New', 'AI run', 'aio-page-builder' ),
					'add_new_item'       => __( 'Add New AI Run', 'aio-page-builder' ),
					'edit_item'          => __( 'Edit AI Run', 'aio-page-builder' ),
					'new_item'           => __( 'New AI Run', 'aio-page-builder' ),
					'view_item'          => __( 'View AI Run', 'aio-page-builder' ),
					'view_items'         => __( 'View AI Runs', 'aio-page-builder' ),
					'search_items'       => __( 'Search AI Runs', 'aio-page-builder' ),
					'not_found'          => __( 'No AI runs found.', 'aio-page-builder' ),
					'not_found_in_trash' => __( 'No AI runs found in Trash.', 'aio-page-builder' ),
					'all_items'          => __( 'AI Runs', 'aio-page-builder' ),
					'item_published'     => __( 'AI run published.', 'aio-page-builder' ),
					'item_updated'       => __( 'AI run updated.', 'aio-page-builder' ),
				),
				Capabilities::VIEW_AI_RUNS,
				array( 'title', 'custom-fields' )
			)
		);
	}

	private function register_ai_chat_session(): void {
		$key = Object_Type_Keys::AI_CHAT_SESSION;
		\register_post_type(
			$key,
			$this->base_args(
				$key,
				array(
					'name'               => _x( 'AI Chat Sessions', 'post type general name', 'aio-page-builder' ),
					'singular_name'      => _x( 'AI Chat Session', 'post type singular name', 'aio-page-builder' ),
					'menu_name'          => _x( 'AI Chat Sessions', 'admin menu', 'aio-page-builder' ),
					'add_new'            => _x( 'Add New', 'AI chat session', 'aio-page-builder' ),
					'add_new_item'       => __( 'Add New AI Chat Session', 'aio-page-builder' ),
					'edit_item'          => __( 'Edit AI Chat Session', 'aio-page-builder' ),
					'new_item'           => __( 'New AI Chat Session', 'aio-page-builder' ),
					'view_item'          => __( 'View AI Chat Session', 'aio-page-builder' ),
					'view_items'         => __( 'View AI Chat Sessions', 'aio-page-builder' ),
					'search_items'       => __( 'Search AI Chat Sessions', 'aio-page-builder' ),
					'not_found'          => __( 'No AI chat sessions found.', 'aio-page-builder' ),
					'not_found_in_trash' => __( 'No AI chat sessions found in Trash.', 'aio-page-builder' ),
					'all_items'          => __( 'AI Chat Sessions', 'aio-page-builder' ),
					'item_published'     => __( 'AI chat session published.', 'aio-page-builder' ),
					'item_updated'       => __( 'AI chat session updated.', 'aio-page-builder' ),
				),
				Capabilities::MANAGE_COMPOSITIONS,
				array( 'title', 'custom-fields' )
			)
		);
	}

	private function register_prompt_pack(): void {
		$key = Object_Type_Keys::PROMPT_PACK;
		\register_post_type(
			$key,
			$this->base_args(
				$key,
				array(
					'name'               => _x( 'Prompt Packs', 'post type general name', 'aio-page-builder' ),
					'singular_name'      => _x( 'Prompt Pack', 'post type singular name', 'aio-page-builder' ),
					'menu_name'          => _x( 'Prompt Packs', 'admin menu', 'aio-page-builder' ),
					'add_new'            => _x( 'Add New', 'prompt pack', 'aio-page-builder' ),
					'add_new_item'       => __( 'Add New Prompt Pack', 'aio-page-builder' ),
					'edit_item'          => __( 'Edit Prompt Pack', 'aio-page-builder' ),
					'new_item'           => __( 'New Prompt Pack', 'aio-page-builder' ),
					'view_item'          => __( 'View Prompt Pack', 'aio-page-builder' ),
					'view_items'         => __( 'View Prompt Packs', 'aio-page-builder' ),
					'search_items'       => __( 'Search Prompt Packs', 'aio-page-builder' ),
					'not_found'          => __( 'No prompt packs found.', 'aio-page-builder' ),
					'not_found_in_trash' => __( 'No prompt packs found in Trash.', 'aio-page-builder' ),
					'all_items'          => __( 'Prompt Packs', 'aio-page-builder' ),
					'item_published'     => __( 'Prompt pack published.', 'aio-page-builder' ),
					'item_updated'       => __( 'Prompt pack updated.', 'aio-page-builder' ),
				),
				Capabilities::MANAGE_PROMPT_PACKS,
				array( 'title', 'custom-fields', 'revisions' )
			)
		);
	}

	private function register_documentation(): void {
		$key = Object_Type_Keys::DOCUMENTATION;
		\register_post_type(
			$key,
			$this->base_args(
				$key,
				array(
					'name'               => _x( 'Documentation', 'post type general name', 'aio-page-builder' ),
					'singular_name'      => _x( 'Documentation Object', 'post type singular name', 'aio-page-builder' ),
					'menu_name'          => _x( 'Documentation', 'admin menu', 'aio-page-builder' ),
					'add_new'            => _x( 'Add New', 'documentation', 'aio-page-builder' ),
					'add_new_item'       => __( 'Add New Documentation', 'aio-page-builder' ),
					'edit_item'          => __( 'Edit Documentation', 'aio-page-builder' ),
					'new_item'           => __( 'New Documentation', 'aio-page-builder' ),
					'view_item'          => __( 'View Documentation', 'aio-page-builder' ),
					'view_items'         => __( 'View Documentation', 'aio-page-builder' ),
					'search_items'       => __( 'Search Documentation', 'aio-page-builder' ),
					'not_found'          => __( 'No documentation found.', 'aio-page-builder' ),
					'not_found_in_trash' => __( 'No documentation found in Trash.', 'aio-page-builder' ),
					'all_items'          => __( 'Documentation', 'aio-page-builder' ),
					'item_published'     => __( 'Documentation published.', 'aio-page-builder' ),
					'item_updated'       => __( 'Documentation updated.', 'aio-page-builder' ),
				),
				Capabilities::MANAGE_DOCUMENTATION,
				array( 'title', 'editor', 'custom-fields', 'revisions' )
			)
		);
	}

	private function register_version_snapshot(): void {
		$key = Object_Type_Keys::VERSION_SNAPSHOT;
		\register_post_type(
			$key,
			$this->base_args(
				$key,
				array(
					'name'               => _x( 'Version Snapshots', 'post type general name', 'aio-page-builder' ),
					'singular_name'      => _x( 'Version Snapshot', 'post type singular name', 'aio-page-builder' ),
					'menu_name'          => _x( 'Version Snapshots', 'admin menu', 'aio-page-builder' ),
					'add_new'            => _x( 'Add New', 'version snapshot', 'aio-page-builder' ),
					'add_new_item'       => __( 'Add New Version Snapshot', 'aio-page-builder' ),
					'edit_item'          => __( 'Edit Version Snapshot', 'aio-page-builder' ),
					'new_item'           => __( 'New Version Snapshot', 'aio-page-builder' ),
					'view_item'          => __( 'View Version Snapshot', 'aio-page-builder' ),
					'view_items'         => __( 'View Version Snapshots', 'aio-page-builder' ),
					'search_items'       => __( 'Search Version Snapshots', 'aio-page-builder' ),
					'not_found'          => __( 'No version snapshots found.', 'aio-page-builder' ),
					'not_found_in_trash' => __( 'No version snapshots found in Trash.', 'aio-page-builder' ),
					'all_items'          => __( 'Version Snapshots', 'aio-page-builder' ),
					'item_published'     => __( 'Version snapshot published.', 'aio-page-builder' ),
					'item_updated'       => __( 'Version snapshot updated.', 'aio-page-builder' ),
				),
				Capabilities::VIEW_VERSION_SNAPSHOTS,
				array( 'title', 'custom-fields' )
			)
		);
	}
}
