<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION["role"] ?? "";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Tracker</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f5f5f5;
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 230px;
            height: 100vh;
            background: #222;
            padding: 20px;
        }

        .sidebar h2 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: block;
            color: #ddd;
            text-decoration: none;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 5px;
        }

        .sidebar a:hover {
            background: #444;
            color: white;
        }

        .main {
            margin-left: 230px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content {
            flex: 1;
            padding: 30px;
        }
    </style>
</head>

<body>

<div class="sidebar">

    <h2>Payroll Tracker</h2>

    <?php if ($role == "Employee"): ?>

        <a href="dashboard.php">Dashboard</a>

        <a href="timeIn.php">Time In</a>

        <a href="timeOut.php">Time Out</a>

        <a href="editAccount.php">Edit Account Info</a>

        <a href="changePassword.php">Change Password</a>


    <?php elseif ($role == "Admin"): ?>

        <a href="dashboard.php">Dashboard</a>

        <a href="employees.php">Employee Management</a>

        <a href="payrollDetails.php">Input Payroll Details</a>


    <?php elseif ($role == "IT Administrator"): ?>

        <a href="dashboard.php">Dashboard</a>

        <a href="timeIn.php">Time In</a>

        <a href="timeOut.php">Time Out</a>

        <a href="editAccount.php">Edit Account Info</a>

        <a href="changePassword.php">Change Password</a>

        <a href="employees.php">Employee Management</a>

        <a href="payrollDetails.php">Input Payroll Details</a>

    <?php endif; ?>

    <a href="login.php">Logout</a>

</div>

<div class="main">
