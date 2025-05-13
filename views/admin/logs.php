<?php

// Include database connection
require_once __DIR__ . '/../../config/config.php';

// Items per page
$itemsPerPage = 10;

// --- Search Functionality ---
$auditSearchTerm = $_GET['audit_search'] ?? '';
$appSearchTerm = $_GET['app_search'] ?? '';

// --- Pagination for Audit Trail ---
$currentAuditPage = $_GET['audit_page'] ?? 1;
$offsetAudit = ($currentAuditPage - 1) * $itemsPerPage;

// Build the WHERE clause for audit log search
$auditWhereClause = '';
if (!empty($auditSearchTerm)) {
    $auditSearchTermSafe = mysqli_real_escape_string($conn, $auditSearchTerm);
    $auditWhereClause = "WHERE al.timestamp LIKE '%$auditSearchTermSafe%' OR
                                    u.username LIKE '%$auditSearchTermSafe%' OR
                                    al.event_type LIKE '%$auditSearchTermSafe%' OR
                                    al.table_name LIKE '%$auditSearchTermSafe%' OR
                                    al.row_id LIKE '%$auditSearchTermSafe%' OR
                                    al.old_values LIKE '%$auditSearchTermSafe%' OR
                                    al.new_values LIKE '%$auditSearchTermSafe%' OR
                                    al.description LIKE '%$auditSearchTermSafe%'";
}

// Fetch paginated and filtered audit log data
$auditLogQuery = "SELECT al.*, u.username AS performed_by_username
                    FROM audit_log al
                    LEFT JOIN users u ON al.user_id = u.user_id
                    $auditWhereClause
                    ORDER BY al.timestamp DESC
                    LIMIT $itemsPerPage OFFSET $offsetAudit";
$auditLogResult = mysqli_query($conn, $auditLogQuery);
$auditLogs = mysqli_fetch_all($auditLogResult, MYSQLI_ASSOC);

// Get total number of filtered audit log entries
$totalAuditLogsQuery = "SELECT COUNT(*) AS total
                                    FROM audit_log al
                                    LEFT JOIN users u ON al.user_id = u.user_id
                                    $auditWhereClause";
$totalAuditLogsResult = mysqli_query($conn, $totalAuditLogsQuery);
$totalAuditLogs = mysqli_fetch_assoc($totalAuditLogsResult)['total'] ?? 0;
$totalPagesAuditLogs = ceil($totalAuditLogs / $itemsPerPage);

// --- Pagination for Application Logs ---
$currentAppPage = $_GET['app_page'] ?? 1;
$offsetApp = ($currentAppPage - 1) * $itemsPerPage;

// Build the WHERE clause for application log search
$appWhereClause = '';
if (!empty($appSearchTerm)) {
    $appSearchTermSafe = mysqli_real_escape_string($conn, $appSearchTerm);
    $appWhereClause = "WHERE l.timestamp LIKE '%$appSearchTermSafe%' OR
                                   u.username LIKE '%$appSearchTermSafe%' OR
                                   l.event_type LIKE '%$appSearchTermSafe%' OR
                                   l.description LIKE '%$appSearchTermSafe%'";
}

// Fetch paginated and filtered application log data
$appLogQuery = "SELECT l.*, u.username AS logged_in_username
                    FROM logs l
                    LEFT JOIN users u ON l.user_id = u.user_id
                    $appWhereClause
                    ORDER BY l.timestamp DESC
                    LIMIT $itemsPerPage OFFSET $offsetApp";
$appLogResult = mysqli_query($conn, $appLogQuery);
$appLogs = mysqli_fetch_all($appLogResult, MYSQLI_ASSOC);

// Get total number of filtered application log entries
$totalAppLogsQuery = "SELECT COUNT(*) AS total
                                    FROM logs l
                                    LEFT JOIN users u ON l.user_id = u.user_id
                                    $appWhereClause";
$totalAppLogsResult = mysqli_query($conn, $totalAppLogsQuery);
$totalAppLogs = mysqli_fetch_assoc($totalAppLogsResult)['total'] ?? 0;
$totalPagesAppLogs = ceil($totalAppLogs / $itemsPerPage);

?>

