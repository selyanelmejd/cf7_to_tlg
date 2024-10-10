<?php
/**
 * Plugin Name: CF7 to Telegram
 * Description: Envoie les données du formulaire Contact Form 7 à Telegram avec des messages formatés et des emojis personnalisables. Permet de personnaliser les labels des balises CF7.
 * Version: 1.7
 * Author: Votre Nom
 * License: GPL2
 */

defined('ABSPATH') || exit;

// Fonction pour envoyer un message formaté avec des emojis à Telegram
function send_to_telegram($message) {
    $bot_token = get_option('cf7_telegram_bot_token');
    $chat_id = get_option('cf7_telegram_chat_id');

    // Vérification que le bot token et le chat ID sont présents
    if (empty($bot_token) || empty($chat_id)) {
        return;
    }

    // Envoi du message texte avec des emojis
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML',
    ];
    wp_remote_post($url, ['body' => $data]);
}

// Hook pour CF7
add_action('wpcf7_mail_sent', 'cf7_telegram_send_data');

function cf7_telegram_send_data($contact_form) {
    // Récupération des données du formulaire
    $submission = WPCF7_Submission::get_instance();
    if ($submission) {
        $data = $submission->get_posted_data();

        // Récupération du message d'accueil personnalisé
        $welcome_message = get_option('cf7_telegram_welcome_message', '<b>Nouvelle soumission de formulaire :</b>');

        // Initialisation du message avec le message d'accueil personnalisé
        $message = "{$welcome_message}\n\n";

        // Récupération des labels et emojis personnalisés des balises CF7 définis dans les options
        $custom_labels = get_option('cf7_telegram_custom_labels', []);
        $custom_emojis = get_option('cf7_telegram_custom_emojis', []);

        // Emoji par défaut pour toutes les balises
        $default_emoji = '🔹'; 

        // Correspondances des préfixes pour gérer les variantes des champs
        $field_prefixes = [
            'your-name', 'your-email', 'your-subject', 'your-message', 'tel', 'date', 'url', 'number', 'textarea', 'select', 'checkbox', 'radio', 'file'
        ];

        // Construction du message pour chaque champ
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                // Détecter le champ correspondant en utilisant les préfixes
                $matching_prefix = '';
                foreach ($field_prefixes as $prefix) {
                    if (strpos($key, $prefix) === 0) {
                        $matching_prefix = $prefix;
                        break;
                    }
                }

                // Utilisation du label et emoji personnalisé ou valeurs par défaut
                $label = isset($custom_labels[$matching_prefix]) ? $custom_labels[$matching_prefix] : $matching_prefix;
                $emoji = isset($custom_emojis[$matching_prefix]) ? $custom_emojis[$matching_prefix] : $default_emoji;

                $message .= "{$emoji} <b>$label :</b> $value\n";
            }
        }

        // Envoyer le message à Telegram
        send_to_telegram($message);
    }
}

// Ajouter un menu d'options dans l'admin
add_action('admin_menu', 'cf7_telegram_menu');

function cf7_telegram_menu() {
    add_options_page('CF7 to Telegram Settings', 'CF7 to Telegram', 'manage_options', 'cf7-telegram-settings', 'cf7_telegram_settings_page');
}

// Afficher la page de paramètres
function cf7_telegram_settings_page() {
    ?>
    <div class="wrap">
        <h1>CF7 to Telegram Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cf7_telegram_options_group');
            do_settings_sections('cf7_telegram_settings');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Bot Token</th>
                    <td><input type="text" name="cf7_telegram_bot_token" value="<?php echo esc_attr(get_option('cf7_telegram_bot_token')); ?>" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Chat ID</th>
                    <td><input type="text" name="cf7_telegram_chat_id" value="<?php echo esc_attr(get_option('cf7_telegram_chat_id')); ?>" required /></td>
                </tr>
            </table>
            <h2>Personnaliser le message d'accueil</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Message d'accueil</th>
                    <td><input type="text" name="cf7_telegram_welcome_message" value="<?php echo esc_attr(get_option('cf7_telegram_welcome_message')); ?>" placeholder="<b>Nouvelle soumission de formulaire :</b>" /></td>
                </tr>
            </table>
            <h2>Personnaliser les labels des balises CF7</h2>
            <table class="form-table">
                <?php
                // Champs de personnalisation des labels et emojis pour chaque balise CF7
                $fields = ['your-name', 'your-email', 'your-subject', 'your-message', 'tel', 'date', 'url', 'number', 'textarea', 'select', 'checkbox', 'radio', 'file'];
                $custom_labels = get_option('cf7_telegram_custom_labels', []);
                $custom_emojis = get_option('cf7_telegram_custom_emojis', []);
                foreach ($fields as $field) {
                    $label_value = isset($custom_labels[$field]) ? $custom_labels[$field] : '';
                    $emoji_value = isset($custom_emojis[$field]) ? $custom_emojis[$field] : '';
                    ?>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html($field); ?></th>
                        <td><input type="text" name="cf7_telegram_custom_labels[<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr($label_value); ?>" placeholder="Label personnalisé pour <?php echo esc_html($field); ?>" /></td>
                        <td><input type="text" name="cf7_telegram_custom_emojis[<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr($emoji_value); ?>" placeholder="Emoji personnalisé pour <?php echo esc_html($field); ?>" /></td>
                    </tr>
                    <?php
                }
                ?>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Enregistrer les options
add_action('admin_init', 'cf7_telegram_register_settings');

function cf7_telegram_register_settings() {
    register_setting('cf7_telegram_options_group', 'cf7_telegram_bot_token');
    register_setting('cf7_telegram_options_group', 'cf7_telegram_chat_id');
    register_setting('cf7_telegram_options_group', 'cf7_telegram_welcome_message'); // Enregistrer le message d'accueil personnalisé
    register_setting('cf7_telegram_options_group', 'cf7_telegram_custom_labels');   // Enregistrer les labels personnalisés
    register_setting('cf7_telegram_options_group', 'cf7_telegram_custom_emojis');   // Enregistrer les emojis personnalisés
}
