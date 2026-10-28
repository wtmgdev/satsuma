<?php
/**
 * Plugin Name: Satsuma Agent
 * Description: Connects this site to Satsuma for inventory scans, update tracking and monitoring.
 * Version: 0.7.0
 * Requires PHP: 7.4
 * Author: Satsuma
 * Text Domain: satsuma-agent
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SATSUMA_AGENT_OPTION', 'satsuma_agent_token');

/**
 * Resolve the active token: the SATSUMA_AGENT_TOKEN constant wins over the stored option.
 */
function satsuma_agent_token(): string
{
    if (defined('SATSUMA_AGENT_TOKEN') && is_string(SATSUMA_AGENT_TOKEN) && SATSUMA_AGENT_TOKEN !== '') {
        return SATSUMA_AGENT_TOKEN;
    }

    $stored = get_option(SATSUMA_AGENT_OPTION, '');

    // Upgrade path: the plugin was previously named "shoal-agent" and
    // stored its token under a different option key — carry it over.
    if ($stored === '' && get_option('shoal_agent_token', '') !== '') {
        $stored = (string) get_option('shoal_agent_token');
        update_option(SATSUMA_AGENT_OPTION, $stored);
    }

    return is_string($stored) ? $stored : '';
}

/**
 * Shared token check for all agent REST routes.
 */
function satsuma_agent_authorize(WP_REST_Request $request): bool
{
    $provided = (string) $request->get_header('X-Agent-Token');
    $expected = satsuma_agent_token();

    return $provided !== ''
        && $expected !== ''
        && hash_equals($expected, $provided);
}

/**
 * Normalize a value that may be an object (stdClass from transients or
 * premium updaters) into an array.
 */
function satsuma_agent_to_array(mixed $value): array
{
    if (is_object($value)) {
        return (array) $value;
    }

    return is_array($value) ? $value : [];
}

add_action(
    'rest_api_init',
    function () {
        register_rest_route('agent/v1', '/ping', [
            'methods' => 'GET',
            'permission_callback' => 'satsuma_agent_authorize',
            'callback' => static function (): WP_REST_Response {
                return rest_ensure_response([
                    'ok' => true,
                    'wp_version' => get_bloginfo('version'),
                    'php_version' => PHP_VERSION,
                ]);
            },
        ]);

        register_rest_route('agent/v1', '/system-info', [
            'methods' => 'GET',
            'permission_callback' => 'satsuma_agent_authorize',
            'callback' => static function (): WP_REST_Response {
                require_once ABSPATH.'wp-admin/includes/plugin.php';
                require_once ABSPATH.'wp-admin/includes/update.php';

                // Premium updaters (e.g. WP Migrate) throttle their own remote
                // checks and skip injecting on every transient rebuild, so a
                // forced check would drop their entries. Snapshot the existing
                // response entries, force the fresh check, then restore any
                // entries the fresh check lost.
                $previousPluginResponses = satsuma_agent_to_array(
                    satsuma_agent_to_array(get_site_transient('update_plugins'))['response'] ?? []
                );
                $previousThemeResponses = satsuma_agent_to_array(
                    satsuma_agent_to_array(get_site_transient('update_themes'))['response'] ?? []
                );

                wp_version_check();
                wp_update_plugins();
                wp_update_themes();

                $updatePlugins = satsuma_agent_to_array(get_site_transient('update_plugins'));
                $updateThemes = satsuma_agent_to_array(get_site_transient('update_themes'));

                $pluginResponses = satsuma_agent_to_array($updatePlugins['response'] ?? []);
                $themeResponses = satsuma_agent_to_array($updateThemes['response'] ?? []);

                foreach ($previousPluginResponses as $file => $entry) {
                    if (!isset($pluginResponses[$file]) && !isset($updatePlugins['no_update'][$file])) {
                        $pluginResponses[$file] = $entry;
                    }
                }

                foreach ($previousThemeResponses as $stylesheet => $entry) {
                    if (!isset($themeResponses[$stylesheet]) && !isset($updateThemes['no_update'][$stylesheet])) {
                        $themeResponses[$stylesheet] = $entry;
                    }
                }

                $coreUpdate = null;
                foreach (satsuma_agent_to_array(get_core_updates(['dismissed' => false])) as $coreOffer) {
                    $coreOffer = satsuma_agent_to_array($coreOffer);

                    if (($coreOffer['response'] ?? '') === 'upgrade') {
                        $coreUpdate = $coreOffer;
                        break;
                    }
                }

                $plugins = [];
                foreach (get_plugins() as $file => $data) {
                    $pluginUpdate = satsuma_agent_to_array($pluginResponses[$file] ?? null);
                    $newVersion = $pluginUpdate['new_version'] ?? null;

                    $plugins[] = [
                        'name' => $data['Name'],
                        'slug' => dirname($file) === '.' ? sanitize_key($data['Name']) : dirname($file),
                        'version' => $data['Version'],
                        'is_active' => is_plugin_active($file),
                        'update_available' => $newVersion !== null
                            && version_compare((string) $data['Version'], (string) $newVersion, '<'),
                        'new_version' => $newVersion,
                    ];
                }

                $themes = [];
                foreach (wp_get_themes() as $theme) {
                    $stylesheet = $theme->get_stylesheet();
                    $themeUpdate = satsuma_agent_to_array($themeResponses[$stylesheet] ?? null);
                    $newVersion = $themeUpdate['new_version'] ?? null;

                    $themes[] = [
                        'name' => $theme->get('Name'),
                        'slug' => $stylesheet,
                        'version' => $theme->get('Version'),
                        'is_active' => $stylesheet === get_stylesheet(),
                        'update_available' => $newVersion !== null
                            && version_compare((string) $theme->get('Version'), (string) $newVersion, '<'),
                        'new_version' => $newVersion,
                    ];
                }

                return rest_ensure_response([
                    'wp_version' => get_bloginfo('version'),
                    'php_version' => PHP_VERSION,
                    'wp_latest_version' => $coreUpdate['version'] ?? null,
                    'core_update_available' => $coreUpdate !== null,
                    'plugins' => $plugins,
                    'themes' => $themes,
                ]);
            },
        ]);
    }
);

