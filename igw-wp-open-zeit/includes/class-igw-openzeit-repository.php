<?php

if (! defined('ABSPATH')) {
    exit;
}

class IGW_Openzeit_Repository
{
    public function get_data()
    {
        $data = get_option(IGW_WP_OPEN_ZEIT_OPTION_KEY, []);
        if (! is_array($data)) {
            $data = [];
        }

        return wp_parse_args($data, $this->get_default_data());
    }

    public function save_data(array $data)
    {
        return update_option(IGW_WP_OPEN_ZEIT_OPTION_KEY, $data, false);
    }

    public function delete_data()
    {
        return delete_option(IGW_WP_OPEN_ZEIT_OPTION_KEY);
    }

    public function get_default_data()
    {
        $weekly = [];
        for ($i = 0; $i <= 6; $i++) {
            $weekly[$i] = [
                'closed'    => false,
                'intervals' => [],
            ];
        }

        return [
            'schema_version' => 1,
            'weekly'         => $weekly,
        ];
    }
}
