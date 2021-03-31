<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

class Utils
{
    public static function dateFormat(string $format, int $timestamp): string
    {
        return date_i18n($format, $timestamp);
    }

    public static function timeFormat(string $format, int $timestamp): string
    {
        return date_i18n($format, $timestamp);
    }

    public static function validateDate(string $date, string $format = 'Y-m-d'): bool
    {
        $dt = \DateTime::createFromFormat($format, $date);
        return $dt && $dt->format($format) === $date;
    }

    public static function validateTime(string $date, string $format = 'H:i:s'): bool
    {
        return self::validateDate($date, $format);
    }

    public static function sanitizeEmail(string $input): string
    {
        $email = sanitize_text_field($input);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
        return '';
    }

    public static function sanitizeMailingList(string $input): string
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
            $mailingList[$email] = implode(',', [$email, $fname, $lname]);
        }
        ksort($mailingList);
        return implode(PHP_EOL, $mailingList);
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

    public static function getFiles(string $path, array $ext, string $needle): array
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $files = [];
        foreach ($iterator as $path) {
            if ($path->isDir()) {
                continue;
            }
            $str = $path->__toString();
            $key = substr($str, strpos($str, $needle));
            if (in_array($path->getExtension(), $ext)) {
                $files[$key] = str_replace('.' . $path->getExtension(), '', $path->getFilename());
            }
        }
        return $files;
    }

    public static function hexToRgb(string $hex): array
    {
        $hex = str_replace('#', '', $hex);
        $length = strlen($hex);
        $rgb = [];
        $rgb[] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
        $rgb[] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
        $rgb[] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
        return $rgb;
    }

    public static function htmlEncode(string $value): string
    {
        return htmlentities(stripslashes($value));
    }

    public static function htmlDecode(string $value): string
    {
        return html_entity_decode(stripslashes($value));
    }

    public static function crypt(string $string, string $action = 'encrypt')
    {
        $secretKey = AUTH_KEY;
        $secretSalt = AUTH_SALT;

        $output = false;
        $encryptMethod = 'AES-256-CBC';
        $key = hash('sha256', $secretKey);
        $salt = substr(hash('sha256', $secretSalt), 0, 16);

        if ($action == 'encrypt') {
            $output = base64_encode(openssl_encrypt($string, $encryptMethod, $key, 0, $salt));
        } else if ($action == 'decrypt') {
            $output = openssl_decrypt(base64_decode($string), $encryptMethod, $key, 0, $salt);
        }

        return $output;
    }

    public static function decrypt(string $string)
    {
        return self::crypt($string, 'decrypt');
    }

    public static function getPostMetaByKey(string $metaKey)
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = %s LIMIT 1", $metaKey));
    }

    public static function redirectTo404()
    {
        if (!empty(locate_template('404.php'))) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            get_template_part(404);
            exit;
        }

        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . site_url());
        exit;
    }
}
