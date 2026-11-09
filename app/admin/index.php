
<?php

require '../../config/config.php';

requireRole('admin');

// Determine current section
$section = $_GET['section'] ?? 'activity-logs';
$action = $_GET['action'] ?? '';

// Initialize variables
$activities = [];
$sessions = [];

// ======================================
// EXPORT CSV
// ======================================

if ($action === 'export') {

    if ($section === 'activity-logs') {

        $stmt = $pdo->query("
            SELECT
                activity_log_id,
                user_id,
                user_email,
                activity_log_action,
                activity_log_status,
                activity_log_ip_address,
                activity_log_user_agent,
                activity_log_created_at
            FROM activity_logs
            ORDER BY activity_log_created_at DESC
        ");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        logActivity(
            $pdo,
            $_SESSION['user_id'],
            $_SESSION['user_email'],
            'export_activity_logs',
            'success'
        );

        exportCsv(
            'activity_logs',
            [
                'Record ID',
                'User ID',
                'User Email',
                'Action',
                'Status',
                'IP Address',
                'User Agent',
                'Date & Time'
            ],
            $rows
        );

    } elseif ($section === 'user-sessions') {

        $stmt = $pdo->query("
            SELECT
                us.session_id,
                us.user_id,
                u.user_username,
                u.user_email,
                u.user_role,
                us.session_start,
                us.session_end,
                us.session_duration
            FROM user_sessions us
            INNER JOIN users u
                ON us.user_id = u.user_id
            ORDER BY us.session_start DESC
        ");

        $sessionsExport = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $rows = [];

        foreach ($sessionsExport as $session) {

            $duration = 'Active';

            if ($session['session_duration'] !== null) {

                $seconds = (int) $session['session_duration'];

                $hours = floor($seconds / 3600);
                $minutes = floor(($seconds % 3600) / 60);
                $remainingSeconds = $seconds % 60;

                $duration = sprintf(
                    '%02d hr %02d min %02d sec',
                    $hours,
                    $minutes,
                    $remainingSeconds
                );
            }

            $rows[] = [
                $session['session_id'],
                $session['user_id'],
                $session['user_username'],
                $session['user_email'],
                $session['user_role'],
                $session['session_start'],
                $session['session_end'] ?? 'Active',
                $duration
            ];
        }

        logActivity(
            $pdo,
            $_SESSION['user_id'],
            $_SESSION['user_email'],
            'export_user_sessions',
            'success'
        );

        exportCsv(
            'user_sessions',
            [
                'Session ID',
                'User ID',
                'Username',
                'Email',
                'Role',
                'Session Start',
                'Session End',
                'Duration'
            ],
            $rows
        );
    }

    exit;
}

// ======================================
// FETCH ACTIVITY LOGS
// ======================================

if ($section === 'activity-logs') {

    $stmt = $pdo->query("
        SELECT *
        FROM activity_logs
        ORDER BY activity_log_created_at DESC
    ");

    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ======================================
// FETCH USER SESSIONS
// ======================================

elseif ($section === 'user-sessions') {

    $stmt = $pdo->query("
        SELECT
            us.session_id,
            us.user_id,
            u.user_username,
            u.user_email,
            u.user_role,
            us.session_start,
            us.session_end,
            us.session_duration
        FROM user_sessions us
        INNER JOIN users u
            ON us.user_id = u.user_id
        ORDER BY us.session_start DESC
    ");

    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
</head>

<body>

    <h1>Welcome Admin</h1>

    <form method="POST" action="../../auth/signout.php">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(generateCsrfToken()) ?>"
        >

        <button type="submit">Sign Out</button>

    </form>

    <hr>

    <!-- NAVIGATION -->

    <nav>
        <a href="index.php?section=activity-logs">
            Activity Logs
        </a>

        |

        <a href="index.php?section=user-sessions">
            User Sessions
        </a>

        |
    </nav>

    <hr>

    <!-- TEMPORARY DEBUG MESSAGE -->

    <p>
        Current section:
        <strong><?= htmlspecialchars($section) ?></strong>
    </p>

    <!-- ================================= -->
    <!-- ACTIVITY LOGS -->
    <!-- ================================= -->

    <?php if ($section === 'activity-logs'): ?>

        <h4>
            Activity Logs

            <a href="index.php?section=activity-logs&amp;action=export">
                Export CSV
            </a>
        </h4>

        <table
            id="activityTable"
            border="1"
            cellpadding="5"
            cellspacing="0"
        >

            <thead>
                <tr>
                    <th>Record ID</th>
                    <th>User ID</th>
                    <th>User Email</th>
                    <th>Action</th>
                    <th>Status</th>
                    <th>IP Address</th>
                    <th>User Agent</th>
                    <th>Date & Time</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($activities)): ?>

                    <tr>
                        <td colspan="8">No activity logs found.</td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($activities as $activity): ?>

                        <tr>
                            <td><?= htmlspecialchars((string)$activity['activity_log_id']) ?></td>
                            <td><?= htmlspecialchars((string)($activity['user_id'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($activity['user_email'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)$activity['activity_log_action']) ?></td>
                            <td><?= htmlspecialchars((string)$activity['activity_log_status']) ?></td>
                            <td><?= htmlspecialchars((string)($activity['activity_log_ip_address'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($activity['activity_log_user_agent'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)$activity['activity_log_created_at']) ?></td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    <?php elseif ($section === 'user-sessions'): ?>

        <!-- ================================= -->
        <!-- USER SESSIONS -->
        <!-- ================================= -->

        <h4>
            User Sessions

            <a href="index.php?section=user-sessions&amp;action=export">
                Export CSV
            </a>
        </h4>

        <table
            id="sessionTable"
            border="1"
            cellpadding="5"
            cellspacing="0"
        >

            <thead>
                <tr>
                    <th>Session ID</th>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Session Start</th>
                    <th>Session End</th>
                    <th>Duration</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($sessions)): ?>

                    <tr>
                        <td colspan="8">No user sessions found.</td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($sessions as $session): ?>

                        <tr>
                            <td><?= htmlspecialchars((string)$session['session_id']) ?></td>
                            <td><?= htmlspecialchars((string)$session['user_id']) ?></td>
                            <td><?= htmlspecialchars((string)$session['user_username']) ?></td>
                            <td><?= htmlspecialchars((string)$session['user_email']) ?></td>
                            <td><?= htmlspecialchars((string)$session['user_role']) ?></td>
                            <td><?= htmlspecialchars((string)$session['session_start']) ?></td>

                            <td>
                                <?= $session['session_end']
                                    ? htmlspecialchars((string)$session['session_end'])
                                    : 'Active'
                                ?>
                            </td>

                            <td>
                                <?php

                                if ($session['session_duration'] !== null) {

                                    $seconds = (int)$session['session_duration'];

                                    $hours = floor($seconds / 3600);
                                    $minutes = floor(($seconds % 3600) / 60);
                                    $remainingSeconds = $seconds % 60;

                                    echo sprintf(
                                        '%02d hr %02d min %02d sec',
                                        $hours,
                                        $minutes,
                                        $remainingSeconds
                                    );

                                } else {

                                    echo 'Active';

                                }

                                ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    <?php else: ?>

        <h3>Invalid section selected.</h3>

        <a href="index.php?section=activity-logs">
            Return to Activity Logs
        </a>

    <?php endif; ?>

</body>
</html>
