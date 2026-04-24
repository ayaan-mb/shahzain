<?php
/**
 * Uninstall handler for SpeedX Site Reset.
 *
 * Removes only plugin-specific data.
 *
 * @package SpeedXSiteReset
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'speedx_site_reset_notice' );
