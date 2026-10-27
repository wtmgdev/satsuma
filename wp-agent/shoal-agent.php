<?php
/**
 * Plugin Name: Shoal Agent
 * Description: Companion REST agent for the Shoal WordPress management platform. Exposes read-only system info secured by a bearer token.
 * Version: 0.1.0
 * Requires PHP: 7.4
 * Author: Shoal
 */

if (!defined('ABSPATH')) {
    exit;
}

// Set this to the API token generated for this site in the Shoal admin panel,
// or run: wp shoal token <token>
if (!defined('SHOAL_AGENT_TOKEN')) {
    $stored = get_option('shoal_agent_token', '');
    define('SHOAL_AGENT_TOKEN', is_string($stored) ? $stored : '');
}

add_action(
    'rest_api_init',
    function () {
        register_rest_route('agent/v1', '/system-info', [
            'methods' => 'GET',
            'permission_callback' => static function (WP_REST_Request $request): bool {
                $provided = (string) $request->get_header('X-Agent-Token');

                return $provided !== ''
                    && SHOAL_AGENT_TOKEN !== ''
                    && hash_equals(SHOAL_AGENT_TOKEN, $provided);
            },
            'callback' => static function (): WP_REST_Response {
                require_once ABSPATH.'wp-admin/includes/plugin.php';
                require_once ABSPATH.'wp-admin/includes/update.php';

                $updatePlugins = get_site_transient('update_plugins');
                $updateThemes = get_site_transient('update_themes');

                $plugins = [];
                foreach (get_plugins() as $file => $data) {
                    $pluginUpdate = $updatePlugins->response[$file] ?? null;
                    $plugins[] = [
                        'name' => $data['Name'],
                        'slug' => dirname($file) === '.' ? sanitize_key($data['Name']) : dirname($file),
                        'version' => $data['Version'],
                        'is_active' => is_plugin_active($file),
                        'update_available' => is_array($pluginUpdate) && isset($pluginUpdate['new_version'])
                            && version_compare($data['Version'], (string) $pluginUpdate['new_version'], '<'),
                        'new_version' => is_array($pluginUpdate) ? ($pluginUpdate['new_version'] ?? null) : null,
                    ];
                }

                $themes = [];
                foreach (wp_get_themes() as $theme) {
                    $stylesheet = $theme->get_stylesheet();
                    $themeUpdate = $updateThemes->response[$stylesheet] ?? null;
                    $themes[] = [
                        'name' => $theme->get('Name'),
                        'slug' => $stylesheet,
                        'version' => $theme->get('Version'),
                        'is_active' => $stylesheet === get_stylesheet(),
                        'update_available' => is_array($themeUpdate) && isset($themeUpdate['new_version'])
                            && version_compare($theme->get('Version'), (string) $themeUpdate['new_version'], '<'),
                        'new_version' => is_array($themeUpdate) ? ($themeUpdate['new_version'] ?? null) : null,
                    ];
                }

                return rest_ensure_response([
                    'wp_version' => get_bloginfo('version'),
                    'php_version' => PHP_VERSION,
                    'plugins' => $plugins,
                    'themes' => $themes,
                ]);
            },
        ]);
    }
);

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('shoal token', static function ($args): void {
        if (empty($args[0])) {
            WP_CLI::error('Usage: wp shoal token <token>');
        }
        update_option('shoal_agent_token', (string) $args[0]);
        WP_CLI::success('Token stored.');
    });
}
