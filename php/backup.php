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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup'])) {
    $excludeWpContent = isset($_POST['exclude_wp_content']);
    $excludeUploads = isset($_POST['exclude_uploads']);

    // Define backup directory and zip file path
    $backupDir = __DIR__;
    $zipFilePath = $backupDir . '/backup.zip';

    // Load database credentials from wp-config.php
    $wpConfig = file_get_contents($backupDir . '/wp-config.php');
    preg_match("/define\('DB_NAME', '(.*)'\);/", $wpConfig, $dbname);
    preg_match("/define\('DB_USER', '(.*)'\);/", $wpConfig, $dbuser);
    preg_match("/define\('DB_PASSWORD', '(.*)'\);/", $wpConfig, $dbpass);
    preg_match("/define\('DB_HOST', '(.*)'\);/", $wpConfig, $dbhost);

    $dbname = $dbname[1];
    $dbuser = $dbuser[1];
    $dbpass = $dbpass[1];
    $dbhost = $dbhost[1];

    // Dump the MySQL database
    $dumpFile = $backupDir . '/database_backup.sql';
    $dumpCommand = "mysqldump --user='$dbuser' --password='$dbpass' --host='$dbhost' $dbname > $dumpFile";
    shell_exec($dumpCommand);

    // Create a new ZipArchive
    $zip = new ZipArchive();
    if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        echo '<div class="error-message">Unable to create zip file.</div>';
        exit;
    }

    // Add files and folders to the zip archive, excluding specified directories
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($backupDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($files as $file) {
        $filePath = realpath($file);
        $relativePath = substr($filePath, strlen($backupDir) + 1);

        // Exclude directories based on user selection
        if (($excludeWpContent && strpos($relativePath, 'wp-content') === 0) ||
            ($excludeUploads && strpos($relativePath, 'wp-content/uploads') === 0)) {
            continue;
        }

        if (is_dir($filePath)) {
            $zip->addEmptyDir($relativePath);
        } else {
            $zip->addFile($filePath, $relativePath);
        }
    }

    // Add the database dump file
    $zip->addFile($dumpFile, 'database_backup.sql');
    $zip->close();

    // Provide the download link and clean up
    echo '<div class="success-message">Backup created successfully. <a href="backup.zip" download>Download Backup</a></div>';

    // Clean up: Delete temporary files
    unlink($dumpFile);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wordpress Backup Tool</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f7f7f7; text-align: center; }
        #container { max-width: 600px; margin: 50px auto; padding: 20px; background: #fff; border-radius: 10px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); }
        h1 { font-size: 24px; color: #333; margin-bottom: 20px; }
        button { padding: 8px 16px; font-size: 16px; cursor: pointer; border: none; border-radius: 5px; background-color: #007bff; color: #fff; }
        button:disabled { background-color: #ccc; cursor: not-allowed; }
        .success-message { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; margin: 20px 0; border-radius: 5px; }
        .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; margin: 20px 0; border-radius: 5px; }
        label { font-weight: bold; color: #333; display: block; margin: 15px 0 5px; text-align: left; }
        input[type="checkbox"] { margin-right: 10px; }
        .info-box { padding: 10px; background-color: #e9ecef; border: 1px solid #ced4da; border-radius: 5px; margin-bottom: 20px; color: #333; }
    </style>
</head>
<body>
    <div id="container">
        <h1>Wordpress Backup Tool</h1>
        
        <!-- Display Domain and File Location Information -->
        <div class="info-box">
            <p><strong>Domain:</strong> <?php echo $_SERVER['SERVER_NAME']; ?></p>
            <p><strong>File Location:</strong> <?php echo __DIR__; ?></p>
        </div>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <label><input type="checkbox" name="exclude_wp_content"> Exclude wp-content directory</label>
            <label><input type="checkbox" name="exclude_uploads"> Exclude uploads directory</label>
            <button type="submit" name="backup">Create Backup</button>
        </form>
        
        <!-- Destroy Tool Button -->
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <button type="submit" name="destroy" onclick="return confirm('Are you sure? This action is irreversible.')">
                Finished? Destroy tool now
            </button>
        </form>
    </div>
</body>
</html>