add_action(
    'admin_menu',
    function () {
        add_options_page(
            __('Satsuma Agent', 'satsuma-agent'),
            __('Satsuma Agent', 'satsuma-agent'),
            'manage_options',
            'satsuma-agent',
            'satsuma_agent_render_settings_page'
        );
    }
);

add_action(
    'admin_init',
    function () {
        register_setting(
            'satsuma_agent',
            SATSUMA_AGENT_OPTION,
            [
                'type' => 'string',
                'sanitize_callback' => static function ($value): string {
                    return is_scalar($value) ? trim((string) $value) : '';
                },
                'default' => '',
            ]
        );
    }
);

function satsuma_agent_render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'satsuma-agent'));
    }

    $constantDefined = defined('SATSUMA_AGENT_TOKEN') && is_string(SATSUMA_AGENT_TOKEN) && SATSUMA_AGENT_TOKEN !== '';
    $token = satsuma_agent_token();
    $assets = plugins_url('assets/', __FILE__);
    $connected = $token !== '';
    ?>
    <div class="wrap satsuma-agent-wrap" style="max-width:1180px;">
        <h1 style="display:flex;align-items:center;gap:10px;">
            <img src="<?php echo esc_url($assets.'icon.svg'); ?>"
                 alt=""
                 style="width:28px;height:28px;display:block;border-radius:6px;" />
            <?php esc_html_e('Satsuma Agent', 'satsuma-agent'); ?>
        </h1>

        <div>
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:24px;max-width:640px;">
                <h2 style="margin-top:0;"><?php esc_html_e('Connect this site to Satsuma', 'satsuma-agent'); ?></h2>
                <p><?php esc_html_e('Paste the API token generated for this site in your Satsuma dashboard (site page → Agent Setup).', 'satsuma-agent'); ?></p>

                <?php if ($connected) : ?>
                    <p>
                        <span style="display:inline-flex;align-items:center;gap:6px;background:#edf9f0;color:#1a7f37;border:1px solid #b8e6c4;border-radius:999px;padding:4px 12px;font-size:13px;">
                            <span style="width:8px;height:8px;border-radius:50%;background:#1a7f37;display:inline-block;"></span>
                            <?php esc_html_e('Token configured', 'satsuma-agent'); ?>
                        </span>
                    </p>
                <?php endif; ?>

                <?php if ($constantDefined) : ?>
                    <div class="notice notice-warning inline">
                        <p><?php esc_html_e('The SATSUMA_AGENT_TOKEN constant is defined, so it overrides the token stored below.', 'satsuma-agent'); ?></p>
                    </div>
                <?php endif; ?>

                <form action="options.php" method="post">
                    <?php settings_fields('satsuma_agent'); ?>
                    <table class="form-table" role="presentation" style="margin-top:0;">
                        <tr>
                            <th scope="row" style="padding-left:0;">
                                <label for="satsuma_agent_token"><?php esc_html_e('API token', 'satsuma-agent'); ?></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="satsuma_agent_token"
                                    name="<?php echo esc_attr(SATSUMA_AGENT_OPTION); ?>"
                                    value="<?php echo esc_attr($token); ?>"
                                    class="regular-text code"
                                    autocomplete="off"
                                    spellcheck="false"
                                />
                                <p class="description">
                                    <?php
                                    if ($connected) {
                                        $masked = str_repeat('•', max(4, strlen($token) - 4)).substr($token, -4);
                                        echo esc_html(sprintf(
                                            /* translators: %s: masked token preview */
                                            __('Token is set (%s). The endpoint is authenticated with it.', 'satsuma-agent'),
                                            $masked
                                        ));
                                    } else {
                                        esc_html_e('No token stored yet — scans from Satsuma will be rejected until one is set.', 'satsuma-agent');
                                    }
                                    ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Save token', 'satsuma-agent')); ?>
                </form>

                <h3 style="margin-bottom:4px;"><?php esc_html_e('Agent endpoint', 'satsuma-agent'); ?></h3>
                <p><code><?php echo esc_url(rest_url('agent/v1/system-info')); ?></code></p>
            </div>

        </div>
    </div>
    <?php
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('satsuma token', static function ($args): void {
        if (empty($args[0])) {
            WP_CLI::error('Usage: wp satsuma token <token>');
        }
        update_option(SATSUMA_AGENT_OPTION, (string) $args[0]);
        WP_CLI::success('Token stored.');
    });
}
