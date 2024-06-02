<?php

// Shortcode callback function
function mahoee_scheduling_shortcode_callback()
{
    // Enqueue required resources
    wp_enqueue_script('mahoee-scheduling', plugin_dir_url(__FILE__) . 'scheduling.js', array('jquery'), null, true);
    wp_enqueue_style('mahoee-scheduling', plugin_dir_url(__FILE__) . 'scheduling.css');

    // Debugging: Ensure the CSS is enqueued
    if (wp_style_is('mahoee-scheduling', 'enqueued')) {
        echo 'CSS is enqueued.';
    } else {
        echo 'CSS is NOT enqueued.';
    }

    // Get site weekdays and shifts
    $options = get_option('mahoee_scheduling_recurring');
    $weekdays = isset($options['weekdays']) ? $options['weekdays'] : [];
    $shifts = isset($options['shifts']) ? $options['shifts'] : [];
    $timezone = isset($options['timezone']) ? $options['timezone'] : '';
    $weekdays_labels = [
        0 => 'Domingo',
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
    ];
    $shifts_labels = [
        0 => 'de manhã',
        1 => 'de tarde',
        2 => 'de noite',
    ];

    $min_random_skip = 2;
    $max_random_skip = 5;
    $pick_count = 3;
    $required_slots = ($pick_count + 1) * $max_random_skip;

    // Determine current date and time
    date_default_timezone_set($timezone ? $timezone : 'UTC');
    $now = new DateTime();
    $current_weekday = (int) $now->format('w');
    $current_time = (int) $now->format('G');

    // Initialize slots array
    $slots = [];

    // Determine the next slots considering the current time and shifts
    $days_checked = 0;
    while (count($slots) < $required_slots) {
        $weekday = ($current_weekday + $days_checked) % 7;
        if (in_array($weekday, $weekdays)) {
            foreach ($shifts as $shift) {
                if ($days_checked == 0 && $shift <= floor($current_time / 8)) {
                    // Skip past shifts for today
                    continue;
                }
                $date = clone $now;
                $date->modify("+$days_checked day");
                $formatted_date = $date->format('Y-m-d');
                $slots[] = [$formatted_date, $weekdays_labels[$weekday], $shifts_labels[$shift]];
            }
        }
        $days_checked++;
    }

    // Pick a few slots
    $selected_slots = [];
    $shifts_missing = array_unique(array_column($slots, 2));
    $current_index = 0;
    $total_slots = count($slots);
    while (count($selected_slots) < $pick_count) {
        $current_slot = $slots[$current_index];
        $shift = $current_slot[2];
        if (empty($shifts_missing) || in_array($shift, $shifts_missing)) {
            $selected_slots[] = $current_slot;
            $shifts_missing = array_diff($shifts_missing, [$shift]);
        }
        $random_skip = rand($min_random_skip, $max_random_skip);
        $current_index = ($current_index + $random_skip) % $total_slots;
    }

    // Generate the HTML output
    $output = '<div class="mahoee-scheduling-block">';
    foreach ($selected_slots as $slot) {
        list($date, $weekday_label, $shift_label) = $slot;
        $output .= '<div class="option">';
        $output .= '<span class="weekday">' . esc_html($weekday_label) . '</span>';
        $output .= '<span class="date">' . esc_html($date) . '</span>';
        $output .= '<span class="shift">' . esc_html($shift_label) . '</span>';
        $output .= '</div>';
    }
    $output .= '</div>';

    // Return output string
    return $output;
}