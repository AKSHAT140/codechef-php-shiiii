<?php
require_once 'functions.php';

$task_error = '';
$email_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Task
    if (isset($_POST['task-name'])) {
        $task_name = trim($_POST['task-name']);
        if ($task_name !== '') {
            if (!addTask($task_name)) {
                $task_error = 'Task already exists!';
            }
        }
    }
    // Mark Complete/Incomplete
    if (isset($_POST['toggle-task']) && isset($_POST['task-id'])) {
        markTaskAsCompleted($_POST['task-id'], $_POST['toggle-task'] === '1');
    }
    // Delete Task
    if (isset($_POST['delete-task']) && isset($_POST['task-id'])) {
        deleteTask($_POST['task-id']);
    }
    // Subscribe Email
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (subscribeEmail($email)) {
                $email_msg = 'Verification email sent!';
            } else {
                $email_msg = 'Already subscribed or pending verification.';
            }
        } else {
            $email_msg = 'Invalid email address.';
        }
    }
}

$tasks = getAllTasks();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Task Scheduler</title>
    <style>
        .completed { text-decoration: line-through; color: #888; }
    </style>
</head>
<body>
    <!-- Add Task Form -->
    <form method="POST" action="">
        <input type="text" name="task-name" id="task-name" placeholder="Enter new task" required>
        <button type="submit" id="add-task">Add Task</button>
        <?php if ($task_error): ?><span style="color:red;"><?=htmlspecialchars($task_error)?></span><?php endif; ?>
    </form>

    <!-- Tasks List -->
    <ul class="tasks-list" id="tasks-list">
        <?php foreach ($tasks as $task): ?>
        <li class="task-item<?= $task['completed'] ? ' completed' : '' ?>">
            <form method="POST" action="" style="display:inline;">
                <input type="hidden" name="task-id" value="<?=htmlspecialchars($task['id'])?>">
                <input type="checkbox" class="task-status" name="toggle-task" value="<?= $task['completed'] ? '0' : '1' ?>" onchange="this.form.submit()" <?= $task['completed'] ? 'checked' : '' ?>>
            </form>
            <?=htmlspecialchars($task['name'])?>
            <form method="POST" action="" style="display:inline;">
                <input type="hidden" name="task-id" value="<?=htmlspecialchars($task['id'])?>">
                <button type="submit" class="delete-task" name="delete-task" value="1">Delete</button>
            </form>
        </li>
        <?php endforeach; ?>
    </ul>

    <!-- Subscription Form -->
    <form method="POST" action="">
        <input type="email" name="email" required>
        <button type="submit" id="submit-email">Subscribe</button>
        <?php if ($email_msg): ?><span><?=htmlspecialchars($email_msg)?></span><?php endif; ?>
    </form>
</body>
</html>
