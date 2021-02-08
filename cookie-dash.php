<?php

/**
 * @package CookieDash
 * @version 1.0
 */
/*
 * Plugin Name:       CookieDash
 * Plugin URI:        https://tortoiseandharesoftware.com/wp-gtm-data-privacy
 * Description:       A WordPress Plugin that allows you to quickly and easily deploy an instance of Google Tag manager and block the loading of the container if cookie consent is not granted.
 * Version:           1.1.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Tortoise and Hare Software
 * Author URI:        https://tortoiseandharesoftware.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tnhs-cookie-dash
 * Domain Path:       /languages
*/

/*
CookieDash is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
CookieDash is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with CookieDash. If not, see https://www.gnu.org/licenses/gpl-2.0.html. */

register_activation_hook(__FILE__, array('TNHS_COOKIE_DASH', 'tnhs_cookie_dash_activation'));
register_deactivation_hook(__FILE__, array('TNHS_COOKIE_DASH', 'tnhs_cookie_dash_deactivation'));
register_uninstall_hook(__FILE__, array('TNHS_COOKIE_DASH', 'tnhs_cookie_dash_uninstall'));

class TNHS_COOKIE_DASH
{
  // plugin version
  private static $tnhs_cookie_dash_plugin_version = '1.1.2';


  // plugin settings variables
  private static $option_gtm_id = 'gtm_id';
  private static $option_allowed_domains = 'allowed_domains';
  private static $option_privacy_consent = "privacy_consent_option";
  private static $option_plugin_version = "tnhs_cookie_dash_plugin_version";

  private static $nonce_options_form = 'tnhs_cookie_dash_OptionsForm';
  private $hostname;


    //deprecated but still needed to remove on plugin upgrade
    private static $option_privacy_policy_slug = "privay_policy_slug";

  public function __construct()
  {
    $this->hostname = $_SERVER['SERVER_NAME'];

    // register actions
    add_action('wp_head', array(&$this, 'tnhs_cookie_dash_hook_header_loggedInOnly'));
    add_action('wp_body_open', array(&$this, 'tnhs_cookie_dash_hook_body_start_loggedInOnly'));
    add_action('admin_menu', array(&$this, 'tnhs_cookie_dash_plugin_menu'));
    add_action('admin_notices', array(&$this, 'tnhs_cookie_dash_hook_admin_notices'));
  }

  static function tnhs_cookie_dash_activation()
  {
    //register options
    add_option(self::$option_gtm_id, null, '', 'yes');
    add_option(self::$option_allowed_domains, null, '', 'yes');
    add_option(self::$option_privacy_policy_slug, null, '', 'yes');
    add_option(self::$option_privacy_consent, null, '', 'yes');
    add_option(self::$option_plugin_version);
  }

  static function tnhs_cookie_dash_deactivation()
  {
  }

  static function tnhs_cookie_dash_uninstall()
  {
    delete_site_option(self::$option_gtm_id);
    delete_site_option(self::$option_allowed_domains);
    delete_site_option(self::$option_privacy_consent);
    delete_site_option(self::$option_plugin_version);
  }


  // delete user cookies and update the plugin version option to the current version of the plugin on upgrade
  static function tnhs_cookie_dash_upgrade_plugin()
  {
    setcookie("UserOptOut", "", time() - 3600);
    setcookie("AcceptedCookies", "", time() - 3600);
    update_option(self::$option_plugin_version, self::$tnhs_cookie_dash_plugin_version);

    if(get_option($option_privacy_policy_slug)){
      delete_option($option_privacy_policy_slug);
    }
  }



  function tnhs_cookie_dash_hook_admin_notices()
  {
    // check to make sure the required options have been configured on the backend
    if (!get_option(self::$option_allowed_domains) || !get_option(self::$option_gtm_id) || if(get_privacy_policy_url() == '') || !get_option(self::$option_privacy_consent)) {
      echo '<div class="notice notice-warning is-dismissible" style="min-height:45px;"><p>CookieDash Plugin has not been fully configured! Visit the <a href="' . admin_url("options-general.php?page=tnhs-cookie-dash") . '">plugin settings page</a> to finish configuration.</p></div>';
    }
  }