<div class="container mx-auto p-6 bg-gray-50 rounded-md shadow-md">
    <h1 class="text-2xl font-semibold mb-6 text-gray-800">System Logs</h1>

    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-3 text-gray-700 flex items-center justify-between">
            Audit Trail
            <form method="get" class="flex items-center">
                <input type="hidden" name="page" value="logs">
                <input type="text" name="audit_search" value="<?php echo htmlspecialchars($auditSearchTerm); ?>"
                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm-text-sm border-gray-300 rounded-md mr-2"
                    placeholder="Search Audit Logs">
                <input type="hidden" name="app_page" value="<?php echo htmlspecialchars($_GET['app_page'] ?? 1); ?>">
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span class="material-symbols-outlined align-middle">search</span>
                </button>
            </form>
        </h2>
        <?php if (empty($auditLogs) && !empty($auditSearchTerm)): ?>
            <p class="text-gray-600 italic">No audit trail entries found matching your search term.</p>
        <?php elseif (empty($auditLogs)): ?>
            <p class="text-gray-600 italic">No audit trail entries found.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full leading-normal">
                    <thead class="bg-gray-100">
                        <tr>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Timestamp</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                User</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Event Type</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Table</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Row ID</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Old Values</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                New Values</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auditLogs as $log): ?>
                            <tr class="<?php echo $loop % 2 === 0 ? 'bg-gray-50' : ''; ?>">
                                <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                    <?php echo htmlspecialchars($log['timestamp']); ?>
                                </td>
                                <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                    <?php echo htmlspecialchars($log['performed_by_username'] ?? 'System'); ?>
                                </td>
                                <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                    <?php echo htmlspecialchars($log['event_type']); ?>
                                </td>
                                <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                    <?php echo htmlspecialchars($log['table_name']); ?>
                                </td>
                                <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                    <?php echo htmlspecialchars($log['row_id'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                    <?php if ($log['old_values']): ?>
                                        <pre
                                            class="text-xs whitespace-pre-wrap"><?php echo htmlspecialchars(json_encode(json_decode($log['old_values']), JSON_PRETTY_PRINT)); ?></pre>
                                    <?php else: ?>
                                        <span class="italic text-gray-500">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                    <?php if ($log['new_values']): ?>
                                        <pre
                                            class="text-xs whitespace-pre-wrap"><?php echo htmlspecialchars(json_encode(json_decode($log['new_values']), JSON_PRETTY_PRINT)); ?></pre>
                                    <?php else: ?>
                                        <span class="italic text-gray-500">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                    <?php echo htmlspecialchars($log['description'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPagesAuditLogs > 1): ?>
                <div class="mt-4 flex justify-center">
                    <?php if ($currentAuditPage > 1): ?>
                        <a href="?page=logs&audit_page=<?php echo $currentAuditPage - 1; ?><?php if (!empty($auditSearchTerm))
                                 echo '&audit_search=' . htmlspecialchars($auditSearchTerm); ?><?php if (isset($_GET['app_page']))
                                         echo '&app_page=' . htmlspecialchars($_GET['app_page']); ?><?php if (!empty($appSearchTerm))
                                                 echo '&app_search=' . htmlspecialchars($appSearchTerm); ?>"
                            class="px-4 py-2 mx-1 bg-gray-200 rounded hover:bg-gray-300">Previous</a>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $currentAuditPage - 2);
                    $endPage = min($totalPagesAuditLogs, $currentAuditPage + 2);

                    if ($startPage > 1)
                        echo '<span class="mx-1">...</span>';

                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?page=logs&audit_page=<?php echo $i; ?><?php if (!empty($auditSearchTerm))
                               echo '&audit_search=' . htmlspecialchars($auditSearchTerm); ?><?php if (isset($_GET['app_page']))
                                       echo '&app_page=' . htmlspecialchars($_GET['app_page']); ?><?php if (!empty($appSearchTerm))
                                               echo '&app_search=' . htmlspecialchars($appSearchTerm); ?>"
                            class="px-4 py-2 mx-1 <?php echo ($i == $currentAuditPage) ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?> rounded"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPagesAuditLogs)
                        echo '<span class="mx-1">...</span>'; ?>
                    <?php if ($currentAuditPage < $totalPagesAuditLogs): ?>
                        <a href="?page=logs&audit_page=<?php echo $currentAuditPage + 1; ?><?php if (!empty($auditSearchTerm))
                                 echo '&audit_search=' . htmlspecialchars($auditSearchTerm); ?><?php if (isset($_GET['app_page']))
                                         echo '&app_page=' . htmlspecialchars($_GET['app_page']); ?><?php if (!empty($appSearchTerm))
                                                 echo '&app_search=' . htmlspecialchars($appSearchTerm); ?>"
                            class="px-4 py-2 mx-1 bg-gray-200 rounded hover:bg-gray-300">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div>
        <h2 class="text-lg font-semibold mb-3 text-gray-700 flex items-center justify-between">
            Application Logs
            <form method="get" class="flex items-center">
                <input type="hidden" name="page" value="logs">
                <input type="text" name="app_search" value="<?php echo htmlspecialchars($appSearchTerm); ?>"
                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm-text-sm border-gray-300 rounded-md mr-2"
                    placeholder="Search Application Logs">
                <input type="hidden" name="audit_page"
                    value="<?php echo htmlspecialchars($_GET['audit_page'] ?? 1); ?>">
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span class="material-symbols-outlined align-middle">search</span>
                </button>
            </form>
        </h2>
        <?php if (empty($appLogs) && !empty($appSearchTerm)): ?>
            <p class="text-gray-600 italic">No application log entries found matching your search term.</p>
        <?php elseif (empty($appLogs)): ?>
            <p class="text-gray-600 italic">No application log entries found.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full leading-normal">
                    <thead class="bg-gray-100">
                        <tr>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Timestamp</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                User</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Event Type</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appLogs as $log): ?>
                            <tr class="<?php echo $loop % 2 === 0 ? 'bg-gray-50' : ''; ?>">
                                <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                    <?php echo htmlspecialchars($log['timestamp']); ?>
                                </td>
                                <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                    <?php echo htmlspecialchars($log['logged_in_username'] ?? 'System'); ?>
                                </td>
                                <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                    <?php echo htmlspecialchars($log['event_type']); ?>
                                </td>
                                <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                    <?php echo htmlspecialchars($log['description']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPagesAppLogs > 1): ?>
                <div class="mt-4 flex justify-center">
                    <?php if ($currentAppPage > 1): ?>
                        <a href="?page=logs&app_page=<?php echo $currentAppPage - 1; ?><?php if (!empty($appSearchTerm))
                                 echo '&app_search=' . htmlspecialchars($appSearchTerm); ?><?php if (isset($_GET['audit_page']))
                                         echo '&audit_page=' . htmlspecialchars($_GET['audit_page']); ?><?php if (!empty($auditSearchTerm))
                                                 echo '&audit_search=' . htmlspecialchars($auditSearchTerm); ?>"
                            class="px-4 py-2 mx-1 bg-gray-200 rounded hover:bg-gray-300">Previous</a>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $currentAppPage - 2);
                    $endPage = min($totalPagesAppLogs, $currentAppPage + 2);

                    if ($startPage > 1)
                        echo '<span class="mx-1">...</span>';

                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?page=logs&app_page=<?php echo $i; ?><?php if (!empty($appSearchTerm))
                               echo '&app_search=' . htmlspecialchars($appSearchTerm); ?><?php if (isset($_GET['audit_page']))
                                       echo '&audit_page=' . htmlspecialchars($_GET['audit_page']); ?><?php if (!empty($auditSearchTerm))
                                               echo '&audit_search=' . htmlspecialchars($auditSearchTerm); ?>"
                            class="px-4 py-2 mx-1 <?php echo ($i == $currentAppPage) ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?> rounded"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPagesAppLogs)
                        echo '<span class="mx-1">...</span>'; ?>
                    <?php if ($currentAppPage < $totalPagesAppLogs): ?>
                        <a href="?page=logs&app_page=<?php echo $currentAppPage + 1; ?><?php if (!empty($appSearchTerm))
                                 echo '&app_search=' . htmlspecialchars($appSearchTerm); ?><?php if (isset($_GET['audit_page']))
                                         echo '&audit_page=' . htmlspecialchars($_GET['audit_page']); ?><?php if (!empty($auditSearchTerm))
                                                 echo '&audit_search=' . htmlspecialchars($auditSearchTerm); ?>"
                            class="px-4 py-2 mx-1 bg-gray-200 rounded hover:bg-gray-300">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>