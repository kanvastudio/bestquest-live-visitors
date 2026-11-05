<?php
/**
 * BestQuest Live Visitors uninstall cleanup.
 *
 * Deletes plugin-owned visitor tables and settings when the administrator
 * leaves "Delete BestQuest Live Visitors tables and settings when the plugin
 * is deleted" enabled (the default).
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

function bqlv_uninstall_current_site() {
    global $wpdb;

    wp_clear_scheduled_hook('bqlv_cleanup_event');

    if (!(bool)get_option('bqlv_delete_on_uninstall', 1)) {
        return;
    }

    $sessions = $wpdb->prefix . 'bqlv_sessions';
    $events = $wpdb->prefix . 'bqlv_events';

    $wpdb->query($wpdb->prepare('DROP TABLE IF EXISTS %i', $events));
    $wpdb->query($wpdb->prepare('DROP TABLE IF EXISTS %i', $sessions));

    $options = [
        'bqlv_db_version',
        'bqlv_ignored_ip_hashes',
        'bqlv_heartbeat_seconds',
        'bqlv_live_window_seconds',
        'bqlv_retention_days',
        'bqlv_tracking_enabled',
        'bqlv_store_ip',
        'bqlv_delete_on_uninstall',
        'bqlv_public_credit',
    ];

    foreach ($options as $option) {
        delete_option($option);
    }
}

if (is_multisite()) {
    $site_ids = get_sites(['fields' => 'ids', 'number' => 0]);
    foreach ($site_ids as $site_id) {
        switch_to_blog((int)$site_id);
        bqlv_uninstall_current_site();
        restore_current_blog();
    }
} else {
    bqlv_uninstall_current_site();
}
