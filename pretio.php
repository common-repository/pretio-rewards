<?php
/*
    Plugin Name: Pretio Rewards for WordPress
    Plugin URI: http://pretio.in
    Version: 0.1
    Author: Pretio Interactive
    Author URI: http://pretio.in
*/

// Version check
global $wp_version;
if(!version_compare($wp_version, '3.0', '>='))
{
    die("Pretio Rewards /requires WordPress 3.0 or above. <a href='http://codex.wordpress.org/Upgrading_WordPress'>Please update!</a>");
}
// END - Version check


//this is to avoid getting in trouble because of the
//wordpress bug http://core.trac.wordpress.org/ticket/16953
$pretio_file = __FILE__; 

if ( isset( $mu_plugin ) ) { 
    $pretio_file = $mu_plugin; 
} 
if ( isset( $network_plugin ) ) { 
    $pretio_file = $network_plugin; 
} 
if ( isset( $plugin ) ) { 
    $pretio_file = $plugin; 
} 

$GLOBALS['pretio_file'] = $pretio_file;


// Make sure class does not exist already.
if(!class_exists('Pretio')) :

    // Declare and define the plugin class.
    class Pretio
    {
        // will contain id of plugin
        private $plugin_id;
        // will contain option info
        private $options;

        /** function/method
        * Usage: defining the constructor
        * Arg(1): string(alphanumeric, underscore, hyphen)
        * Return: void
        */
        public function __construct($id)
        {
            // set id
            $this->plugin_id = $id;

            // create array of options
            $this->options = array();

            // set default options
            $this->options['key'] = '';

            /*
            * Add Hooks
            */
            // register the script files into the footer section
            add_action('wp_footer', array(&$this, 'pretio_scripts'));

            // initialize the plugin (saving default options)
            register_activation_hook(__FILE__, array(&$this, 'install'));

            // triggered when comment is posted (Email)
            add_action('comment_post', array(&$this, 'comment_reward'));

            // triggered when plugin is initialized (used for updating options)
            add_action('admin_init', array(&$this, 'init'));

            // register the menu under settings
            add_action('admin_menu', array(&$this, 'menu'));


            /*
            * END -Add Hooks
            */

            /*
            * Process queued events
            */
            if(isset($_COOKIE['pretio_comment_posted']))
            {
              setcookie("pretio_comment_posted", "", time()-3600, COOKIEPATH, COOKIE_DOMAIN);
              add_action('wp_head', create_function('', 'echo "<script type=\"text/javascript\">var _piq = []; var _btq = []; _piq.push([\"comment\",\"12\"]); _btq.push([\"comment\",\"42\"]);</script>";'));
            }
           /*
            * END -Process queued events
            */
        }

        /** function/method
        * Usage: return plugin options
        * Arg(0): null
        * Return: array
        */
        private function get_options()
        {
            // return saved options
            $options = get_option($this->plugin_id);
            return $options;
        }
        /** function/method
        * Usage: update plugin options
        * Arg(0): null
        * Return: void
        */
        private function update_options($options=array())
        {
            // update options
            update_option($this->plugin_id, $options);
        }

        /** function/method
        * Usage: helper for loading pretio.js
        * Arg(0): null
        * Return: void
        */
        public function pretio_scripts()
        {
            // Don't load Pretio scripts on admin dashboard pages
            if (!is_admin()) {

                $options = $this->get_options();
                $key = $options['key'];

                // Sanitize key to only include alphanumeric + underscore + hyphen
                $safe_key = preg_replace("/[^a-zA-Z0-9_-]+/", "", $key);

                // Don't load until a valid app key has been entered
                if ($safe_key != '') {
                    $this->show_pretio_reward_js($safe_key);
                }
            }
        }

        public function show_pretio_reward_js($key)
        {
            $static_asset_host = "https://static.rewardsden.com";
            $json_key = json_encode($key);

            echo "
            <script type=\"text/javascript\" charset=\"utf-8\">
            var _piq = _piq || [];
            var _pretio_settings = {
                key: $json_key
            };

            (function(d){
                var js, id = 'rewardsden-preloader'; if (d.getElementById(id)) {return;}
                js = d.createElement('script'); js.id = id; js.async = true;
                js.src = '$static_asset_host/loaders/$key.js';
                d.getElementsByTagName('body')[0].appendChild(js);
            }(document));
            </script>
            ";
        }

        /** function/method
        * Usage: helper for hooking activation (creating the option fields)
        * Arg(0): null
        * Return: void
        */
        public function install()
        {
            $this->update_options($this->options);
        }
        /** function/method
        * Usage: helper for hooking notification when comment is posted
        * Arg(1): int (comment id)
        * Return: int (comment id)
        */
        public function comment_reward($comment)
        {
              setcookie("pretio_comment_posted", 1, time()+3600, COOKIEPATH, COOKIE_DOMAIN);
        }
        /** function/method
        * Usage: helper for hooking (registering) options
        * Arg(0): null
        * Return: void
        */
        public function init()
        {
            register_setting($this->plugin_id.'_options', $this->plugin_id);
        }
        /** function/method
        * Usage: show options/settings form page
        * Arg(0): null
        * Return: void
        */
        public function options_page()
        {
            if (!current_user_can('manage_options'))
            {
                wp_die( __('You can manage options from the Settings->Pretio Rewards Options menu.') );
            }
            
            $this->render_options_form();
        }
        /** function/method
        * Usage: helper for hooking (registering) the plugin menu under settings
        * Arg(0): null
        * Return: void
        */
        public function menu()
        {
            add_options_page('Pretio Options', 'Pretio Rewards', 'manage_options', $this->plugin_id.'-plugin', array(&$this, 'options_page'));
        }

        public function render_options_form()
        {
            $options = $this->get_options();
            ?>
            <div class="wrap">
                <?php screen_icon(); ?>

                <form action="options.php" method="post" id="<?php echo $this->plugin_id; ?>_options_form" name="<?php echo $this->plugin_id; ?>_options_form">

                    <?php settings_fields($this->plugin_id.'_options'); ?>

                    <h2>Pretio Rewards &raquo; Options</h2>

                    <h3>Rewards</h3>
                    <p>Reward your users with free promotional gift cards from top brands – and earn revenue from every redemption. The possibilities are endless!</p>

                    <table class="form-table">
                        <tbody>
                            <tr valign="top">
                                <th scope="row"><label for="<?php echo $this->plugin_id; ?>[key]">App Key</label></th>
                                <td>
                                    <input placeholder="Got a Pretio key? Enter it here." id="pretio_key" name="<?php echo $this->plugin_id; ?>[key]" type="text" value="<?php echo $options['key']; ?>" class="regular-text" />
                                    <p class="description">Need a key? Visit our <a href="http://pretiointeractive.com/#contact-top" target="_blank">sign-up page</a> to request access.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
                    </p>
                    <div>By installing Pretio Rewards you agree to the <a href="http://pretio.in/terms-of-service">Terms of Service</a></div>

                </form>
            </div>
            <?php
        }
    }

    // Instantiate the plugin
    $Pretio = new Pretio('pretio');

// END - class exists
endif;
?>
