<?php
/**
 * Plugin Name: Simple SMTP Mailer
 * Description: Sends WordPress emails through an SMTP server.
 * Version: 1.0.0
 * Author: Codex
 * License: GPL-2.0-or-later
 * Text Domain: simple-smtp-mailer
 */

if (! defined('ABSPATH')) {
    exit;
}

class Simple_SMTP_Mailer {
    private const OPTION_KEY = 'ssm_options';

    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'register_settings_page'));
        add_action('phpmailer_init', array($this, 'configure_phpmailer'));
        add_filter('wp_mail_from', array($this, 'filter_mail_from'));
        add_filter('wp_mail_from_name', array($this, 'filter_mail_from_name'));
    }

    public function register_settings(): void {
        register_setting(
            'ssm_settings_group',
            self::OPTION_KEY,
            array($this, 'sanitize_options')
        );

        add_settings_section(
            'ssm_main_section',
            __('SMTP Configuration', 'simple-smtp-mailer'),
            '__return_false',
            'simple-smtp-mailer'
        );

        $fields = array(
            'host'        => __('SMTP Host', 'simple-smtp-mailer'),
            'port'        => __('SMTP Port', 'simple-smtp-mailer'),
            'encryption'  => __('Encryption (none, ssl, tls)', 'simple-smtp-mailer'),
            'auth'        => __('Use SMTP Authentication', 'simple-smtp-mailer'),
            'username'    => __('SMTP Username', 'simple-smtp-mailer'),
            'password'    => __('SMTP Password', 'simple-smtp-mailer'),
            'from_email'  => __('From Email Address', 'simple-smtp-mailer'),
            'from_name'   => __('From Name', 'simple-smtp-mailer'),
        );

        foreach ($fields as $key => $label) {
            add_settings_field(
                'ssm_' . $key,
                $label,
                array($this, 'render_field'),
                'simple-smtp-mailer',
                'ssm_main_section',
                array('key' => $key)
            );
        }
    }

    public function sanitize_options(array $input): array {
        $output = array();

        $output['host'] = isset($input['host']) ? sanitize_text_field($input['host']) : '';
        $output['port'] = isset($input['port']) ? absint($input['port']) : 587;

        $allowed_encryptions = array('', 'ssl', 'tls');
        $encryption = isset($input['encryption']) ? strtolower(sanitize_text_field($input['encryption'])) : '';
        $output['encryption'] = in_array($encryption, $allowed_encryptions, true) ? $encryption : '';

        $output['auth'] = ! empty($input['auth']) ? 1 : 0;
        $output['username'] = isset($input['username']) ? sanitize_text_field($input['username']) : '';
        $output['password'] = isset($input['password']) ? sanitize_text_field($input['password']) : '';
        $output['from_email'] = isset($input['from_email']) ? sanitize_email($input['from_email']) : '';
        $output['from_name'] = isset($input['from_name']) ? sanitize_text_field($input['from_name']) : '';

        return $output;
    }

    public function register_settings_page(): void {
        add_options_page(
            __('Simple SMTP Mailer', 'simple-smtp-mailer'),
            __('Simple SMTP Mailer', 'simple-smtp-mailer'),
            'manage_options',
            'simple-smtp-mailer',
            array($this, 'render_settings_page')
        );
    }

    public function render_settings_page(): void {
        if (! current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Simple SMTP Mailer', 'simple-smtp-mailer'); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('ssm_settings_group');
                do_settings_sections('simple-smtp-mailer');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_field(array $args): void {
        $options = get_option(self::OPTION_KEY, array());
        $key = $args['key'];
        $value = isset($options[$key]) ? $options[$key] : '';
        $name = self::OPTION_KEY . '[' . $key . ']';

        if ('auth' === $key) {
            echo '<label><input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked(1, (int) $value, false) . ' /> ' . esc_html__('Enable authentication', 'simple-smtp-mailer') . '</label>';
            return;
        }

        $type = 'password' === $key ? 'password' : ('port' === $key ? 'number' : 'text');
        $extra = 'port' === $key ? ' min="1" max="65535"' : '';

        echo '<input type="' . esc_attr($type) . '" class="regular-text" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '"' . $extra . ' />';
    }

    public function configure_phpmailer($phpmailer): void {
        $options = get_option(self::OPTION_KEY, array());

        if (empty($options['host'])) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host = $options['host'];
        $phpmailer->Port = ! empty($options['port']) ? (int) $options['port'] : 587;
        $phpmailer->SMTPAuth = ! empty($options['auth']);
        $phpmailer->Username = ! empty($options['username']) ? $options['username'] : '';
        $phpmailer->Password = ! empty($options['password']) ? $options['password'] : '';

        if (! empty($options['encryption'])) {
            $phpmailer->SMTPSecure = $options['encryption'];
        }
    }

    public function filter_mail_from(string $from): string {
        $options = get_option(self::OPTION_KEY, array());
        if (! empty($options['from_email']) && is_email($options['from_email'])) {
            return $options['from_email'];
        }
        return $from;
    }

    public function filter_mail_from_name(string $name): string {
        $options = get_option(self::OPTION_KEY, array());
        if (! empty($options['from_name'])) {
            return $options['from_name'];
        }
        return $name;
    }
}

new Simple_SMTP_Mailer();
