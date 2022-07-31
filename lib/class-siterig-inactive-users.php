<?php
namespace SiteRig;

class InactiveUsers extends Core
{

    public $inactivity_duration;

    public $inactivity_duration_unit;

    public $inactivity_duration_unix;

    public $inactivity_duration_human;

    public $cron_schedule;

    /**
     * Create a new instance
     *
     * @since 1.0.0
     */
    public function __construct( $file )
    {
        register_activation_hook( $file, [ $this, 'activation_tasks' ] );

        add_action( 'after_setup_theme', [$this, 'get_plugin_settings' ] );
        add_action( 'admin_menu', [ $this, 'add_submenu_items' ] );
        add_action( 'restrict_manage_users', [ $this, 'filter_by_inactive_users' ] );

        apply_filters( 'users_list_table_query_args', [ $this, 'filter_users_list' ] );
    }

    /**
     * Add submenu items to Users menu
     *
     * @since 1.0.0
     */
    public function activation_tasks()
    {

       

    }

    /**
     * Get the stored plugin settings
     *
     * @since 1.0.0
     */
    public function get_plugin_settings()
    {

        // Get the cron schedule
        $this->cron_schedule = get_option('siterig_iuad_cron_schedule', 'daily');

        // Get inactivity duration and unit
        $this->inactivity_duration = get_option( 'siterig_iuad_inactivity_duration', 2 );
        $this->inactivity_duration_unit = get_option( 'siterig_iuad_inactivity_duration_unit', 'years' );

        // Check if the unit is years
        if ( $this->inactivity_duration_unit == 'years' ) {

            // Convert duration into days
            $this->inactivity_duration_unix = ($this->inactivity_duration * 365) * 86400;

        // Check if the unit is months
        } elseif ( $this->inactivity_duration_days == 'months' ) {

            // Convert duration into days
            $this->inactivity_duration_unix = ($this->inactivity_duration * 30) * 86400;

        } else {

            // Set inactivity duration in seconds
            $this->$inactivity_duration_unix = $this->inactivity_duration * 86400;

        }

        // calculate the unix timestamp for inactivity
        $this->inactivity_duration_human = human_time_diff( time() - $this->inactivity_duration_unix );
    }

    /**
     * Add submenu items to Users menu
     *
     * @since 1.0.0
     */
    public function add_submenu_items()
    {
        add_submenu_page( 'options-general.php', 'Inactive Users Auto-Delete Settings', 'Inactive Users', 'manage_options', 'siterig-iuad-auto-delete', [ $this, 'iaud_settings_page' ] );
    }

    /**
     * Add Inactive Users Auto-Delete settings page
     *
     * @since 1.0.0
     */
    public function iaud_settings_page()
    {
        ?>
        <div class="wrap">
            <h1>Inactive Users Auto-Delete Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'siterig_iuad_plugin_settings_group' ); ?>
                <?php do_settings_sections( 'siterig_iuad_plugin_settings_group' ); ?>
                <h2>Auto-Delete Frequency</h2>
                <p>How often you'd like to process automatic inactive user account deletions.</p>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">WP Cron Schedule</th>
                        <td>
                            <select name="siterig_iuad_cron_schedule" id="siterig_iuad_cron_schedule">
                                <option value="hourly"<?php if ( $this->cron_schedule == 'hourly' ) { ?> selected<?php } ?>>Hourly</option>
                                <option value="twicedaily"<?php if ( $this->cron_schedule == 'twicedaily') { ?> selected<?php } ?>>Twice Daily</option>
                                <option value="daily"<?php if ( $this->cron_schedule == 'daily' ) { ?> selected<?php } ?>>Daily</option>
                                <?php
                                // Check if this is WordPress 5.4 or higher
                                if (version_compare(get_bloginfo('version'), '5.4') >= 0) {
                                ?>
                                    <option value="weekly"<?php if ( $this->cron_schedule == 'weekly' ) { ?> selected<?php } ?>>Weekly</option>
                                <?php
                                }
                                ?>
                                <option value="none"<?php if ( $this->cron_schedule == 'none' ) { ?> selected<?php } ?>>None (Disabled)</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <h2>User Inactivity</h2>
                <p>Select how long until a user is considered inactive based on their last login timestamp.</p>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Duration</th>
                        <td>
                            <label>
                                Users become inactive 
                                <input type="number" name="siterig_iuad_inactivity_duration" id="siterig_iuad_inactivity_duration" value="<?php echo $this->inactivity_duration; ?>" class="small-text">
                            </label>
                            <label>
                                <select name="siterig_iuad_inactivity_duration_unit" id="siterig_iuad_inactivity_duration_unit">
                                    <option value="days"<?php if ( $this->inactivity_duration_unit == 'days') { ?> selected<?php } ?>>Day(s)</option>
                                    <option value="months"<?php if ( $this->inactivity_duration_unit == 'months' ) { ?> selected<?php } ?>>Month(s)</option>
                                    <option value="years"<?php if ( $this->inactivity_duration_unit == 'years' ) { ?> selected<?php } ?>>Year(s)</option>
                                </select>
                                after their last login
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Filter for Users by Inactive state
     *
     * @since 1.0.0
     * 
     * @param string $which
     */
    function filter_by_inactive_users(string $which)
    {
        // template for filtering
        $select = '
            <select name="user_state_%s" style="float:none;margin-left:10px;">
                <option>Last login...</option>
                <option value="active">In the last ' . $this->inactivity_duration_human . '</option>
                <option value="inactive">More than ' . $this->inactivity_duration_human . ' ago</option>
            </select>
        ';

        // output <select> and submit button
        echo $select;
        submit_button(__( 'Filter' ), null, $which, false);
    }

    /**
     * Add submenu items to Users menu
     *
     * @since 1.0.0
     */
    private function filter_users_list()
    {
        global $wpdb;

        $inactive_users = [];

        // calculate the unix timestamp for inactivity
        $inactivity_timestamp = time() - $this->inactivity_duration_unix;
        
        // get all users who haven't logged in for more than a day
        $inactive_users_query = $wpdb->get_results(
            "
                SELECT user_id
                FROM $wpdb->usermeta
                WHERE meta_key = 'session_tokens'
                AND SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(meta_value, 'login', -1), ';', 2), ':', -1) < " . $inactivity_timestamp
        );
        
        // Check if there were any inactive users
        if ( $inactive_users_query ) {

            // Loop through inactive users
            foreach ($inactive_users as $inactive_user ) {

                // Add user id to array
                $inactive_users[] = $inactive_user->ID;

            }

        }

        // 
        $args = array(
            'include' => array( $inactive_users )
        );

    }

}
