<?php

// Shortcode callback function
function mahoee_scheduling_shortcode_callback()
{
    // Enqueue required resources
    wp_enqueue_script('mahoee-scheduling', plugin_dir_url(__FILE__) . 'scheduling.js', array('jquery'), null, true);
    wp_enqueue_style('mahoee-scheduling', plugin_dir_url(__FILE__) . 'scheduling.css');

    // Get site weekdays and shifts
    $options = get_option('mahoee_scheduling_recurring');
    $weekdays = isset($options['weekdays']) ? $options['weekdays'] : [];
    $shifts = isset($options['shifts']) ? $options['shifts'] : [];
    $weekdays_labels = [
        0 => 'Domingo',
        1 => 'Segunda',
        2 => 'Terça',
        3 => 'Quarta',
        4 => 'Quinta',
        5 => 'Sexta',
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

    $site_timezone = wp_timezone();
    $now = new DateTime('now', $site_timezone);
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
    $output = '<div class="mahoee-scheduling-block" data-state="initial">';
    $output .= '<div class="slots">';
    foreach ($selected_slots as $slot) {
        list($date, $weekday_label, $shift_label) = $slot;
        $output .= '<div class="option" data-selected="false">';
        $output .= '<span class="weekday">' . esc_html($weekday_label) . '</span>';
        $output .= '<span class="date">' . esc_html($date) . '</span>';
        $output .= '<span class="shift">' . esc_html($shift_label) . '</span>';
        $output .= '</div>';
    }
    $output .= '</div>';
    $output .= '<div class="actions">';
    $output .= '<button class="confirm" disabled>Confirmar</button>';
    $output .= '<button class="change" disabled>Mudar</button>';
    $output .= '</div>';
    $output .= '</div>';

    // Return output string
    return $output;
}