<?php

if (! defined('ABSPATH')) {
    exit;
}

class IGW_Openzeit_Validator
{
    public function validate_weekly(array $weekly)
    {
        $errors  = [];
        $cleaned = [];

        for ($day = 0; $day <= 6; $day++) {
            $raw_day           = isset($weekly[$day]) && is_array($weekly[$day]) ? $weekly[$day] : [];
            $closed            = ! empty($raw_day['closed']);
            $cleaned_intervals = [];

            if (! $closed) {
                $intervals_result = $this->validate_intervals(isset($raw_day['intervals']) && is_array($raw_day['intervals']) ? $raw_day['intervals'] : []);
                $errors           = array_merge($errors, array_map(static function ($error) use ($day) {
                    return sprintf(__('Wochentag %d: %s', 'igw_wp_open_zeit'), $day, $error);
                }, $intervals_result['errors']));
                $cleaned_intervals = $intervals_result['intervals'];
            }

            $cleaned[$day] = [
                'closed'    => $closed,
                'intervals' => $closed ? [] : $cleaned_intervals,
            ];
        }

        return [
            'errors' => $errors,
            'weekly' => $cleaned,
        ];
    }

    public function validate_intervals(array $intervals)
    {
        $errors = [];
        $clean  = [];
        $ranges = [];

        foreach ($intervals as $index => $interval) {
            if (! is_array($interval)) {
                continue;
            }

            $start = isset($interval['start']) ? sanitize_text_field((string) $interval['start']) : '';
            $end   = isset($interval['end']) ? sanitize_text_field((string) $interval['end']) : '';

            if (! $this->is_valid_time($start) || ! $this->is_valid_time($end)) {
                $errors[] = sprintf(__('Ungültige Uhrzeit in Intervall %d.', 'igw_wp_open_zeit'), $index + 1);
                continue;
            }

            if ($this->to_minutes($end) <= $this->to_minutes($start)) {
                $errors[] = sprintf(__('Intervall %d: Endzeit muss größer als Startzeit sein.', 'igw_wp_open_zeit'), $index + 1);
                continue;
            }

            $clean[] = [
                'start' => $start,
                'end'   => $end,
            ];

            $ranges[] = [
                'start' => $this->to_minutes($start),
                'end'   => $this->to_minutes($end),
            ];
        }

        $range_count = count($ranges);
        for ($i = 0; $i < $range_count; $i++) {
            for ($j = $i + 1; $j < $range_count; $j++) {
                $a_start = $ranges[$i]['start'];
                $a_end   = $ranges[$i]['end'];
                $b_start = $ranges[$j]['start'];
                $b_end   = $ranges[$j]['end'];

                if ($a_start < $b_end && $b_start < $a_end) {
                    $errors[] = __('Zeitintervalle überlappen sich.', 'igw_wp_open_zeit');
                    break 2;
                }
            }
        }

        return [
            'errors'    => array_values(array_unique($errors)),
            'intervals' => $clean,
        ];
    }

    private function is_valid_time($time)
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) === 1;
    }

    private function to_minutes($time)
    {
        [$h, $m] = array_map('intval', explode(':', $time));
        return $h * 60 + $m;
    }
}
