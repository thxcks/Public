<?php

// Define the lock file path
$lockFilePath = __DIR__ . '/ip_lock.lock';

// Get the visitor's IP address
$visitorIp = $_SERVER['REMOTE_ADDR'];

// Check if the lock file exists
if (!file_exists($lockFilePath)) {
    // If not, create it and store the current visitor's IP
    file_put_contents($lockFilePath, $visitorIp);
}

// Read the locked IP from the file
$lockedIp = file_get_contents($lockFilePath);

// Check if the current visitor's IP matches the locked IP
if (trim($lockedIp) !== $visitorIp) {
    // Deny access for other IPs
    exit("Access denied. This tool is locked to another IP.");
}

if (isset($_POST['destroy'])) {
    // Get the current file path
    $filePath = __FILE__;
    $lockFilePath = dirname(__FILE__) . '/ip_lock.lock';

    echo "
    <style>
        body { font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        #message-container { text-align: center; background-color: #fff; padding: 40px; border-radius: 8px; box-shadow: 0px 0px 8px rgba(0, 0, 0, 0.1); max-width: 400px; }
        .success-icon { font-size: 50px; color: #28a745; margin-bottom: 15px; }
        h2 { color: #333; font-size: 22px; margin: 0; }
        p { color: #666; font-size: 16px; margin-top: 5px; }
    </style>
    <div id='message-container'>";

    // Display success icon and friendly message
    echo "<div class='success-icon'>✓</div>";

    // Delete the ip_lock.lock file
    if (file_exists($lockFilePath) && unlink($lockFilePath)) {
        echo "<h2>Cleanup Successful</h2>";
        echo "<p>The lock file was removed successfully.</p>";
    } else {
        echo "<h2>Cleanup Partial</h2>";
        echo "<p>The lock file was not found or couldn't be removed.</p>";
    }

    // Delete the file itself
    if (unlink($filePath)) {
        echo "<h2>Tool Deleted</h2>";
        echo "<p>This tool has been removed from the system.</p>";
    } else {
        echo "<h2>Deletion Failed</h2>";
        echo "<p>There was an issue deleting this tool.</p>";
    }

    echo "</div>";

    // Stop further execution
    exit;
}

// Function to retrieve PHP INI settings.
function get_php_ini_settings() {
    return [
        'Memory Limit' => ini_get('memory_limit'),
        'Post Max Size' => ini_get('post_max_size'),
        'Upload Max Filesize' => ini_get('upload_max_filesize'),
        'Max Execution Time' => ini_get('max_execution_time'),
        'Max Input Time' => ini_get('max_input_time'),
        'Max Input Vars' => ini_get('max_input_vars'),
    ];
}

// Function to get loaded PHP extensions.
function get_loaded_extensions_list() {
    return implode(', ', get_loaded_extensions());
}

function display_server_info() {
    return [
        'Server Environment' => [
            'Operating System' => php_uname('s') . ' ' . php_uname('r'),
            'Web Server' => $_SERVER['SERVER_SOFTWARE'],
            'PHP Handler' => php_sapi_name(),
            'cURL Version' => function_exists('curl_version') ? curl_version()['version'] : 'Not Available',
            'Is SUHOSIN Installed?' => extension_loaded('suhosin') ? 'Yes' : 'No',
            'Is Imagick Available?' => extension_loaded('imagick') ? 'Yes' : 'No',
            'Pretty Permalinks Supported' => function_exists('got_mod_rewrite') && got_mod_rewrite() ? 'Yes' : 'No',
            '.htaccess Rules' => file_exists('.htaccess') ? file_get_contents('.htaccess') : 'Not Available',
            'Current Time' => date('Y-m-d H:i:s'),
            'Current UTC Time' => gmdate('Y-m-d H:i:s'),
            'Current Server Time' => date('Y-m-d H:i:s', time()),
        ],
        'Document Root' => $_SERVER['DOCUMENT_ROOT'],
        'PHP Version' => PHP_VERSION,
        'Loaded Extensions' => get_loaded_extensions_list(),
        'PHP INI Settings' => get_php_ini_settings(),
        'Database Info' => get_database_info(),
        'SSL Status' => get_ssl_info(),
        'WordPress Info' => file_exists('wp-config.php') ? get_wordpress_info() : 'Not Available',
        'Server IP' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
        'Client IP' => $_SERVER['REMOTE_ADDR'],
    ];
}

// Function to render HTML output.
function render_html($server_info) {

    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Server Info Tool</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body>
        <div class='container mt-5'>
            <h1 class='text-center mb-4'>Server Info Tool</h1>
            <div class='accordion' id='serverInfoAccordion'>";
    
    foreach ($server_info as $section => $info) {
        $id = strtolower(str_replace(' ', '-', $section));
        echo "<div class='accordion-item'>
                <h2 class='accordion-header' id='heading-{$id}'>
                    <button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#collapse-{$id}' aria-expanded='false' aria-controls='collapse-{$id}'>
                        {$section}
                    </button>
                </h2>
                <div id='collapse-{$id}' class='accordion-collapse collapse' aria-labelledby='heading-{$id}' data-bs-parent='#serverInfoAccordion'>
                    <div class='accordion-body'>";
        
        if (is_array($info)) {
            echo "<table class='table table-striped'>";
            if ($section === 'Database Info' && !file_exists('wp-config.php')) {
                echo "<form method='POST' class='mb-4'>
                        <div class='mb-3'>
                            <label for='hostname' class='form-label'>Hostname</label>
                            <input type='text' class='form-control' id='hostname' name='hostname' required>
                        </div>
                        <div class='mb-3'>
                            <label for='username' class='form-label'>Username</label>
                            <input type='text' class='form-control' id='username' name='username' required>
                        </div>
                        <div class='mb-3'>
                            <label for='password' class='form-label'>Password</label>
                            <input type='password' class='form-control' id='password' name='password'>
                        </div>
                        <div class='mb-3'>
                            <label for='database' class='form-label'>Database Name</label>
                            <input type='text' class='form-control' id='database' name='database' required>
                        </div>
                        <button type='submit' class='btn btn-primary'>Submit</button>
                      </form>";
            }
            foreach ($info as $key => $value) {
                if (is_array($value)) {
                    if ($key === 'WP-Config Settings' || $key === 'Active Plugins') {
                        $value = implode('<br>', array_map(function($k, $v) {
                            return "{$k}: {$v}";
                        }, array_keys($value), $value));
                    } elseif ($key === 'Active Theme') {
                        $value = implode('<br>', $value);
                    } else {
                        $value = implode(', ', $value);
                    }
                }
                if ($key === '.htaccess Rules') {
                    echo "<tr><th>{$key}</th><td><pre style='max-height: 200px; overflow-y: auto;'>{$value}</pre></td></tr>";
                } else {
                    echo "<tr><th>{$key}</th><td>{$value}</td></tr>";
                }
            }
            echo "</table>";
        } else {
            echo "<p>{$info}</p>";
        }

        echo "      </div>
                </div>
            </div>";
    }

    echo "      </div>
        </div>
        
        <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'></script>
            </body>
    </html>";
}

function get_database_info() {
    $db_info = [
        'MySQL Version' => 'Not Available',
        'Database Username' => $username,
        'Database Host' => $hostname,
        'Database Name' => $database,
        'Table Prefix' => $wpdb->prefix ?? 'Unknown',
        'Database Charset' => $wpdb->charset ?? 'Unknown',
        'Database Collation' => $wpdb->collate ?? 'Unknown',
        'Max Allowed Packet Size' => 'Unknown',
        'Connection Test' => 'Failed',
        'Database Size' => 'Unknown',
        'Table Count' => 0,
        'Default Storage Engine' => 'Unknown',
    ];

    $mysqli = null;

    $hostname = '127.0.0.1';
    $username = '';
    $password = '';
    $database = '';

    if (file_exists('wp-config.php')) {
        $config = file_get_contents('wp-config.php');
        preg_match("/define\(\s*'DB_USER',\s*'([^']+)'\s*\);/", $config, $user_match);
        preg_match("/define\(\s*'DB_PASSWORD',\s*'([^']+)'\s*\);/", $config, $pass_match);
        preg_match("/define\(\s*'DB_NAME',\s*'([^']+)'\s*\);/", $config, $name_match);

        $username = $user_match[1] ?? '';
        $password = $pass_match[1] ?? '';
        $database = $name_match[1] ?? '';
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $database = $_POST['database'] ?? '';
        }
    }

    if ($hostname && $username && $database) {
        $mysqli = new mysqli($hostname, $username, $password, $database);

        if ($mysqli->connect_error) {
            $db_info['Connection Test'] = 'Failed: ' . $mysqli->connect_error;
            return $db_info;
        }

        if ($mysqli) {
            $db_info['Connection Test'] = 'Successful';
            $db_info['MySQL Version'] = $mysqli->server_info;

            $result = $mysqli->query("SHOW VARIABLES LIKE 'max_allowed_packet'");
            if ($result) {
                $row = $result->fetch_assoc();
                $db_info['Max Allowed Packet Size'] = $row['Value'] ?? 'Unknown';
            }

            $result = $mysqli->query("SHOW VARIABLES LIKE 'storage_engine'");
            if ($result) {
                $row = $result->fetch_assoc();
                $db_info['Default Storage Engine'] = $row['Value'] ?? 'Unknown';
            }

            $result = $mysqli->query("SELECT table_schema AS 'Database', 
                                             ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)', 
                                             COUNT(*) AS 'Tables' 
                                      FROM information_schema.tables 
                                      WHERE table_schema = '$database' 
                                      GROUP BY table_schema");

            if ($result) {
                $row = $result->fetch_assoc();
                $db_info['Database Size'] = $row['Size (MB)'] . ' MB';
                $db_info['Table Count'] = $row['Tables'];
            }

            $mysqli->close();
        }
    }

    return $db_info;
}
function get_ssl_info() {
    $ssl_info = [
        'Status' => 'Disabled',
        'Issuer' => 'Unknown',
        'Expiry Date' => 'Unknown',
        'Protocols Enabled' => 'Unknown',
    ];

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $ssl_info['Status'] = 'Enabled';

        $stream_context = stream_context_create(["ssl" => ["capture_peer_cert" => true]]);
        $client = stream_socket_client("ssl://{$_SERVER['HTTP_HOST']}:443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $stream_context);
        if ($client) {
            $cont = stream_context_get_params($client);
            $cert = openssl_x509_parse($cont["options"]["ssl"]["peer_certificate"]);

            if ($cert) {
                $ssl_info['Issuer'] = $cert['issuer']['O'] ?? 'Unknown';
                $ssl_info['Expiry Date'] = date('Y-m-d H:i:s', $cert['validTo_time_t']);
            }

            $ssl_info['Protocols Enabled'] = implode(', ', array_filter([
                'TLS 1.0' => defined('STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT'),
                'TLS 1.1' => defined('STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT'),
                'TLS 1.2' => defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT'),
                'TLS 1.3' => defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT'),
            ], function ($method) {
                return $method;
            }));
        }
    }

    return $ssl_info;
}
function get_wordpress_info() {
    $wp_info = [
        'WordPress Version' => 'Unknown',
        'WP-Config Settings' => [],
        'Debug Mode' => 'Disabled',
        'Active Theme' => 'Unknown',
        'Active Plugins' => [],
    ];

    if (file_exists('wp-config.php')) {
        // Load WordPress environment
        if (file_exists('wp-load.php')) {
            require_once('wp-load.php');
        } else {
            return ['Error' => 'WordPress environment not loaded.'];
        }

        // Get WordPress version
        $wp_info['WordPress Version'] = get_bloginfo('version');

        // Get WP-Config settings
        $wp_info['WP-Config Settings'] = [
            'DB Name' => DB_NAME,
            'DB User' => DB_USER,
            'DB Host' => DB_HOST,
        ];

        // Check if debug mode is enabled
        $wp_info['Debug Mode'] = defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled';

        // Get active theme
        $theme = wp_get_theme();
        $wp_info['Active Theme'] = [
            'Name' => $theme->get('Name'),
            'Version' => $theme->get('Version')
        ];

        // Get active plugins
        $active_plugins = get_option('active_plugins');
        foreach ($active_plugins as $plugin) {
            $plugin_path = WP_PLUGIN_DIR . '/' . $plugin;
            if (file_exists($plugin_path)) {
                $plugin_data = get_file_data($plugin_path, ['Name' => 'Plugin Name', 'Version' => 'Version']);
                $wp_info['Active Plugins'][] = $plugin_data['Name'] . ' ' . $plugin_data['Version'];
            }
        }
    }

    return $wp_info;
}

$server_info = display_server_info();
render_html($server_info);

?>

<html>
<br>    
<div class="d-flex justify-content-center gap-3">
    <button id="downloadPage" class="btn btn-primary">Download This Page</button>
    <form method="post">
        <button 
            type="submit" 
            name="destroy" 
            onclick="return confirm('Are you sure? This action is irreversible.');" 
            class="btn btn-danger"
        >
            Finished? Destroy tool now
        </button>
    </form>
</div>
    

    
    <script>
        document.getElementById('downloadPage').addEventListener('click', function () {
            // Get the entire current page's HTML
            const pageHTML = document.documentElement.outerHTML;

            // Create a Blob with the HTML content
            const blob = new Blob([pageHTML], { type: 'text/html' });

            // Create a link element
            const a = document.createElement('a');

            // Set the download attribute with the file name
            a.download = 'webpage.html';

            // Create an object URL for the Blob
            a.href = URL.createObjectURL(blob);

            // Simulate a click on the link to trigger download
            a.click();

            // Revoke the object URL to free memory
            URL.revokeObjectURL(a.href);
        });
    </script>
    
</html>
