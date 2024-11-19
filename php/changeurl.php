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
    echo "<div class='success-icon'>&#10003;</div>";

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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change WordPress Domain</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f7f7f7; text-align: center; }
        #container { max-width: 600px; margin: 50px auto; padding: 20px; background: #fff; border-radius: 10px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); }
        h1 { font-size: 24px; color: #333; margin-bottom: 10px; }
        p.description { font-size: 14px; color: #666; margin-top: 0; margin-bottom: 20px; }
        .success-message, .error-message, .output-message { border-radius: 5px; padding: 10px; margin: 20px 0; text-align: center; }
        .success-message { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .output-message { background-color: #f0f0f0; color: #333; border: 1px solid #ddd; text-align: left; padding: 15px; font-family: monospace; }
        form { margin-bottom: 20px; }
        input[type="text"] { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ddd; border-radius: 5px; text-align: center; box-sizing: border-box; }
        input[readonly] { background-color: #f0f0f0; color: #888; }
        button { padding: 8px 12px; font-size: 14px; cursor: pointer; border: none; border-radius: 5px; background-color: #007bff; color: #fff; }
        button:disabled { background-color: #ccc; cursor: not-allowed; }
    </style>
</head>
<body>
    <div id="container">
        <h1>Change WordPress Domain</h1>
        <p class="description">Enter the new domain without including <code>http://</code> or <code>https://</code>.</p>
        <?php
        session_start();

        // Retrieve the current domain using WP-CLI and strip http:// or https://
        $current_domain_full = trim(shell_exec("wp option get siteurl"));
        $current_domain = preg_replace("(^https?://)", "", $current_domain_full);

        if (!$current_domain) {
            echo "<div class='error-message'>Error: Could not retrieve the current domain. Ensure WP-CLI is configured.</div>";
        }

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['convert']) && $current_domain) {
            $command = "wp search-replace 'http://$current_domain' 'https://$current_domain' --all-tables-with-prefix";
            exec($command . " 2>&1", $output, $status);

            if ($status === 0) {
                echo "<div class='success-message'>Content successfully converted to HTTPS for $current_domain.</div>";
            } else {
                echo "<div class='error-message'>Error: Unable to convert content to HTTPS. Check WP-CLI configuration.</div>";
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $current_domain) {
            $new_domain = filter_input(INPUT_POST, 'new_domain', FILTER_SANITIZE_URL);
            $new_domain = preg_replace("(^https?://)", "", $new_domain); // Strip http/https from input

            if ($new_domain) {
                $command = "wp search-replace '$current_domain' '$new_domain' --all-tables-with-prefix";
                exec($command . " 2>&1", $output, $status); // Capture both output and errors

                // Store messages in session
                if ($status === 0) {
                    $_SESSION['message'] = "<div class='success-message'>Domain successfully changed from $current_domain to $new_domain.</div>";
                } else {
                    $_SESSION['message'] = "<div class='error-message'>Error: Unable to change domain. Check WP-CLI configuration.</div>";
                }
                $_SESSION['output'] = "<div class='output-message'><strong>Instruction Output:</strong><br>" . implode("<br>", $output) . "</div>";
                $_SESSION['command'] = "<div class='output-message'><strong>Instruction Command:</strong> <br>$command</div>";

                // Redirect to avoid form resubmission
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                echo "<div class='error-message'>Invalid domain entry. Please try again.</div>";
            }
        }

        // Display messages from session
        if (isset($_SESSION['message'])) {
            echo $_SESSION['message'];
            echo $_SESSION['output'];
            echo $_SESSION['command'];
            // Clear messages from session
            unset($_SESSION['message'], $_SESSION['output'], $_SESSION['command']);
        }
        ?>
        
        <form method="post">
            <input type="text" name="current_domain" placeholder="Current Domain" value="<?= htmlspecialchars($current_domain); ?>" required readonly>
            <input type="text" name="new_domain" placeholder="New Domain" required>
            <button type="submit">Change Domain</button>
        </form>

        <h1>Convert Content from HTTP to HTTPS</h1>
        <p class="description">Ensure your content is served securely by converting all links to HTTPS.</p>
        
        <form method="post">
            <input type="text" name="http_domain" placeholder="HTTP Domain" value="<?= 'http://' . htmlspecialchars($current_domain); ?>" readonly>
            <input type="text" name="https_domain" placeholder="HTTPS Domain" value="<?= 'https://' . htmlspecialchars($current_domain); ?>" readonly>
            <button type="submit" name="convert">Convert to HTTPS</button>
        </form>
    </div>
    
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <button type="submit" name="destroy" onclick="return confirm('Are you sure? This action is irreversible.')">
                Finished? Destroy tool now
            </button>
        </form>
</body>
</html>
