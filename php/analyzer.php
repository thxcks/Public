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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Header Analyzer</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { margin: 20px; }
        .result { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Email Header Analyzer</h1>
        <form method="post">
            <div class="form-group">
                <label for="headers">Paste Email Headers Here:</label>
                <textarea class="form-control" name="headers" id="headers" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Analyze</button>
        </form>

        <div class="result mt-4">
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['headers'])) {
            $headers = $_POST['headers'];
            $headers = preg_replace('/\r\n\s+/', ' ', $headers); // Unfold headers
            $lines = explode("\n", $headers);
            $parsedHeaders = [];
            foreach ($lines as $line) {
                if (preg_match('/^([a-zA-Z\-]+):\s*(.*)$/', $line, $matches)) {
                    $field = strtolower($matches[1]);
                    $value = $matches[2];
                    if (!isset($parsedHeaders[$field])) {
                        $parsedHeaders[$field] = $value;
                    } else {
                        $parsedHeaders[$field] .= ', ' . $value;
                    }
                }
            }
            $importantHeaders = ['received', 'from', 'to', 'subject', 'date', 'message-id', 'mime-version', 'content-type', 'dkim-signature', 'arc-seal'];
            $receivedHosts = [];

            // Extract hostnames from Received headers
            if (isset($parsedHeaders['received'])) {
                $receivedHeaders = explode(', ', $parsedHeaders['received']);
                foreach ($receivedHeaders as $received) {
                    if (preg_match('/from\s+([^\s]+)/i', $received, $matches)) {
                        $receivedHosts[] = $matches[1];
                    }
                }
            }
            $otherHeaders = [];

            // Display the email hops in a table
            if (!empty($receivedHosts)) {
                echo "<h2>Email Hops:</h2>";
                echo "<table class='table table-sm table-bordered'><thead><tr><th>Hop</th><th>Hostname</th></tr></thead><tbody>";
                $reversedHosts = array_reverse($receivedHosts);
                $highlightDomains = ['krystal.io', 'cloudhosting.uk', 'krystal.uk', 'mailchannels.net', 'strikemail.co.uk'];
                foreach ($reversedHosts as $index => $host) {
                    $bgClass = 'bg-danger'; // Default to red
                    foreach ($highlightDomains as $domain) {
                        if (strpos($host, $domain) !== false) {
                            $bgClass = 'bg-success'; // Change to green if domain matches
                            break;
                        }
                    }
                    echo "<tr class='{$bgClass}'><td>" . ($index + 1) . "</td><td>" . htmlspecialchars($host) . "</td></tr>";
                }
                echo "</tbody></table>";
            }

            echo "<h2>Important Headers:</h2>";
            echo "<table class='table table-sm table-bordered'><thead><tr><th>Header</th><th>Value</th></tr></thead><tbody>";
            function decodeHeader($header) {
                $decoded = imap_mime_header_decode($header);
                $result = '';
                foreach ($decoded as $part) {
                    $result .= $part->text;
                }
                return $result;
            }

            foreach ($importantHeaders as $key) {
                if (isset($parsedHeaders[$key])) {
                    $decodedValue = decodeHeader($parsedHeaders[$key]);
                    echo "<tr><td><strong>" . ucfirst($key) . ":</strong></td><td>" . htmlspecialchars($decodedValue) . "</td></tr>";
                }
            }
            echo "</tbody></table>";

            echo "<h2>Other Headers:</h2>";
            echo "<table class='table table-sm table-bordered'><thead><tr><th>Header</th><th>Value</th></tr></thead><tbody>";
            foreach ($parsedHeaders as $key => $value) {
                if (!in_array($key, $importantHeaders)) {
                    echo "<tr><td><strong>" . ucfirst($key) . ":</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
                }
            }
            echo "</tbody></table>";
        }
        ?>
    </div>
<center>        
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
</center>
</body>
</html>
