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
 * - **Set Permissions**: Provides a button to set permissions recursively within the selected folder. 
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
 * - **Setting Permissions**: Once in the desired directory, click the "Set Permissions" button to apply 
 *   0755 permissions to directories and 0644 permissions to files within that directory.
 * - **Security Restrictions**: The script restricts access to directories outside of the user's home 
 *   directory, enhancing security.
 *
 * Requirements:
 * - **cPanel Environment**: This script is optimized for cPanel servers, where each user has a 
 *   separate home directory.
 * - **PHP Executable Permissions**: Ensure that your server allows PHP scripts to execute shell 
 *   commands like `find` and `chmod`, as these are required for setting permissions.
 * 
 * Note: This script is designed for ease of use and does not include authentication, as it is intended 
 * for environments where users manage their own cPanel accounts.
 */

$message = '';

// Set the base directory to the user's home directory by navigating one level up from the script location
$baseDir = realpath(__DIR__ . '/..');
$currentDir = isset($_GET['dir']) ? realpath($baseDir . '/' . $_GET['dir']) : $baseDir;

// Validate $currentDir to ensure it's within $baseDir and not empty
if (!$currentDir || strpos($currentDir, $baseDir) !== 0) {
    $currentDir = $baseDir;
}

// Get the current directory name for display
$currentDirName = basename($currentDir);

// Calculate the "Up a Level" path, but ensure it does not go above $baseDir
$parentDir = realpath($currentDir . '/..');
$upLevelPath = ($parentDir && strpos($parentDir, $baseDir) === 0 && $currentDir !== $baseDir)
    ? str_replace($baseDir, '', $parentDir)
    : ''; // Keeps the user at $baseDir if already at home directory

// Function to list directories within the current directory
function listDirectory($dir) {
    if (!$dir || !is_dir($dir)) {
        echo "Directory not found or invalid.";
        return;
    }

    $items = array_diff(scandir($dir), array('.', '..'));
    echo "<ul>";
    foreach ($items as $item) {
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            $relativePath = str_replace(realpath($GLOBALS['baseDir']), '', realpath($path));
            echo "<li><a href='?dir=" . urlencode($relativePath) . "'>$item/</a></li>";
        }
    }
    echo "</ul>";
}

// If the form is submitted, set permissions in the selected directory
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedDir = realpath($baseDir . '/' . $_POST['path']);
    
    // Additional security check
    if (strpos($selectedDir, $baseDir) !== 0) {
        die("Access denied.");
    }

    // Commands to set permissions
    $cmdDirectories = "find $selectedDir -type d -exec chmod 0755 {} \;";
    $cmdFiles = "find $selectedDir -type f -exec chmod 0644 {} \;";

    // Execute commands
    exec($cmdDirectories, $outputDirectories, $returnCodeDirectories);
    exec($cmdFiles, $outputFiles, $returnCodeFiles);

    $message = "Permissions updated for $selectedDir";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Permission Fixer</title>
    <style>
        body { display: flex; }
        .content { width: 50%; padding: 20px; }
        .instructions { border-left: 1px solid #ccc; padding-left: 20px; }
        .notification { margin-top: 15px; padding: 10px; background-color: #e6ffe6; border: 1px solid #b3ffb3; color: #333; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="content">
        <h2>Current Directory: <?php echo htmlspecialchars($currentDir); ?></h2>
        
        <!-- Up a Level Button -->
        <p><a href="?dir=<?php echo urlencode($upLevelPath); ?>">⬆️ Up a Level</a></p>

        <!-- Directory browsing section -->
        <div>
            <h3>Browse Directories:</h3>
            <?php listDirectory($currentDir); ?>
        </div>

        <!-- Form to set permissions -->
        <div>
            <h3>Set Permissions <?php echo htmlspecialchars($currentDirName); ?>:</h3>
            <form method="POST">
                <input type="hidden" name="path" value="<?php echo str_replace(realpath($baseDir), '', realpath($currentDir)); ?>">
                <button type="submit">Set Permissions to 0755 (directories) / 0644 (files)</button>
            </form>
            
            <?php if ($message): ?>
                <div class="notification">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Instructions section -->
    <div class="content instructions">
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
</body>
</html>
