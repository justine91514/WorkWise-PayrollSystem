```php
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
// VARIABLES
// ==================================================

$selectedMonth = "";

$userName = "";
$employeeCode = "";
$employeeId = 0;

$monthlySalary = 0;

$firstWorkedDays = 0;
$secondWorkedDays = 0;

$firstWorkingDays = 0;
$secondWorkingDays = 0;

$firstCutoffSalary = 0;
$secondCutoffSalary = 0;

$firstActualPayroll = 0;
$secondActualPayroll = 0;


// ==================================================
// GET LOGGED-IN USER + EMPLOYEE
// ==================================================

$stmt = $conn->prepare("
    SELECT
        u.name,
        u.username,
        e.id AS employee_id,
        e.employee_code,
        e.monthly_salary
    FROM users u

    INNER JOIN employees e
        ON e.user_id = u.id

    WHERE u.id = ?

    LIMIT 1
");

$stmt->bind_param(
    "i",
    $userId
);

$stmt->execute();

$user =
    $stmt
        ->get_result()
        ->fetch_assoc();


if (!$user) {

    die("Employee record not found.");

}


// ==================================================
// EMPLOYEE INFORMATION
// ==================================================

$userName =
    $user["name"];

$employeeId =
    intval(
        $user["employee_id"]
    );

$employeeCode =
    $user["employee_code"];

$monthlySalary =
    floatval(
        $user["monthly_salary"]
    );


// ==================================================
// CALCULATE PAYROLL
// ==================================================

if (
    $_SERVER["REQUEST_METHOD"] == "POST"
) {

    $selectedMonth =
        $_POST["month"] ?? "";


    $date =
        DateTime::createFromFormat(
            "Y-m",
            $selectedMonth
        );


    if ($date) {

        $year =
            $date->format("Y");

        $month =
            $date->format("m");


        // ==================================================
        // NUMBER OF DAYS IN MONTH
        // ==================================================

        $daysInMonth =
            cal_days_in_month(
                CAL_GREGORIAN,
                $month,
                $year
            );


        // ==================================================
        // FIRST CUTOFF
        // 1 - 15
        // ==================================================

        $firstWorkingDays = 0;


        for (
            $day = 1;
            $day <= 15;
            $day++
        ) {

            $checkDate =
                new DateTime(
                    "$year-$month-" .
                    str_pad(
                        $day,
                        2,
                        "0",
                        STR_PAD_LEFT
                    )
                );


            /*
                Monday = 1
                Tuesday = 2
                Wednesday = 3
                Thursday = 4
                Friday = 5
            */

            if (
                $checkDate->format("N") <= 5
            ) {

                $firstWorkingDays++;

            }

        }


        // ==================================================
        // SECOND CUTOFF
        // 16 - 30
        // ==================================================

        $secondWorkingDays = 0;


        $endDay =
            min(
                30,
                $daysInMonth
            );


        for (
            $day = 16;
            $day <= $endDay;
            $day++
        ) {

            $checkDate =
                new DateTime(
                    "$year-$month-" .
                    str_pad(
                        $day,
                        2,
                        "0",
                        STR_PAD_LEFT
                    )
                );


            if (
                $checkDate->format("N") <= 5
            ) {

                $secondWorkingDays++;

            }

        }


        // ==================================================
        // DIVIDE MONTHLY SALARY INTO TWO CUTOFFS
        // ==================================================

        /*
            Example:

            Monthly Salary = ₱30,000

            1 - 15  = ₱15,000
            16 - 30 = ₱15,000
        */

        $firstCutoffSalary =
            $monthlySalary / 2;


        $secondCutoffSalary =
            $monthlySalary / 2;


        // ==================================================
        // TODAY
        // ==================================================

        $today =
            date("Y-m-d");


        // ==================================================
        // FIRST CUTOFF ATTENDANCE
        // 1 - 15
        // ==================================================

        $startFirst =
            "$year-$month-01";

        $endFirst =
            "$year-$month-15";


        /*
            If today is before the
            end of the cutoff,
            do not count future dates.
        */

        if (
            $endFirst > $today
        ) {

            $endFirst =
                $today;

        }


        if (
            $startFirst <= $endFirst
        ) {

            $stmt = $conn->prepare("
                SELECT COUNT(*) AS total

                FROM employee_attendance

                WHERE employee_id = ?

                AND attendance_date
                    BETWEEN ? AND ?

                AND DAYOFWEEK(attendance_date)
                    BETWEEN 2 AND 6

                AND attendance_date <= ?
            ");


            $stmt->bind_param(
                "isss",
                $employeeId,
                $startFirst,
                $endFirst,
                $today
            );


            $stmt->execute();


            $result =
                $stmt
                    ->get_result()
                    ->fetch_assoc();


            $firstWorkedDays =
                intval(
                    $result["total"]
                );

        }


        // ==================================================
        // SECOND CUTOFF ATTENDANCE
        // 16 - 30
        // ==================================================

        $startSecond =
            "$year-$month-16";

        $endSecond =
            "$year-$month-30";


        /*
            If today is before the
            end of the cutoff,
            do not count future dates.
        */

        if (
            $endSecond > $today
        ) {

            $endSecond =
                $today;

        }


        if (
            $startSecond <= $endSecond
        ) {

            $stmt = $conn->prepare("
                SELECT COUNT(*) AS total

                FROM employee_attendance

                WHERE employee_id = ?

                AND attendance_date
                    BETWEEN ? AND ?

                AND DAYOFWEEK(attendance_date)
                    BETWEEN 2 AND 6

                AND attendance_date <= ?
            ");


            $stmt->bind_param(
                "isss",
                $employeeId,
                $startSecond,
                $endSecond,
                $today
            );


            $stmt->execute();


            $result =
                $stmt
                    ->get_result()
                    ->fetch_assoc();


            $secondWorkedDays =
                intval(
                    $result["total"]
                );

        }


        // ==================================================
        // AUTOMATIC DAILY VALUE PER CUTOFF
        // ==================================================

        /*
            We don't ask the user to enter
            a daily rate anymore.

            Instead:

            First cutoff daily value =
            15th cutoff salary ÷ weekdays

            Second cutoff daily value =
            15th cutoff salary ÷ weekdays
        */

        $firstDailyValue = 0;
        $secondDailyValue = 0;


        if (
            $firstWorkingDays > 0
        ) {

            $firstDailyValue =
                $firstCutoffSalary /
                $firstWorkingDays;

        }


        if (
            $secondWorkingDays > 0
        ) {

            $secondDailyValue =
                $secondCutoffSalary /
                $secondWorkingDays;

        }


        // ==================================================
        // ACTUAL PAYROLL
        // ==================================================

        $firstActualPayroll =
            $firstWorkedDays *
            $firstDailyValue;


        $secondActualPayroll =
            $secondWorkedDays *
            $secondDailyValue;

    }

}

?>

<div class="content">

    <h2>Payroll Details</h2>


    <!-- ==================================================
         EMPLOYEE INFORMATION
    =================================================== -->

    <div class="employee-info">

        <p>

            Employee:

            <strong>

                <?php

                echo htmlspecialchars(
                    $userName
                );

                ?>

            </strong>

        </p>


        <p>

            Employee Code:

            <strong>

                <?php

                echo htmlspecialchars(
                    $employeeCode
                );

                ?>

            </strong>

        </p>

    </div>


    <hr>


    <!-- ==================================================
         SELECT MONTH
    =================================================== -->

    <form method="POST">

        <label for="month">

            Select Month

        </label>

        <br>

        <input
            type="month"
            id="month"
            name="month"
            value="<?php

                echo htmlspecialchars(
                    $selectedMonth
                );

            ?>"
            required
        >

        <br><br>


        <button type="submit">

            Calculate Payroll

        </button>

    </form>


    <?php if ($selectedMonth != ""): ?>

        <hr>


        <!-- ==================================================
             SALARY INFORMATION
        =================================================== -->

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

            Salary Per Cutoff:

            <strong>

                ₱<?php

                echo number_format(
                    $monthlySalary / 2,
                    2
                );

                ?>

            </strong>

        </p>


        <hr>


        <!-- ==================================================
             30TH PAYROLL
             1 - 15
        =================================================== -->

        <h3>
            30th Payroll
        </h3>


        <p>

            Cutoff:

            <strong>
                1 - 15
            </strong>

        </p>


        <p>

            Weekdays in Cutoff:

            <strong>

                <?php

                echo $firstWorkingDays;

                ?>

            </strong>

        </p>


        <p>

            Actual Days Worked:

            <strong>

                <?php

                echo $firstWorkedDays;

                ?>

            </strong>

        </p>


        <p>

            Estimated Payroll:

            <strong>

                ₱<?php

                echo number_format(
                    $firstCutoffSalary,
                    2
                );

                ?>

            </strong>

        </p>


        <p>

            Actual Payroll:

            <strong>

                ₱<?php

                echo number_format(
                    $firstActualPayroll,
                    2
                );

                ?>

            </strong>

        </p>


        <p>

            Pay Date:

            <strong>
                30th of this month
            </strong>

        </p>


        <hr>


        <!-- ==================================================
             15TH PAYROLL
             16 - 30
        =================================================== -->

        <h3>
            15th Payroll
        </h3>


        <p>

            Cutoff:

            <strong>
                16 - 30
            </strong>

        </p>


        <p>

            Weekdays in Cutoff:

            <strong>

                <?php

                echo $secondWorkingDays;

                ?>

            </strong>

        </p>


        <p>

            Actual Days Worked:

            <strong>

                <?php

                echo $secondWorkedDays;

                ?>

            </strong>

        </p>


        <p>

            Estimated Payroll:

            <strong>

                ₱<?php

                echo number_format(
                    $secondCutoffSalary,
                    2
                );

                ?>

            </strong>

        </p>


        <p>

            Actual Payroll:

            <strong>

                ₱<?php

                echo number_format(
                    $secondActualPayroll,
                    2
                );

                ?>

            </strong>

        </p>


        <p>

            Pay Date:

            <strong>
                15th of next month
            </strong>

        </p>


    <?php endif; ?>

</div>


<style>

.employee-info {

    background: white;

    padding: 15px 20px;

    margin: 15px 0;

    border-radius: 8px;

    box-shadow:
        0 2px 8px
        rgba(0, 0, 0, 0.08);

}


.employee-info p {

    margin: 8px 0;

}


.content input[type="month"] {

    padding: 8px;

    width: 250px;

}


.content button {

    padding: 10px 20px;

    cursor: pointer;

}


.content h3 {

    margin-top: 20px;

}


.content strong {

    font-weight: 600;

}

</style>


<?php include "includes/footer.php"; ?>
```
