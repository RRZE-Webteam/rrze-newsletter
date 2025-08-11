<?php

namespace RRZE\Newsletter\Blocks\ICS;

defined('ABSPATH') || exit;

use ICal\ICal;
use RRule\RRule;
use function RRZE\Newsletter\plugin;

class ICS
{
    /**
     * register
     * Registers the block on server.
     */
    public static function register()
    {
        register_block_type(
            plugin()->getPath('build/editor/blocks/ics') . 'block.json',
            [
                'render_callback' => [__CLASS__, 'renderHTML'],
            ]
        );
    }

    /**
     * parseAtts
     * Parse block attributes.
     *
     * @param array $atts
     * @return array
     */
    protected static function parseAtts(array $atts): array
    {
        $defaultAtts = [];
        $metaDataFile = plugin()->getPath('build/editor/blocks/ics') . 'block.json';
        if (
            file_exists($metaDataFile)
            && !is_null($metaData = wp_json_file_decode($metaDataFile, ['associative' => true]))
        ) {
            foreach ($metaData['attributes'] as $key => $value) {
                $defaultAtts[$key] = $value['default'];
            }
        }
        $atts = wp_parse_args($atts, $defaultAtts);
        return $atts;
    }

    /**
     * renderHTML
     * Render the block on the server in HTML format.
     * @param array $atts The block attributes.
     * 
     * @return string Returns the block content.
     */
    public static function renderHTML(array $atts): string
    {
        $atts = self::parseAtts($atts);
        $feedItems = self::getItems($atts['feedURL'], $atts);

        if (is_wp_error($feedItems)) {
            return '<div class="components-placeholder"><div class="notice notice-error"><strong>' . __('ICS Error:', 'rrze-newsletter') . '</strong> ' . $feedItems->get_error_message() . '</div></div>';
        }

        if (!$feedItems) {
            $feedItems = sprintf('<div class="rrze-newsletter-ics"><p>%s</p></div>', __('There are no events available.', 'rrze-newsletter'));
        } else {
            $feedItems = self::render($atts, $feedItems);
        }

        return $feedItems;
    }

    /**
     * renderMJML
     * Render the block on the server in MJML format.
     * 
     * @param array $atts The block attributes.
     * @return string Returns the block content.
     */
    public static function renderMJML(array $atts): string
    {
        $atts = self::parseAtts($atts);
        $feedItems = self::getItems($atts['feedURL'], $atts);

        if (!is_wp_error($feedItems) && $feedItems) {
            $feedItems = self::render($atts, $feedItems);
        } else {
            $feedItems = '';
        }

        if (!$feedItems) {
            $feedItems = sprintf('<div class="rrze-newsletter-ics"><p>%s</p></div>', __('There are no events available.', 'rrze-newsletter'));
        } else {
            wp_cache_set('rrze_newsletter_ics_block_not_empty', 1, $atts['postId']);
        }

        return $feedItems;
    }

