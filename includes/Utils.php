<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

class Utils
{
    public static function sanitizePageTitle(string $title): string
    {
        $options = (object) Settings::getOptions();
        $default = $options->subscription_page_title;
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

    public static function sanitizeSenderEmail(string $input): string
    {
        $email = self::sanitizeEmail($input);
        $parts = explode('@', $email);
        $domain = array_pop($parts);
        $allowedDomains = (array) apply_filters('rrze_newsletter_sender_allowed_domains', []);
        if (
            filter_var($email, FILTER_VALIDATE_EMAIL)
            && (empty($allowedDomains) || in_array($domain, $allowedDomains))
        ) {
            return $email;
        }
        return '';
    }

    public static function sanitizeRecipientEmail(string $input): string
    {
        $email = self::sanitizeEmail($input);
        $parts = explode('@', $email);
        $domain = array_pop($parts);
        $allowedDomains = (array) apply_filters('rrze_newsletter_recipient_allowed_domains', []);
        if (
            filter_var($email, FILTER_VALIDATE_EMAIL)
            && (empty($allowedDomains) || in_array($domain, $allowedDomains))
        ) {
            return $email;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            do_action(
                'rrze.log.error',
                [
                    'plugin' => plugin()->getBaseName(),
                    'method' => __METHOD__,
                    'message' => sprintf('Error: Invalid Email Address. The recipient email address %s is not valid.', $email)
                ]
            );
        } elseif (!empty($allowedDomains) && !in_array($domain, $allowedDomains)) {
            do_action(
                'rrze.log.error',
                [
                    'plugin' => plugin()->getBaseName(),
                    'method' => __METHOD__,
                    'message' => sprintf('Error: Email Address Not Allowed. The recipient email address %s is not allowed.', $email)
                ]
            );
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

    public static function validateIntRange(string $input, int $default, int $min, int $max): int
    {
        $integer = intval($input);
        if (filter_var($integer, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]) === false) {
            return $default;
        } else {
            return $integer;
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

            if (!self::sanitizeRecipientEmail($email)) {
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
            if (!self::sanitizeRecipientEmail($email)) {
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

    public static function encryptQueryVar(string $string)
    {
        return self::encrypt($string, 'encrypt', true);
    }

    public static function decryptQueryVar(string $string)
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

    public static function getWeekDays(string $yy, string $mm, string $dw)
    {
        return new \DatePeriod(
            new \DateTime("first $dw of $yy-$mm"),
            \DateInterval::createFromDateString("next $dw"),
            new \DateTime("last day of $yy-$mm")
        );
    }

    public static function getWeeklyRecurrence(string $date, int $interval = 1, $verbose = false)
    {
        $unixTimestamp = strtotime($date);
        $year = date('Y', $unixTimestamp);
        $month = date('m', $unixTimestamp);
        $dayOfWeek = date('l', $unixTimestamp);
        $output = [];
        $weekDays = Utils::getWeekDays($year, $month, $dayOfWeek);
        foreach ($weekDays as $day) {
            if ($verbose) {
                $value = sprintf(
                    /* translators: %s: the day of the week. */
                    __('Weekly on %s', 'rrze-newsletter'),
                    __($day->format('l'))
                );
            } else {
                $value = sprintf(
                    'FREQ=WEEKLY;BYDAY=%1$s;INTERVAL=%2$s',
                    strtoupper(substr($day->format('l'), 0, 2)),
                    $interval
                );
            }
            $output[$day->format('Y-m-d')] = $value;
        }
        return $output;
    }

    public static function getMonthlyRecurrence(string $date, int $interval = 1, $verbose = false)
    {
        $unixTimestamp = strtotime($date);
        $year = date('Y', $unixTimestamp);
        $month = date('m', $unixTimestamp);
        $dayOfWeek = date('l', $unixTimestamp);
        $pos = [
            1 => __('first', 'rrze-newsletter'),
            2 => __('second', 'rrze-newsletter'),
            3 => __('third', 'rrze-newsletter'),
            4 => __('fourth', 'rrze-newsletter'),
            -1 => __('last', 'rrze-newsletter')
        ];
        $output = [];
        $weekDays = Utils::getWeekDays($year, $month, $dayOfWeek);
        $count = 0;
        foreach ($weekDays as $day) {
            $count++;
        }
        $i = 1;
        foreach ($weekDays as $day) {
            if ($verbose) {
                $value = [
                    [
                        'BYMONTHDAY' =>
                        sprintf(
                            /* translators: %s: the number of the day. */
                            __('Monthly on day %s', 'rrze-newsletter'),
                            $day->format('j')
                        )
                    ],
                    [
                        'BYSETPOS' =>
                        sprintf(
                            /* translators: 1: The number of the day, 2: The day of the week. */
                            __('Monthly on the %1$s %2$s', 'rrze-newsletter'),
                            $pos[$i],
                            __($day->format('l'))
                        )
                    ]
                ];
            } else {
                $value = [
                    'BYMONTHDAY' =>
                    sprintf(
                        'FREQ=MONTHLY;BYMONTHDAY=%1$s;INTERVAL=%2$s',
                        $day->format('j'),
                        $interval
                    ),
                    'BYSETPOS' =>
                    sprintf(
                        'FREQ=MONTHLY;BYSETPOS=%1$s;BYDAY=%2$s;INTERVAL=%3$s',
                        $i,
                        strtoupper(substr($day->format('l'), 0, 2)),
                        $interval
                    )

                ];
            }
            $i++;
            if ($i == $count) {
                $i = -1;
            }
            $output[$day->format('Y-m-d')] = $value;
        }
        return $output;
    }

    /**
     *  Returns the current timezone
     *
     * Gets timezone settings from the db. If a timezone identifier is used just turns
     * it into a DateTimeZone. If an offset is used, it tries to find a suitable timezone.
     * If all else fails it uses UTC.
     *
     * @return \DateTimeZone The current timezone.
     */
    public static function currentTimeZone()
    {

        $tzStr = get_option('timezone_string');
        $offset = get_option('gmt_offset');

        //Manual offset...
        //@see http://us.php.net/manual/en/timezones.others.php
        //@see https://bugs.php.net/bug.php?id=45543
        //@see https://bugs.php.net/bug.php?id=45528
        //IANA timezone database that provides PHP's timezone support uses POSIX (i.e. reversed) style signs
        if (empty($tzStr) && 0 != $offset && floor($offset) == $offset) {
            $offsetStr = $offset > 0 ? "-$offset" : '+' . absint($offset);
            $tzStr  = 'Etc/GMT' . $offsetStr;
        }

        //Issue with the timezone selected, set to 'UTC'
        if (empty($tzStr)) {
            $tzStr = 'UTC';
        }

        $timezone = new \DateTimeZone($tzStr);
        return $timezone;
    }

    /**
     * Returns the next occurrences rrule
     *
     * @param string $dtStart
     * @param string $rrule
     * @param integer $count
     * @return array The next ocurrences. An array of \DateTime objects.
     */
    public static function nextOcurrences(string $dtStart, string $rrule, int $count = 1)
    {
        $dt = new \DateTime($dtStart, Utils::currentTimeZone());
        $r = new Recurrence();
        $r->startDate($dt)
            ->rrule($rrule)
            ->exclusions([$dt])
            ->count($count)
            ->generateOccurrences();
        return $r->occurrences;
    }

    /**
     * Validate a datetime string.
     *
     * @param string $date
     * @param string $format
     * @return boolean
     */
    public static function validateDate(string $date, string $format = 'Y-m-d H:i:s'): bool
    {
        $dt = \DateTime::createFromFormat($format, $date);
        return $dt && $dt->format($format) === $date;
    }

    /**
     * Search for a key in an array, recursively.
     *
     * @param array $haystack
     * @param string $needle
     * @return object
     */
    public static function recursiveSearchArrayKey(array $haystack, string $needle): object
    {
        $iterator = new \RecursiveArrayIterator($haystack);
        $recursive = new \RecursiveIteratorIterator(
            $iterator,
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($recursive as $key => $value) {
            if ($key === $needle) {
                yield $value;
            }
        }
    }

    /**
     * Replaces double line breaks with paragraph elements.
     * @see https://developer.wordpress.org/reference/functions/wpautop/
     *
     * @param string $text
     * @param boolean $br
     * @return string
     */
    public static function wpautop($text, $br = false)
    {
        $pre_tags = array();

        if (trim($text) === '') {
            return '';
        }

        // Just to make things a little easier, pad the end.
        $text = $text . "\n";

        /*
         * Pre tags shouldn't be touched by autop.
         * Replace pre tags with placeholders and bring them back after autop.
         */
        if (str_contains($text, '<pre')) {
            $text_parts = explode('</pre>', $text);
            $last_part  = array_pop($text_parts);
            $text       = '';
            $i          = 0;

            foreach ($text_parts as $text_part) {
                $start = strpos($text_part, '<pre');

                // Malformed HTML?
                if (false === $start) {
                    $text .= $text_part;
                    continue;
                }

                $name              = "<pre wp-pre-tag-$i></pre>";
                $pre_tags[$name] = substr($text_part, $start) . '</pre>';

                $text .= substr($text_part, 0, $start) . $name;
                ++$i;
            }

            $text .= $last_part;
        }
        // Change multiple <br>'s into two line breaks, which will turn into paragraphs.
        $text = preg_replace('|<br\s*/?>\s*<br\s*/?>|', "\n\n", $text);

        $allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|map|area|blockquote|address|style|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';

        // Add a double line break above block-level opening tags.
        $text = preg_replace('!(<' . $allblocks . '[\s/>])!', "\n\n$1", $text);

        // Add a double line break below block-level closing tags.
        $text = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $text);

        // Add a double line break after hr tags, which are self closing.
        $text = preg_replace('!(<hr\s*?/?>)!', "$1\n\n", $text);

        // Standardize newline characters to "\n".
        $text = str_replace(array("\r\n", "\r"), "\n", $text);

        // Find newlines in all elements and add placeholders.
        $text = wp_replace_in_html_tags($text, array("\n" => ' <!-- wpnl --> '));

        // Collapse line breaks before and after <option> elements so they don't get autop'd.
        if (str_contains($text, '<option')) {
            $text = preg_replace('|\s*<option|', '<option', $text);
            $text = preg_replace('|</option>\s*|', '</option>', $text);
        }

        /*
         * Collapse line breaks inside <object> elements, before <param> and <embed> elements
         * so they don't get autop'd.
         */
        if (str_contains($text, '</object>')) {
            $text = preg_replace('|(<object[^>]*>)\s*|', '$1', $text);
            $text = preg_replace('|\s*</object>|', '</object>', $text);
            $text = preg_replace('%\s*(</?(?:param|embed)[^>]*>)\s*%', '$1', $text);
        }

        /*
         * Collapse line breaks inside <audio> and <video> elements,
         * before and after <source> and <track> elements.
         */
        if (str_contains($text, '<source') || str_contains($text, '<track')) {
            $text = preg_replace('%([<\[](?:audio|video)[^>\]]*[>\]])\s*%', '$1', $text);
            $text = preg_replace('%\s*([<\[]/(?:audio|video)[>\]])%', '$1', $text);
            $text = preg_replace('%\s*(<(?:source|track)[^>]*>)\s*%', '$1', $text);
        }

        // Collapse line breaks before and after <figcaption> elements.
        if (str_contains($text, '<figcaption')) {
            $text = preg_replace('|\s*(<figcaption[^>]*>)|', '$1', $text);
            $text = preg_replace('|</figcaption>\s*|', '</figcaption>', $text);
        }

        // Remove more than two contiguous line breaks.
        $text = preg_replace("/\n\n+/", "\n\n", $text);

        // Split up the contents into an array of strings, separated by double line breaks.
        $paragraphs = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Reset $text prior to rebuilding.
        $text = '';

        // Rebuild the content as a string, wrapping every bit with a <p>.
        foreach ($paragraphs as $paragraph) {
            $text .= '<p class="has-normal-padding">' . trim($paragraph, "\n") . "</p>\n";
        }

        // Under certain strange conditions it could create a P of entirely whitespace.
        $text = preg_replace('|<p class="has-normal-padding">\s*</p>|', '', $text);

        // Add a closing <p> inside <div>, <address>, or <form> tag if missing.
        $text = preg_replace('!<p class="has-normal-padding">([^<]+)</(div|address|form)>!', '<p class="has-normal-padding">$1</p></$2>', $text);

        // If an opening or closing block element tag is wrapped in a <p>, unwrap it.
        $text = preg_replace('!<p class="has-normal-padding">\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', '$1', $text);

        // In some cases <li> may get wrapped in <p>, fix them.
        $text = preg_replace('|<p class="has-normal-padding">(<li.+?)</p>|', '$1', $text);

        // If a <blockquote> is wrapped with a <p>, move it inside the <blockquote>.
        $text = preg_replace('|<p class="has-normal-padding"><blockquote([^>]*)>|i', '<blockquote$1><p class="has-normal-padding">', $text);
        $text = str_replace('</blockquote></p>', '</p></blockquote>', $text);

        // If an opening or closing block element tag is preceded by an opening <p> tag, remove it.
        $text = preg_replace('!<p class="has-normal-padding">\s*(</?' . $allblocks . '[^>]*>)!', '$1', $text);

        // If an opening or closing block element tag is followed by a closing <p> tag, remove it.
        $text = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', '$1', $text);

        // Optionally insert line breaks.
        if ($br) {
            // Replace newlines that shouldn't be touched with a placeholder.
            $text = preg_replace_callback('/<(script|style|svg|math).*?<\/\\1>/s', '_autop_newline_preservation_helper', $text);

            // Normalize <br>
            $text = str_replace(array('<br>', '<br/>'), '<br />', $text);

            // Replace any new line characters that aren't preceded by a <br /> with a <br />.
            $text = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $text);

            // Replace newline placeholders with newlines.
            $text = str_replace('<WPPreserveNewline />', "\n", $text);
        }

        // If a <br /> tag is after an opening or closing block tag, remove it.
        $text = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', '$1', $text);

        // If a <br /> tag is before a subset of opening or closing block tags, remove it.
        $text = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $text);
        $text = preg_replace("|\n</p>$|", '</p>', $text);

        // Replace placeholder <pre> tags with their original content.
        if (!empty($pre_tags)) {
            $text = str_replace(array_keys($pre_tags), array_values($pre_tags), $text);
        }

        // Restore newlines in all elements.
        if (str_contains($text, '<!-- wpnl -->')) {
            $text = str_replace(array(' <!-- wpnl --> ', '<!-- wpnl -->'), "\n", $text);
        }

        return $text;
    }
}
