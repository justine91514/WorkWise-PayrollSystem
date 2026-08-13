```php
<?php

include "includes/sidebar.php";
include "includes/config.php";


// ==================================================
// GET ALL REGISTERED USERS / EMPLOYEES
// ==================================================

$result = $conn->query("
    SELECT
        e.id,
        e.user_id,
        e.employee_code,
        u.name,
        u.username,
        e.monthly_salary,
        e.status,
        e.created_at

    FROM employees e

    INNER JOIN users u
        ON e.user_id = u.id

    ORDER BY e.id DESC
");

?>

<div class="content">

    <h2>Employees</h2>

    <p>
        Employees listed here are users who have
        created an account.
    </p>

    <hr>

    <h3>Employee List</h3>

    <table border="1" cellpadding="10">

        <tr>

            <th>ID</th>

            <th>Employee Code</th>

            <th>Name</th>

            <th>Username</th>

            <th>Monthly Salary</th>

            <th>Status</th>

            <th>Date Registered</th>

        </tr>


        <?php if ($result->num_rows > 0): ?>

            <?php while (
                $employee =
                $result->fetch_assoc()
            ): ?>

                <tr>

                    <td>

                        <?php
                        echo $employee["id"];
                        ?>

                    </td>


                    <td>

                        <?php
                        echo htmlspecialchars(
                            $employee["employee_code"]
                        );
                        ?>

                    </td>


                    <td>

                        <?php
                        echo htmlspecialchars(
                            $employee["name"]
                        );
                        ?>

                    </td>


                    <td>

                        <?php
                        echo htmlspecialchars(
                            $employee["username"]
                        );
                        ?>

                    </td>


                    <td>

                        ₱<?php

                        echo number_format(
                            $employee["monthly_salary"],
                            2
                        );

                        ?>

                    </td>


                    <td>

                        <?php
                        echo htmlspecialchars(
                            $employee["status"]
                        );
                        ?>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $employee["created_at"]
                        );

                        ?>

                    </td>

                </tr>

            <?php endwhile; ?>


        <?php else: ?>

            <tr>

                <td
                    colspan="7"
                    style="text-align:center;"
                >

                    No employees registered yet.

                </td>

            </tr>

        <?php endif; ?>


    </table>

</div>


<?php include "includes/footer.php"; ?>
```