    /**
     * render
     * Render the block on the server.
     *
     * @param array $atts
     * @param array $feedItems
     * @return string
     */
    protected static function render(array $atts, $feedItems)
    {
        $headingStyle = !empty($atts['headingFontSize']) ? 'font-size:' . $atts['headingFontSize'] . ';' : '';
        $headingStyle .= !empty($atts['headingColor']) ? 'color:' . $atts['headingColor'] . ';' : '';
        $headingStyle = $headingStyle ? ' style="' . $headingStyle . '"' : '';

        $textStyle = !empty($atts['textFontSize']) ? 'font-size:' . $atts['textFontSize'] . ';' : '';
        $textStyle .= !empty($atts['textColor']) ? 'color:' . $atts['textColor'] . ';' : '';
        $textStyle = $textStyle ? ' style="' . $textStyle . '"' : '';

        $listItems = '';
        $dateFormat = get_option('date_format');

        $i = 0;
        $multidayEventKeysUsed = [];

        foreach (array_keys((array)$feedItems['events']) as $year) {
            for ($m = 1; $m <= 12; $m++) {
                $month = $m < 10 ? '0' . $m : '' . $m;
                $ym = $year . $month;
                if ($ym < $feedItems['earliest']) {
                    continue;
                }
                if ($ym > $feedItems['latest']) {
                    break 2;
                }

                if (isset($feedItems['events'][$year][$month])) {
                    foreach ((array)$feedItems['events'][$year][$month] as $day => $dayEvents) {

                        // Pull out multi-day events and display them separately first
                        foreach ((array)$dayEvents as $time => $events) {

                            foreach ((array)$events as $eventKey => $event) {
                                if (empty($event['multiday'])) {
                                    continue;
                                }

                                if (in_array($event['multiday']['event_key'], $multidayEventKeysUsed)) {
                                    continue;
                                }

                                // Format date/time for header
                                $mdStart = self::dateFormat($dateFormat, strtotime($event['multiday']['start_date']));
                                $mdEnd = self::dateFormat($dateFormat, strtotime($event['multiday']['end_date']));
                                if ($time != 'all-day') {
                                    $mdStart .= ' ' . self::timeFormat($event['multiday']['start_time']);
                                    $mdEnd .= ' ' . self::timeFormat($event['multiday']['end_time']);
                                }

                                // Event label (title)
                                $title = self::eventLabelHtml($event);
                                if (!empty($event['url'])) {
                                    $title = '<a href="' . esc_url($event['url']) . '"' . (!self::domainMatch($event['url']) ? ' target="_blank" rel="noopener noreferrer nofollow"' : '') . '>' . $title . '</a>';
                                }
                                $listItems .= '<h3 ' . $headingStyle . '>' . $title . '</h3>';

                                $mdate = $mdStart . ' &#8211; ' . $mdEnd;
                                $listItems .= '<p ' . $textStyle . '>' . $mdate . '</p>';

                                // RRULE/FREQ
                                if (!empty($event['rrule'])) {
                                    // $listItems .= self::humanReadableRecurrence($event['rrule'], $textStyle);
                                }

                                // Location/Organizer/Description
                                $listItems .= self::eventDescriptionHtml($atts, $event, $textStyle);

                                // We've now used this event
                                $multidayEventKeysUsed[] = $event['multiday']['event_key'];
                                $i++;
                                if (!empty($atts['itemsToShow']) && $i >= intval($atts['itemsToShow'])) {
                                    break 5;
                                }

                                // Remove event from array (to skip day if it only has multi-day events)
                                unset($dayEvents[$time][$eventKey]);
                            }

                            // Remove time from array if all of its events have been removed
                            if (empty($dayEvents[$time])) {
                                unset($dayEvents[$time]);
                            }
                        }

                        // Skip day if all of its events were multi-day
                        if (empty($dayEvents)) {
                            continue;
                        }

                        // Loop through day events
                        foreach ((array)$dayEvents as $time => $events) {

                            foreach ((array)$events as $event) {
                                if (!empty($event['multiday'])) {
                                    continue;
                                }

                                // Event label (title)
                                $title = self::eventLabelHtml($event);
                                if (!empty($event['url'])) {
                                    $title = '<a href="' . esc_url($event['url']) . '"' . (!self::domainMatch($event['url']) ? ' target="_blank" rel="noopener noreferrer nofollow"' : '') . '>' . $title . '</a>';
                                }
                                $listItems .= '<h3 ' . $headingStyle . '>' . $title . '</h3>';

                                $mdate = self::dateFormat($dateFormat, $month . '/' . $day . '/' . $year);

                                $mtime = '';
                                if ($time !== 'all-day') {
                                    if (!empty($event['start'])) {
                                        $mtime = ' ' . $event['start'];
                                        if (!empty($event['end']) && $event['end'] != $event['start']) {
                                            $mtime .= ' &#8211; ' . $event['end'];
                                        }
                                    }
                                }

                                $listItems .= $mtime ? sprintf(
                                    '<p %1$s>%2$s%3$s</p>',
                                    $textStyle,
                                    $mdate,
                                    $mtime
                                ) : '';

                                // RRULE/FREQ
                                if (!empty($event['rrule'])) {
                                    // $listItems .= self::humanReadableRecurrence($event['rrule'], $textStyle);
                                }

                                // Location/Organizer/Description
                                $listItems .= self::eventDescriptionHtml($atts, $event, $textStyle);

                                $i++;
                                if (!empty($atts['itemsToShow']) && $i >= intval($atts['itemsToShow'])) {
                                    break 5;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $listItems ? '<div class="rrze-newsletter-ics" ' . $textStyle . '>' . $listItems . '</div>' : '';
    }

    /**
     * getItems
     *
     * @param string $url
     * @param array $atts
     * @return mixed
     */
    protected static function getItems(string $url, array $atts)
    {
        $feedItems = [];
        $hasEvents = false;

        // Convert URL into array and iterate.
        $feedItems['events'] = [];
        $feedItems['urls'] = self::spacePipeExplode($url);
        $feedItems['tz'] = !empty($tz) ? self::spacePipeExplode($tz) : get_option('timezone_string');

        // Add a month to $rangeStart to accommodate multi-day events that may begin out of range.
        $rangeStart = self::dateFormat('Y/m/d', null, null, '-' . intval(wp_date('j') + 30) . ' days');
        // Extend by one week past current date.
        $rangeEnd = self::dateFormat('Y/m/d', null, null, '+360 days');

        // Get day counts for ICS Parser's range filters
        $nowDtm = new \DateTime();
        $filterDaysAfter = $nowDtm->diff(new \DateTime($rangeEnd))->format('%a');
        $filterDaysBefore = $nowDtm->diff(new \DateTime($rangeStart))->format('%a');

        // Set display date range.
        $firstDate = self::dateFormat('Ymd');
        $limitDate = self::dateFormat('Ymd', $firstDate, null, '+360 days');

        // Set earliest and latest dates
        $feedItems['earliest'] = substr($firstDate, 0, 6);
        $feedItems['latest'] = substr($limitDate, 0, 6);

        // Process each individual feed URL
        foreach ((array)$feedItems['urls'] as $feedKey => $url) {

            // Get timezone for this feed
            $urlTz = self::getFeedTz($feedItems, $feedKey);

            // Fix URL protocol
            if (strpos($url, 'webcal://') === 0) {
                $url = str_replace('webcal://', 'https://', $url);
            }

            // Get ICS file contents
            $icsContent = self::urlGetContent($url, 'curl');

            // No ICS data present
            if (empty($icsContent)) {
                continue;
            }

            // Parse ICS contents
            $ICal = new ICal('ICal.ics', array(
                'defaultSpan'                 => 1,
                'defaultTimeZone'             => $urlTz->getName(),
                'disableCharacterReplacement' => true,
                'filterDaysAfter'             => $filterDaysAfter,
                'filterDaysBefore'            => $filterDaysBefore,
                'replaceWindowsTimeZoneIds'   => true,
                'skipRecurrence'              => false,
            ));
            $ICal->initString($icsContent);

            // Free up some memory
            unset($icsContent);

            // Update general calendar information
            $feedItems['title'] = $ICal->calendarName();
            $feedItems['description'] = $ICal->calendarDescription();
            $feedItems['timezone'][$url] = $ICal->calendarTimeZone();

            // Process events
            if ($ICal->hasEvents() && $ics_events = $ICal->eventsFromRange($rangeStart, $rangeEnd)) {

                // Assemble events
                foreach ((array)$ics_events as $eventKey => $event) {
                    // Set start and end dates for event
                    $dtstartDate = wp_date('Ymd', $event->dtstart_array[2], $urlTz);
                    // Conditional is for events that are missing DTEND altogether
                    $dtendDate = wp_date('Ymd', (!isset($event->dtend_array[2]) ? $event->dtstart_array[2] : $event->dtend_array[2]), $urlTz);

                    // All-day events
                    if (strlen($event->dtstart) == 8 || (strpos($event->dtstart, 'T000000') !== false && strpos($event->dtend, 'T000000') !== false)) {
                        $dtstartTime = null;
                        $dtendTime = null;
                        $allDay = true;
                    }
                    // Start/end times
                    else {
                        $dtstartTime = wp_date('His', $event->dtstart_array[2], $urlTz);
                        // Conditional is for events that are missing DTEND altogether
                        $dtendTime = wp_date('His', (!isset($event->dtend_array[2]) ? $event->dtstart_array[2] : $event->dtend_array[2]), $urlTz);
                        $allDay = false;
                    }

                    // Workaround for events in feeds that do not contain an end date/time
                    if (empty($dtendDate)) {
                        $dtendDate = isset($dtstartDate) ? $dtstartDate : null;
                    }
                    if (empty($dtendTime)) {
                        $dtendTime = isset($dtstartTime) ? $dtstartTime : null;
                    }

                    // General event item details (regardless of all-day/start/end times)
                    $eventItem = [
                        'label' => (!empty($maskinfo) ? $maskinfo : @$event->summary),
                        'dtstart_time' => @$dtstartTime,
                        'dtend_time' => @$dtendTime,
                        'feed_key' => $feedKey,
                        'eventdesc' => @$event->description,
                        'location' => @$event->location,
                        'organizer' => (!empty($event->organizer_array) ? $event->organizer_array : @$event->organizer),
                        'url' => (!empty($event->url) ? $event->url : null),
                        'rrule' => (!empty($event->rrule) ? $event->rrule : null),
                    ];

                    // Events with different start and end dates
                    if (
                        $dtendDate != $dtstartDate &&
                        // Events that are NOT multiday, but end at midnight of the start date!
                        !($dtendDate == self::dateFormat('Ymd', $dtstartDate, $urlTz, '+1 day') && $dtendTime == '000000')
                    ) {
                        $loopDate = $dtstartDate;
                        while ($loopDate <= $dtendDate) {
                            // Classified as an all-day event and we've hit the end date
                            if ($allDay && $loopDate == $dtendDate) {
                                break;
                            }
                            // Multi-day events may be given with end date/time as midnight of the NEXT day
                            $actualEndDate = (!empty($allDay) && empty($dtendTime))
                                ? self::dateFormat('Ymd', $dtendDate, $urlTz, '-1 day')
                                : $dtendDate;
                            if ($dtstartDate == $actualEndDate) {
                                $feedItems['events'][$dtstartDate]['all-day'][] = $eventItem;
                                break;
                            }
                            // Get full date/time range of multi-day event
                            $eventItem['multiday'] = array(
                                'event_key' => $eventKey,
                                'start_date' => $dtstartDate,
                                'start_time' => $dtstartTime,
                                'end_date' => $actualEndDate,
                                'end_time' => $dtendTime,
                                'all_day' => $allDay,
                            );
                            // Classified as an all-day event, or we're in the middle of the range -- treat as regular all-day event
                            // For all-day events, $dtendDate is midnight on the date after the event ends
                            if ($allDay || ($loopDate != $dtstartDate && $loopDate != $dtendDate)) {
                                $eventItem['multiday']['position'] = 'middle';
                                if ($loopDate == $dtstartDate) {
                                    $eventItem['multiday']['position'] = 'first';
                                } elseif ($loopDate == $actualEndDate) {
                                    $eventItem['multiday']['position'] = 'last';
                                }
                                $eventItem['start'] = $eventItem['end'] = null;
                                $feedItems['events'][$loopDate]['all-day'][] = $eventItem;
                            }
                            // First date in range: show start time
                            elseif ($loopDate == $dtstartDate) {
                                $eventItem['start'] = self::timeFormat($dtstartTime);
                                $eventItem['end'] = null;
                                $eventItem['multiday']['position'] = 'first';
                                $feedItems['events'][$loopDate]['t' . $dtstartTime][] = $eventItem;
                            }
                            // Last date in range: show end time
                            elseif ($loopDate == $actualEndDate) {
                                // If event ends at midnight, skip
                                if (!empty($dtendTime) && $dtendTime != '000000') {
                                    $eventItem['sublabel'] = __('Ends', 'rrze-newsletter') . ' ' . self::timeFormat($dtendTime);
                                    $eventItem['start'] = null;
                                    $eventItem['end'] = self::timeFormat($dtendTime);
                                    $eventItem['multiday']['position'] = 'last';
                                    $feedItems['events'][$loopDate]['t' . $dtendTime][] = $eventItem;
                                }
                            }
                            $loopDate = self::dateFormat('Ymd', $loopDate, $urlTz, '+1 day');
                        }
                    }
                    // All-day events
                    elseif ($allDay) {
                        $feedItems['events'][$dtstartDate]['all-day'][] = $eventItem;
                    }
                    // Events with start/end times
                    else {
                        $eventItem['start'] = self::timeFormat($dtstartTime);
                        $eventItem['end'] = self::timeFormat($dtendTime);
                        $feedItems['events'][$dtstartDate]['t' . $dtstartTime][] = $eventItem;
                    }
                }
            }

            // If no events, create empty array for today
            if (empty($feedItems['events'])) {
                $feedItems['events'] = [self::dateFormat('Ymd') => []];
            } else {
                $hasEvents = true;
            }
        }

        // Remove out-of-range dates and sort the rest
        if (!empty($feedItems['events'])) {
            foreach (array_keys((array)$feedItems['events']) as $date) {
                if ($date < $firstDate || $date > $limitDate) {
                    unset($feedItems['events'][$date]);
                } else {
                    ksort($feedItems['events'][$date]);
                }
            }
            ksort($feedItems['events']);
        }

        // Split events into year/month/day groupings
        foreach ((array)$feedItems['events'] as $date => $events) {
            $year = substr($date, 0, 4);
            $month = substr($date, 4, 2);
            $day = substr($date, 6, 2);
            $ym = substr($date, 0, 6);
            $feedItems['events'][$year][$month][$day] = $events;
            unset($feedItems['events'][$date]);
        }

        // Add empty event arrays
        for ($i = substr($feedItems['earliest'], 0, 6); $i <= substr($feedItems['latest'], 0, 6); $i++) {
            $Y = substr($i, 0, 4);
            $m = substr($i, 4, 2);
            if (intval($m) < 1 || intval($m) > 12) {
                continue;
            }
            if (!isset($feedItems['events'][$Y][$m])) {
                $feedItems['events'][$Y][$m] = null;
            }
        }

        // Sort events
        foreach (array_keys((array)$feedItems['events']) as $keyYear) {
            ksort($feedItems['events'][$keyYear]);
        }
        ksort($feedItems['events']);

        if ($hasEvents) {
            return $feedItems;
        }

        return null;
    }

    /**
     * spacePipeExplode
     * Break a string into an array using any combination of 
     * spaces and/or pipes as the delimiter.
     *
     * @param string $str
     * @return array
     */
    protected static function spacePipeExplode($str)
    {
        $exploded = preg_split('/[\s\|]+/', $str);
        if (count($exploded) == 1) {
            return $str;
        }
        return $exploded;
    }

    /**
     * getFeedTz
     * Get timezone for feed.
     *
     * @param array $feedItems
     * @param string $feedKey
     * @return object
     */
    protected static function getFeedTz($feedItems, $feedKey)
    {
        $tzid = null;
        if (is_array($feedItems['tz'])) {
            $tzid = isset($feedItems['tz'][$feedKey]) ? trim($feedItems['tz'][$feedKey]) : trim($feedItems['tz'][0]);
        } elseif (!empty($feedItems['tz'])) {
            $tzid = $feedItems['tz'];
        }
        return self::isValidTz($tzid) ? timezone_open($tzid) : wp_timezone();
    }

    /**
     * isValidTz
     * Check if it is a valid timezone.
     *
     * @param string $tzid
     * @return boolean
     */
    protected static function isValidTz($tzid)
    {
        if (empty($tzid)) {
            return false;
        }
        foreach (timezone_abbreviations_list() as $zone) {
            foreach ($zone as $item) {
                if ($item['timezone_id'] == $tzid) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * dateFormat
     * Formatted date strings.
     *
     * @param string $format
     * @param mixed $dtStr
     * @param mixed $tz
     * @param mixed $offset
     * @return string
     */
    protected static function dateFormat($format, $dtStr = null, $tz = null, $offset = null)
    {
        global $wp_locale;
        $date = null;

        $dtStr = !empty($dtStr) ? $dtStr : '';
        $tz = !empty($tz) ? $tz : '';
        $offset = !empty($offset) ? $offset : '';

        // Safely catch Unix timestamps
        if (strlen($dtStr) >= 10 && is_numeric($dtStr)) {
            $dtStr = '@' . $dtStr;
        }

        // Convert $tz to DateTimeZone object if applicable
        if (!empty($tz) && is_string($tz)) {
            $tz = new \DateTimeZone($tz);
        }

        // Set default timezone if null
        if (empty($tz)) {
            $tz = new \DateTimeZone(get_option('timezone_string') ? get_option('timezone_string') : 'UTC');
        }

        // Fix signs in offset
        $offset = str_replace('--', '+', str_replace('+-', '-', $offset));

        // Create new datetime from date string
        $dt = new \DateTime(trim($dtStr . ' ' . $offset), $tz);

        // Localize
        if (empty($wp_locale->month) || empty($wp_locale->weekday)) {
            $date = $dt->format($format);
        } else {
            $format = preg_replace('/(?<!\\\\)r/', DATE_RFC2822, $format);
            $newFormat    = '';
            $format_length = strlen($format);
            $month = $wp_locale->get_month($dt->format('m'));
            $weekday = $wp_locale->get_weekday($dt->format('w'));
            for ($i = 0; $i < $format_length; $i++) {
                switch ($format[$i]) {
                    case 'D':
                        $newFormat .= addcslashes($wp_locale->get_weekday_abbrev($weekday), '\\A..Za..z');
                        break;
                    case 'F':
                        $newFormat .= addcslashes($month, '\\A..Za..z');
                        break;
                    case 'l':
                        $newFormat .= addcslashes($weekday, '\\A..Za..z');
                        break;
                    case 'M':
                        $newFormat .= addcslashes($wp_locale->get_month_abbrev($month), '\\A..Za..z');
                        break;
                    case 'a':
                        $newFormat .= addcslashes($wp_locale->get_meridiem($dt->format('a')), '\\A..Za..z');
                        break;
                    case 'A':
                        $newFormat .= addcslashes($wp_locale->get_meridiem($dt->format('A')), '\\A..Za..z');
                        break;
                    case '\\':
                        $newFormat .= $format[$i];
                        if ($i < $format_length) {
                            $newFormat .= $format[++$i];
                        }
                        break;
                    default:
                        $newFormat .= $format[$i];
                        break;
                }
            }
            $date = $dt->format($newFormat);
            $date = wp_maybe_decline_date($date, $format);
        }

        return $date;
    }

    /**
     * timeFormat
     * Format time string data.
     *
     * @param string $timeString
     * @param mixed $format
     * @return string
     */
    protected static function timeFormat($timeString, $format = null)
    {
        $output = null;
        if (empty($format)) {
            $format = get_option('time_format');
        }

        // Strip unsupported format elements from string
        $format = trim(preg_replace('/[BsueOPTZ]/', '', $format));

        // Get digits from time string
        $timeDigits = preg_replace('/[^0-9]+/', '', $timeString);

        // Get am/pm from time string
        $timeAmPm = preg_replace('/[^amp]+/', '', strtolower($timeString));
        if ($timeAmPm != 'am' && $timeAmPm != 'pm') {
            $timeAmPm = null;
        }

        // Prepend zero to digits if length is odd
        if (strlen($timeDigits) % 2 == 1) {
            $timeDigits = '0' . $timeDigits;
        }

        // Get hour, minutes and seconds from time digits
        $timeH = substr($timeDigits, 0, 2);
        $timeM = substr($timeDigits, 2, 2);
        $timeS = strlen($timeDigits) == 6 ? substr($timeDigits, 4, 2) : null;

        // Convert hour to correct 24-hour value if needed
        if ($timeAmPm == 'pm') {
            $timeH = (int)$timeH + 12;
        }

        if ($timeAmPm == 'am' && $timeH == '12') {
            $timeH = '00';
        }

        // Determine am/pm if not passed in
        if (empty($timeAmPm)) {
            $timeAmPm = (int)$timeH >= 12 ? 'pm' : 'am';
        }

        // Get 12-hour version of hour
        $timeH12 = (int)$timeH % 12;
        if ($timeH12 == 0) {
            $timeH12 = 12;
        }
        if ($timeH12 < 10) {
            $timeH12 = '0' . (string)$timeH12;
        }

        // Convert am/pm abbreviations for Greek (this is simpler than putting it in the i18n files)
        if (get_locale() == 'el') {
            $timeAmPm = ($timeAmPm == 'am') ? 'πμ' : 'μμ';
        }

        // Format output
        switch ($format) {
                // 12-hour formats without seconds
            case 'g:i a':
                $output = intval($timeH12) . ':' . $timeM . '&nbsp;' . $timeAmPm;
                break;
            case 'g:ia':
                $output = intval($timeH12) . ':' . $timeM . $timeAmPm;
                break;
            case 'g:i A':
                $output = intval($timeH12) . ':' . $timeM . '&nbsp;' . strtoupper($timeAmPm);
                break;
            case 'g:iA':
                $output = intval($timeH12) . ':' . $timeM . strtoupper($timeAmPm);
                break;
            case 'h:i a':
                $output = $timeH12 . ':' . $timeM . '&nbsp;' . $timeAmPm;
                break;
            case 'h:ia':
                $output = $timeH12 . ':' . $timeM . $timeAmPm;
                break;
            case 'h:i A':
                $output = $timeH12 . ':' . $timeM . '&nbsp;' . strtoupper($timeAmPm);
                break;
            case 'h:iA':
                $output = $timeH12 . ':' . $timeM . strtoupper($timeAmPm);
                break;
                // 24-hour formats without seconds
            case 'G:i':
                $output = intval($timeH) . ':' . $timeM;
                break;
            case 'Gi':
                $output = intval($timeH) . $timeM;
                break;
                // case 'H:i': is the default, below
            case 'Hi':
                $output = $timeH . $timeM;
                break;
                // 24-hour formats without seconds, using h and m or min
            case 'G \h i \m\i\n':
                $output = intval($timeH) . '&nbsp;h&nbsp;' . $timeM . '&nbsp;min';
                break;
            case 'G\h i\m\i\n':
                $output = intval($timeH) . 'h&nbsp;' . $timeM . 'min';
                break;
            case 'G\hi\m\i\n':
                $output = intval($timeH) . 'h' . $timeM . 'min';
                break;
            case 'G \h i \m':
                $output = intval($timeH) . '&nbsp;h&nbsp;' . $timeM . '&nbsp;m';
                break;
            case 'G\h i\m':
                $output = intval($timeH) . 'h&nbsp;' . $timeM . 'm';
                break;
            case 'G\hi\m':
                $output = intval($timeH) . 'h' . $timeM . 'm';
                break;
            case 'H \h i \m\i\n':
                $output = $timeH . '&nbsp;h&nbsp;' . $timeM . '&nbsp;min';
                break;
            case 'H\h i\m\i\n':
                $output = $timeH . 'h&nbsp;' . $timeM . 'min';
                break;
            case 'H\hi\m\i\n':
                $output = $timeH . 'h' . $timeM . 'min';
                break;
            case 'H \h i \m':
                $output = $timeH . '&nbsp;h&nbsp;' . $timeM . '&nbsp;m';
                break;
            case 'H\h i\m':
                $output = $timeH . 'h&nbsp;' . $timeM . 'm';
                break;
            case 'H\hi\m':
                $output = $timeH . 'h' . $timeM . 'm';
                break;
                // 12-hour formats with seconds
            case 'g:i:s a':
                $output = intval($timeH12) . ':' . $timeM . ':' . $timeS . '&nbsp;' . $timeAmPm;
                break;
            case 'g:i:sa':
                $output = intval($timeH12) . ':' . $timeM . ':' . $timeS . $timeAmPm;
                break;
            case 'g:i:s A':
                $output = intval($timeH12) . ':' . $timeM . ':' . $timeS . '&nbsp;' . strtoupper($timeAmPm);
                break;
            case 'g:i:sA':
                $output = intval($timeH12) . ':' . $timeM . ':' . $timeS . strtoupper($timeAmPm);
                break;
            case 'h:i:s a':
                $output = $timeH12 . ':' . $timeM . ':' . $timeS . '&nbsp;' . $timeAmPm;
                break;
            case 'h:i:sa':
                $output = $timeH12 . ':' . $timeM . ':' . $timeS . $timeAmPm;
                break;
            case 'h:i:s A':
                $output = $timeH12 . ':' . $timeM . ':' . $timeS . '&nbsp;' . strtoupper($timeAmPm);
                break;
            case 'h:i:sA':
                $output = $timeH12 . ':' . $timeM . ':' . $timeS . strtoupper($timeAmPm);
                break;
                // 24-hour formats with seconds
            case 'G:i:s':
                $output = intval($timeH) . ':' . $timeM . ':' . $timeS;
                break;
            case 'H:i:s':
                $output = $timeH . ':' . $timeM . ':' . $timeS;
                break;
            case 'His':
                $output = $timeH . $timeM . $timeS;
                break;
                // Hour-only formats used for grid labels
            case 'H:00':
                $output = $timeH . ':00';
                break;
            case 'h:00':
                $output = $timeH12 . ':00';
                break;
            case 'H00':
                $output = $timeH . '00';
                break;
            case 'g a':
                $output = intval($timeH12) . ' ' . $timeAmPm;
                break;
            case 'g A':
                $output = intval($timeH12) . ' ' . strtoupper($timeAmPm);
                break;
                // Default
            case 'H:i':
            default:
                $output = $timeH . ':' . $timeM;
                break;
        }

        return $output;
    }

    /**
     * urlGetContent
     * Retrieve file from remote server with fallback methods.
     *
     * @param string $url
     * @param mixed $method
     * @param boolean $recursion
     * @return mixed
     */
    protected static function urlGetContent($url, $method = null, $recursion = false)
    {
        // Must have a URL
        if (empty($url)) {
            // Error: No ICS URL provided.
            return false;
        }

        // Valid method values
        $validMethods = array('curl', 'fopen');
        $method = in_array(strtolower($method), $validMethods) ? strtolower($method) : null;

        // Replace ampersand entities in URL to plain ampersands
        $url = str_replace('&amp;', '&', $url);

        $urlContent = null;
        $curlResponseCode = null;
        $curlRedirectUrl = null;

        // Set a user_agent string
        ini_set('user_agent', 'RRZE Newsletter');

        // Attempt to use cURL functions
        if (defined('CURLVERSION_NOW') && function_exists('curl_exec') && (empty($method) || $method == 'curl')) {
            $conn = curl_init($url);
            if (file_exists(ABSPATH . 'wp-includes/certificates/ca-bundle.crt')) {
                curl_setopt($conn, CURLOPT_CAINFO, ABSPATH . 'wp-includes/certificates/ca-bundle.crt');
            }
            curl_setopt($conn, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($conn, CURLOPT_FRESH_CONNECT,  true);
            curl_setopt($conn, CURLOPT_MAXREDIRS, 5);
            curl_setopt($conn, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, true);
            // Allow external customization of cURL options
            $urlContent = (curl_exec($conn));
            $curlResponseCode = curl_getinfo($conn, CURLINFO_RESPONSE_CODE);
            $curlRedirectUrl = curl_getinfo($conn, CURLINFO_REDIRECT_URL);
            curl_close($conn);
        }

        // Attempt to use fopen functions
        if (ini_get('allow_url_fopen') && (empty($urlContent) || $method == 'fopen' || intval($curlResponseCode) >= 400)) {
            $urlContent = file_get_contents($url);
        }

        // Follow rewrites (if CURLOPT_FOLLOWLOCATION failed or using fopen)
        // If possible, we check for a 301 or 302 response code, falling back on certain text strings contained in the response
        // Outlook rewrites may include the string '">Found</a>' in the output
        // Most other feeds (e.g. Google Calendar) will include 'Moved Permanently' in the output
        if (!$recursion && ($curlResponseCode == '301' ||
            $curlResponseCode == '302' ||
            stripos($urlContent, '">Found</a>') !== false ||
            stripos($urlContent, 'Moved Permanently') !== false ||
            strpos($urlContent, 'Object moved') !== false)) {

            // Use cURL redirect URL if provided
            if (!empty($curlRedirectUrl)) {
                $urlContent = self::urlGetContent($curlRedirectUrl, $method, true);
            }

            // Scrape URL from returned HTML if necessary
            else {
                preg_match('/<(a href|A HREF)="([^"]+)"/', $urlContent, $urlMatch);
                if (isset($urlMatch[2])) {
                    $urlContent = self::urlGetContent($urlMatch[2], $method, true);
                }
            }
        }

        // Cannot retrieve file
        if (empty($urlContent)) {
            $urlContent = false;
        }

        return $urlContent;
    }

    /**
     * domainMatch
     * Check if a URL's domain is the same as the current site.
     *
     * @param string $url
     * @return boolean
     */
    protected static function domainMatch($url)
    {
        return (parse_url($url, PHP_URL_HOST) == $_SERVER['SERVER_NAME']);
    }

    /**
     * filterTheContent
     * Skips some of the functions WP normally runs on 'the_content'.
     *
     * @param string $content
     * @return void
     */
    protected static function filterTheContent($content)
    {
        return wpautop(convert_chars(wptexturize($content)));
    }

    /**
     * emptyContent
     * Check if the content is empty.
     *
     * @param string $content
     * @return boolean
     */
    protected static function emptyContent($content)
    {
        return empty(trim(str_replace('&nbsp;', '', strip_tags($content, '<img><iframe><audio><video>'))));
    }

    /**
     * recurrenceExplode
     * Explode a recurrence rule into an array.
     *
     * @param string $rrule
     * @return array
     */
    protected static function recurrenceExplode($rrule)
    {
        $output = null;
        if ($parts = explode(';', $rrule)) {
            $output = [];
            foreach ((array)$parts as $part) {
                $arr = explode('=', $part);
                $output[$arr[0]] = $arr[1];
            }
        }
        return $output;
    }

    /**
     * humanReadableRecurrence
     * Convert a recurrence rule into a human-readable expression.
     *
     * @param string $rrule
     * @param string $textStyle
     * @return string
     */
    public static function humanReadableRecurrence($rrule, $textStyle)
    {
        $opt = [
            'use_intl' => true,
            'locale' => substr(get_locale(), 0, 2),
            'date_formatter' => function ($date) {
                return $date->format(__('m-d-Y', 'rrze-calendar'));
            },
            'fallback' => 'en',
            'explicit_infinite' => true,
            'include_start' => false,
            'include_until' => true,
            'custom_path' => plugin()->getPath('config/blocks/ics/rrule'),
        ];

        $rrule = new RRule($rrule);
        $output = $rrule->humanReadable($opt);
        return '<p ' . $textStyle . '>' . $output . '</p>';
    }

    /**
     * eventLabelHtml
     *
     * @param array $event
     * @return string
     */
    protected static function eventLabelHtml($event)
    {
        $output = '';
        if (!empty($event['url'])) {
            $output .= '<a href="' . esc_url($event['url']) . '" ' . (!self::domainMatch($event['url']) ? ' target="_blank" rel="noopener noreferrer nofollow"' : '') . '>';
        }
        $output .= html_entity_decode(str_replace('/', '/<wbr />', $event['label']));
        if (!empty($event['url'])) {
            $output .= '</a>';
        }
        return $output;
    }

    /**
     * eventLocationHtml
     *
     * @param mixed $location
     * @param string $textStyle
     * @return string
     */
    protected static function eventLocationHtml($location, $textStyle)
    {
        return '<p ' . $textStyle . '>' . make_clickable($location) . '</p>';
    }

    /**
     * eventOrganizerHtml
     *
     * @param mixed $organizer
     * @param string $textStyle
     * @return string
     */
    protected static function eventOrganizerHtml($organizer, $textStyle)
    {
        $content = '';
        if (is_array($organizer)) {
            if (count((array)$organizer) == 2 && isset($organizer[0]['CN'])) {
                $content = '<a href="' . esc_url($organizer[1]) . '" rel="noopener noreferrer nofollow">' . rawurldecode($organizer[0]['CN']) . '</a>';
            } elseif (!empty($organizer[1]) && is_scalar($organizer[1])) {
                $content = $organizer[1];
            }
        } else {
            $content = $organizer;
        }
        return '<p ' . $textStyle . '>' . $content . '</p>';
    }

    /**
     * eventDescriptionHtml
     *
     * @param array $atts
     * @param array $event
     * @param string $textStyle
     * @return string
     */
    protected static function eventDescriptionHtml($atts, $event, $textStyle)
    {
        $content = '';
        if ($atts['displayLocation'] && !empty($event['location'])) {
            $content .= self::eventLocationHtml($event['location'], $textStyle);
        }
        if ($atts['displayOrganizer'] && !empty($event['organizer'])) {
            $content .= self::eventOrganizerHtml($event['organizer'], $textStyle);
        }

        $description = '';
        if ($atts['displayDescription'] && !empty($event['eventdesc'])) {
            $description = self::filterTheContent($event['eventdesc']);
            if ($atts['descriptionLimit']) {
                $description = make_clickable(wp_trim_words($description, absint($atts['descriptionLength']), ' [&hellip;]'));
            } else {
                $description = make_clickable($description);
            }
        }

        if (!self::emptyContent($description)) {
            $content .= $description;
        }

        return $content;
    }
}
