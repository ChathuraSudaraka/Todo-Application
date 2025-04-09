<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include config file
require_once "config.php";

// Define variables for task operations
$task_title = $task_description = "";
$title_err = "";
$success_msg = "";

// Process form submission for adding a new task
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_task"])){
    // Validate task title
    if(empty(trim($_POST["task_title"]))){
        $title_err = "Please enter a task title.";
    } else {
        $task_title = trim($_POST["task_title"]);
        $task_description = trim($_POST["task_description"]);
        
        // Insert task into database
        $sql = "INSERT INTO tasks (user_id, title, description) VALUES (?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "iss", $param_user_id, $param_title, $param_description);
            
            // Set parameters
            $param_user_id = $_SESSION["id"];
            $param_title = $task_title;
            $param_description = $task_description;
            
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "Task added successfully!";
                $task_title = $task_description = "";
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Process task completion
if(isset($_GET["complete"]) && !empty($_GET["complete"])){
    $task_id = $_GET["complete"];
    
    // Update task status
    $sql = "UPDATE tasks SET status = 'completed' WHERE id = ? AND user_id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $param_id, $param_user_id);
        
        // Set parameters
        $param_id = $task_id;
        $param_user_id = $_SESSION["id"];
        
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Redirect to prevent form resubmission
        header("location: index.php");
        exit;
    }
}

// Process task deletion
if(isset($_GET["delete"]) && !empty($_GET["delete"])){
    $task_id = $_GET["delete"];
    
    // Delete task
    $sql = "DELETE FROM tasks WHERE id = ? AND user_id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $param_id, $param_user_id);
        
        // Set parameters
        $param_id = $task_id;
        $param_user_id = $_SESSION["id"];
        
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Redirect to prevent form resubmission
        header("location: index.php");
        exit;
    }
}

