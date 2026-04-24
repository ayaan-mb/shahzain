<?php
/**
 * Plugin Name:       SpeedX Site Reset
 * Plugin URI:        https://example.com/speedx-site-reset
 * Description:       Safely reset a WordPress site content and settings to a near-fresh state.
 * Version:           1.0.0
 * Author:            SpeedX
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       speedx-site-reset
 *
 * @package SpeedXSiteReset
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SpeedX_Site_Reset' ) ) {
	/**
	 * Main plugin class.
	 */
	class SpeedX_Site_Reset {
		/**
		 * Option key used for one-time success notice.
		 *
		 * @var string
		 */
		const NOTICE_OPTION = 'speedx_site_reset_notice';

		/**
		 * Singleton instance.
		 *
		 * @var SpeedX_Site_Reset|null
		 */
		private static $instance = null;

		/**
		 * Get singleton instance.
		 *
		 * @return SpeedX_Site_Reset
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		private function __construct() {
			add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'admin_post_speedx_site_reset_run', array( $this, 'handle_reset' ) );
			add_action( 'admin_notices', array( $this, 'render_notice' ) );
		}

		/**
		 * Register reset page under Tools.
		 *
		 * @return void
		 */
		public function register_admin_page() {
			add_management_page(
				esc_html__( 'SpeedX Site Reset', 'speedx-site-reset' ),
				esc_html__( 'SpeedX Site Reset', 'speedx-site-reset' ),
				'manage_options',
				'speedx-site-reset',
				array( $this, 'render_admin_page' )
			);
		}

		/**
		 * Enqueue admin styles and scripts on plugin page only.
		 *
		 * @param string $hook_suffix Current admin page hook suffix.
		 *
		 * @return void
		 */
		public function enqueue_assets( $hook_suffix ) {
			if ( 'tools_page_speedx-site-reset' !== $hook_suffix ) {
				return;
			}

			wp_enqueue_style(
				'speedx-site-reset-admin',
				plugin_dir_url( __FILE__ ) . 'assets/admin.css',
				array(),
				'1.0.0'
			);

			wp_enqueue_script(
				'speedx-site-reset-admin',
				plugin_dir_url( __FILE__ ) . 'assets/admin.js',
				array(),
				'1.0.0',
				true
			);

			wp_localize_script(
				'speedx-site-reset-admin',
				'speedxSiteResetConfig',
				array(
					'confirmText' => __( 'Are you absolutely sure? This will permanently delete website data.', 'speedx-site-reset' ),
				)
			);
		}

		/**
		 * Render admin interface.
		 *
		 * @return void
		 */
		public function render_admin_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You are not allowed to access this page.', 'speedx-site-reset' ) );
			}
			?>
			<div class="wrap speedx-reset-wrap">
				<h1 class="speedx-title"><?php echo esc_html__( 'SpeedX Site Reset', 'speedx-site-reset' ); ?></h1>

				<div class="speedx-card speedx-danger-box">
					<h2><?php echo esc_html__( 'Danger Zone: Destructive Action', 'speedx-site-reset' ); ?></h2>
					<p>
						<?php echo esc_html__( 'This action is irreversible. It will permanently remove website data and reset your site close to a fresh WordPress installation.', 'speedx-site-reset' ); ?>
					</p>
					<p>
						<strong><?php echo esc_html__( 'Only proceed if you are absolutely sure and have a complete backup.', 'speedx-site-reset' ); ?></strong>
					</p>
				</div>

				<div class="speedx-grid">
					<div class="speedx-card">
						<h2><?php echo esc_html__( 'This reset will delete', 'speedx-site-reset' ); ?></h2>
						<ul>
							<li><?php echo esc_html__( 'All posts, pages, and custom post type entries', 'speedx-site-reset' ); ?></li>
							<li><?php echo esc_html__( 'All comments', 'speedx-site-reset' ); ?></li>
							<li><?php echo esc_html__( 'Terms from custom taxonomies', 'speedx-site-reset' ); ?></li>
							<li><?php echo esc_html__( 'All media attachments and uploaded files', 'speedx-site-reset' ); ?></li>
							<li><?php echo esc_html__( 'Navigation menus, widgets, and theme mods', 'speedx-site-reset' ); ?></li>
							<li><?php echo esc_html__( 'Transients and selected common plugin options', 'speedx-site-reset' ); ?></li>
						</ul>
					</div>

					<div class="speedx-card">
						<h2><?php echo esc_html__( 'This reset will keep', 'speedx-site-reset' ); ?></h2>
						<ul>
							<li><?php echo esc_html__( 'Current administrator user account', 'speedx-site-reset' ); ?></li>
							<li><?php echo esc_html__( 'WordPress core files and database structure', 'speedx-site-reset' ); ?></li>
							<li><?php echo esc_html__( 'Installed themes and plugins files', 'speedx-site-reset' ); ?></li>
							<li><?php echo esc_html__( 'This plugin until reset completes', 'speedx-site-reset' ); ?></li>
						</ul>
					</div>
				</div>

				<div class="speedx-card speedx-confirm-card">
					<h2><?php echo esc_html__( 'Confirmation Required', 'speedx-site-reset' ); ?></h2>
					<p>
						<?php echo esc_html__( 'To continue, type', 'speedx-site-reset' ); ?>
						<code>reset</code>
						<?php echo esc_html__( 'exactly in the field below.', 'speedx-site-reset' ); ?>
					</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="speedx-reset-form">
						<?php wp_nonce_field( 'speedx_site_reset_action', 'speedx_site_reset_nonce' ); ?>
						<input type="hidden" name="action" value="speedx_site_reset_run" />
						<label for="speedx-reset-confirm" class="screen-reader-text">
							<?php echo esc_html__( 'Type reset to confirm', 'speedx-site-reset' ); ?>
						</label>
						<input type="text" id="speedx-reset-confirm" name="speedx_reset_confirm" autocomplete="off" spellcheck="false" />
						<button type="submit" class="button button-primary speedx-reset-button" id="speedx-reset-submit" disabled>
							<?php echo esc_html__( 'Reset Site Now', 'speedx-site-reset' ); ?>
						</button>
					</form>
				</div>
			</div>
			<?php
		}

		/**
		 * Render success notice after redirect.
		 *
		 * @return void
		 */
		public function render_notice() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( ! get_option( self::NOTICE_OPTION ) ) {
				return;
			}

			delete_option( self::NOTICE_OPTION );
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html__( 'Site reset completed successfully.', 'speedx-site-reset' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Handle reset form submission securely.
		 *
		 * @return void
		 */
		public function handle_reset() {
			if ( ! is_admin() ) {
				wp_die( esc_html__( 'Invalid request context.', 'speedx-site-reset' ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Insufficient permissions.', 'speedx-site-reset' ) );
			}

			check_admin_referer( 'speedx_site_reset_action', 'speedx_site_reset_nonce' );

			$confirmation = isset( $_POST['speedx_reset_confirm'] ) ? sanitize_text_field( wp_unslash( $_POST['speedx_reset_confirm'] ) ) : '';

			if ( 'reset' !== $confirmation ) {
				wp_die( esc_html__( 'Confirmation text did not match. Reset aborted.', 'speedx-site-reset' ) );
			}

			$this->run_full_reset();

			update_option( self::NOTICE_OPTION, 1, false );

			wp_safe_redirect( admin_url() );
			exit;
		}

		/**
		 * Execute full reset process.
		 *
		 * @return void
		 */
		private function run_full_reset() {
			$this->deactivate_other_plugins();
			$this->delete_all_attachments();
			$this->delete_all_posts_except_attachments();
			$this->delete_all_comments();
			$this->delete_custom_taxonomy_terms();
			$this->delete_all_navigation_menus();
			$this->reset_widgets_and_sidebars();
			$this->reset_theme_mods();
			$this->delete_all_transients();
			$this->delete_common_plugin_options();
			$this->reset_wordpress_options();
			$this->flush_rewrite_rules();
			wp_cache_flush();
		}

		/**
		 * Deactivate all plugins except this plugin.
		 *
		 * @return void
		 */
		private function deactivate_other_plugins() {
			$active_plugins = (array) get_option( 'active_plugins', array() );
			$self_plugin    = plugin_basename( __FILE__ );

			$active_plugins = array_values(
				array_filter(
					$active_plugins,
					static function ( $plugin_file ) use ( $self_plugin ) {
						return $plugin_file === $self_plugin;
					}
				)
			);

			update_option( 'active_plugins', $active_plugins, true );

			if ( is_multisite() ) {
				$sitewide_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
				if ( ! empty( $sitewide_plugins ) ) {
					foreach ( array_keys( $sitewide_plugins ) as $plugin_file ) {
						if ( $plugin_file !== $self_plugin ) {
							unset( $sitewide_plugins[ $plugin_file ] );
						}
					}
					update_site_option( 'active_sitewide_plugins', $sitewide_plugins );
				}
			}
		}

		/**
		 * Delete all media attachments and files.
		 *
		 * @return void
		 */
		private function delete_all_attachments() {
			$attachments = get_posts(
				array(
					'post_type'              => 'attachment',
					'post_status'            => 'any',
					'posts_per_page'         => -1,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'cache_results'          => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			foreach ( $attachments as $attachment_id ) {
				wp_delete_attachment( (int) $attachment_id, true );
			}
		}

		/**
		 * Delete all post content excluding attachments.
		 *
		 * @return void
		 */
		private function delete_all_posts_except_attachments() {
			$post_ids = get_posts(
				array(
					'post_type'              => 'any',
					'post_status'            => 'any',
					'posts_per_page'         => -1,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'cache_results'          => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			foreach ( $post_ids as $post_id ) {
				if ( 'attachment' === get_post_type( $post_id ) ) {
					continue;
				}

				wp_delete_post( (int) $post_id, true );
			}
		}

		/**
		 * Delete all comments.
		 *
		 * @return void
		 */
		private function delete_all_comments() {
			$comment_ids = get_comments(
				array(
					'fields' => 'ids',
					'status' => 'all',
				)
			);

			foreach ( $comment_ids as $comment_id ) {
				wp_delete_comment( (int) $comment_id, true );
			}
		}

		/**
		 * Delete all terms for custom taxonomies.
		 *
		 * @return void
		 */
		private function delete_custom_taxonomy_terms() {
			$taxonomies = get_taxonomies(
				array(
					'_builtin' => false,
				),
				'names'
			);

			foreach ( $taxonomies as $taxonomy ) {
				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => false,
						'fields'     => 'ids',
					)
				);

				if ( is_wp_error( $terms ) ) {
					continue;
				}

				foreach ( $terms as $term_id ) {
					wp_delete_term( (int) $term_id, $taxonomy );
				}
			}
		}

		/**
		 * Delete all nav menus.
		 *
		 * @return void
		 */
		private function delete_all_navigation_menus() {
			$menus = wp_get_nav_menus();

			foreach ( $menus as $menu ) {
				wp_delete_nav_menu( $menu->term_id );
			}
		}

		/**
		 * Reset widgets and sidebars options.
		 *
		 * @return void
		 */
		private function reset_widgets_and_sidebars() {
			global $wpdb;

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
					$wpdb->esc_like( 'widget_' ) . '%'
				)
			);

			update_option( 'sidebars_widgets', array(), true );
		}

		/**
		 * Reset theme mods.
		 *
		 * @return void
		 */
		private function reset_theme_mods() {
			remove_theme_mods();
		}

		/**
		 * Delete transients (site and regular).
		 *
		 * @return void
		 */
		private function delete_all_transients() {
			global $wpdb;

			$patterns = array(
				'_transient_%',
				'_site_transient_%',
				'_transient_timeout_%',
				'_site_transient_timeout_%',
			);

			foreach ( $patterns as $pattern ) {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
						$pattern
					)
				);
			}
		}

		/**
		 * Delete selected common plugin options conservatively.
		 *
		 * @return void
		 */
		private function delete_common_plugin_options() {
			global $wpdb;

			$option_prefixes = array(
				'elementor_%',
				'_elementor_%',
				'woocommerce_%',
				'wc_%',
				'yoast_%',
				'_yoast_%',
				'rank_math_%',
				'wpcf7_%',
				'aios_%',
				'jetpack_%',
				'_jetpack_%',
			);

			foreach ( $option_prefixes as $prefix ) {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
						$prefix
					)
				);
			}
		}

		/**
		 * Reset core options to sensible fresh-install defaults.
		 *
		 * @return void
		 */
		private function reset_wordpress_options() {
			$defaults = array(
				'blogdescription'           => __( 'Just another WordPress site' ),
				'show_on_front'             => 'posts',
				'page_on_front'             => 0,
				'page_for_posts'            => 0,
				'posts_per_page'            => 10,
				'posts_per_rss'             => 10,
				'default_comment_status'    => 'open',
				'default_ping_status'       => 'open',
				'use_smilies'               => 1,
				'start_of_week'             => 1,
				'thumbnail_size_w'          => 150,
				'thumbnail_size_h'          => 150,
				'thumbnail_crop'            => 1,
				'medium_size_w'             => 300,
				'medium_size_h'             => 300,
				'large_size_w'              => 1024,
				'large_size_h'              => 1024,
				'date_format'               => 'F j, Y',
				'time_format'               => 'g:i a',
				'permalink_structure'       => '',
				'category_base'             => '',
				'tag_base'                  => '',
				'comments_per_page'         => 50,
				'comment_moderation'        => 0,
				'comment_registration'      => 0,
				'close_comments_for_old_posts' => 0,
			);

			foreach ( $defaults as $option => $value ) {
				update_option( $option, $value );
			}

			delete_option( 'widget_block' );
			delete_option( 'recently_edited' );
			delete_option( 'custom_css_post_id' );
			delete_option( 'nav_menu_locations' );
		}

		/**
		 * Flush rewrite rules.
		 *
		 * @return void
		 */
		private function flush_rewrite_rules() {
			global $wp_rewrite;

			if ( $wp_rewrite instanceof WP_Rewrite ) {
				$wp_rewrite->set_permalink_structure( '' );
			}

			flush_rewrite_rules();
		}
	}

	SpeedX_Site_Reset::instance();
}
