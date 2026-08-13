<?php

include "includes/config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];


    // ==================================================
    // VALIDATE INPUT
    // ==================================================

    if (
        empty($name) ||
        empty($username) ||
        empty($password) ||
        empty($confirmPassword)
    ) {

        $message = "Please fill in all fields.";

    }
    elseif ($password != $confirmPassword) {

        $message = "Passwords do not match.";

    }
    else {


        // ==================================================
        // CHECK IF USERNAME ALREADY EXISTS
        // ==================================================

        $check = $conn->prepare("
            SELECT id
            FROM users
            WHERE username = ?
        ");

        $check->bind_param(
            "s",
            $username
        );

        $check->execute();

        $result = $check->get_result();


        if ($result->num_rows > 0) {

            $message = "Username already exists.";

        }
        else {


            // ==================================================
            // HASH PASSWORD
            // ==================================================

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            // ==================================================
            // CREATE USER
            // ==================================================

            $stmt = $conn->prepare("
                INSERT INTO users
                (name, username, password)
                VALUES (?, ?, ?)
            ");

            $stmt->bind_param(
                "sss",
                $name,
                $username,
                $hashedPassword
            );


            if ($stmt->execute()) {


                // ==================================================
                // GET NEW USER ID
                // ==================================================

                $userId = $conn->insert_id;


                // ==================================================
                // GENERATE EMPLOYEE CODE
                // ==================================================

                $employeeCode =
                    "EMP" .
                    str_pad(
                        $userId,
                        3,
                        "0",
                        STR_PAD_LEFT
                    );


                // ==================================================
                // DEFAULT VALUES
                // ==================================================

                $monthlySalary = 0.00;

                $status = "Active";


                // ==================================================
                // CREATE EMPLOYEE RECORD
                // ==================================================

                $employeeStmt = $conn->prepare("
                    INSERT INTO employees
                    (
                        user_id,
                        employee_code,
                        monthly_salary,
                        status
                    )
                    VALUES (?, ?, ?, ?)
                ");

                $employeeStmt->bind_param(
                    "isds",
                    $userId,
                    $employeeCode,
                    $monthlySalary,
                    $status
                );


                if ($employeeStmt->execute()) {

                    // Successfully created both
                    // user and employee.

                    header("Location: login.php");
                    exit();

                }
                else {

                    $message =
                        "Account created, but employee record failed.";

                }

            }
            else {

                $message =
                    "Registration failed.";

            }

        }

    }

}

?>

<!DOCTYPE html>

<html>

<head>

    <title>Sign Up</title>

    <link
        rel="stylesheet"
        href="styles/signup.css"
    >

</head>


<body>


<div class="signup-box">

    <h2>Create Account</h2>


    <?php

    if ($message != "") {

        echo "<p class='error'>" .
            htmlspecialchars($message) .
            "</p>";

    }

    ?>


    <form method="POST">


        <!-- NAME -->

        <input
            type="text"
            name="name"
            placeholder="Name"
            required
        >


        <!-- USERNAME -->

        <input
            type="text"
            name="username"
            placeholder="Gmail"
            required
        >


        <!-- PASSWORD -->

        <input
            type="password"
            name="password"
            placeholder="Password"
            required
        >


        <!-- CONFIRM PASSWORD -->

        <input
            type="password"
            name="confirm_password"
            placeholder="Confirm Password"
            required
        >


        <button type="submit">

            Sign Up

        </button>


    </form>


    <p style="margin-top:15px;text-align:center;">

        Already have an account?

        <a href="login.php">
            Login
        </a>

    </p>


</div>


</body>

</html>
```
