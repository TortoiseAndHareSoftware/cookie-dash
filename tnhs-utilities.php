<?php

/**
 * @package WP GTM Data Privacy
 * @version 1.0
 */
/*
 * Plugin Name:       WP GTM Data Privacy
 * Plugin URI:        https://tortoiseandharesoftware.com/wp-gtm-data-privacy
 * Description:       A WordPress Plugin that allows you to quickly and easily deploy an instance of Google Tag manager and block the loading of the container if cookie consent is not granted.
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Tortoise and Hare Software
 * Author URI:        https://tortoiseandharesoftware.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tnhs-wp-gtm-data-privacy
 * Domain Path:       /languages
*/

/*
WP GTM Data Privacy is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
WP GTM Data Privacy is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with WP GTM Data Privacy. If not, see https://www.gnu.org/licenses/gpl-2.0.html. */

register_activation_hook(__FILE__, array('TNHS_WP_GTM_DataPrivacy', 'tnhs_wp_gtm_data_privacy_activation'));
register_deactivation_hook(__FILE__, array('TNHS_WP_GTM_DataPrivacy', 'tnhs_wp_gtm_data_privacy_deactivation'));
register_uninstall_hook(__FILE__, array('TNHS_WP_GTM_DataPrivacy', 'tnhs_wp_gtm_data_privacy_uninstall'));

class TNHS_WP_GTM_DataPrivacy
{
  private static $option_gtm_id = 'gtm_id';
  private static $nonce_options_form = 'tnhs_wp_gtm_data_privacy_OptionsForm';
  private $hostname;

  public function __construct()
  {
    $this->hostname = $_SERVER['SERVER_NAME'];

    // register actions
    add_action('wp_head', array(&$this, 'tnhs_wp_gtm_data_privacy_hook_header_loggedInOnly'));
    add_action('wp_body_open', array(&$this, 'tnhs_wp_gtm_data_privacy_hook_body_start_loggedInOnly'));
    add_action('admin_menu', array(&$this, 'tnhs_wp_gtm_data_privacy_plugin_menu'));
    add_option(self::$option_gtm_id, null, '', 'yes');
  }

  static function tnhs_wp_gtm_data_privacy_activation()
  {
  }

  static function tnhs_wp_gtm_data_privacy_deactivation()
  {
  }

  static function tnhs_wp_gtm_data_privacy_uninstall()
  {
    delete_site_option(self::$option_gtm_id);
  }

  function tnhs_wp_gtm_data_privacy_hook_header_loggedInOnly()
  {

    // if our opt our cookie isn't detected then we want to notify the user that the site uses cookies
    if (!isset($_COOKIE["UserOptOut"]) && !isset($_COOKIE["AcceptedCookies"])) {
      echo '<div id="ThisSiteUsesCookiesBox" style="position: fixed;bottom: 0;z-index: 5;width: 100%;text-align: center;background-color: black; color:#6b6b6b; display:none;">
      <p style="margin:0px;">
      This site uses cookies, learn more at our <a href="/privacy-policy/">Privacy Policy</a> |       
      <a id="AcceptCookiesButton" href="#">Accept</a> |
      <a id="DeclineCookiesButton" href="#">Decline</a>
      </p>
      </div>';
      wp_enqueue_script('cookiespopupmanager', plugin_dir_url(__FILE__) . 'cookiespopup.js', array('jquery'));
    }

    // if we've gotten to here the user either hasn't answered or has accepted the cookies and we can output the GTM code

    if ($this->should_write()) : ?>

      <!-- Google Tag Manager -->
      <script>
        (function(w, d, s, l, i) {
          w[l] = w[l] || [];
          w[l].push({
            'gtm.start': new Date().getTime(),
            event: 'gtm.js'
          });
          var f = d.getElementsByTagName(s)[0],
            j = d.createElement(s),
            dl = l != 'dataLayer' ? '&l=' + l : '';
          j.async = true;
          j.src =
            'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
          f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', '<?php echo (get_option(self::$option_gtm_id)); ?>');
      </script>
      <!-- End Google Tag Manager -->

    <?php
    else :
      $this->shouldnt_write_message();
    endif;
  }

  function tnhs_wp_gtm_data_privacy_hook_body_start_loggedInOnly()
  {

    if ($this->should_write()) : ?>
      <!-- Google Tag Manager (noscript) -->
      <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo (get_option(self::$option_gtm_id)); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
      <!-- End Google Tag Manager (noscript) -->
    <?php
    else :
      $this->shouldnt_write_message();
    endif;
  }


  function tnhs_wp_gtm_data_privacy_plugin_menu()
  {
    add_options_page('WP GTM Data Privacy', 'WP GTM Data Privacy', 'manage_options', 'tnhs-wp-gtm-data-privacy', array(&$this, 'tnhs_wp_gtm_data_privacy_plugin_options'));
  }


  function tnhs_wp_gtm_data_privacy_plugin_options()
  {

    //must check that the user has the required capability 
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }


    // variables for the field and option names 
    $hidden_field_name = 'submit_hidden';

    // Read in existing option value from database
    $opt_val = get_option(self::$option_gtm_id);

    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y') {

      if (check_admin_referer(self::$nonce_options_form) < 1){
        wp_die(__('You\'ve attempted and invalid action.'));
      }

      // Read their posted value
      $opt_val = $_POST[self::$option_gtm_id];

      // sanitize our input
      $opt_val = sanitize_text_field($opt_val);

      // Save the posted value in the database
      update_option(self::$option_gtm_id, $opt_val);

      // Put a "settings saved" message on the screen

    ?>
      <div class="updated">
        <p><strong><?php _e('settings saved.', 'menu-test'); ?></strong></p>
      </div>
    <?php

    }

    // Now display the settings editing screen

    echo '<div class="wrap">';

    // header

    echo "<h2>" . __('Menu Test Plugin Settings', 'menu-test') . "</h2>";

    // settings form

    ?>

    <form name="form1" method="post" action="">
      <?php
    if ( function_exists('wp_nonce_field') ) 
	wp_nonce_field(self::$nonce_options_form); 
?>
      <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

      <p><?php _e("Google Tag Manager ID:", 'menu-test'); ?>
        <input type="text" name="<?php echo self::$option_gtm_id; ?>" value="<?php echo esc_html($opt_val); ?>" size="20">
      </p>
      <hr />

      <p class="submit">
        <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
      </p>

    </form>
    </div>

<?php

  }




  private function should_write()
  {
    if (
      //strpos($this->hostname, 'local') === false && 
      is_user_logged_in() === false &&
      !isset($_COOKIE["UserOptOut"]) &&
      get_option(self::$option_gtm_id)
    ) {
      return true;
    } else {
      return false;
    }
  }

  private function shouldnt_write_message()
  {
    echo ("<!--If your seeing this the user logged in, or your on a local host, or the google tag manager id isn't set in the options table-->");
  }
}

$TNHS_WP_GTM_DataPrivacy = new TNHS_WP_GTM_DataPrivacy();

?>
