# Valcode Appoint Demo Data Generator

A WordPress plugin that automatically generates demo appointments for the Valcode Appoint plugin.

## Description

This plugin automatically creates realistic demo appointment data on a daily basis for the Valcode Appoint booking system. It's designed to help demonstrate the appointment system's functionality with fresh, randomized data.

## Features

- Automatic daily generation of 5-6 random demo appointments
- Scheduled cleanup runs at 2:00 AM daily
- Manual trigger option via admin interface
- Creates demo customers if they don't exist
- Assigns random services and staff members to appointments
- Generates appointments for dates 1-14 days in the future
- Random appointment times between 9 AM and 5 PM
- Statistics dashboard showing current appointment counts

## Requirements

- WordPress 5.0 or higher
- Valcode Appoint plugin (must be installed and active)
- PHP 7.2 or higher

## Installation

1. Upload the `Appoint-Demo` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically run once upon activation
4. Access the admin page via the WordPress admin menu

## Usage

### Automatic Generation

Once activated, the plugin will:
- Run immediately upon activation
- Schedule daily generation at 2:00 AM
- Delete all existing appointments
- Create 5-6 new random appointments

### Manual Generation

1. Navigate to the plugin's admin page in WordPress
2. Click the "Jetzt Demo-Termine generieren" button
3. Confirm the action (this will delete all existing appointments)
4. New demo appointments will be generated immediately

## Admin Interface

The plugin adds a menu item to WordPress admin:
- If Valcode Appoint menu exists: Appears as a submenu under "Valcode Appoint"
- If Valcode Appoint menu doesn't exist: Creates a standalone "Appoint Demo" menu

The admin page displays:
- Next scheduled automatic generation time
- Manual trigger button
- Statistics (total appointments, active services, active staff)

## Demo Data

### Customers

The plugin uses 10 predefined demo customers:
- Anna Müller
- Thomas Schmidt
- Sarah Weber
- Michael Wagner
- Laura Fischer
- Daniel Becker
- Julia Hoffmann
- Christian Klein
- Nina Schulz
- Markus Meyer

### Appointment Details

Each generated appointment includes:
- Random service from active services
- Random staff member (who can perform the selected service)
- Random customer from demo customer pool
- Date: 1-14 days in the future
- Time: Between 9:00 AM and 5:00 PM (on the hour or half-hour)
- Status: "pending" or "confirmed" (75% confirmed)
- Optional notes (random)

## Technical Details

### Hooks

- `valcode_appoint_demo_daily_cleanup` - Scheduled event for daily generation
- Activation hook - Schedules the daily event and runs initial generation
- Deactivation hook - Removes scheduled events

### Database

The plugin interacts with Valcode Appoint's database tables:
- `appointments` - Stores appointment records
- `services` - Reads active services
- `staff` - Reads active staff members
- `customers` - Creates/reads customer records

## Development

### File Structure

```
Appoint-Demo/
├── valcode-appoint-demo.php    # Main plugin file
└── README.md                   # This file
```

### Main Class

`Valcode_Appoint_Demo_Data` - Singleton pattern implementation with methods:
- `activate()` - Runs on plugin activation
- `deactivate()` - Runs on plugin deactivation
- `daily_cleanup_and_generate()` - Main generation logic
- `manual_trigger()` - Handles manual generation requests
- `render_admin_page()` - Displays admin interface

## Uninstallation

When you deactivate the plugin:
- The scheduled daily event is removed
- Demo appointments remain in the database (manual deletion required)

## Version History

### 1.0.0
- Initial release
- Daily automatic generation
- Manual trigger functionality
- Demo customer management
- Admin statistics dashboard

## Author

Valcode
- Website: [https://valcode.ch](https://valcode.ch)
- Plugin URI: [https://appoint.valcode.ch](https://appoint.valcode.ch)

## License

This plugin is proprietary software for use with the Valcode Appoint plugin.
