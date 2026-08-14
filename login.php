<?php
include "includes/config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];
	$role = $_POST["role"];

    $stmt = $conn->prepare("
        SELECT *
        FROM users
        WHERE username = ?
        AND role = ?
    ");

    $stmt->bind_param(
        "ss",
        $username,
		$role
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        if (password_verify(
            $password,
            $user["password"]
        )) {

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["role"] = $user["role"];

            header("Location: dashboard.php");
            exit();

        } else {

            $error = "Incorrect password.";

        }

    } else {

        $error = "User not found.";

    }
}
?>

<!DOCTYPE html>
<html>

<head>

    <title>Login</title>

    <link
        rel="stylesheet"
        href="styles/login.css"
    >

</head>

<body>

<div class="login-box">

    <h2>Login</h2>

    <?php

    if ($error != "") {

        echo "<p class='error'>" .
             htmlspecialchars($error) .
             "</p>";

    }

    ?>

    <form method="POST">

        <input
            type="text"
            name="username"
            placeholder="Gmail"
            required
        >

        <input
            type="password"
            name="password"
            placeholder="Password"
            required
        >

        <select name="role" required>

            <option value="">Select User Type</option>

            <option value="Employee">
                Employee
            </option>

            <option value="Admin">
                Admin
            </option>

            <option value="IT Administrator">
                IT Administrator
            </option>

        </select>

        <button type="submit">
            Login
        </button>

    </form>

    <p style="margin-top:15px;text-align:center;">

        Create an account?

        <a href="signup.php">
            Sign Up
        </a>

    </p>

</div>

</body>

</html>
