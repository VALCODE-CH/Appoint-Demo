<?php
/**
 * Plugin Name:     Valcode Appoint Demo Data Generator
* Plugin URI:       https://appoint.valcode.ch
 * Description:     Automatically generates demo appointments daily for Valcode Appoint plugin
 * Version:         1.0.0
 * Author:          Valcode
 * Author URI:      https://valcode.ch
 */

if (!defined('ABSPATH')) exit;

class Valcode_Appoint_Demo_Data {
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Register activation hook
        register_activation_hook(__FILE__, [$this, 'activate']);
        
        // Register deactivation hook
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Hook into the scheduled event
        add_action('valcode_appoint_demo_daily_cleanup', [$this, 'daily_cleanup_and_generate']);
        
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Add manual trigger button handler
        add_action('admin_post_valcode_demo_manual_trigger', [$this, 'manual_trigger']);
    }
    
    public function activate() {
        // Schedule daily event at 2 AM
        if (!wp_next_scheduled('valcode_appoint_demo_daily_cleanup')) {
            wp_schedule_event(strtotime('tomorrow 2:00 AM'), 'daily', 'valcode_appoint_demo_daily_cleanup');
        }
        
        // Run once on activation
        $this->daily_cleanup_and_generate();
    }
    
    public function deactivate() {
        // Remove scheduled event
        $timestamp = wp_next_scheduled('valcode_appoint_demo_daily_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'valcode_appoint_demo_daily_cleanup');
        }
    }
    
    public function add_admin_menu() {
        // Check if the main Valcode Appoint menu exists (license valid)
        global $menu;
        $parent_exists = false;
        foreach ($menu as $item) {
            if (isset($item[2]) && $item[2] === 'valcode-appoint') {
                $parent_exists = true;
                break;
            }
        }

        if ($parent_exists) {
            // Add as submenu if parent menu exists
            add_submenu_page(
                'valcode-appoint',
                'Demo Data Generator',
                'Demo Data',
                'manage_options',
                'valcode-appoint-demo',
                [$this, 'render_admin_page']
            );
        } else {
            // Create standalone menu if parent doesn't exist (e.g., license invalid)
            add_menu_page(
                'Valcode Appoint Demo',
                'Appoint Demo',
                'manage_options',
                'valcode-appoint-demo',
                [$this, 'render_admin_page'],
                'dashicons-calendar-alt',
                27
            );
        }
    }
    
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>Valcode Appoint Demo Data Generator</h1>
            
            <?php if (isset($_GET['generated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Erfolg!</strong> Demo-Termine wurden generiert.</p>
                </div>
            <?php endif; ?>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Automatische Demo-Daten</h2>
                <p>Dieses Plugin generiert automatisch täglich um 2:00 Uhr morgens neue Demo-Termine.</p>
                
                <h3>Was passiert täglich:</h3>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li>Alle bestehenden Termine werden gelöscht</li>
                    <li>5-6 neue zufällige Termine werden erstellt</li>
                    <li>Termine werden für die nächsten 1-14 Tage generiert</li>
                    <li>Zufällige Services und Mitarbeiter werden zugewiesen</li>
                </ul>
                
                <h3>Nächste automatische Ausführung:</h3>
                <p><strong><?php echo $this->get_next_scheduled_time(); ?></strong></p>
                
                <h3>Manuelle Ausführung:</h3>
                <p>Klicken Sie auf den Button unten, um sofort neue Demo-Termine zu generieren.</p>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('valcode_demo_manual_trigger', '_wpnonce'); ?>
                    <input type="hidden" name="action" value="valcode_demo_manual_trigger"/>
                    <button type="submit" class="button button-primary" onclick="return confirm('Alle bestehenden Termine werden gelöscht und neue Demo-Termine erstellt. Fortfahren?');">
                        Jetzt Demo-Termine generieren
                    </button>
                </form>
                
                <h3 style="margin-top: 30px;">Statistiken:</h3>
                <?php $this->display_stats(); ?>
            </div>
        </div>
        <?php
    }
    
    private function get_next_scheduled_time() {
        $timestamp = wp_next_scheduled('valcode_appoint_demo_daily_cleanup');
        if ($timestamp) {
            return wp_date('d.m.Y H:i:s', $timestamp);
        }
        return 'Nicht geplant';
    }
    
    private function display_stats() {
        global $wpdb;
        
        // Get Valcode Appoint instance to access table names
        if (!class_exists('Valcode_Appoint')) {
            echo '<p style="color: #dc3545;">Valcode Appoint Plugin ist nicht aktiv!</p>';
            return;
        }
        
        $appoint = Valcode_Appoint::instance();
        $tables = $appoint->tables;
        
        $total_appointments = $wpdb->get_var("SELECT COUNT(*) FROM {$tables['appointments']}");
        $total_services = $wpdb->get_var("SELECT COUNT(*) FROM {$tables['services']} WHERE active=1");
        $total_staff = $wpdb->get_var("SELECT COUNT(*) FROM {$tables['staff']} WHERE active=1");
        
        echo '<table class="widefat" style="max-width: 400px;">';
        echo '<tbody>';
        echo '<tr><td><strong>Aktuelle Termine:</strong></td><td>' . esc_html($total_appointments) . '</td></tr>';
        echo '<tr><td><strong>Aktive Services:</strong></td><td>' . esc_html($total_services) . '</td></tr>';
        echo '<tr><td><strong>Aktive Mitarbeiter:</strong></td><td>' . esc_html($total_staff) . '</td></tr>';
        echo '</tbody>';
        echo '</table>';
    }
    
    public function manual_trigger() {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung');
        }
        
        check_admin_referer('valcode_demo_manual_trigger', '_wpnonce');
        
        // Run the cleanup and generation
        $this->daily_cleanup_and_generate();
        
        // Redirect back with success message
        wp_safe_redirect(admin_url('admin.php?page=valcode-appoint-demo&generated=1'));
        exit;
    }
    
    public function daily_cleanup_and_generate() {
        global $wpdb;
        
        // Check if Valcode Appoint is active
        if (!class_exists('Valcode_Appoint')) {
            error_log('Valcode Appoint Demo: Main plugin not active');
            return;
        }
        
        $appoint = Valcode_Appoint::instance();
        $tables = $appoint->tables;
        
        // Delete all existing appointments
        $deleted = $wpdb->query("DELETE FROM {$tables['appointments']}");
        error_log("Valcode Appoint Demo: Deleted {$deleted} appointments");
        
        // Get active services
        $services = $wpdb->get_results("SELECT id, name, duration_minutes FROM {$tables['services']} WHERE active=1");
        if (empty($services)) {
            error_log('Valcode Appoint Demo: No active services found');
            return;
        }
        
        // Get active staff
        $staff = $wpdb->get_results("SELECT id, display_name, services FROM {$tables['staff']} WHERE active=1");
        if (empty($staff)) {
            error_log('Valcode Appoint Demo: No active staff found');
            return;
        }
        
        // Get or create demo customers
        $customers = $this->get_or_create_demo_customers();
        
        // Generate 5-6 random appointments
        $num_appointments = rand(5, 6);
        $generated = 0;
        
        for ($i = 0; $i < $num_appointments; $i++) {
            // Random service
            $service = $services[array_rand($services)];
            
            // Random staff member who can perform this service
            $valid_staff = $this->get_staff_for_service($staff, $service->id);
            if (empty($valid_staff)) {
                continue;
            }
            $staff_member = $valid_staff[array_rand($valid_staff)];
            
            // Random customer
            $customer = $customers[array_rand($customers)];
            
            // Random date in the next 1-14 days
            $days_ahead = rand(1, 14);
            $date = date('Y-m-d', strtotime("+{$days_ahead} days"));
            
            // Random time between 9 AM and 5 PM
            $hour = rand(9, 16);
            $minute = rand(0, 1) * 30; // Either :00 or :30
            $time = sprintf('%02d:%02d:00', $hour, $minute);
            
            $starts_at = $date . ' ' . $time;
            $ends_at = date('Y-m-d H:i:s', strtotime($starts_at . ' +' . $service->duration_minutes . ' minutes'));
            
            // Random status
            $statuses = ['pending', 'confirmed', 'confirmed', 'confirmed']; // More likely to be confirmed
            $status = $statuses[array_rand($statuses)];
            
            // Insert appointment
            $result = $wpdb->insert(
                $tables['appointments'],
                [
                    'customer_name' => $customer['name'],
                    'customer_email' => $customer['email'],
                    'service_id' => $service->id,
                    'staff_id' => $staff_member->id,
                    'starts_at' => $starts_at,
                    'ends_at' => $ends_at,
                    'status' => $status,
                    'notes' => $this->get_random_note(),
                    'created_at' => current_time('mysql')
                ],
                ['%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s']
            );
            
            if ($result) {
                $generated++;
            }
        }
        
        error_log("Valcode Appoint Demo: Generated {$generated} new appointments");
    }
    
    private function get_staff_for_service($all_staff, $service_id) {
        $valid_staff = [];
        
        foreach ($all_staff as $staff_member) {
            $services = json_decode($staff_member->services, true);
            if (is_array($services) && in_array($service_id, $services)) {
                $valid_staff[] = $staff_member;
            }
        }
        
        // If no staff found for this service, return all staff
        return empty($valid_staff) ? $all_staff : $valid_staff;
    }
    
    private function get_or_create_demo_customers() {
        global $wpdb;
        
        $appoint = Valcode_Appoint::instance();
        $tables = $appoint->tables;
        
        // Demo customer names
        $demo_customers = [
            ['name' => 'Anna Müller', 'email' => 'anna.mueller@example.com'],
            ['name' => 'Thomas Schmidt', 'email' => 'thomas.schmidt@example.com'],
            ['name' => 'Sarah Weber', 'email' => 'sarah.weber@example.com'],
            ['name' => 'Michael Wagner', 'email' => 'michael.wagner@example.com'],
            ['name' => 'Laura Fischer', 'email' => 'laura.fischer@example.com'],
            ['name' => 'Daniel Becker', 'email' => 'daniel.becker@example.com'],
            ['name' => 'Julia Hoffmann', 'email' => 'julia.hoffmann@example.com'],
            ['name' => 'Christian Klein', 'email' => 'christian.klein@example.com'],
            ['name' => 'Nina Schulz', 'email' => 'nina.schulz@example.com'],
            ['name' => 'Markus Meyer', 'email' => 'markus.meyer@example.com'],
        ];
        
        // Ensure all demo customers exist
        foreach ($demo_customers as &$customer) {
            $exists = $wpdb->get_row($wpdb->prepare(
                "SELECT id, first_name, last_name FROM {$tables['customers']} WHERE email = %s",
                $customer['email']
            ));
            
            if (!$exists) {
                // Create customer
                $name_parts = explode(' ', $customer['name'], 2);
                $wpdb->insert(
                    $tables['customers'],
                    [
                        'first_name' => $name_parts[0],
                        'last_name' => $name_parts[1] ?? '',
                        'email' => $customer['email'],
                        'is_guest' => 1,
                        'created_at' => current_time('mysql')
                    ]
                );
            }
        }
        
        return $demo_customers;
    }
    
    private function get_random_note() {
        $notes = [
            '',
            '',
            '',
            'Bitte bei Ankunft melden',
            'Parkplatz reserviert',
            'Erster Termin - Beratung gewünscht',
            'Folge-Termin',
            'Bitte Unterlagen mitbringen',
            'Telefonische Erinnerung gewünscht',
        ];
        
        return $notes[array_rand($notes)];
    }
}

// Initialize plugin
Valcode_Appoint_Demo_Data::instance();