  function tnhs_cookie_dash_hook_header_loggedInOnly()
  {
    // make sure the plugin is on the current version
    if (self::$tnhs_cookie_dash_plugin_version != !get_option(self::$option_plugin_version)) {
      self::tnhs_cookie_dash_upgrade_plugin();
    }

    // check to make sure the required options have been configured on the backend
    if (!get_option(self::$option_allowed_domains) || !get_option(self::$option_gtm_id) || !get_option(self::$option_privacy_policy_slug) || !get_option(self::$option_privacy_consent)) {
      return;
    }




    // make sure we are on an allowed domain
    $arr_allowed_domains = explode(",", get_option(self::$option_allowed_domains));
    if (!in_array($this->hostname, $arr_allowed_domains))
      return;



    // if our opt out cookie isn't detected then we want to notify the user that the site uses cookies
    if (!isset($_COOKIE["UserOptOut"]) && !isset($_COOKIE["AcceptedCookies"])) {
      echo '<div id="ThisSiteUsesCookiesBox" style="position: fixed;bottom: 0;z-index: 5;width: 100%;text-align: center;background-color: black; color:#6b6b6b; display:none;">
      <p style="margin:0px;">
      This site uses personalization cookies, learn more at our <a target="_blank" href="' .  get_privacy_policy_url() . '">Privacy Policy</a> |       
      <a id="AcceptCookiesButton" href="#">Accept</a> |
      <a id="DeclineCookiesButton" href="#">Decline</a>
      </p>
      </div>';
      wp_enqueue_script('cookiespopupmanager', plugin_dir_url(__FILE__) . 'cookiespopup.js', array('jquery'));
    }

    // if we've gotten to here the user either hasn't answered or has accepted the cookies and we can check if the GTM code is writable in settings and output it if so
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

  function tnhs_cookie_dash_hook_body_start_loggedInOnly()
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


  function tnhs_cookie_dash_plugin_menu()
  {
    add_options_page('CookieDash', 'CookieDash', 'manage_options', 'tnhs-cookie-dash', array(&$this, 'tnhs_cookie_dash_plugin_options'));
  }


  function tnhs_cookie_dash_plugin_options()
  {

    //must check that the user has the required capability 
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }


    // variables for the field and option names 
    $hidden_field_name = 'submit_hidden';

    // Read in existing options from database
    $opt_val_gtm_id = get_option(self::$option_gtm_id);
    $opt_val_allowed_domains = get_option(self::$option_allowed_domains);
    $opt_val_privacy_policy_slug = get_option(self::$option_privacy_policy_slug);
    $opt_val_privacy_consent = get_option(self::$option_privacy_consent);



    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y') {

      if (check_admin_referer(self::$nonce_options_form) < 1) {
        wp_die(__('You\'ve attempted and invalid action.'));
      }

      // Read their posted values
      $opt_val_gtm_id = $_POST[self::$option_gtm_id];
      $opt_val_allowed_domains  = $_POST[self::$option_allowed_domains];
      $opt_val_privacy_consent  = $_POST[self::$option_privacy_consent];

      // sanitize our input
      $opt_val_gtm_id = sanitize_text_field($opt_val_gtm_id);
      $opt_val_allowed_domains = sanitize_text_field($opt_val_allowed_domains);
      $opt_val_privacy_consent = sanitize_text_field($opt_val_privacy_consent);

      // Save the posted value in the database
      update_option(self::$option_gtm_id, trim($opt_val_gtm_id));
      update_option(self::$option_allowed_domains, trim($opt_val_allowed_domains));
      update_option(self::$option_privacy_consent, trim($opt_val_privacy_consent));

      // Put a "settings saved" message on the screen

    ?>
      <div class="updated">
        <p><strong>Settings saved</strong></p>
      </div>
    <?php

    }

    // Now display the settings editing screen

    echo '<div class="wrap">';

    // header

    echo "<h2>CookieDash Plugin Settings</h2>";

    // settings form

