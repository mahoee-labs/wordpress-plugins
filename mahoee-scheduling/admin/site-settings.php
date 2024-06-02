<?php

function mahoee_scheduling_site_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    echo '<form method="post" action="options.php">';
    settings_fields('mahoee_scheduling');
    do_settings_sections('mahoee-scheduling');
    submit_button();
    echo '</form>';

}

function mahoee_scheduling_register_settings()
{
    register_setting('mahoee_scheduling', 'mahoee_scheduling_recurring', ['type' => 'object']);

    add_settings_section(
        'recurring',
        'Agenda Recorrente',
        'mahoee_scheduling_recurring_section_callback',
        'mahoee-scheduling',
    );

    add_settings_field(
        'weekdays',
        'Dias da Semana',
        'mahoee_scheduling_recurring_weekdays_callback',
        'mahoee-scheduling',
        'recurring'
    );

    add_settings_field(
        'shifts',
        'Turnos nesses Dias',
        'mahoee_scheduling_recurring_shifts_callback',
        'mahoee-scheduling',
        'recurring'
    );
}
add_action('admin_init', 'mahoee_scheduling_register_settings');

function mahoee_scheduling_recurring_section_callback()
{
    echo 'Informe quais dias da semana você opera consistentemente com os turnos selecionado.';
}

function mahoee_scheduling_recurring_weekdays_callback()
{
    $options = get_option('mahoee_scheduling_recurring');
    $picked = isset($options['weekdays']) ? $options['weekdays'] : [];
    $labels_values = [
        0 => 'Domingo',
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
    ];

    foreach ($labels_values as $value => $label) {
        echo '<label><input type="checkbox" name="mahoee_scheduling_recurring[weekdays][]" value="' . $value . '" ' . (in_array($value, $picked) ? 'checked' : '') . '> ' . $label . '</label><br>';
    }
}

function mahoee_scheduling_recurring_shifts_callback()
{
    $options = get_option('mahoee_scheduling_recurring');
    $picked = isset($options['shifts']) ? $options['shifts'] : [];
    $labels_values = [
        0 => 'Manhã',
        1 => 'Tarde',
        2 => 'Noite',
    ];

    foreach ($labels_values as $value => $label) {
        echo '<label><input type="checkbox" name="mahoee_scheduling_recurring[shifts][]" value="' . $value . '" ' . (in_array($value, $picked) ? 'checked' : '') . '> ' . $label . '</label><br>';
    }
}