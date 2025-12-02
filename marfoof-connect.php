<?php
/**
 * Plugin Name: Marfoof Connect
 * Description: Connect your WordPress with your Marfoof Store
 * Version:     1.0.0
 * Author:      Ahmed Salah
 * License:     GPLv2 or later
 * Text Domain: marfoof-connect
 */
define("MARFOOF_CONNECT_API_KEY", "marfoof-secret-static-key-here-123");

// Include user export functionality
require_once plugin_dir_path(__FILE__) . "users-export.php";

// Include products export functionality
require_once plugin_dir_path(__FILE__) . "products-export.php";

// Include categories export functionality
require_once plugin_dir_path(__FILE__) . "categories-export.php";

// Exit if accessed directly.
if (!defined("ABSPATH")) {
    exit();
}

/**
 * Adds a new submenu item to the "Settings" menu.
 */
function marfoof_connect_add_settings_page()
{
    add_options_page(
        "Marfoof Connect Settings", // Page title
        "Marfoof Connect", // Menu title
        "manage_options", // Capability required to access the page
        "marfoof-connect-settings", // Unique menu slug
        "marfoof_connect_render_settings_page", // Callback function to render the page content
    );
}

/**
 * Registers the custom REST API endpoint for users with a key.
 */
function marfoof_connect_register_api_route()
{
    register_rest_route("marfoof-connect/v1", "/export", [
        "methods" => "GET",
        "callback" => "marfoof_connect_export_users",
        "permission_callback" => "marfoof_connect_check_api_key",
    ]);
    
    register_rest_route("marfoof-connect/v1", "/export/products", [
        "methods" => "GET",
        "callback" => "marfoof_connect_export_products",
        "permission_callback" => "marfoof_connect_check_api_key",
    ]);
    
    register_rest_route("marfoof-connect/v1", "/export/categories", [
        "methods" => "GET",
        "callback" => "marfoof_connect_export_categories",
        "permission_callback" => "marfoof_connect_check_api_key",
    ]);
}

/**
 * Renders the content for the settings page.
 */
