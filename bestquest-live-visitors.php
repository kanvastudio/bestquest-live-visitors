<?php
/**
 * Plugin Name: BestQuest Live Visitors
 * Description: Lightweight real-time visitor and affiliate-click dashboard for WordPress.
 * Version: 1.5.4
 * Plugin URI: https://bestquest.net/bestquest-live-visitors-wordpress-plugin/
 * Author: BestQuest
 * Author URI: https://bestquest.net/
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bestquest-live-visitors
 */
/**
 * BestQuest Live Visitors — original WordPress plugin by BestQuest.
 * Project website: https://bestquest.net/
 * Licensed under GPL-2.0-or-later.
 *
 * Attribution is informational; it does not affect visitor tracking.
 */
if (!defined('ABSPATH')) exit;

final class BQLV_Plugin {
    const VERSION = '1.5.4';
    const DB_VERSION = '1.5.4';
    const OPT_DB_VERSION = 'bqlv_db_version';
    const NS = 'bestquest-live/v1';
    const OPT_IPS = 'bqlv_ignored_ip_hashes';
    const OPT_HEARTBEAT = 'bqlv_heartbeat_seconds';
    const OPT_LIVE = 'bqlv_live_window_seconds';
    const OPT_RETENTION = 'bqlv_retention_days';
    const OPT_TRACKING_ENABLED = 'bqlv_tracking_enabled';
    const OPT_STORE_IP = 'bqlv_store_ip';
    const OPT_DELETE_ON_UNINSTALL = 'bqlv_delete_on_uninstall';
    const OPT_PUBLIC_CREDIT = 'bqlv_public_credit';

    private static $instance;

