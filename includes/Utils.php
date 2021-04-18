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

    public static function sanitizePageSlug(string $slug): string
    {
        $options = (object) Settings::getOptions();
        $default = $options->mailing_list_subsc_page_slug;
        $sanitizedSlug = sanitize_title($slug);
        return mb_strlen($sanitizedSlug) > 3 ? $sanitizedSlug : $default;
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

    public static function getPassoword(string $password): string
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
            $mailingList[$email] = trim(implode(',', [$email, $fname, $lname]), ',');
        }
        ksort($mailingList);
        return implode(PHP_EOL, $mailingList);
    }

    public static function sanitizeUnsubscribedList(string $input): string
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
        return implode(PHP_EOL, $mailingList);
    }

    public static function encrypt(string $string, string $action = 'encrypt')
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
        return self::encrypt($string, 'decrypt');
    }
}
