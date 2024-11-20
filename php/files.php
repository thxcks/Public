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

function humanReadableSize($bytes, $decimals = 2) {
    $size = ['B', 'KB', 'MB', 'GB', 'TB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $size[$factor];
}

function getLargestFilesAndDirs($dir, $limit = 10) {
    $files = [];
    $dirs = [];

    $ignoredDirs = ['.cagefs', '.cl.selector', '.cpanel', '.cphorde', '.htpasswds', '.kapps', '.softaculous', '.spamassassin', 'etc', 'logs', 'lscache', 'ssl', 'tmp'];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            function ($current, $key, $iterator) use ($ignoredDirs) {
                if ($current->isDir() && in_array($current->getFilename(), $ignoredDirs)) {
                    return false;
                }
                return true;
            }
        ),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $path) {
        if ($path->isFile()) {
            $files[$path->getPathname()] = $path->getSize();
        } elseif ($path->isDir()) {
            $size = 0;
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path->getPathname(), RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
                $size += $file->getSize();
            }
            $dirs[$path->getPathname()] = $size;
        }
    }

    arsort($files);
    arsort($dirs);

    return [
        'files' => array_slice($files, 0, $limit, true),
        'dirs' => array_slice($dirs, 0, $limit, true),
    ];
}

function getSubdirectoryInodeUsage($dir) {
    $output = [];
    foreach (glob(rtrim($dir, '/') . '/*') as $subdir) {
        if (is_dir($subdir)) {
            $count = shell_exec("find '$subdir' | wc -l");
            $output[] = [$subdir, trim($count) . ' inodes'];
        }
    }
    return $output;
}

function getInodeUsage($dir) {
    $output = [];
    $output = [];
    foreach (glob(rtrim($dir, '/') . '/*') as $subdir) {
        if (is_dir($subdir)) {
            $count = shell_exec("find '$subdir' | wc -l");
            $output[] = [$subdir, trim($count) . ' inodes'];
        }
    }
    return $output;
}

if (isset($_GET['dir'])) {
    $dir = $_GET['dir'];
    $inodeUsage = getSubdirectoryInodeUsage($dir);
    echo json_encode($inodeUsage);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = get_current_user();
    $directory = "/home/$user/";
    $largest = getLargestFilesAndDirs($directory);
    $inodeUsage = getInodeUsage($directory);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Analyzer</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function fetchSubdirectoryInodes(element, dir) {
            const parentRow = element.closest('tr');
            const nextRow = parentRow.nextElementSibling;
            if (nextRow && nextRow.classList.contains('subdir-row')) {
                nextRow.remove();
                return;
            }

            fetch('?dir=' + encodeURIComponent(dir))
            .then(response => response.json())
            .then(data => {
                let subdirTable = '<tr class="subdir-row"><td colspan="2"><table class="table table-striped"><thead><tr><th>Subdirectory</th><th>Inode Count</th></tr></thead><tbody>';
                data.forEach(row => {
                    subdirTable += `<tr><td><a href="#" onclick="fetchSubdirectoryInodes(this, '${row[0]}'); return false;">${row[0]}</a></td><td>${row[1]}</td></tr>`;
                });
                subdirTable += '</tbody></table></td></tr>';
                parentRow.insertAdjacentHTML('afterend', subdirTable);
            })
            .catch(error => console.error('Error fetching subdirectory inodes:', error));
        }
    </script>
<body>
    <div class="container mt-5">
        <div class="text-center">
            <h1>File Analyzer</h1>
            <div class="alert alert-info" role="alert">
                This tool analyzes the specified directory to find the largest files and directories, and checks inode usage. Click the "Start" button to begin the analysis.
            </div>
            <form method="post" class="mt-3 d-inline">
                <button type="submit" class="btn btn-primary">Start</button>
            </form>
            <?php if (isset($largest)): ?>
                <form method="post" class="mt-3 d-inline">
                    <button type="submit" class="btn btn-secondary">Retest</button>
                </form>
            <?php endif; ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="mt-3 d-inline">
                <button type="submit" name="destroy" class="btn btn-danger" onclick="return confirm('Are you sure? This action is irreversible.');">
                    Finished? Destroy tool now
                </button>
            </form>
        </div>
    </div>

    <?php if (isset($largest)): ?>
        <div class="container mt-4">
            <h2>Largest Files</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Size</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($largest['files'] as $file => $size): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($file); ?></td>
                            <td><?php echo humanReadableSize($size); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Largest Directories</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Directory</th>
                        <th>Size</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($largest['dirs'] as $dir => $size): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($dir); ?></td>
                            <td><?php echo humanReadableSize($size); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Inode Usage</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Directory</th>
                        <th>Inode Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inodeUsage as $row): ?>
                        <tr>
                        <td><a href="#" onclick="fetchSubdirectoryInodes(this, '<?php echo addslashes($row[0]); ?>'); return false;"><?php echo htmlspecialchars($row[0]); ?></a></td>
                        <td><?php echo htmlspecialchars($row[1]); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div id="subdir-inodes" class="mt-4"></div>
    <?php endif; ?>
</body>
</html>
