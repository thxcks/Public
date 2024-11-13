<?php
/*
 * Directory Permission Manager
 *
 * This PHP script allows users on a cPanel server to browse and manage file permissions 
 * within their home directory. It provides a simple web interface where users can navigate 
 * through directories and set permissions on files and folders without needing command-line access.
 *
 * Features:
 * - **Directory Browser**: Users can navigate through directories within their home folder.
 * - **Set Permissions**: Provides buttons to set permissions separately for directories and files within the selected folder. 
 *   Sets 0755 permissions for directories and 0644 for files.
 * - **Up a Level Navigation**: Includes a button to move one level up in the directory tree, 
 *   restricted to the user's home directory.
 * - **Dynamic Notifications**: Displays a confirmation message in the interface when permissions 
 *   are successfully set, enhancing user feedback.
 *
 * Usage Instructions:
 * - **Uploading the Script**: Place this PHP file anywhere within your cPanel home directory 
 *   (e.g., /home/username/public_html).
 * - **Navigating**: Use the "Browse Directories" section to navigate to a target directory. The "Up a Level" 
 *   button allows moving one directory up without exiting the home directory.
 * - **Setting Permissions**: Once in the desired directory, click either the "Set Directories" button to apply 
 *   0755 permissions to directories or the "Set Files" button to apply 0644 permissions to files within that directory.
 * - **Security Restrictions**: The script restricts access to directories outside of the user's home 
 *   directory, enhancing security.
 *
 * Requirements:
 * - **cPanel Environment**: This script is optimized for cPanel servers, where each user has a 
 *   separate home directory.
 * - **PHP Executable Permissions**: Ensure that your server allows PHP scripts to execute shell 
 *   commands like `find` and `chmod`, as these are required for setting permissions.
 */

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

    // Display a message before deleting
    echo "<p>Destroying tool...</p>";

    // Delete the file itself
    if (unlink($filePath)) {
        echo "<p>Tool destroyed successfully.</p>";
    } else {
        echo "<p>Failed to destroy tool.</p>";
    }

    // Stop further execution
    exit;
}

$message = '';

$baseDir = realpath(__DIR__ . '/..');
$currentDir = isset($_GET['dir']) ? realpath($baseDir . '/' . $_GET['dir']) : $baseDir;

if (!$currentDir || strpos($currentDir, $baseDir) !== 0) {
    $currentDir = $baseDir;
}

$currentDirName = basename($currentDir);
$parentDir = realpath($currentDir . '/..');
$upLevelPath = ($parentDir && strpos($parentDir, $baseDir) === 0 && $currentDir !== $baseDir)
    ? str_replace($baseDir, '', $parentDir)
    : '';

function listDirectory($dir) {
    if (!$dir || !is_dir($dir)) {
        echo "Directory not found or invalid.";
        return;
    }

    $items = array_diff(scandir($dir), array('.', '..'));
    echo "<ul class='directories'>";
    foreach ($items as $item) {
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            $relativePath = str_replace(realpath($GLOBALS['baseDir']), '', realpath($path));
            echo "<li><a href='?dir=" . urlencode($relativePath) . "'>$item/</a></li>";
        }
    }
    echo "</ul>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedDir = realpath($baseDir . '/' . $_POST['path']);
    
    if (strpos($selectedDir, $baseDir) !== 0) {
        die("Access denied.");
    }

    if (isset($_POST['set_directories'])) {
        $cmdDirectories = "find $selectedDir -type d -exec chmod 0755 {} \;";
        exec($cmdDirectories, $outputDirectories, $returnCodeDirectories);
        $message = "Directory permissions updated to 0755 for $selectedDir";
    } elseif (isset($_POST['set_files'])) {
        $cmdFiles = "find $selectedDir -type f -exec chmod 0644 {} \;";
        exec($cmdFiles, $outputFiles, $returnCodeFiles);
        $message = "File permissions updated to 0644 for $selectedDir";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Directory Permission Manager</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f7f7f7; }
        .container {
            display: flex;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            gap: 20px; /* Adds spacing between sections */
        }
        #main-content, #instructions {
            flex: 1;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0px 0px 8px rgba(0, 0, 0, 0.1);
        }
        #instructions { border-left: 2px solid #ddd; }
        h2 { color: #333; margin-bottom: 20px; font-size: 22px; }
        .directories { list-style-type: none; padding: 0; }
        .directories li { margin: 5px 0; }
        .directories a { color: #007bff; text-decoration: none; }
        .directories a:hover { text-decoration: underline; }
        button { padding: 10px 20px; font-size: 14px; margin: 5px; cursor: pointer; border: none; border-radius: 4px; }
        .button-set-dir { background-color: #28a745; color: #fff; }
        .button-set-file { background-color: #007bff; color: #fff; }
        #message { font-size: 16px; color: #333; margin-top: 15px; padding: 10px; background-color: #e6ffe6; border: 1px solid #b3ffb3; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div id="main-content">
            <h2>Directory Permission Manager</h2>
            
            <!-- Up a Level Button -->
            <p><a href="?dir=<?php echo urlencode($upLevelPath); ?>" class="button">⬆️ Up a Level</a></p>
            
            <p class="description">Current Directory: <?php echo htmlspecialchars($currentDir); ?></p>

            <!-- Directory browsing section -->
            <div>
                <h3>Browse Directories:</h3>
                <?php listDirectory($currentDir); ?>
            </div>

            <!-- Form to set permissions -->
            <div>
                <h3>Set Permissions for <?php echo htmlspecialchars($currentDirName); ?>:</h3>
                <form method="POST">
                    <input type="hidden" name="path" value="<?php echo str_replace(realpath($baseDir), '', realpath($currentDir)); ?>">
                    <button type="submit" name="set_directories" class="button-set-dir">Set Directories to 0755</button>
                    <button type="submit" name="set_files" class="button-set-file">Set Files to 0644</button>
                </form>
                
                <?php if ($message): ?>
                    <div id="message"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div id="instructions">
            <h3>Instructions</h3>
            <p>Use this tool to browse directories within your home folder and set file and directory permissions.</p>
            <p>The user’s PHP execution settings must allow use of exec(), and find commands should be accessible on the server.</p>
            <ul>
             <li><b>Current Directory:</b> Shows the directory you’re currently in.</li>
             <li><b>⬆️ Up a Level:</b> Click to go up one level in the directory tree. If you're already at the top level, it will simply refresh the page.</li>
             <li><b>Browse Directories:</b> Click any folder name to navigate to it.</li>
             <li><b>Set Permissions:</b> Click the button to set permissions to 0755 for directories and 0644 for files within the current folder.</li>
            </ul>
            <p>Note: Only files and folders within your home directory are accessible.</p>
        </div>
    </div>
<!-- HTML Form with the "Destroy" button -->
<center>        
    <form method="post">
    <button type="submit" name="destroy" onclick="return confirm('Are you sure? This action is irreversible.')">
        Finished? Destroy tool now
    </button>
    </form>
</center>
</body>
</html>
