<?php

if (! defined('ABSPATH')) {
    exit;
}

class IGW_Openzeit_Validator
{
    const NAME_MAX_LENGTH = 80;

    public function validate_weekly(array $weekly)
    {
        $errors   = [];
        $cleaned  = [];

        for ($day = 0; $day <= 6; $day++) {
            $raw_day = isset($weekly[$day]) && is_array($weekly[$day]) ? $weekly[$day] : [];
            $closed  = ! empty($raw_day['closed']);
            $cleaned_intervals = [];

            if (! $closed) {
                $intervals_result = $this->validate_intervals(isset($raw_day['intervals']) && is_array($raw_day['intervals']) ? $raw_day['intervals'] : []);
                $errors = array_merge($errors, array_map(static function ($error) use ($day) {
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

    public function validate_holiday(array $record)
    {
        $errors = [];
        $id     = isset($record['id']) ? sanitize_text_field((string) $record['id']) : '';
        if ($id === '') {
            $id = wp_generate_uuid4();
        }
        $name   = $this->sanitize_name(isset($record['name']) ? $record['name'] : '');
        $start  = isset($record['start_date']) ? sanitize_text_field((string) $record['start_date']) : '';
        $end    = isset($record['end_date']) ? sanitize_text_field((string) $record['end_date']) : '';

        if ($name === '') {
            $errors[] = __('Ferienname ist erforderlich.', 'igw_wp_open_zeit');
        }
        if (! $this->is_valid_date($start)) {
            $errors[] = __('Ungültiges Ferien-Startdatum.', 'igw_wp_open_zeit');
        }
        if (! $this->is_valid_date($end)) {
            $errors[] = __('Ungültiges Ferien-Enddatum.', 'igw_wp_open_zeit');
        }
        if ($this->is_valid_date($start) && $this->is_valid_date($end) && $start > $end) {
            $errors[] = __('Ferien: Startdatum muss vor oder gleich Enddatum liegen.', 'igw_wp_open_zeit');
        }

        return [
            'errors' => $errors,
            'record' => [
                'id'         => $id,
                'name'       => $name,
                'start_date' => $start,
                'end_date'   => $end,
            ],
        ];
    }

    public function validate_exception(array $record)
    {
        $errors = [];
        $id     = isset($record['id']) ? sanitize_text_field((string) $record['id']) : '';
        if ($id === '') {
            $id = wp_generate_uuid4();
        }
        $name   = $this->sanitize_name(isset($record['name']) ? $record['name'] : '');
        $date   = isset($record['date']) ? sanitize_text_field((string) $record['date']) : '';
        $closed = ! empty($record['closed']);

        if ($name === '') {
            $errors[] = __('Ausnahmename ist erforderlich.', 'igw_wp_open_zeit');
        }
        if (! $this->is_valid_date($date)) {
            $errors[] = __('Ungültiges Ausnahmedatum.', 'igw_wp_open_zeit');
        }

        $intervals = [];
        if (! $closed) {
            $intervals_result = $this->validate_intervals(isset($record['intervals']) && is_array($record['intervals']) ? $record['intervals'] : []);
            $errors    = array_merge($errors, $intervals_result['errors']);
            $intervals = $intervals_result['intervals'];
        }

        return [
            'errors' => $errors,
            'record' => [
                'id'        => $id,
                'name'      => $name,
                'date'      => $date,
                'closed'    => $closed,
                'intervals' => $closed ? [] : $intervals,
            ],
        ];
    }

    public function validate_intervals(array $intervals)
    {
        $errors = [];
        $clean  = [];
        $segments = [];

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

            $segments[] = [$this->to_minutes($start), $this->to_minutes($end)];
        }

        usort($segments, static function ($a, $b) {
            return $a[0] <=> $b[0];
        });

        $last_end = -1;
        foreach ($segments as $segment) {
            if ($segment[0] < $last_end) {
                $errors[] = __('Zeitintervalle überlappen sich.', 'igw_wp_open_zeit');
                break;
            }
            $last_end = max($last_end, $segment[1]);
        }

        return [
            'errors'    => array_values(array_unique($errors)),
            'intervals' => $clean,
        ];
    }

    public function validate_conflicts(array $holidays, array $exceptions, $ignore_holiday_id = '', $ignore_exception_id = '')
    {
        $errors = [];

        for ($i = 0; $i < count($holidays); $i++) {
            for ($j = $i + 1; $j < count($holidays); $j++) {
                if ($holidays[$i]['id'] === $ignore_holiday_id || $holidays[$j]['id'] === $ignore_holiday_id) {
                    continue;
                }
                if ($this->ranges_overlap($holidays[$i]['start_date'], $holidays[$i]['end_date'], $holidays[$j]['start_date'], $holidays[$j]['end_date'])) {
                    $errors[] = __('Ferienbereiche dürfen sich nicht überschneiden.', 'igw_wp_open_zeit');
                }
            }
        }

        $dates = [];
        foreach ($exceptions as $exception) {
            if ($exception['id'] === $ignore_exception_id) {
                continue;
            }
            if (isset($dates[$exception['date']])) {
                $errors[] = __('Pro Datum ist nur eine Ausnahme erlaubt.', 'igw_wp_open_zeit');
                break;
            }
            $dates[$exception['date']] = true;
        }

        foreach ($exceptions as $exception) {
            if ($exception['id'] === $ignore_exception_id) {
                continue;
            }
            foreach ($holidays as $holiday) {
                if ($holiday['id'] === $ignore_holiday_id) {
                    continue;
                }
                if ($exception['date'] >= $holiday['start_date'] && $exception['date'] <= $holiday['end_date']) {
                    $errors[] = __('Ausnahme-Datum darf nicht innerhalb eines Ferienbereichs liegen.', 'igw_wp_open_zeit');
                    break 2;
                }
            }
        }

        return array_values(array_unique($errors));
    }

    private function sanitize_name($name)
    {
        $name = sanitize_text_field((string) $name);
        $name = trim($name);

        if (mb_strlen($name) > self::NAME_MAX_LENGTH) {
            $name = mb_substr($name, 0, self::NAME_MAX_LENGTH);
        }

        return $name;
    }

    private function is_valid_time($time)
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) === 1;
    }

    private function is_valid_date($date)
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        [$y, $m, $d] = array_map('intval', explode('-', $date));

        return checkdate($m, $d, $y);
    }

    private function to_minutes($time)
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $h * 60 + $m;
    }


    private function ranges_overlap($a_start, $a_end, $b_start, $b_end)
    {
        return ! ($a_end < $b_start || $b_end < $a_start);
    }
}