    public static function instance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        add_action('plugins_loaded', [$this, 'maybe_upgrade_schema']);
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend']);
        add_action('wp_footer', [$this, 'render_public_credit'], 100);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin']);
        add_action('wp_ajax_bqlv_live_data', [$this, 'ajax_live_data']);
        add_action('wp_ajax_bqlv_recent_data', [$this, 'ajax_recent_data']);
        add_action('wp_ajax_bqlv_ignore_current_ip', [$this, 'ajax_ignore_current_ip']);
        add_action('wp_ajax_bqlv_remove_ignored_ip', [$this, 'ajax_remove_ignored_ip']);
        add_action('wp_ajax_bqlv_save_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_bqlv_cleanup_now', [$this, 'ajax_cleanup_now']);
        add_action('bqlv_cleanup_event', [$this, 'cleanup_old_data']);
    }

    private function sessions_table() {
        global $wpdb;
        return $wpdb->prefix . 'bqlv_sessions';
    }

    private function events_table() {
        global $wpdb;
        return $wpdb->prefix . 'bqlv_events';
    }

    public function activate() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $s = $this->sessions_table();
        $e = $this->events_table();

        dbDelta("CREATE TABLE $s (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_hash char(64) NOT NULL,
            visitor_hash char(64) NOT NULL,
            current_path varchar(1024) NOT NULL DEFAULT '',
            page_title varchar(255) NOT NULL DEFAULT '',
            referrer varchar(1024) NOT NULL DEFAULT '',
            first_seen datetime NOT NULL,
            last_seen datetime NOT NULL,
            browser varchar(64) NOT NULL DEFAULT '',
            device varchar(64) NOT NULL DEFAULT '',
            timezone varchar(128) NOT NULL DEFAULT '',
            ip_address varchar(45) NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            UNIQUE KEY session_hash (session_hash),
            KEY last_seen (last_seen),
            KEY visitor_hash (visitor_hash)
        ) $charset;");

        dbDelta("CREATE TABLE $e (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_hash char(64) NOT NULL,
            event_type varchar(20) NOT NULL DEFAULT 'page',
            path varchar(1024) NOT NULL DEFAULT '',
            page_title varchar(255) NOT NULL DEFAULT '',
            action_text varchar(255) NOT NULL DEFAULT '',
            action_url varchar(2048) NOT NULL DEFAULT '',
            seen_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY session_hash (session_hash),
            KEY event_type (event_type),
            KEY seen_at (seen_at)
        ) $charset;");

        add_option(self::OPT_HEARTBEAT, 20);
        add_option(self::OPT_LIVE, 60);
        add_option(self::OPT_RETENTION, 7);
        add_option(self::OPT_IPS, []);
        add_option(self::OPT_TRACKING_ENABLED, 1);
        add_option(self::OPT_STORE_IP, 1);
        add_option(self::OPT_DELETE_ON_UNINSTALL, 1);
        add_option(self::OPT_PUBLIC_CREDIT, 0);
        update_option(self::OPT_DB_VERSION, self::DB_VERSION, false);
        if (!wp_next_scheduled('bqlv_cleanup_event')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'bqlv_cleanup_event');
        }
    }

    public function maybe_upgrade_schema() {
        if (get_option(self::OPT_DB_VERSION, '') === self::DB_VERSION) return;
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $s = $this->sessions_table();
        $e = $this->events_table();

        dbDelta("CREATE TABLE $s (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_hash char(64) NOT NULL,
            visitor_hash char(64) NOT NULL,
            current_path varchar(1024) NOT NULL DEFAULT '',
            page_title varchar(255) NOT NULL DEFAULT '',
            referrer varchar(1024) NOT NULL DEFAULT '',
            first_seen datetime NOT NULL,
            last_seen datetime NOT NULL,
            browser varchar(64) NOT NULL DEFAULT '',
            device varchar(64) NOT NULL DEFAULT '',
            timezone varchar(128) NOT NULL DEFAULT '',
            ip_address varchar(45) NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            UNIQUE KEY session_hash (session_hash),
            KEY last_seen (last_seen),
            KEY visitor_hash (visitor_hash)
        ) $charset;");

        dbDelta("CREATE TABLE $e (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_hash char(64) NOT NULL,
            event_type varchar(20) NOT NULL DEFAULT 'page',
            path varchar(1024) NOT NULL DEFAULT '',
            page_title varchar(255) NOT NULL DEFAULT '',
            action_text varchar(255) NOT NULL DEFAULT '',
            action_url varchar(2048) NOT NULL DEFAULT '',
            seen_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY session_hash (session_hash),
            KEY event_type (event_type),
            KEY seen_at (seen_at)
        ) $charset;");

        add_option(self::OPT_TRACKING_ENABLED, 1);
        add_option(self::OPT_STORE_IP, 1);
        add_option(self::OPT_DELETE_ON_UNINSTALL, 1);
        add_option(self::OPT_PUBLIC_CREDIT, 0);
        update_option(self::OPT_DB_VERSION, self::DB_VERSION, false);
    }

    public function deactivate() {
        $ts = wp_next_scheduled('bqlv_cleanup_event');
        if ($ts) wp_unschedule_event($ts, 'bqlv_cleanup_event');
    }

    private function client_ip() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        if (!filter_var($ip, FILTER_VALIDATE_IP)) $ip = '';

        /**
         * Allows a site owner to supply a trusted proxy/CDN client IP.
         * Do not trust forwarding headers unless the proxy itself is trusted.
         */
        $filtered = apply_filters('bqlv_client_ip', $ip);
        $filtered = is_string($filtered) ? trim($filtered) : '';
        return filter_var($filtered, FILTER_VALIDATE_IP) ? $filtered : $ip;
    }

    private function ip_hash($ip) {
        return hash_hmac('sha256', (string)$ip, wp_salt('auth'));
    }

    private function tracking_enabled() {
        $enabled = (bool)get_option(self::OPT_TRACKING_ENABLED, 1);
        return (bool)apply_filters('bqlv_tracking_enabled', $enabled);
    }

    private function should_store_ip() {
        $enabled = (bool)get_option(self::OPT_STORE_IP, 1);
        return (bool)apply_filters('bqlv_store_ip_address', $enabled);
    }

    private function is_ignored_ip() {
        $ip = $this->client_ip();
        if (!$ip) return false;
        $hashes = get_option(self::OPT_IPS, []);
        return in_array($this->ip_hash($ip), is_array($hashes) ? $hashes : [], true);
    }

    private function should_track() {
        if (!$this->tracking_enabled()) return false;
        if ($this->is_ignored_ip()) return false;
        if (is_user_logged_in() && current_user_can('manage_options')) return false;
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']))) : '';
        if (preg_match('/bot|crawler|spider|headless|lighthouse|pagespeed|uptime|monitor|python-requests|curl\//i', $ua)) return false;

        /**
         * Consent/privacy integration hook. A consent plugin can return false
         * after inspecting its own cookie or request state.
         */
        return (bool)apply_filters('bqlv_should_track_request', true);
    }

    private function same_site_frontend_request(WP_REST_Request $request) {
        $nonce = sanitize_text_field((string)$request->get_header('x-bqlv-nonce'));
        if (!$nonce || !wp_verify_nonce($nonce, 'bqlv_frontend')) return false;

        $site_host = strtolower((string)wp_parse_url(home_url(), PHP_URL_HOST));
        if (!$site_host) return false;

        $origin = isset($_SERVER['HTTP_ORIGIN']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_ORIGIN'])) : '';
        if ($origin) {
            return strtolower((string)wp_parse_url($origin, PHP_URL_HOST)) === $site_host;
        }

        // Some privacy tools omit Origin. Accept an exact same-host Referer fallback.
        $referer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '';
        if ($referer) {
            return strtolower((string)wp_parse_url($referer, PHP_URL_HOST)) === $site_host;
        }

        return false;
    }

    private function rate_limit_allows($scope, $identity, $limit) {
        $identity = (string)$identity;
        if ($identity === '') return true;
        $key = 'bqlv_rl_' . substr(hash_hmac('sha256', $scope . '|' . $identity, wp_salt('nonce')), 0, 40);
        $hits = (int)get_transient($key);
        if ($hits >= (int)$limit) return false;
        set_transient($key, $hits + 1, MINUTE_IN_SECONDS);
        return true;
    }

    private function session_event_count($session_hash) {
        global $wpdb;
        $e = $this->events_table();
        return (int)$wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM %i WHERE session_hash=%s',
            $e,
            $session_hash
        ));
    }

    public function enqueue_frontend() {
        if (is_admin() || is_404() || !$this->should_track()) return;
        wp_enqueue_script('bqlv-live', plugins_url('assets/live.js', __FILE__), [], self::VERSION, true);
        wp_localize_script('bqlv-live', 'BQLV', [
            'endpoint' => esc_url_raw(rest_url(self::NS . '/heartbeat')),
            'actionEndpoint' => esc_url_raw(rest_url(self::NS . '/action')),
            'heartbeat' => max(10, min(120, (int)get_option(self::OPT_HEARTBEAT, 20))),
            'nonce' => wp_create_nonce('bqlv_frontend'),
        ]);
    }

    public function register_routes() {
        register_rest_route(self::NS, '/heartbeat', [
            'methods' => 'POST',
            'callback' => [$this, 'heartbeat'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route(self::NS, '/action', [
            'methods' => 'POST',
            'callback' => [$this, 'record_action'],
            'permission_callback' => '__return_true',
        ]);
    }

    private function clean_path($value) {
        $value = (string)$value;
        if (preg_match('#^https?://#i', $value)) {
            $parts = wp_parse_url($value);
            $value = $parts['path'] ?? '/';
        }
        $value = '/' . ltrim($value, '/');
        $value = strtok($value, '?');
        return mb_substr(sanitize_text_field($value), 0, 1024);
    }

    private function clean_referrer($value) {
        $value = esc_url_raw((string)$value);
        if (!$value) return '';
        $host = wp_parse_url($value, PHP_URL_HOST);
        $site = wp_parse_url(home_url(), PHP_URL_HOST);
        if ($host && $site && strtolower($host) === strtolower($site)) return 'Internal';
        // Store only the referring origin to avoid persisting tokens, personal paths or query parameters.
        $scheme = wp_parse_url($value, PHP_URL_SCHEME);
        return ($scheme === 'https' || $scheme === 'http') && $host ? mb_substr(strtolower($scheme) . '://' . strtolower($host), 0, 1024) : '';
    }

    private function browser($ua) {
        $u = strtolower($ua);
        // iOS browsers use WebKit but identify their app with distinct UA tokens.
        // Order matters because they commonly also include Safari/.
        if (strpos($u, 'edgios/') !== false || strpos($u, 'edga/') !== false || strpos($u, 'edg/') !== false) return 'Edge';
        if (strpos($u, 'opios/') !== false || strpos($u, 'opr/') !== false || strpos($u, 'opera') !== false) return 'Opera';
        if (strpos($u, 'crios/') !== false || strpos($u, 'chrome/') !== false) return 'Chrome';
        if (strpos($u, 'fxios/') !== false || strpos($u, 'firefox/') !== false) return 'Firefox';
        if (strpos($u, 'safari/') !== false) return 'Safari';
        return 'Other';
    }

    private function device($ua) {
        $u = strtolower($ua);
        if (strpos($u, 'ipad') !== false || strpos($u, 'tablet') !== false) return 'Tablet';
        if (strpos($u, 'mobile') !== false || strpos($u, 'iphone') !== false || strpos($u, 'android') !== false) return 'Mobile';
        return 'Desktop';
    }

    public function heartbeat(WP_REST_Request $request) {
        if (!$this->should_track()) return new WP_REST_Response(['tracked' => false], 200);
        if (!$this->same_site_frontend_request($request)) return new WP_REST_Response(['tracked' => false], 403);

        $p = $request->get_json_params();
        $p = is_array($p) ? $p : [];
        $vid = sanitize_text_field($p['visitor'] ?? '');
        $sid = sanitize_text_field($p['session'] ?? '');
        if (!preg_match('/^[a-f0-9-]{36}$/i', $vid) || !preg_match('/^[a-f0-9-]{36}$/i', $sid)) {
            return new WP_REST_Response(['tracked' => false], 400);
        }

        $remote = $this->client_ip();
        // Normal tracking is 3-6 heartbeats/minute per session. The IP ceiling is
        // intentionally much higher so many legitimate visitors can share NAT.
        if (!$this->rate_limit_allows('heartbeat_session', $sid, 12)) {
            return new WP_REST_Response(['tracked' => false], 429);
        }
        if ($remote && !$this->rate_limit_allows('heartbeat_ip', $remote, 600)) {
            return new WP_REST_Response(['tracked' => false], 429);
        }

        $vh = hash_hmac('sha256', $vid, wp_salt('secure_auth'));
        $sh = hash_hmac('sha256', $sid, wp_salt('nonce'));
        $path = $this->clean_path($p['path'] ?? '/');
        $title = mb_substr(sanitize_text_field($p['title'] ?? ''), 0, 255);
        $ref = $this->clean_referrer($p['referrer'] ?? '');
        $tz = mb_substr(sanitize_text_field($p['timezone'] ?? ''), 0, 128);
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
        $now = current_time('mysql', true);
        $stored_ip = $this->should_store_ip() ? $remote : '';

        global $wpdb;
        $s = $this->sessions_table();
        $e = $this->events_table();
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT id,current_path,visitor_hash FROM %i WHERE session_hash=%s LIMIT 1',
            $s,
            $sh
        ));

        if ($row && !hash_equals((string)$row->visitor_hash, $vh)) {
            return new WP_REST_Response(['tracked' => false], 409);
        }

        if ($row) {
            $wpdb->update($s, [
                'current_path' => $path,
                'page_title' => $title,
                'last_seen' => $now,
                'browser' => $this->browser($ua),
                'device' => $this->device($ua),
                'timezone' => $tz,
                'ip_address' => $stored_ip,
            ], ['id' => (int)$row->id]);

            if ((string)$row->current_path !== $path && $this->session_event_count($sh) < 500) {
                $wpdb->insert($e, [
                    'session_hash' => $sh,
                    'event_type' => 'page',
                    'path' => $path,
                    'page_title' => $title,
                    'seen_at' => $now,
                ]);
            }
        } else {
            // A single network address can create at most 60 new sessions/minute.
            if ($remote && !$this->rate_limit_allows('new_session_ip', $remote, 60)) {
                return new WP_REST_Response(['tracked' => false], 429);
            }

            $wpdb->insert($s, [
                'session_hash' => $sh,
                'visitor_hash' => $vh,
                'current_path' => $path,
                'page_title' => $title,
                'referrer' => $ref,
                'first_seen' => $now,
                'last_seen' => $now,
                'browser' => $this->browser($ua),
                'device' => $this->device($ua),
                'timezone' => $tz,
                'ip_address' => $stored_ip,
            ]);
            $wpdb->insert($e, [
                'session_hash' => $sh,
                'event_type' => 'page',
                'path' => $path,
                'page_title' => $title,
                'seen_at' => $now,
            ]);
        }

        return new WP_REST_Response(['tracked' => true], 200);
    }

    public function record_action(WP_REST_Request $request) {
        if (!$this->should_track()) return new WP_REST_Response(['tracked' => false], 200);
        if (!$this->same_site_frontend_request($request)) return new WP_REST_Response(['tracked' => false], 403);

        $p = $request->get_json_params();
        $p = is_array($p) ? $p : [];
        $vid = sanitize_text_field($p['visitor'] ?? '');
        $sid = sanitize_text_field($p['session'] ?? '');
        if (!preg_match('/^[a-f0-9-]{36}$/i', $vid) || !preg_match('/^[a-f0-9-]{36}$/i', $sid)) {
            return new WP_REST_Response(['tracked' => false], 400);
        }

        $remote = $this->client_ip();
        if (!$this->rate_limit_allows('action_session', $sid, 30)) {
            return new WP_REST_Response(['tracked' => false], 429);
        }
        if ($remote && !$this->rate_limit_allows('action_ip', $remote, 600)) {
            return new WP_REST_Response(['tracked' => false], 429);
        }

        $action_url = esc_url_raw((string)($p['action_url'] ?? ''), ['http', 'https']);
        if (!$action_url) return new WP_REST_Response(['tracked' => false], 400);
        $action_url = mb_substr($action_url, 0, 2048);
        $action_text = mb_substr(sanitize_text_field($p['action_text'] ?? ''), 0, 255);
        if ($action_text === '') $action_text = 'Tracked link';

        $vh = hash_hmac('sha256', $vid, wp_salt('secure_auth'));
        $sh = hash_hmac('sha256', $sid, wp_salt('nonce'));
        $path = $this->clean_path($p['path'] ?? '/');
        $title = mb_substr(sanitize_text_field($p['title'] ?? ''), 0, 255);
        $ref = $this->clean_referrer($p['referrer'] ?? '');
        $tz = mb_substr(sanitize_text_field($p['timezone'] ?? ''), 0, 128);
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
        $now = current_time('mysql', true);
        $stored_ip = $this->should_store_ip() ? $remote : '';

        global $wpdb;
        $s = $this->sessions_table();
        $e = $this->events_table();
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT id,current_path,visitor_hash FROM %i WHERE session_hash=%s LIMIT 1',
            $s,
            $sh
        ));

        if ($row && !hash_equals((string)$row->visitor_hash, $vh)) {
            return new WP_REST_Response(['tracked' => false], 409);
        }

        $event_count = $row ? $this->session_event_count($sh) : 0;
        if ($event_count >= 500) {
            return new WP_REST_Response(['tracked' => false], 429);
        }

        if ($row) {
            $wpdb->update($s, [
                'current_path' => $path,
                'page_title' => $title,
                'last_seen' => $now,
                'browser' => $this->browser($ua),
                'device' => $this->device($ua),
                'timezone' => $tz,
                'ip_address' => $stored_ip,
            ], ['id' => (int)$row->id]);

            if ((string)$row->current_path !== $path && $event_count < 499) {
                $wpdb->insert($e, [
                    'session_hash' => $sh,
                    'event_type' => 'page',
                    'path' => $path,
                    'page_title' => $title,
                    'seen_at' => $now,
                ]);
                $event_count++;
            }
        } else {
            if ($remote && !$this->rate_limit_allows('new_session_ip', $remote, 60)) {
                return new WP_REST_Response(['tracked' => false], 429);
            }

            $wpdb->insert($s, [
                'session_hash' => $sh,
                'visitor_hash' => $vh,
                'current_path' => $path,
                'page_title' => $title,
                'referrer' => $ref,
                'first_seen' => $now,
                'last_seen' => $now,
                'browser' => $this->browser($ua),
                'device' => $this->device($ua),
                'timezone' => $tz,
                'ip_address' => $stored_ip,
            ]);
            $wpdb->insert($e, [
                'session_hash' => $sh,
                'event_type' => 'page',
                'path' => $path,
                'page_title' => $title,
                'seen_at' => $now,
            ]);
            $event_count = 1;
        }

        if ($event_count >= 500) {
            return new WP_REST_Response(['tracked' => false], 429);
        }

        $wpdb->insert($e, [
            'session_hash' => $sh,
            'event_type' => 'click',
            'path' => $path,
            'page_title' => $title,
            'action_text' => $action_text,
            'action_url' => $action_url,
            'seen_at' => $now,
        ]);

        return new WP_REST_Response(['tracked' => true], 200);
    }

    /**
     * Optional public publisher credit.
     * Disabled by default and displayed only after an administrator opts in.
     */
    public function render_public_credit() {
        if (is_admin() || is_feed() || is_embed() || wp_doing_ajax() || !get_option(self::OPT_PUBLIC_CREDIT, 0)) return;
        echo '<div class="bqlv-publisher-credit" style="clear:both;text-align:center;font-size:12px;line-height:1.6;padding:8px 12px;color:inherit;opacity:.72">';
        echo 'Visitor insights powered by <a href="' . esc_url('https://bestquest.net/') . '" rel="nofollow" style="color:inherit;text-decoration:underline">BestQuest</a>';
        echo '</div>';
    }

    public function admin_menu() {
        add_menu_page('Live Visitors', 'Live Visitors', 'manage_options', 'bqlv-live-visitors', [$this, 'render_admin'], 'dashicons-visibility', 3);
    }

    public function enqueue_admin($hook) {
        if ($hook !== 'toplevel_page_bqlv-live-visitors') return;
        wp_enqueue_style('bqlv-admin', plugins_url('assets/admin.css', __FILE__), [], self::VERSION);
        wp_enqueue_script('bqlv-admin', plugins_url('assets/admin.js', __FILE__), [], self::VERSION, true);
        wp_localize_script('bqlv-admin', 'BQLVAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bqlv_admin'),
        ]);
    }

    public function render_admin() {
        if (!current_user_can('manage_options')) return;
        $heartbeat = (int)get_option(self::OPT_HEARTBEAT, 20);
        $live = (int)get_option(self::OPT_LIVE, 60);
        $retention = (int)get_option(self::OPT_RETENTION, 7);
        $tracking_enabled = (bool)get_option(self::OPT_TRACKING_ENABLED, 1);
        $store_ip = (bool)get_option(self::OPT_STORE_IP, 1);
        $delete_on_uninstall = (bool)get_option(self::OPT_DELETE_ON_UNINSTALL, 1);
        $public_credit = (bool)get_option(self::OPT_PUBLIC_CREDIT, 0);
        $hashes = get_option(self::OPT_IPS, []);
        $hashes = is_array($hashes) ? $hashes : [];
        $ignored = in_array($this->ip_hash($this->client_ip()), $hashes, true);
        ?>
        <div class="wrap bqlv-wrap">
            <h1>BestQuest Live Visitors</h1>
            <p class="description">Real-time activity for site administration. IP storage is configurable; when enabled, visitor IP addresses are stored locally for the configured retention period and are visible only to administrators.</p>

            <div class="bqlv-cards">
                <div class="bqlv-card"><strong id="bqlv-live-count">—</strong><span>Live now</span></div>
                <div class="bqlv-card"><strong id="bqlv-active-count">—</strong><span>Active in last 15 min</span></div>
                <div class="bqlv-card"><strong id="bqlv-pageviews">—</strong><span>Unique visitors today</span></div>
            </div>

            <div class="bqlv-panel">
                <div class="bqlv-panel-head"><h2>Live visitors</h2><span id="bqlv-updated">Refreshing…</span></div>
                <div class="bqlv-table-wrap">
                    <table class="widefat striped">
                        <thead><tr>
                            <th>Current page</th><th>Referrer</th><th>Browser / device</th>
                            <th>IP address</th><th>Timezone</th><th>Action</th><th>Since first seen</th><th>Last heartbeat</th>
                        </tr></thead>
                        <tbody id="bqlv-live-body"><tr><td colspan="8">Loading…</td></tr></tbody>
                    </table>
                </div>
            </div>

            <div class="bqlv-panel">
                <div class="bqlv-panel-head">
                    <h2>Recent visitors (after leaving)</h2>
                    <button type="button" class="button" id="bqlv-recent-toggle" aria-expanded="false" aria-controls="bqlv-recent-content">Show recent visitors</button>
                </div>
                <div id="bqlv-recent-content" hidden>
                    <p class="description">Sessions whose last heartbeat is outside the live window. First seen plus recorded span describe the recorded session interval. Records are kept for the retention period set below; up to 100 recent sessions are shown.</p>
                    <p><button type="button" class="button" id="bqlv-recent-refresh">Refresh history</button> <span id="bqlv-recent-updated"></span></p>
                    <div class="bqlv-table-wrap"><table class="widefat striped">
                        <thead><tr><th>Last page</th><th>IP address</th><th>Referrer</th><th>Browser / device</th><th>Timezone</th><th>Action</th><th>First seen</th><th>Recorded span</th></tr></thead>
                        <tbody id="bqlv-recent-body"><tr><td colspan="8">Click Show recent visitors to load history.</td></tr></tbody>
                    </table></div>
                </div>
            </div>

            <div class="bqlv-panel bqlv-settings">
                <h2>Settings</h2>
                <div class="bqlv-setting-row">
                    <div><strong>Ignore this IP</strong><p>Your current IP is <?php echo $ignored ? '<strong>already ignored</strong>.' : 'not ignored yet.'; ?> Ignored-IP matching still uses a one-way hash.</p></div>
                    <button class="button button-primary" id="bqlv-ignore-ip" <?php disabled($ignored); ?>><?php echo $ignored ? 'Current IP ignored' : 'Ignore my current IP'; ?></button>
                </div>

                <div class="bqlv-setting-row">
                    <div><strong>Ignored IP hashes</strong><p>Only one-way hashes are stored.</p></div>
                    <div id="bqlv-ip-list">
                    <?php if (!$hashes): ?><em>None</em>
                    <?php else: foreach ($hashes as $i => $h): ?>
                        <div class="bqlv-ip-item"><code><?php echo esc_html(substr($h,0,12)); ?>…</code>
                        <button class="button-link-delete bqlv-remove-ip" data-index="<?php echo esc_attr($i); ?>">Remove</button></div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>

                <div class="bqlv-grid">
                    <label>Heartbeat interval (seconds)<input type="number" id="bqlv-heartbeat" min="10" max="120" value="<?php echo esc_attr($heartbeat); ?>"></label>
                    <label>Consider visitor live for (seconds)<input type="number" id="bqlv-live-window" min="30" max="300" value="<?php echo esc_attr($live); ?>"></label>
                    <label>Retain session data (days)<input type="number" id="bqlv-retention" min="1" max="30" value="<?php echo esc_attr($retention); ?>"></label>
                </div>
                <p><label><input type="checkbox" id="bqlv-tracking-enabled" <?php checked($tracking_enabled); ?>> Enable frontend visitor tracking</label></p>
                <p><label><input type="checkbox" id="bqlv-store-ip" <?php checked($store_ip); ?>> Store visitor IP addresses</label></p>
                <p class="description">Turning IP storage off immediately clears IP addresses already stored by this plugin. Hashed visitor/session identifiers and activity history remain until normal retention cleanup.</p>
                <p><label><input type="checkbox" id="bqlv-delete-on-uninstall" <?php checked($delete_on_uninstall); ?>> Delete BestQuest Live Visitors tables and settings when the plugin is deleted</label></p>
                <p class="description">Enabled by default. Deactivation still preserves data; deletion from the Plugins screen performs the cleanup.</p>
                <p><label><input type="checkbox" id="bqlv-public-credit" <?php checked($public_credit); ?>> Show public footer credit linking to BestQuest</label></p>
                <p class="description">Disabled by default. Enable this only if you want to display “Visitor insights powered by BestQuest” on public pages. You can turn it off again at any time.</p>
                <p>
                    <button class="button button-primary" id="bqlv-save-settings">Save settings</button>
                    <button class="button" id="bqlv-cleanup-now">Clean expired data now</button>
                    <span id="bqlv-settings-msg"></span>
                </p>
            </div>
        </div>
        <?php
    }

    private function admin_ajax_guard() {
        check_ajax_referer('bqlv_admin', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

    private function dur($seconds) {
        $seconds = max(0, (int)$seconds);
        if ($seconds < 60) return $seconds . 's';
        $m = floor($seconds / 60);
        if ($m < 60) return $m . 'm ' . ($seconds % 60) . 's';
        $h = floor($m / 60);
        return $h . 'h ' . ($m % 60) . 'm';
    }

    private function session_actions($session_hash, $limit = 10) {
        global $wpdb;
        $limit = max(1, min(25, (int)$limit));
        $e = $this->events_table();
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT path,page_title,action_text,action_url,seen_at FROM %i WHERE session_hash=%s AND event_type='click' ORDER BY seen_at DESC,id DESC LIMIT %d",
            $e,
            $session_hash,
            $limit
        ));
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'text' => $row->action_text,
                'url' => $row->action_url,
                'path' => $row->path,
                'page_title' => $row->page_title,
                'time' => wp_date('H:i:s', strtotime($row->seen_at . ' UTC')),
            ];
        }
        return $out;
    }

    public function ajax_live_data() {
        $this->admin_ajax_guard();
        global $wpdb;
        $s = $this->sessions_table();
        $e = $this->events_table();
        $live_window = max(30, min(300, (int)get_option(self::OPT_LIVE, 60)));
        $cutoff = gmdate('Y-m-d H:i:s', time() - $live_window);
        // Site-local day boundary converted to UTC for stored UTC timestamps.
        $today = (new DateTimeImmutable('today', wp_timezone()))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM %i WHERE last_seen >= %s ORDER BY last_seen DESC LIMIT 200",
            $s,
            $cutoff
        ));
        $out = [];
        $seen_visitors = [];

        // Multiple tabs or browser sessions can belong to one anonymous visitor.
        // Because rows are ordered by last activity, keep only the freshest row
        // for each visitor in the live dashboard.
        foreach ($rows as $row) {
            if (isset($seen_visitors[$row->visitor_hash])) {
                continue;
            }
            $seen_visitors[$row->visitor_hash] = true;

            $jr = $wpdb->get_results($wpdb->prepare(
                "SELECT path,page_title FROM %i WHERE session_hash=%s AND event_type='page' ORDER BY seen_at ASC LIMIT 20",
                $e,
                $row->session_hash
            ));
            $journey = [];
            foreach ($jr as $j) $journey[] = ['path' => $j->path, 'title' => $j->page_title];

            $out[] = [
                'session_key' => substr($row->session_hash, 0, 16),
                'path' => $row->current_path,
                'title' => $row->page_title,
                'referrer' => $row->referrer,
                'browser' => $row->browser,
                'device' => $row->device,
                'timezone' => $row->timezone,
                'ip_address' => $row->ip_address,
                'on_site' => $this->dur(time() - strtotime($row->first_seen . ' UTC')),
                'last_active' => $this->dur(max(0, time() - strtotime($row->last_seen . ' UTC'))) . ' ago',
                'journey' => $journey,
                'actions' => $this->session_actions($row->session_hash),
            ];
        }

        $active_15_cutoff = gmdate('Y-m-d H:i:s', time() - (15 * MINUTE_IN_SECONDS));
        $active_15m = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT visitor_hash) FROM %i WHERE last_seen >= %s",
            $s,
            $active_15_cutoff
        ));
        $visitors_today = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT visitor_hash) FROM %i WHERE last_seen >= %s",
            $s,
            $today
        ));

        wp_send_json_success([
            'live_count' => count($out),
            'active_15m' => $active_15m,
            'visitors_today' => $visitors_today,
            'rows' => $out,
            'updated' => current_time('H:i:s'),
        ]);
    }

    public function ajax_recent_data() {
        $this->admin_ajax_guard();
        global $wpdb;
        $s = $this->sessions_table();
        $e = $this->events_table();
        $window = max(30, min(300, (int)get_option(self::OPT_LIVE, 60)));
        $retention = max(1, min(30, (int)get_option(self::OPT_RETENTION, 7)));
        $end = gmdate('Y-m-d H:i:s', time() - $window);
        $start = gmdate('Y-m-d H:i:s', time() - $retention * DAY_IN_SECONDS);
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT session_hash,current_path,page_title,referrer,first_seen,last_seen,browser,device,timezone,ip_address FROM %i WHERE last_seen < %s AND last_seen >= %s ORDER BY last_seen DESC LIMIT 100",
            $s,
            $end,
            $start
        ));
        $out = [];
        foreach ($rows as $row) {
            $events = $wpdb->get_results($wpdb->prepare(
                "SELECT path,page_title FROM %i WHERE session_hash=%s AND event_type='page' ORDER BY seen_at ASC LIMIT 30",
                $e,
                $row->session_hash
            ));
            $journey = [];
            foreach ($events as $event) $journey[] = ['path' => $event->path, 'title' => $event->page_title];
            $out[] = [
                'session_key' => substr($row->session_hash, 0, 16),
                'path' => $row->current_path,
                'title' => $row->page_title,
                'referrer' => $row->referrer,
                'browser' => $row->browser,
                'device' => $row->device,
                'timezone' => $row->timezone,
                'ip_address' => $row->ip_address,
                'first_seen' => wp_date('Y-m-d H:i:s', strtotime($row->first_seen . ' UTC')),
                'recorded_span' => $this->dur(strtotime($row->last_seen . ' UTC') - strtotime($row->first_seen . ' UTC')),
                'journey' => $journey,
                'actions' => $this->session_actions($row->session_hash),
            ];
        }
        wp_send_json_success(['rows' => $out, 'updated' => current_time('H:i:s')]);
    }

    public function ajax_ignore_current_ip() {
        $this->admin_ajax_guard();
        $ip = $this->client_ip();
        if (!$ip) wp_send_json_error(['message' => 'Could not determine current IP.']);
        $hashes = get_option(self::OPT_IPS, []);
        $hashes = is_array($hashes) ? $hashes : [];
        $hash = $this->ip_hash($ip);
        if (!in_array($hash, $hashes, true)) {
            $hashes[] = $hash;
            update_option(self::OPT_IPS, array_values($hashes), false);
        }
        wp_send_json_success(['message' => 'Current IP is now ignored.']);
    }

    public function ajax_remove_ignored_ip() {
        $this->admin_ajax_guard();
        check_ajax_referer('bqlv_admin', 'nonce');
        $i = isset($_POST['index']) ? absint(wp_unslash($_POST['index'])) : -1;
        $hashes = get_option(self::OPT_IPS, []);
        $hashes = is_array($hashes) ? $hashes : [];
        if (isset($hashes[$i])) {
            unset($hashes[$i]);
            update_option(self::OPT_IPS, array_values($hashes), false);
        }
        wp_send_json_success();
    }

    private function clear_stored_ips() {
        global $wpdb;
        $s = $this->sessions_table();
        $wpdb->query($wpdb->prepare("UPDATE %i SET ip_address=''", $s));
    }

    public function ajax_save_settings() {
        $this->admin_ajax_guard();
        check_ajax_referer('bqlv_admin', 'nonce');
        $h = max(10, min(120, absint(wp_unslash($_POST['heartbeat'] ?? 20))));
        $l = max(30, min(300, absint(wp_unslash($_POST['live_window'] ?? 60))));
        $r = max(1, min(30, absint(wp_unslash($_POST['retention'] ?? 7))));
        $tracking = isset($_POST['tracking_enabled']) && (string)wp_unslash($_POST['tracking_enabled']) === '1' ? 1 : 0;
        $store_ip = isset($_POST['store_ip']) && (string)wp_unslash($_POST['store_ip']) === '1' ? 1 : 0;
        $delete_on_uninstall = isset($_POST['delete_on_uninstall']) && (string)wp_unslash($_POST['delete_on_uninstall']) === '1' ? 1 : 0;
        $previous_store_ip = (bool)get_option(self::OPT_STORE_IP, 1);

        update_option(self::OPT_HEARTBEAT, $h, false);
        update_option(self::OPT_LIVE, $l, false);
        update_option(self::OPT_RETENTION, $r, false);
        update_option(self::OPT_TRACKING_ENABLED, $tracking, false);
        update_option(self::OPT_STORE_IP, $store_ip, false);
        update_option(self::OPT_DELETE_ON_UNINSTALL, $delete_on_uninstall, false);
        $credit = isset($_POST['public_credit']) && (string)wp_unslash($_POST['public_credit']) === '1' ? 1 : 0;
        update_option(self::OPT_PUBLIC_CREDIT, $credit, false);

        if ($previous_store_ip && !$store_ip) {
            $this->clear_stored_ips();
        }

        wp_send_json_success(['message' => 'Settings saved.']);
    }

    public function ajax_cleanup_now() {
        $this->admin_ajax_guard();
        $this->cleanup_old_data();
        wp_send_json_success(['message' => 'Expired data cleaned.']);
    }

    public function cleanup_old_data() {
        global $wpdb;
        $days = max(1, min(30, (int)get_option(self::OPT_RETENTION, 7)));
        $cutoff = gmdate('Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS);
        $events_table = $this->events_table();
        $sessions_table = $this->sessions_table();
        $wpdb->query($wpdb->prepare("DELETE FROM %i WHERE seen_at < %s", $events_table, $cutoff));
        $wpdb->query($wpdb->prepare("DELETE FROM %i WHERE last_seen < %s", $sessions_table, $cutoff));
    }
}
BQLV_Plugin::instance();
