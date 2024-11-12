<?php


$dashboardPassword = "";
session_start();

// Set session timeout to 30 minutes
$timeoutDuration = 1800;

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeoutDuration) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['LAST_ACTIVITY'] = time();

if (empty($dashboardPassword)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        $dashboardPassword = $_POST['password'];
        $fileContent = file_get_contents(__FILE__);
        $updatedContent = preg_replace(
            '/\$dashboardPassword = \".*?\";/',
            '\$dashboardPassword = "' . addslashes($dashboardPassword) . '";',
            $fileContent
        );
        file_put_contents(__FILE__, $updatedContent);
        $_SESSION['authenticated'] = true;
    } else {
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Set Dashboard Password</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        </head>
        <body>
            <div class="d-flex justify-content-center align-items-center" style="height: 100vh;">
                <div class="card p-4" style="width: 300px;">
                    <h4 class="card-title text-center mb-4">Set Password</h4>
                    <form method="POST">
                        <div class="mb-3">
                            <input type="password" name="password" class="form-control" placeholder="Password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Set Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </body>
        </html>';
        exit;
    }
} elseif (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    // User already authenticated
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $dashboardPassword) {
        $_SESSION['authenticated'] = true;
    }
}

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Login</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    </head>
    <body>
        <div class="d-flex justify-content-center align-items-center" style="height: 100vh;">
            <div class="card p-4" style="width: 300px;">
                <h4 class="card-title text-center mb-4">Enter Password</h4>
                <form method="POST">
                    <div class="mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </body>
    </html>';
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
            margin-bottom: 20px;
        }
        .control-button, .control-input {
            padding: 10px;
            font-size: 14px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .control-button { background-color: #007bff; color: #fff; }
        .control-input {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
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
