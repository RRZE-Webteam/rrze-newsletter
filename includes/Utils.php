<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

class Utils
{
    public static function sanitizePageTitle(string $title): string
    {
        $options = (object) Settings::getOptions();
        $default = $options->mailing_list_subsc_page_title;
        $sanitizedTitle = sanitize_text_field($title);
        return mb_strlen($sanitizedTitle) > 3 ? $sanitizedTitle : $default;
    }

    public static function sanitizeUrl(string $input): string
    {
        $url = sanitize_text_field($input);
        if (filter_var($url, FILTER_SANITIZE_URL)) {
            return $url;
        }
        return '';
    }

    public static function sanitizeEmail(string $input): string
    {
        $email = sanitize_text_field($input);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
        return '';
    }

    public static function sanitizePassword(string $password): string
    {
        return self::setPassword($password);
    }

    public static function setPassword(string $password): string
    {
        return self::encrypt($password);
    }

    public static function getPassword(string $password): string
    {
        return self::decrypt($password);
    }

    public static function validateIntRange(string $input, int $default, int $min, int $max, bool $absint = true): int
    {
        $integer = $absint ? absint($input) : intval($input);
        if (filter_var(absint($input), FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]) === false) {
            return $integer;
        } else {
            return $default;
        }
    }

    public static function sanitizeMailingList(string $input, string $output = '')
    {
        $mailingList = [];
        $textField = explode(PHP_EOL, sanitize_textarea_field($input));
        foreach ($textField as $row) {
            $aryRow = explode(',', $row);
            $email = isset($aryRow[0]) ? trim($aryRow[0]) : ''; // Email Address
            $fname = isset($aryRow[1]) ? trim($aryRow[1]) : ''; // First Name
            $lname = isset($aryRow[2]) ? trim($aryRow[2]) : ''; // Last Name

            if (!self::sanitizeEmail($email)) {
                continue;
            }
            $mailingList[$email] = trim(implode(',', [$email, $fname, $lname]), ',');
        }
        ksort($mailingList);
        return $output == '' ? implode(PHP_EOL, $mailingList) : $mailingList;
    }

    public static function sanitizeUnsubscribedList(string $input, string $output = '')
    {
        $mailingList = [];
        $emails = explode(PHP_EOL, sanitize_textarea_field($input));
        foreach ($emails as $email) {
            $email = trim($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $mailingList[$email] = $email;
        }
        ksort($mailingList);
        return $output == '' ? implode(PHP_EOL, $mailingList) : $mailingList;
    }

    public static function encrypt(string $string, string $action = 'encrypt', bool $safeurl = false)
    {
        if ($string == '') {
            return $string;
        }

        $secretKey = AUTH_KEY;
        $secretSalt = AUTH_SALT;

        $output = false;
        $encryptMethod = 'AES-256-CBC';
        $key = hash('sha256', $secretKey);
        $salt = substr(hash('sha256', $secretSalt), 0, 16);

        if ($action == 'encrypt') {
            if ($safeurl) {
                $output = self::urlsafeEncode(openssl_encrypt($string, $encryptMethod, $key, 0, $salt));
            } else {
                $output = base64_encode(openssl_encrypt($string, $encryptMethod, $key, 0, $salt));
            }
        } else if ($action == 'decrypt') {
            if ($safeurl) {
                $output = openssl_decrypt(self::urlsafeDecode($string), $encryptMethod, $key, 0, $salt);
            } else {
                $output = openssl_decrypt(base64_decode($string), $encryptMethod, $key, 0, $salt);
            }
        }

        return $output;
    }

    public static function decrypt(string $string)
    {
        return self::encrypt($string, 'decrypt');
    }

    public static function encryptUrlQuery(string $string)
    {
        return self::encrypt($string, 'encrypt', true);
    }

    public static function decryptUrlQuery(string $string)
    {
        return self::encrypt($string, 'decrypt', true);
    }

    private static function urlsafeEncode(string $string)
    {
        return str_replace('=', '', strtr(base64_encode($string), '+/', '-_'));
    }

    private static function urlsafeDecode(string $string)
    {
        $remainder = strlen($string) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $string .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($string, '-_', '+/'));
    }

    public static function isPluginAvailable($plugin)
    {
        if (is_network_admin()) {
            return file_exists(WP_PLUGIN_DIR . '/' . $plugin);
        } elseif (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        return is_plugin_active($plugin);
    }

    public static function getCustomMjmlEndpoint()
    {
        if (
            self::isPluginAvailable('rrze-settings/rrze-settings.php')
            && class_exists('\RRZE\Settings\Options')
        ) {
            $optionName = method_exists('\RRZE\Settings\Options', 'getOptionName')
                ? \RRZE\Settings\Options::getOptionName()
                : '';
            $options = (array) get_site_option($optionName);
            if (isset($options['plugins'])) {
                $plugins = (array) $options['plugins'];
                return !empty($plugins['rrze_newsletter_mjml_endpoint'])
                    ? $plugins['rrze_newsletter_mjml_endpoint']
                    : '';
            }
        }
        return '';
    }
}
