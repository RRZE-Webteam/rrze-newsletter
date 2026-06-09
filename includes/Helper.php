<?php


namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

class Helper
{
    private static ?string $debugRequestId = null;

    private static int $debugSequence = 0;

    private static ?float $debugStartTime = null;

    private static bool $debugShutdownRegistered = false;

    /**
     * Checks whether the given plugin is available in the current context.
     *
     * - In the network admin, this only checks whether the plugin file exists in the plugins directory.
     * - Outside the network admin, this checks whether the plugin is active for the current site.
     *
     * @param string $plugin Plugin path relative to the plugins directory, e.g. 'akismet/akismet.php'.
     *
     * @return bool True if the plugin file exists (network admin) or the plugin is active (site context),
     *              otherwise false.
     */
    public static function isPluginAvailable(string $plugin): bool
    {
        if (is_network_admin()) {
            return file_exists(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin);
        } elseif (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        return is_plugin_active($plugin);
    }

    /**
     * @param array<string, mixed> $input
     */
    public static function get_html_var_dump(array $input, bool $nohtml = true): string
    {
        if ($nohtml) {
            foreach ($input as $key => $value) {

                if (is_array($value)) {
                    foreach ($value as $skey => $svalue) {
                        if (is_string($svalue)) {
                            $input[$key][$skey] = '<em>' . esc_html($svalue) . '</em>';
                        }
                    }
                } elseif (is_string($value)) {
                    $input[$key] = esc_html($value);
                }
            }
        }

        $out = self::get_var_dump($input);

        $patterns = [
            "/=>[\r\n\s]+/" => ' => ',
            "/\s+bool\(true\)/" => ' <span style="color:green">TRUE</span>,',
            "/\s+bool\(false\)/" => ' <span style="color:red">FALSE</span>,',
            "/,([\r\n\s]+})/" => "$1",
            "/\s+string\(\d+\)/" => '',
        ];
        foreach ($patterns as $pattern => $replacement) {
            $replaced = preg_replace($pattern, $replacement, $out);
            if ($replaced !== null) {
                $out = $replaced;
            }
        }

        $replacedKeys = preg_replace(
            "/\[\"([a-z\-_0-9]+)\"\]/i",
            "[\"<span style=\"color:#dd8800\">$1</span>\"]",
            $out
        );
        if ($replacedKeys !== null) {
            $out = $replacedKeys;
        }

        return '<pre>' . $out . '</pre>';
    }

    public static function get_var_dump(mixed $input): string
    {
        ob_start();
        var_dump($input);
        return "\n" . (string)ob_get_clean();
    }

    /**
     * Writes a structured entry to the WordPress debug log.
     */
    public static function debug(mixed $input, string $level = 'i'): void
    {
        if (
            !defined('WP_DEBUG')
            || !WP_DEBUG
            || !defined('WP_DEBUG_LOG')
            || !WP_DEBUG_LOG
        ) {
            return;
        }

        if (in_array(strtolower((string) WP_DEBUG_LOG), ['true', '1'], true)) {
            $logPath = WP_CONTENT_DIR . '/debug.log';
        } elseif (is_string(WP_DEBUG_LOG)) {
            $logPath = WP_DEBUG_LOG;
        } else {
            return;
        }

        if (is_array($input) || is_object($input)) {
            $input = print_r($input, true);
        }

        switch (strtolower($level)) {
            case 'e':
            case 'error':
                $level = 'ERROR';
                break;
            case 'i':
            case 'info':
                $level = 'INFO';
                break;
            case 'd':
            case 'debug':
                $level = 'DEBUG';
                break;
            default:
                $level = 'INFO';
        }

        self::$debugStartTime ??= (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
        self::$debugRequestId ??= substr(
            md5(self::$debugStartTime . ':' . getmypid()),
            0,
            8
        );
        if (!self::$debugShutdownRegistered) {
            register_shutdown_function([self::class, 'writeDebugEnd'], $logPath);
            self::$debugShutdownRegistered = true;
        }
        self::$debugSequence++;

        $elapsed = microtime(true) - self::$debugStartTime;
        $context = self::getDebugRequestContext();
        $request = self::getDebugRequestDescription();
        $prefix = sprintf(
            '[%s][%s][%03d][+%.3fs][%s][%s]',
            self::$debugRequestId,
            $level,
            self::$debugSequence,
            $elapsed,
            $context,
            $request
        );

        if (self::$debugSequence === 1) {
            error_log(
                gmdate('[d-M-Y H:i:s \U\T\C]')
                . ' RRZE Newsletter TRACE BEGIN '
                . $prefix
                . PHP_EOL,
                3,
                $logPath
            );
        }

        error_log(
            gmdate('[d-M-Y H:i:s \U\T\C]')
            . ' RRZE Newsletter '
            . $prefix
            . ' '
            . (string) $input
            . PHP_EOL,
            3,
            $logPath
        );
    }

    private static function getDebugRequestContext(): string
    {
        $path = isset($_SERVER['REQUEST_URI'])
            ? (string) parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH)
            : '';

        if (defined('WP_CLI') && WP_CLI) {
            return 'cli';
        }
        if ((defined('DOING_CRON') && DOING_CRON) || str_ends_with($path, '/wp-cron.php')) {
            return 'cron';
        }
        if (
            (defined('REST_REQUEST') && REST_REQUEST)
            || str_contains($path, '/wp-json/')
            || isset($_GET['rest_route'])
        ) {
            return 'rest';
        }
        if (
            (defined('DOING_AJAX') && DOING_AJAX)
            || str_ends_with($path, '/wp-admin/admin-ajax.php')
        ) {
            return 'ajax';
        }
        if (function_exists('is_network_admin') && is_network_admin()) {
            return 'network-admin';
        }
        if (
            (function_exists('is_admin') && is_admin())
            || str_contains($path, '/wp-admin/')
        ) {
            return 'admin';
        }
        return 'frontend';
    }

    private static function getDebugRequestDescription(): string
    {
        if (defined('WP_CLI') && WP_CLI) {
            return 'WP-CLI';
        }

        $method = isset($_SERVER['REQUEST_METHOD'])
            ? strtoupper((string) $_SERVER['REQUEST_METHOD'])
            : 'INTERNAL';
        $uri = isset($_SERVER['REQUEST_URI'])
            ? (string) parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH)
            : '';
        $description = trim($method . ' ' . $uri);

        if (
            defined('DOING_AJAX')
            && DOING_AJAX
            && isset($_REQUEST['action'])
            && is_scalar($_REQUEST['action'])
        ) {
            $action = preg_replace('/[^a-zA-Z0-9_.-]/', '', (string) $_REQUEST['action']);
            if ($action !== '') {
                $description .= ' action=' . $action;
            }
        }

        return $description !== '' ? $description : 'internal';
    }

    public static function writeDebugEnd(string $logPath): void
    {
        $elapsed = microtime(true) - (self::$debugStartTime ?? microtime(true));
        $status = http_response_code();
        $status = $status !== false ? $status : 200;

        error_log(
            gmdate('[d-M-Y H:i:s \U\T\C]')
            . sprintf(
                ' RRZE Newsletter TRACE END [%s][calls=%d][%.3fs][status=%d][peak=%.1fMB][%s][%s]',
                self::$debugRequestId ?? 'unknown',
                self::$debugSequence,
                $elapsed,
                $status,
                memory_get_peak_usage(true) / 1048576,
                self::getDebugRequestContext(),
                self::getDebugRequestDescription()
            )
            . PHP_EOL,
            3,
            $logPath
        );
    }

    public static function shortcode_boolean(mixed $value): bool
    {
        $value = esc_attr($value);
        return in_array($value, [true, 'true', '1', 'yes', 'ja', 'on'], true);
    }
}