    ?>
    <p>Welcome to CookieDash, brought to your by <a href="https://tortoiseandharesoftware.com/wp-gtm-data-privacy/?utm_source=wordpress&utm_medium=direct&utm_campaign=WP%20GTM%20Data%20Privacy%20Plugin%20Settings%20Page">Tortoise and Hare Software</a>. Complete the form fields below to get started. Full plugin documentation and support can be found on <a href="https://wordpress.org/plugins/wp-gtm-data-privacy/">the CookieDash plugin page</a></p>

    <form name="tnhs-cookie-dash-configuration-settings-form" method="post" action="">
      <?php
      if (function_exists('wp_nonce_field'))
        wp_nonce_field(self::$nonce_options_form);
      ?>
      <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
      <table class="form-table" role="presentation">
        <tbody>
          <tr>
            <th scope="row"><label for="<?php echo self::$option_allowed_domains; ?>">Allowed Domains:</label></th>
            <td>
              <input type="text" id="<?php echo self::$option_allowed_domains; ?>" name="<?php echo self::$option_allowed_domains; ?>" value="<?php echo esc_html($opt_val_allowed_domains); ?>" size="200" class="regular-text">
              <p class="description">Comma seperated list of allowed domains - for example: domain1.com, domain2.com, domain3.com</strong></p>
            </td>
          </tr>

          <tr>
            <th scope="row"><label for="<?php echo self::$option_gtm_id; ?>">Google Tag Manager ID:</label></th>
            <td><input type="text" id="<?php echo self::$option_gtm_id; ?>" name="<?php echo self::$option_gtm_id; ?>" value="<?php echo esc_html($opt_val_gtm_id); ?>" size="20" class="regular-text"></td>
          </tr>


          <tr>
            <th scope="row"><label for="<?php echo self::$option_privacy_consent; ?>">Privacy Consent Option:</label></th>
            <td>
              <input type="radio" id="<?php echo self::$option_privacy_consent; ?>" name="<?php echo self::$option_privacy_consent; ?>" value="optin" size="20" class="regular-text" <?php if ($opt_val_privacy_consent == 'optin') {
                                                                                                                                                                                        echo ("checked");
                                                                                                                                                                                      } ?>>
              <label for="<?php echo self::$option_privacy_consent; ?>">Opt-In</label>
              <input type="radio" id="<?php echo self::$option_privacy_consent; ?>" name="<?php echo self::$option_privacy_consent; ?>" value="optout" size="20" class="regular-text" <?php if ($opt_val_privacy_consent == 'optout' || $opt_val_privacy_consent == '' || is_null($opt_val_privacy_consent)) {
                                                                                                                                                                                        echo ("checked");
                                                                                                                                                                                      } ?>>
              <label for="<?php echo self::$option_privacy_consent; ?>">Opt-Out</label>
              <p class="description">Indicates whether the Google Tag Manager container is loaded by default. When selecting "Opt-In" the container is not loaded by default and the user must click "Accept" on the cookie consent popup in order to opt in to tracking. <br />When selecting "Opt-Out" the container is loaded by default and clicking "Decline" on the cookie consent popup will block loading of the container.</strong></p>
            </td>
          </tr>
        </tbody>
      </table>
      <?php 
      if(get_privacy_policy_url() == ''){
        echo('<div class="notice notice-error is-dismissible" style="min-height:150px;"><p>No privacy policy page has been set, please <a href="' . admin_url("options-privacy.php") . '">set one.</a>  If you need help creating a privacy policy and setting a privacy policy URL from within WordPress read the <a href="https://www.wpbeginner.com/beginners-guide/how-to-add-a-privacy-policy-in-wordpress/">blog post here.</a></p></div>');
      }

      ?>



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
      //is_user_logged_in() === false &&
      get_option(self::$option_gtm_id) &&
      !isset($_COOKIE["UserOptOut"])
      && (
        (get_option(self::$option_privacy_consent) == 'optin' && isset($_COOKIE["AcceptedCookies"])) || get_option(self::$option_privacy_consent) == 'optout')
    ) {
      return true;
    } else {
      return false;
    }
  }

  private function shouldnt_write_message()
  {
    echo ("<!--If your seeing this the user logged in, or your not on an allowed domain, the plugin hasn't been configured fully, or you have opted out of tracking already-->");
  }
}

$TNHS_COOKIE_DASH = new TNHS_COOKIE_DASH();

?>