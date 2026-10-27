<?php
/**
 * Plugin Name: Shoal Agent
 * Description: Companion REST agent for the Shoal WordPress management platform. Exposes read-only system info secured by a bearer token.
 * Version: 0.2.0
 * Requires PHP: 7.4
 * Author: Shoal
 * Text Domain: shoal-agent
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SHOAL_AGENT_OPTION', 'shoal_agent_token');

/**
 * Resolve the active token: the SHOAL_AGENT_TOKEN constant wins over the stored option.
 */
function shoal_agent_token(): string
{
    if (defined('SHOAL_AGENT_TOKEN') && is_string(SHOAL_AGENT_TOKEN) && SHOAL_AGENT_TOKEN !== '') {
        return SHOAL_AGENT_TOKEN;
    }

    $stored = get_option(SHOAL_AGENT_OPTION, '');

    return is_string($stored) ? $stored : '';
}

add_action(
    'rest_api_init',
    function () {
        register_rest_route('agent/v1', '/system-info', [
            'methods' => 'GET',
            'permission_callback' => static function (WP_REST_Request $request): bool {
                $provided = (string) $request->get_header('X-Agent-Token');
                $expected = shoal_agent_token();

                return $provided !== ''
                    && $expected !== ''
                    && hash_equals($expected, $provided);
            },
            'callback' => static function (): WP_REST_Response {
                require_once ABSPATH.'wp-admin/includes/plugin.php';
                require_once ABSPATH.'wp-admin/includes/update.php';

                $updatePlugins = get_site_transient('update_plugins');
                $updateThemes = get_site_transient('update_themes');

                $plugins = [];
                foreach (get_plugins() as $file => $data) {
                    $pluginUpdate = is_object($updatePlugins) ? ($updatePlugins->response[$file] ?? null) : null;
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
                    $themeUpdate = is_object($updateThemes) ? ($updateThemes->response[$stylesheet] ?? null) : null;
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

add_action(
    'admin_menu',
    function () {
        add_options_page(
            __('Shoal Agent', 'shoal-agent'),
            __('Shoal Agent', 'shoal-agent'),
            'manage_options',
            'shoal-agent',
            'shoal_agent_render_settings_page'
        );
    }
);

add_action(
    'admin_init',
    function () {
        register_setting(
            'shoal_agent',
            SHOAL_AGENT_OPTION,
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

function shoal_agent_render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'shoal-agent'));
    }

    $constantDefined = defined('SHOAL_AGENT_TOKEN') && is_string(SHOAL_AGENT_TOKEN) && SHOAL_AGENT_TOKEN !== '';
    $token = shoal_agent_token();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <p>
            <?php
            echo esc_html__(
                'Paste the API token generated for this site in your Shoal dashboard (site page → Agent Setup).',
                'shoal-agent'
            );
            ?>
        </p>

        <?php if ($constantDefined) : ?>
            <div class="notice notice-warning">
                <p>
                    <?php
                    echo esc_html__(
                        'The SHOAL_AGENT_TOKEN constant is defined, so it overrides the token stored below.',
                        'shoal-agent'
                    );
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <form action="options.php" method="post">
            <?php
            settings_fields('shoal_agent');
            ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="shoal_agent_token"><?php esc_html_e('API token', 'shoal-agent'); ?></label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="shoal_agent_token"
                            name="<?php echo esc_attr(SHOAL_AGENT_OPTION); ?>"
                            value="<?php echo esc_attr($token); ?>"
                            class="regular-text code"
                            autocomplete="off"
                            spellcheck="false"
                        />
                        <p class="description">
                            <?php
                            if ($token !== '') {
                                $masked = str_repeat('•', max(4, strlen($token) - 4)).substr($token, -4);
                                echo esc_html(
                                    sprintf(
                                        /* translators: %s: masked token preview */
                                        __('Token is set (%s). The endpoint is authenticated with it.', 'shoal-agent'),
                                        $masked
                                    )
                                );
                            } else {
                                esc_html_e('No token stored yet — scans from Shoal will be rejected until one is set.', 'shoal-agent');
                            }
                            ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <h2><?php esc_html_e('Endpoint', 'shoal-agent'); ?></h2>
        <p>
            <code><?php echo esc_url(rest_url('agent/v1/system-info')); ?></code>
        </p>
    </div>
    <?php
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('shoal token', static function ($args): void {
        if (empty($args[0])) {
            WP_CLI::error('Usage: wp shoal token <token>');
        }
        update_option(SHOAL_AGENT_OPTION, (string) $args[0]);
        WP_CLI::success('Token stored.');
    });
}