// Fetch user's tasks
$tasks = [];
$sql = "SELECT id, title, description, status, created_at FROM tasks WHERE user_id = ? ORDER BY created_at DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $param_user_id);
    $param_user_id = $_SESSION["id"];
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_array($result)){
            $tasks[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Count pending and completed tasks
$pending_count = 0;
$completed_count = 0;

foreach($tasks as $task) {
    if($task['status'] == 'pending') {
        $pending_count++;
    } else {
        $completed_count++;
    }
}

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #121212;
            --card-bg: #1e1e1e;
            --sidebar-bg: #1a1a1a;
            --primary-color: #7b68ee;
            --secondary-color: #bb86fc;
            --text-color: #e0e0e0;
            --text-muted: #9e9e9e;
            --error-color: #cf6679;
            --success-color: #03dac6;
            --border-color: #333333;
            --input-bg: #2a2a2a;
            --task-hover: #252525;
            --task-completed-bg: rgba(3, 218, 198, 0.05);
            --task-pending-bg: rgba(187, 134, 252, 0.05);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
        }
        
        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg);
            padding: 20px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100%;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
        }
        
        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .logo i {
            font-size: 24px;
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        .logo-text {
            font-weight: 700;
            font-size: 20px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            padding: 15px 0;
            margin-bottom: 20px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 10px;
            font-weight: bold;
            color: white;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 16px;
        }
        
        .sidebar-menu {
            list-style: none;
            margin-top: 20px;
            flex-grow: 1;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            text-decoration: none;
            color: var(--text-color);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: var(--card-bg);
        }
        
        .sidebar-menu a.active {
            border-left: 3px solid var(--secondary-color);
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            font-size: 18px;
            color: var(--secondary-color);
        }
        
        .logout {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        .logout a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            text-decoration: none;
            color: var(--error-color);
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .logout a:hover {
            background-color: rgba(207, 102, 121, 0.1);
        }
        
        .logout i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
            width: calc(100% - 250px);
        }
        
        .dashboard-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card .stat-label {
            color: var(--text-muted);
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .stat-card .stat-label i {
            margin-right: 5px;
        }
        
        .stat-card.pending {
            border-left: 4px solid var(--secondary-color);
        }
        
        .stat-card.completed {
            border-left: 4px solid var(--success-color);
        }
        
        .add-task-container {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
            color: var(--secondary-color);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
            color: var(--secondary-color);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--input-bg);
            color: var(--text-color);
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(187, 134, 252, 0.2);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            background-color: var(--secondary-color);
            color: #121212;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        .success-message {
            background-color: rgba(3, 218, 198, 0.1);
            color: var(--success-color);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--success-color);
        }
        
        .error-message {
            background-color: rgba(207, 102, 121, 0.1);
            color: var(--error-color);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--error-color);
        }
        
        .tasks-container {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .tasks-filters {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
        }
        
        .filter-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            padding: 8px 15px;
            cursor: pointer;
            border-radius: 20px;
            font-size: 14px;
            transition: all 0.3s ease;
            margin-right: 10px;
        }
        
        .filter-btn.active {
            background-color: var(--secondary-color);
            color: #121212;
            font-weight: 600;
        }
        
        .tasks-list {
            list-style: none;
        }
        
        .task-item {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            display: flex;
            align-items: flex-start;
        }
        
        .task-item:hover {
            background-color: var(--task-hover);
        }
        
        .task-item.completed {
            background-color: var(--task-completed-bg);
            border-left-color: var(--success-color);
        }
        
        .task-item.pending {
            background-color: var(--task-pending-bg);
            border-left-color: var(--secondary-color);
        }
        
        .task-checkbox {
            margin-right: 15px;
            margin-top: 3px;
        }
        
        .custom-checkbox {
            display: inline-block;
            width: 22px;
            height: 22px;
            background-color: var(--input-bg);
            border: 2px solid var(--border-color);
            border-radius: 4px;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .custom-checkbox.checked {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .custom-checkbox.checked:after {
            content: '';
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(45deg);
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
        }
        
        .task-content {
            flex: 1;
        }
        
        .task-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
            color: var(--text-color);
        }
        
        .task-completed .task-title {
            text-decoration: line-through;
            color: var(--text-muted);
        }
        
        .task-description {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .task-meta {
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
        }
        
        .task-meta i {
            margin-right: 5px;
        }
        
        .task-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            border: none;
            background: none;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .action-btn:hover {
            color: var(--text-color);
        }
        
        .action-btn.delete:hover {
            color: var(--error-color);
        }
        
        .action-btn.complete:hover {
            color: var(--success-color);
        }
        
        .empty-message {
            text-align: center;
            padding: 40px 0;
            color: var(--text-muted);
        }
        
        .empty-message i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding: 15px 10px;
            }
            
            .logo-text, .user-name, .sidebar-menu span, .logout span {
                display: none;
            }
            
            .sidebar-menu a, .logout a {
                justify-content: center;
            }
            
            .sidebar-menu i, .logout i {
                margin-right: 0;
            }
            
            .main-content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-tasks"></i>
            <span class="logo-text">Task Manager</span>
        </div>
        
        <div class="user-info">
            <div class="user-avatar">
                <?php echo substr($_SESSION["username"], 0, 1); ?>
            </div>
            <div class="user-name">
                <?php echo htmlspecialchars($_SESSION["username"]); ?>
            </div>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="index.php" class="active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Calendar</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
        
        <div class="logout">
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="dashboard-header">
            <h1 class="page-title">Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION["username"]); ?>! Here's your task overview.</p>
        </div>
        
        <div class="stats-container">
            <div class="stat-card pending">
                <div class="stat-value"><?php echo $pending_count; ?></div>
                <div class="stat-label">
                    <i class="fas fa-clock"></i>
                    Pending Tasks
                </div>
            </div>
            
            <div class="stat-card completed">
                <div class="stat-value"><?php echo $completed_count; ?></div>
                <div class="stat-label">
                    <i class="fas fa-check-circle"></i>
                    Completed Tasks
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo count($tasks); ?></div>
                <div class="stat-label">
                    <i class="fas fa-list"></i>
                    Total Tasks
                </div>
            </div>
        </div>
        
        <div class="add-task-container">
            <h2 class="section-title">
                <i class="fas fa-plus-circle"></i>
                Add New Task
            </h2>
            
            <?php 
            if(!empty($success_msg)){
                echo '<div class="success-message">' . $success_msg . '</div>';
            }
            if(!empty($title_err)){
                echo '<div class="error-message">' . $title_err . '</div>';
            }
            ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="task_title">Task Title</label>
                    <input type="text" name="task_title" id="task_title" class="form-control" value="<?php echo $task_title; ?>" placeholder="Enter task title">
                </div>
                
                <div class="form-group">
                    <label for="task_description">Description (Optional)</label>
                    <textarea name="task_description" id="task_description" class="form-control" placeholder="Enter task description"><?php echo $task_description; ?></textarea>
                </div>
                
                <button type="submit" name="add_task" class="btn">
                    <i class="fas fa-plus"></i>
                    Add Task
                </button>
            </form>
        </div>
        
        <div class="tasks-container">
            <h2 class="section-title">
                <i class="fas fa-list"></i>
                Your Tasks
            </h2>
            
            <div class="tasks-filters">
                <button class="filter-btn active" data-filter="all">All Tasks</button>
                <button class="filter-btn" data-filter="pending">Pending</button>
                <button class="filter-btn" data-filter="completed">Completed</button>
            </div>
            
            <?php if(empty($tasks)): ?>
                <div class="empty-message">
                    <i class="fas fa-clipboard-list"></i>
                    <p>You don't have any tasks yet. Start by adding a new task above.</p>
                </div>
            <?php else: ?>
                <ul class="tasks-list">
                    <?php foreach($tasks as $task): ?>
                        <li class="task-item <?php echo ($task['status'] == 'completed') ? 'completed' : 'pending'; ?>" data-status="<?php echo $task['status']; ?>">
                            <div class="task-checkbox">
                                <span class="custom-checkbox <?php echo ($task['status'] == 'completed') ? 'checked' : ''; ?>"></span>
                            </div>
                            
                            <div class="task-content">
                                <div class="task-title"><?php echo htmlspecialchars($task['title']); ?></div>
                                
                                <?php if(!empty($task['description'])): ?>
                                    <div class="task-description"><?php echo htmlspecialchars($task['description']); ?></div>
                                <?php endif; ?>
                                
                                <div class="task-meta">
                                    <i class="far fa-calendar-alt"></i>
                                    Created: <?php echo date('F j, Y, g:i a', strtotime($task['created_at'])); ?>
                                </div>
                            </div>
                            
                            <div class="task-actions">
                                <?php if($task['status'] == 'pending'): ?>
                                    <a href="?complete=<?php echo $task['id']; ?>" class="action-btn complete" onclick="return confirm('Mark this task as completed?')">
                                        <i class="fas fa-check"></i> Complete
                                    </a>
                                <?php endif; ?>
                                
                                <a href="?delete=<?php echo $task['id']; ?>" class="action-btn delete" onclick="return confirm('Are you sure you want to delete this task?')">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Task filtering
            const filterButtons = document.querySelectorAll('.filter-btn');
            const taskItems = document.querySelectorAll('.task-item');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const filter = this.getAttribute('data-filter');
                    
                    // Update active button
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Filter tasks
                    taskItems.forEach(task => {
                        const status = task.getAttribute('data-status');
                        
                        if (filter === 'all' || status === filter) {
                            task.style.display = 'flex';
                        } else {
                            task.style.display = 'none';
                        }
                    });
                });
            });
            
            // Custom checkbox click handling
            document.querySelectorAll('.custom-checkbox').forEach(checkbox => {
                checkbox.addEventListener('click', function() {
                    const taskItem = this.closest('.task-item');
                    const taskId = taskItem.querySelector('.task-actions .complete')?.href.split('=')[1];
                    
                    if (taskId && !this.classList.contains('checked')) {
                        window.location.href = `?complete=${taskId}`;
                    }
                });
            });
        });
    </script>
</body>
</html>