<?php
/**
 * WM Salon Sovereign Hub — uninstaller
 *
 * Preserves options and rate-limit transients by default. Deletes plugin data only when the administrator explicitly
 * opted in via `wm_salon_delete_data_on_uninstall`.
 *
 * @package WM_Salon_Sovereign_Hub
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! filter_var( get_option( 'wm_salon_delete_data_on_uninstall', false ), FILTER_VALIDATE_BOOLEAN ) ) {
	return;
}

delete_option( 'wm_salon_delete_data_on_uninstall' );
if ( function_exists( 'delete_site_option' ) ) {
	delete_site_option( 'wm_salon_delete_data_on_uninstall' );
}

global $wpdb;
if ( isset( $wpdb ) && is_object( $wpdb ) && isset( $wpdb->options ) && method_exists( $wpdb, 'query' ) && method_exists( $wpdb, 'prepare' ) && method_exists( $wpdb, 'esc_like' ) ) {
	$transient_like = $wpdb->esc_like( '_transient_wm_salon_rl_' ) . '%';
	$timeout_like   = $wpdb->esc_like( '_transient_timeout_wm_salon_rl_' ) . '%';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $transient_like, $timeout_like ) );
}
