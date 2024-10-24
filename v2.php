<?php
/**
 * Plugin Name: CF7 Telegram Notifier
 * Description: Notifiez sur Telegram lors des soumissions de Contact Form 7.
 * Version: 1.0
 * Author: Votre Nom
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Enregistrement des paramètres
add_action('admin_init', function() {
    register_setting('cf7-telegram-notifier-group', 'telegram_bot_token');
    register_setting('cf7-telegram-notifier-group', 'telegram_chat_id');
    register_setting('cf7-telegram-notifier-group', 'telegram_button_text');
    register_setting('cf7-telegram-notifier-group', 'telegram_button_url');
    register_setting('cf7-telegram-notifier-group', 'telegram_custom_message');
});

// Création de la page d'options
add_action('admin_menu', function() {
    add_options_page('CF7 Telegram Notifier', 'CF7 Telegram Notifier', 'manage_options', 'cf7-telegram-notifier', 'render_options_page');
});

// Fonction pour afficher la page d'options
function render_options_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('CF7 Telegram Notifier', 'cf7-telegram-notifier'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('cf7-telegram-notifier-group'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Telegram Bot Token', 'cf7-telegram-notifier'); ?></th>
                    <td><input type="text" name="telegram_bot_token" value="<?php echo esc_attr(get_option('telegram_bot_token')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Telegram Chat ID', 'cf7-telegram-notifier'); ?></th>
                    <td><input type="text" name="telegram_chat_id" value="<?php echo esc_attr(get_option('telegram_chat_id')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Custom Message', 'cf7-telegram-notifier'); ?></th>
                    <td><textarea name="telegram_custom_message" class="regular-text" rows="5"><?php echo esc_textarea(get_option('telegram_custom_message', __('Nouvelle soumission:', 'cf7-telegram-notifier'))); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Button Text', 'cf7-telegram-notifier'); ?></th>
                    <td><input type="text" name="telegram_button_text" value="<?php echo esc_attr(get_option('telegram_button_text', __('Click here', 'cf7-telegram-notifier'))); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Button URL', 'cf7-telegram-notifier'); ?></th>
                    <td><input type="text" name="telegram_button_url" value="<?php echo esc_attr(get_option('telegram_button_url')); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Fonction pour envoyer le message à Telegram
add_action('wpcf7_mail_sent', 'send_to_telegram');
function send_to_telegram($contact_form) {
    $bot_token = get_option('telegram_bot_token');
    $chat_id = get_option('telegram_chat_id');
    
    if (empty($bot_token) || empty($chat_id)) {
        return; // Ne pas envoyer si le token ou l'ID est vide
    }

    $submission = WPCF7_Submission::get_instance();
    if ($submission) {
        $data = $submission->get_posted_data();
        $custom_message = get_option('telegram_custom_message', __('Nouvelle soumission:', 'cf7-telegram-notifier'));
        $message = "* {$custom_message} *\n\n"; // Message personnalisable

        foreach ($data as $key => $value) {
            $message .= "*" . esc_html($key) . "*: " . esc_html($value) . "\n"; // Champs en gras, données normales
        }

        // Ajout du bouton inline
        $button_text = get_option('telegram_button_text', __('Click here', 'cf7-telegram-notifier'));
        $button_url = get_option('telegram_button_url');

        if (!empty($button_url)) {
            $button = [
                [
                    'text' => $button_text,
                    'url' => $button_url // Ouvre le lien normalement
                ]
            ];
            $inline_keyboard = json_encode(['inline_keyboard' => [$button]]);
        }

        // Envoi du message à Telegram
        send_message($bot_token, $chat_id, $message, $inline_keyboard ?? null);
    }
}

// Fonction pour envoyer le message
function send_message($bot_token, $chat_id, $message, $inline_keyboard = null) {
    $api_url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $body = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'Markdown' // Utiliser Markdown pour les liens
    ];

    if ($inline_keyboard) {
        $body['reply_markup'] = $inline_keyboard; // Ajout du clavier inline
    }

    $response = wp_remote_post($api_url, [
        'body' => $body
    ]);
}
