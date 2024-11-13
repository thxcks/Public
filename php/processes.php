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


// Load database details from wp-config.php
$config_file = dirname(__FILE__) . '/wp-config.php';

if (file_exists($config_file)) {
    $config_content = file_get_contents($config_file);

    // Extract DB credentials with flexible regex to handle spaces
    preg_match("/define\(\s*'DB_NAME',\s*'(.+?)'\s*\);/", $config_content, $db_name);
    preg_match("/define\(\s*'DB_USER',\s*'(.+?)'\s*\);/", $config_content, $db_user);
    preg_match("/define\(\s*'DB_PASSWORD',\s*'(.+?)'\s*\);/", $config_content, $db_password);
    preg_match("/define\(\s*'DB_HOST',\s*'(.+?)'\s*\);/", $config_content, $db_host);

    $db_name = $db_name[1] ?? null;
    $db_user = $db_user[1] ?? null;
    $db_password = $db_password[1] ?? null;
    $db_host = $db_host[1] ?? null;

    // Check if all values were successfully extracted
    if (!$db_name || !$db_user || !$db_password || !$db_host) {
        die("Error: Database credentials are missing or not loaded correctly from wp-config.php.");
    }
} else {
    die("wp-config.php not found.");
}

// Connect to the database using credentials from wp-config.php
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to fetch process data from MySQL's INFORMATION_SCHEMA.PROCESSLIST
function getProcessData($conn, $db_name) {
    $query = "SELECT * FROM INFORMATION_SCHEMA.PROCESSLIST WHERE db = '$db_name';";
    $result = $conn->query($query);
    $output = "<table class='process-table'>
                <tr>
                    <th>Process ID</th>
                    <th>User</th>
                    <th>Host</th>
                    <th>DB</th>
                    <th>Command</th>
                    <th>Time</th>
                    <th>State</th>
                    <th>Info</th>
                </tr>";
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $output .= "<tr>
                            <td>" . htmlspecialchars($row['ID']) . "</td>
                            <td>" . htmlspecialchars($row['USER']) . "</td>
                            <td>" . htmlspecialchars($row['HOST']) . "</td>
                            <td>" . htmlspecialchars($row['DB']) . "</td>
                            <td>" . htmlspecialchars($row['COMMAND']) . "</td>
                            <td>" . htmlspecialchars($row['TIME']) . "</td>
                            <td>" . htmlspecialchars($row['STATE']) . "</td>
                            <td>" . htmlspecialchars($row['INFO']) . "</td>
                        </tr>";
        }
    } else {
        $output .= "<tr><td colspan='8'>No active processes for this database.</td></tr>";
    }
    
    $output .= "</table>";
    return $output;
}

// Output process data as HTML for AJAX
if (isset($_GET['fetch_data'])) {
    echo getProcessData($conn, $db_name);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Monitor</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f7f7f7; }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        #instructions {
            margin-bottom: 20px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0px 0px 8px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #ddd;
        }
        h2 { color: #333; margin-bottom: 20px; font-size: 22px; }
        .process-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0px 0px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .process-table th, .process-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .process-table th {
            background-color: #f2f2f2;
        }
        .button-container {
            display: flex;
            align-items: center;
            gap: 10px;
    }
        .control-button {
           background-color: #007bff;
           color: #fff;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
    }
    .control-input {
        width: 60px;
        padding: 6px;
        text-align: center;
    }
    form {
        display: inline; /* Ensure form does not break line */
    }
    </style>
</head>
<body>

<div class="container">
    <div id="instructions">
        <h2>Database Process viewer</h2>
        <p>Use this page to monitor processes the processes in your Wordpress Database.</p>
        <p>This page refreshes automatically every 2 seconds by default</p>
		<p>Use the Start/Stop buttons to pause the refresh and review the data.</p>
    </div>

<div class="button-container">
    <button class="control-button" onclick="startRefresh()">Start</button>
    <button class="control-button" onclick="stopRefresh()">Stop</button>
    <label>Interval (seconds):</label>
    <input type="number" id="intervalInput" class="control-input" value="2" min="1" onchange="updateInterval()" />
    
    <form method="post" style="display: inline;">
        <button type="submit" name="destroy" class="control-button" onclick="return confirm('Are you sure? This action is irreversible.')">
            Finished? Destroy tool now
        </button>
    </form>
</div>

    <div id="main-content">
        <h2>Process Data</h2>
        <div id="process-data">
            <?php echo getProcessData($conn, $db_name); ?>
        </div>
    </div>
</div>

<script>
    let refreshInterval;
    let intervalSpeed = 2000; // Default to 2 seconds (2000 ms)

    // Function to fetch data and update the table
    function fetchProcessData() {
        fetch('?fetch_data=1')
            .then(response => response.text())
            .then(data => {
                document.getElementById('process-data').innerHTML = data;
            })
            .catch(error => console.error('Error fetching data:', error));
    }

    // Function to start the automatic refresh
    function startRefresh() {
        if (!refreshInterval) { // Ensure only one interval is running
            refreshInterval = setInterval(fetchProcessData, intervalSpeed);
            fetchProcessData(); // Immediately fetch data on start
        }
    }

    // Function to stop the automatic refresh
    function stopRefresh() {
        clearInterval(refreshInterval);
        refreshInterval = null; // Reset interval variable
    }

    // Update the refresh interval speed
    function updateInterval() {
        const newInterval = document.getElementById('intervalInput').value;
        intervalSpeed = newInterval * 1000; // Convert seconds to milliseconds

        // If the refresh is running, restart it with the new interval
        if (refreshInterval) {
            stopRefresh();
            startRefresh();
        }
    }

    // Start auto-refresh when the page loads
    window.onload = startRefresh;
</script>

</body>
</html>
