<?php

include "includes/sidebar.php";
include "includes/config.php";


// ==================================================
// CHECK LOGIN
// ==================================================

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit();

}

$userId = $_SESSION["user_id"];


// ==================================================
// GET EMPLOYEE
// ==================================================

$stmt = $conn->prepare("
    SELECT
        u.name,
        u.username,
        e.id AS employee_id,
        e.employee_code,
        e.monthly_salary
    FROM employees e
    INNER JOIN users u
        ON e.user_id = u.id
    WHERE e.user_id = ?
");

$stmt->bind_param(
    "i",
    $userId
);

$stmt->execute();

$employee = $stmt
    ->get_result()
    ->fetch_assoc();


if (!$employee) {

    die("Employee record not found.");

}


// ==================================================
// EMPLOYEE INFORMATION
// ==================================================

$employeeId = $employee["employee_id"];

$employeeName = $employee["name"];

$monthlySalary =
    floatval($employee["monthly_salary"]);


// ==================================================
// CURRENT DATE
// ==================================================

$today = new DateTime();

$currentYear =
    $today->format("Y");

$currentMonth =
    $today->format("m");

$currentDay =
    intval($today->format("d"));


// ==================================================
// COUNT WORKING DAYS OF CURRENT MONTH
// ==================================================

$daysInMonth = cal_days_in_month(
    CAL_GREGORIAN,
    $currentMonth,
    $currentYear
);

$firstWorkingDays = 0;
$secondWorkingDays = 0;


// ==================================================
// 1 - 15 WORKING DAYS
// ==================================================

for (
    $day = 1;
    $day <= 15;
    $day++
) {

    $date = new DateTime(
        "$currentYear-$currentMonth-" .
        str_pad(
            $day,
            2,
            "0",
            STR_PAD_LEFT
        )
    );

    if ($date->format("N") <= 5) {

        $firstWorkingDays++;

    }

}


// ==================================================
// 16 - 30 WORKING DAYS
// ==================================================

$endSecond = min(
    30,
    $daysInMonth
);

for (
    $day = 16;
    $day <= $endSecond;
    $day++
) {

    $date = new DateTime(
        "$currentYear-$currentMonth-" .
        str_pad(
            $day,
            2,
            "0",
            STR_PAD_LEFT
        )
    );

    if ($date->format("N") <= 5) {

        $secondWorkingDays++;

    }

}


$totalWorkingDays =
    $firstWorkingDays +
    $secondWorkingDays;


// ==================================================
// DAILY RATE
// ==================================================

$dailyRate = 0;

if (
    $totalWorkingDays > 0 &&
    $monthlySalary > 0
) {

    $dailyRate =
        $monthlySalary /
        $totalWorkingDays;

}


// ==================================================
// 30TH PAYROLL
// CURRENT MONTH 1 - 15
// ==================================================

$firstWorkedDays = 0;

$firstPayroll = 0;


// Only calculate dates that have already happened.

$firstEndDay =
    min(
        15,
        $currentDay
    );


if ($firstEndDay >= 1) {

    $startFirst =
        "$currentYear-$currentMonth-01";

    $endFirst =
        "$currentYear-$currentMonth-" .
        str_pad(
            $firstEndDay,
            2,
            "0",
            STR_PAD_LEFT
        );


    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total

        FROM employee_attendance

        WHERE employee_id = ?

        AND attendance_date BETWEEN ? AND ?

        AND DAYOFWEEK(attendance_date)
            BETWEEN 2 AND 6

        AND attendance_date <= CURDATE()
    ");


    $stmt->bind_param(
        "iss",
        $employeeId,
        $startFirst,
        $endFirst
    );


    $stmt->execute();


    $result = $stmt
        ->get_result()
        ->fetch_assoc();


    $firstWorkedDays =
        intval($result["total"]);


    $firstPayroll =
        $firstWorkedDays *
        $dailyRate;

}


// ==================================================
// 15TH PAYROLL
// PREVIOUS MONTH 16 - 30
// ==================================================

$secondWorkedDays = 0;

$secondPayroll = 0;


// Get previous month

$previousMonth = clone $today;

$previousMonth->modify("-1 month");


$previousYear =
    $previousMonth->format("Y");

$previousMonthNumber =
    $previousMonth->format("m");

$previousDaysInMonth =
    cal_days_in_month(
        CAL_GREGORIAN,
        $previousMonthNumber,
        $previousYear
    );


// 16 - 30

$previousEndDay =
    min(
        30,
        $previousDaysInMonth
    );


$startSecond =
    "$previousYear-$previousMonthNumber-16";

$endSecond =
    "$previousYear-$previousMonthNumber-" .
    str_pad(
        $previousEndDay,
        2,
        "0",
        STR_PAD_LEFT
    );


$stmt = $conn->prepare("
    SELECT COUNT(*) AS total

    FROM employee_attendance

    WHERE employee_id = ?

    AND attendance_date BETWEEN ? AND ?

    AND DAYOFWEEK(attendance_date)
        BETWEEN 2 AND 6

    AND attendance_date <= CURDATE()
");


$stmt->bind_param(
    "iss",
    $employeeId,
    $startSecond,
    $endSecond
);


$stmt->execute();


$result = $stmt
    ->get_result()
    ->fetch_assoc();


$secondWorkedDays =
    intval($result["total"]);


// Calculate previous month's working days

$previousFirstWorkingDays = 0;
$previousSecondWorkingDays = 0;


// Previous month 1 - 15

for (
    $day = 1;
    $day <= 15;
    $day++
) {

    $date = new DateTime(
        "$previousYear-$previousMonthNumber-" .
        str_pad(
            $day,
            2,
            "0",
            STR_PAD_LEFT
        )
    );

    if ($date->format("N") <= 5) {

        $previousFirstWorkingDays++;

    }

}


// Previous month 16 - 30

for (
    $day = 16;
    $day <= $previousEndDay;
    $day++
) {

    $date = new DateTime(
        "$previousYear-$previousMonthNumber-" .
        str_pad(
            $day,
            2,
            "0",
            STR_PAD_LEFT
        )
    );

    if ($date->format("N") <= 5) {

        $previousSecondWorkingDays++;

    }

}


$previousTotalWorkingDays =
    $previousFirstWorkingDays +
    $previousSecondWorkingDays;


$previousDailyRate = 0;


if (
    $previousTotalWorkingDays > 0 &&
    $monthlySalary > 0
) {

    $previousDailyRate =
        $monthlySalary /
        $previousTotalWorkingDays;

}


$secondPayroll =
    $secondWorkedDays *
    $previousDailyRate;

?>

<div class="content">

    <h1>Dashboard</h1>


    <p>

        Welcome,

        <strong>

            <?php
            echo htmlspecialchars(
                $employeeName
            );
            ?>

        </strong>

    </p>


    <p>

        Today:

        <strong>

            <?php
            echo date("F d, Y");
            ?>

        </strong>

    </p>


    <hr>


    <div class="payroll-cards">


        <!-- 15TH PAYROLL -->

        <div class="payroll-card">

            <h3>
                Payroll - 15th
            </h3>


            <p>

                Cutoff:

                <strong>
                    16 - 30
                </strong>

            </p>


            <h1>

                ₱<?php

                echo number_format(
                    $secondPayroll,
                    2
                );

                ?>

            </h1>


            <p>

                <?php
                echo $secondWorkedDays;
                ?>

                day(s) worked

            </p>


            <small>

                <?php

                echo $previousMonth
                    ->format("F");

                ?>

                16-30

            </small>

        </div>


        <!-- 30TH PAYROLL -->

        <div class="payroll-card">

            <h3>
                Payroll - 30th
            </h3>


            <p>

                Cutoff:

                <strong>
                    1 - 15
                </strong>

            </p>


            <h1>

                ₱<?php

                echo number_format(
                    $firstPayroll,
                    2
                );

                ?>

            </h1>


            <p>

                <?php
                echo $firstWorkedDays;
                ?>

                day(s) worked

            </p>


            <small>

                <?php

                echo $today
                    ->format("F");

                ?>

                1-15

            </small>

        </div>


    </div>


    <hr>


    <h3>
        Salary Information
    </h3>


    <p>

        Monthly Salary:

        <strong>

            ₱<?php

            echo number_format(
                $monthlySalary,
                2
            );

            ?>

        </strong>

    </p>


    <p>

        Daily Rate:

        <strong>

            ₱<?php

            echo number_format(
                $dailyRate,
                2
            );

            ?>

        </strong>

    </p>

</div>


<style>

.payroll-cards {

    display: flex;

    gap: 20px;

    margin-top: 25px;

    flex-wrap: wrap;

}

.payroll-card {

    background: white;

    padding: 25px;

    width: 300px;

    border-radius: 10px;

    box-shadow:
        0 2px 10px
        rgba(0,0,0,0.1);

}

.payroll-card h1 {

    margin: 15px 0;

}

.payroll-card small {

    color: #666;

}

</style>


<?php include "includes/footer.php"; ?>
