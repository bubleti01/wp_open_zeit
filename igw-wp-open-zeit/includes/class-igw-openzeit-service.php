<?php

if (! defined('ABSPATH')) {
    exit;
}

class IGW_Openzeit_Service
{
    private $repository;

    public function __construct(IGW_Openzeit_Repository $repository)
    {
        $this->repository = $repository;
    }

    public function get_day_display(DateTimeImmutable $date)
    {
        $data      = $this->repository->get_data();
        $date_str  = $date->format('Y-m-d');
        $weekday   = (int) $date->format('w');

        foreach ($data['holidays'] as $holiday) {
            if ($date_str >= $holiday['start_date'] && $date_str <= $holiday['end_date']) {
                return ['type' => 'holiday', 'label' => $holiday['name']];
            }
        }

        foreach ($data['exceptions'] as $exception) {
            if ($date_str === $exception['date']) {
                if (! empty($exception['closed'])) {
                    return ['type' => 'closed', 'label' => __('Geschlossen', 'igw_wp_open_zeit')];
                }

                return ['type' => 'hours', 'label' => $this->format_intervals($exception['intervals'])];
            }
        }

        $day = isset($data['weekly'][$weekday]) ? $data['weekly'][$weekday] : ['closed' => true, 'intervals' => []];

        if (! empty($day['closed'])) {
            return ['type' => 'closed', 'label' => __('Geschlossen', 'igw_wp_open_zeit')];
        }

        return ['type' => 'hours', 'label' => $this->format_intervals($day['intervals'])];
    }

    public function is_open_now(DateTimeImmutable $now = null)
    {
        $dt      = $now ?: DateTimeImmutable::createFromInterface(current_datetime());
        $data    = $this->repository->get_data();
        $today   = $dt->format('Y-m-d');
        $weekday = (int) $dt->format('w');

        foreach ($data['holidays'] as $holiday) {
            if ($today >= $holiday['start_date'] && $today <= $holiday['end_date']) {
                return false;
            }
        }

        $exception_today = null;
        foreach ($data['exceptions'] as $exception) {
            if ($exception['date'] === $today) {
                $exception_today = $exception;
                break;
            }
        }

        if ($exception_today !== null) {
            $intervals = ! empty($exception_today['closed']) ? [] : $exception_today['intervals'];

            return $this->time_in_intervals($dt, $intervals);
        }

        $day = isset($data['weekly'][$weekday]) ? $data['weekly'][$weekday] : ['closed' => true, 'intervals' => []];
        $intervals = ! empty($day['closed']) ? [] : $day['intervals'];

        return $this->time_in_intervals($dt, $intervals);
    }

    public function get_current_week_days()
    {
        $now          = DateTimeImmutable::createFromInterface(current_datetime());
        $start_of_week = (int) get_option('start_of_week', 1);
        $weekday      = (int) $now->format('w');
        $diff         = ($weekday - $start_of_week + 7) % 7;
        $week_start   = $now->modify(sprintf('-%d day', $diff));

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $week_start->modify(sprintf('+%d day', $i));
        }

        return $days;
    }

    public function get_grouped_weekly_lines()
    {
        $data          = $this->repository->get_data();
        $start_of_week = (int) get_option('start_of_week', 1);
        $order         = [];

        for ($i = 0; $i < 7; $i++) {
            $order[] = ($start_of_week + $i) % 7;
        }

        $groups = [];
        $last_pos = null;
        foreach ($order as $pos => $day) {
            $day_data = isset($data['weekly'][$day]) ? $data['weekly'][$day] : ['closed' => true, 'intervals' => []];
            if (! empty($day_data['closed']) || empty($day_data['intervals'])) {
                $last_pos = null;
                continue;
            }

            $repr = $this->format_intervals($day_data['intervals']);
            if (empty($groups)) {
                $groups[] = ['start' => $day, 'end' => $day, 'repr' => $repr];
                $last_pos = $pos;
                continue;
            }

            $last_idx = count($groups) - 1;
            if ($groups[$last_idx]['repr'] === $repr && $last_pos !== null && $pos === ($last_pos + 1)) {
                $groups[$last_idx]['end'] = $day;
            } else {
                $groups[] = ['start' => $day, 'end' => $day, 'repr' => $repr];
            }
            $last_pos = $pos;
        }

        return $groups;
    }

    public function format_intervals(array $intervals)
    {
        $parts = [];
        foreach ($intervals as $interval) {
            $parts[] = sprintf('%s - %s', $interval['start'], $interval['end']);
        }

        return implode(', ', $parts);
    }

    private function time_in_intervals(DateTimeImmutable $now, array $intervals)
    {
        $minutes_now = ((int) $now->format('H')) * 60 + (int) $now->format('i');
        foreach ($intervals as $interval) {
            $start = $this->minutes($interval['start']);
            $end   = $this->minutes($interval['end']);
            if ($minutes_now >= $start && $minutes_now < $end) {
                return true;
            }
        }

        return false;
    }

    private function minutes($time)
    {
        [$h, $m] = array_map('intval', explode(':', $time));
        return ($h * 60) + $m;
    }
}
