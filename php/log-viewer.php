<?php

/**
 * Log Viewer PHP Script
 * 
 * This script provides a simple interface to view, filter, sort, and paginate server log files.
 * Designed for analyzing Apache-style access logs in a specific directory structure.
 * 
 * Features:
 * - File selection from dropdown for multiple log files
 * - Filters for IP Address, Date Range, URL, Status Code, and User Agent
 * - Sorting by Date/Time, IP, URL, Status Code, and User Agent
 * - Pagination with 100 entries per page
 * - "Top Stats" for the most common IPs, URLs, and User Agents
 * 
 * Setup:
 * 1. Upload this script to your server in a directory that can access the log files.
 * 2. The script is designed for cPanel servers using /home/{username}/ layouts.
 * 3. Open the script in a browser to view logs, apply filters, and review statistics.
 * 
 * Requirements:
 * - PHP 7.0+
 * - Server with Apache-style access logs
 * - Proper read permissions for the log directory
 * 
 * Usage Example:
 * - Filter logs by IP or Date Range to analyze specific events.
 * - Use "Top Stats" to quickly identify frequent visitors, popular URLs, or common user agents.
 * 
 * Notes:
 * - Ensure logs follow Apache format; adjust parsing if logs are in a different format.
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

// Configuration
define('LOG_DIRECTORY', dirname(__DIR__) . '/access-logs');

// Helper: Parse Log Line
function parseLogLine($line) {
    preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}) - - \[(.*?)\] "(.*?)" (\d{3}) \d+ "(.*?)" "(.*?)"/', $line, $matches);
    if (count($matches) === 7) {
        return [
            'ip' => $matches[1],
            'datetime' => $matches[2],
            'request' => $matches[3],
            'status' => $matches[4],
            'referer' => $matches[5],
            'user_agent' => $matches[6]
        ];
    }
    return null;
}

// Convert datetime string from log format to timestamp
function logDatetimeToTimestamp($datetime) {
    return DateTime::createFromFormat('d/M/Y:H:i:s O', $datetime)->getTimestamp();
}

// Ensure logs array is initialized
$logs = [];

// Get Log Files
$logFiles = glob(LOG_DIRECTORY . '/*');
$currentFile = $_GET['file'] ?? ($logFiles[0] ?? null);

if ($currentFile && file_exists($currentFile)) {
    // Read and Parse Logs
    foreach (file($currentFile) as $line) {
        $parsedLine = parseLogLine($line);
        if ($parsedLine) {
            $logs[] = $parsedLine; // Only add parsed lines
        }
    }
}

// Get filter parameters
$ipFilter = $_GET['filter']['ip'] ?? '';
$requestFilter = $_GET['filter']['request'] ?? '';
$statusFilter = $_GET['filter']['status'] ?? '';
$userAgentFilter = $_GET['filter']['user_agent'] ?? '';
$fromDate = isset($_GET['from_date']) ? strtotime($_GET['from_date']) : null;
$toDate = isset($_GET['to_date']) ? strtotime($_GET['to_date']) : null;

// Apply all filters including date range
$logs = array_filter($logs, function ($log) use ($ipFilter, $requestFilter, $statusFilter, $userAgentFilter, $fromDate, $toDate) {
    // Apply IP filter
    if ($ipFilter && stripos($log['ip'], $ipFilter) === false) {
        return false;
    }

    // Apply URL filter
    if ($requestFilter && stripos($log['request'], $requestFilter) === false) {
        return false;
    }

    // Apply Status Code filter
    if ($statusFilter && stripos($log['status'], $statusFilter) === false) {
        return false;
    }

    // Apply User Agent filter
    if ($userAgentFilter && stripos($log['user_agent'], $userAgentFilter) === false) {
        return false;
    }

    // Apply Date Range filter
    $logTimestamp = logDatetimeToTimestamp($log['datetime']);
    if ($fromDate && $logTimestamp < $fromDate) {
        return false;
    }
    if ($toDate && $logTimestamp > $toDate) {
        return false;
    }

    return true;
});

// Pagination Setup
$totalLogs = count($logs);
$perPage = 100; // Number of rows per page
$totalPages = ceil($totalLogs / $perPage);
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, min($totalPages, $currentPage));
$offset = ($currentPage - 1) * $perPage;

// Slice logs for current page
$logsToShow = array_slice($logs, $offset, $perPage);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Historical Log Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px; }
        #container { display: flex; max-width: 100%; }
        #log-viewer { flex: 1; margin-right: 20px; }
        .stats-container { width: 300px; background: #fff; border-radius: 8px; padding: 15px; box-shadow: 0px 0px 8px rgba(0, 0, 0, 0.1); }
        .table-container { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 8px rgba(0, 0, 0, 0.1); }
        h2 { color: #333; margin-bottom: 20px; font-size: 22px; }
        .btn-primary { background-color: #007bff; color: #fff; }
        .btn-secondary { background-color: #28a745; color: #fff; }
        .pagination { margin-top: 15px; justify-content: center; }
        .stats-container table { width: 100%; margin-bottom: 15px; }
        .stats-container table th, .stats-container table td { padding: 5px; font-size: 14px; word-wrap: break-word; }
    </style>
</head>
<body>
<div id="container">
    <!-- Main Log Viewer Section -->
    <div id="log-viewer">
        <div class="table-container">
            <h2>Historical Access Log Viewer</h2>
            <form method="GET">
                <div class="mb-3">
                    <label for="file">Select Log File:</label>
                    <select name="file" id="file" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($logFiles as $file): ?>
                            <option value="<?= $file ?>" <?= $file === $currentFile ? 'selected' : '' ?>><?= basename($file) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row mb-3">
                    <div class="col"><input type="text" name="filter[ip]" placeholder="IP Address" value="<?= htmlspecialchars($ipFilter) ?>" class="form-control"></div>
                    <div class="col"><input type="text" name="filter[request]" placeholder="URL" value="<?= htmlspecialchars($requestFilter) ?>" class="form-control"></div>
                    <div class="col"><input type="text" name="filter[status]" placeholder="Status Code" value="<?= htmlspecialchars($statusFilter) ?>" class="form-control"></div>
                    <div class="col"><input type="text" name="filter[user_agent]" placeholder="User Agent" value="<?= htmlspecialchars($userAgentFilter) ?>" class="form-control"></div>
                </div>

                <!-- Date Range Filters -->
                <div class="row mb-3">
                    <div class="col"><label>From Date</label><input type="datetime-local" name="from_date" value="<?= $_GET['from_date'] ?? '' ?>" class="form-control"></div>
                    <div class="col"><label>To Date</label><input type="datetime-local" name="to_date" value="<?= $_GET['to_date'] ?? '' ?>" class="form-control"></div>
                </div>

                <button type="submit" class="btn btn-primary">Filter</button>
            </form>

            <table class="table table-striped table-responsive" id="logTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)">Date/Time</th>
                        <th onclick="sortTable(1)">IP</th>
                        <th onclick="sortTable(2)">URL</th>
                        <th onclick="sortTable(3)">Status Code</th>
                        <th onclick="sortTable(4)">User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logsToShow as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['datetime']) ?></td>
                            <td><?= htmlspecialchars($log['ip']) ?></td>
                            <td><?= htmlspecialchars($log['request']) ?></td>
                            <td><?= htmlspecialchars($log['status']) ?></td>
                            <td><?= htmlspecialchars($log['user_agent']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <nav>
                <ul class="pagination">
                    <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $currentPage - 1 ?>&file=<?= urlencode($currentFile) ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($currentPage == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&file=<?= urlencode($currentFile) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $currentPage + 1 ?>&file=<?= urlencode($currentFile) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-container">
        <h3>Top Stats</h3>
        
        <!-- Top Visitors Table -->
        <table class="table">
            <thead><tr><th>Top 10 Visitors</th><th>Visits</th></tr></thead>
            <tbody>
                <?php
                $topIPs = array_count_values(array_column($logs, 'ip'));
                arsort($topIPs);
                foreach (array_slice($topIPs, 0, 10) as $ip => $count) {
                    echo "<tr><td>$ip</td><td>$count</td></tr>";
                }
                ?>
            </tbody>
        </table>
        
        <!-- Top URLs Table -->
        <table class="table">
            <thead><tr><th>Top 10 URLs</th><th>Visits</th></tr></thead>
            <tbody>
                <?php
                $topURLs = array_count_values(array_column($logs, 'request'));
                arsort($topURLs);
                foreach (array_slice($topURLs, 0, 10) as $url => $count) {
                    echo "<tr><td>$url</td><td>$count</td></tr>";
                }
                ?>
            </tbody>
        </table>
        
        <!-- Top User Agents Table -->
        <table class="table">
            <thead><tr><th>Top User Agents</th><th>Usage</th></tr></thead>
            <tbody>
                <?php
                $topAgents = array_count_values(array_column($logs, 'user_agent'));
                arsort($topAgents);
                foreach (array_slice($topAgents, 0, 10) as $agent => $count) {
                    echo "<tr><td>" . htmlspecialchars($agent) . "</td><td>$count</td></tr>";
                }
                ?>
            </tbody>
        </table>
                    <table class="table">
                <tboday> <center>        
    <form method="post">
    <button type="submit" name="destroy" onclick="return confirm('Are you sure? This action is irreversible.')">
        Finished? Destroy tool now
    </button>
    </form>
</center></tboday>
            </table>
    </div>
</div>
</body>
</html>