function marfoof_connect_render_settings_page()
{
    if (!current_user_can("manage_options")) {
        return;
    }

    // Get saved Node.js server URL
    $node_server_url = get_option('marfoof_node_server_url', 'http://localhost:5000');
    ?>

    <div class="wrap">
        <h1>Marfoof Connect - Export & Send to Server</h1>
        
        <style>
            .marfoof-container {
                display: flex;
                flex-direction: column;
                gap: 15px;
                max-width: 600px;
                margin: 20px 0;
            }
            .marfoof-btn {
                display: inline-block;
                padding: 12px 24px;
                background: #2271b1;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                border: none;
                font-size: 16px;
                cursor: pointer;
                text-align: center;
                width: 200px;
            }
            .marfoof-btn:hover {
                background: #135e96;
            }
            .marfoof-btn:disabled {
                background: #cccccc;
                cursor: not-allowed;
            }
            .result-box {
                padding: 10px;
                margin: 10px 0;
                border-radius: 4px;
                display: none;
            }
            .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
            .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
            .loading { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
            .settings-form {
                background: #fff;
                padding: 20px;
                border: 1px solid #ccd0d4;
                margin-top: 30px;
                max-width: 600px;
            }
            .settings-form input {
                width: 100%;
                padding: 8px;
                margin: 5px 0 15px 0;
            }
        </style>

        <div class="marfoof-container">
            <button class="marfoof-btn" onclick="exportAndSend('users')" id="btn-users">
                Export & Send Users
            </button>
            <div id="result-users" class="result-box"></div>

            <button class="marfoof-btn" onclick="exportAndSend('products')" id="btn-products">
                Export & Send Products
            </button>
            <div id="result-products" class="result-box"></div>

            <button class="marfoof-btn" onclick="exportAndSend('categories')" id="btn-categories">
                Export & Send Categories
            </button>
            <div id="result-categories" class="result-box"></div>
        </div>

        <div class="settings-form">
            <h3>Node.js Server Settings</h3>
            <form id="server-settings-form">
                <label for="server-url">Server URL:</label>
                <input type="url" id="server-url" name="server_url" 
                       value="<?php echo esc_attr($node_server_url); ?>" 
                       placeholder="http://localhost:5000">
                
                <button type="submit" class="button button-primary">Save Settings</button>
                <span id="settings-result" style="margin-left: 10px;"></span>
            </form>
        </div>

        <script>
            
            // Save server settings
            document.getElementById('server-settings-form').onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'marfoof_save_server_settings',
                        'server_url': document.getElementById('server-url').value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    const resultEl = document.getElementById('settings-result');
                    if (data.success) {
                        resultEl.innerHTML = '<span style="color:green">✓ Settings saved</span>';
                    } else {
                        resultEl.innerHTML = '<span style="color:red">Error saving settings</span>';
                    }
                });
            };

            // Export and send function
            function exportAndSend(type) {
                const button = document.getElementById(`btn-${type}`);
                const resultEl = document.getElementById(`result-${type}`);
                
                button.disabled = true;
                button.innerHTML = 'Processing...';
                resultEl.className = 'result-box loading';
                resultEl.innerHTML = 'Exporting data from WordPress...';
                resultEl.style.display = 'block';
                
                let apiUrl = '';
                if (type === 'users') {
                    apiUrl = '<?php echo rest_url("marfoof-connect/v1/export"); ?>';
                } else if (type === 'products') {
                    apiUrl = '<?php echo rest_url("marfoof-connect/v1/export/products"); ?>';
                } else if (type === 'categories') {
                    apiUrl = '<?php echo rest_url("marfoof-connect/v1/export/categories"); ?>';
                }
                
                fetch(apiUrl, {
                    method: 'GET',
                    headers: {
                        'Authorization': 'Bearer <?php echo MARFOOF_CONNECT_API_KEY; ?>'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    resultEl.innerHTML = `Exported ${data.length || 1} ${type}. Sending to server...`;
                    
                    const serverUrl = document.getElementById('server-url').value;
                    return fetch(`${serverUrl}/import/${type}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-API-Key': 'marfoof-test-key-123' // Example server API key
                        },
                        body: JSON.stringify(data)
                    });
                })
                .then(serverResponse => {
                    if (!serverResponse.ok) {
                        throw new Error(`Server error! status: ${serverResponse.status}`);
                    }
                    return serverResponse.json();
                })
                .then(serverResult => {
                    resultEl.className = 'result-box success';
                    resultEl.innerHTML = `Success! ${type} exported and sent to server.<br>
                                         ${serverResult.message || 'Data saved on server.'}`;
                })
                .catch(error => {
                    resultEl.className = 'result-box error';
                    resultEl.innerHTML = `Error: ${error.message}`;
                    console.error('Error:', error);
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = type === 'users' ? 'Export & Send Users' 
                                   : type === 'products' ? 'Export & Send Products'
                                   : 'Export & Send Categories';
                });
            }
        </script>
    </div>

    <?php
}

// AJAX handler for saving server settings
add_action('wp_ajax_marfoof_save_server_settings', 'marfoof_connect_save_server_settings');

function marfoof_connect_save_server_settings() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $server_url = sanitize_text_field($_POST['server_url']);
    update_option('marfoof_node_server_url', $server_url);
    
    wp_send_json_success(array('message' => 'Server settings saved'));
}

function marfoof_connect_settings_link($actions, $plugin_file)
{
    // Get the base name of your plugin file
    $plugin_base = plugin_basename(__FILE__);

    // Check if the current plugin is yours
    if ($plugin_base === $plugin_file) {
        // Create the URL for the settings page
        $settings_url = add_query_arg(
            "page",
            "marfoof-connect-settings",
            admin_url("options-general.php"),
        );

        // Create the HTML for the link
        $settings_link =
            '<a href="' .
            esc_url($settings_url) .
            '">' .
            __("Settings", "marfoof-connect") .
            "</a>";

        // Add the link to the beginning of the actions array
        array_unshift($actions, $settings_link);
    }

    return $actions;
}

/**
 * Checks for a valid static API key in the Authorization header.
 */
function marfoof_connect_check_api_key(WP_REST_Request $request)
{
    $auth_header = $request->get_header("Authorization");

    // Check if the Authorization header exists and starts with "Bearer "
    if ($auth_header && strpos($auth_header, "Bearer ") === 0) {
        $key = substr($auth_header, 7);
        if ($key === MARFOOF_CONNECT_API_KEY) {
            return true;
        }
    }

    // If the check fails, return a forbidden error
    return new WP_Error(
        "rest_forbidden",
        __("Invalid or missing Authorization header.", "marfoof-connect"),
        ["status" => 401],
    );
}

add_action("admin_menu", "marfoof_connect_add_settings_page");
add_filter("plugin_action_links", "marfoof_connect_settings_link", 10, 2);
add_action("rest_api_init", "marfoof_connect_register_api_route");