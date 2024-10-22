<?php
/**
 * Plugin Name: CF7 Telegram Notifier
 * Description: Sends Contact Form 7 submissions to a Telegram bot with bold fields and a customizable welcome message.
 * Version: 1.4
 * Author: Anonymous
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Add menu in admin panel
add_action('admin_menu', 'cf7_telegram_notifier_menu');
function cf7_telegram_notifier_menu() {
    add_options_page('CF7 Telegram Notifier', 'CF7 Telegram Notifier', 'manage_options', 'cf7-telegram-notifier', 'cf7_telegram_notifier_options');
}

// Plugin settings function
function cf7_telegram_notifier_options() {
    ?>
    <div class="wrap">
        <h1>CF7 Telegram Notifier Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cf7-telegram-notifier-group');
            do_settings_sections('cf7-telegram-notifier-group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Telegram Bot Token</th>
                    <td><input type="text" name="telegram_bot_token" value="<?php echo esc_attr(get_option('telegram_bot_token')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Telegram Chat ID</th>
                    <td><input type="text" name="telegram_chat_id" value="<?php echo esc_attr(get_option('telegram_chat_id')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Welcome Message</th>
                    <td><textarea name="telegram_welcome_message" rows="3" cols="50"><?php echo esc_attr(get_option('telegram_welcome_message', 'New form submission:')); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register settings
add_action('admin_init', 'cf7_telegram_notifier_settings');
function cf7_telegram_notifier_settings() {
    register_setting('cf7-telegram-notifier-group', 'telegram_bot_token');
    register_setting('cf7-telegram-notifier-group', 'telegram_chat_id');
    register_setting('cf7-telegram-notifier-group', 'telegram_welcome_message');
}

// Send form data to Telegram
add_action('wpcf7_mail_sent', 'send_to_telegram');
function send_to_telegram($contact_form) {
    $submission = WPCF7_Submission::get_instance();
    if ($submission) {
        $data = $submission->get_posted_data();

        // Get bot token, chat ID, and welcome message from settings
        $bot_token = get_option('telegram_bot_token');
        $chat_id = get_option('telegram_chat_id');
        $welcome_message = get_option('telegram_welcome_message', 'New form submission:');
        
        // Format message
        $message = "$welcome_message\n";
        foreach ($data as $key => $value) {
            $message .= "*$key*: $value\n"; // Bold fields
        }

        // Send message to Telegram
        $url = "https://api.telegram.org/bot$bot_token/sendMessage?chat_id=$chat_id&parse_mode=Markdown&text=" . urlencode($message);
        
        // Execute request
        wp_remote_get($url);
    }
}
