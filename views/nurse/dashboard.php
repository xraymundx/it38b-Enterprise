<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=home,paid,person,calendar_month">
    <style>
        .dashboard-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(5, auto);
            /* Using auto for row height based on content */
            grid-column-gap: 20px;
            grid-row-gap: 20px;
            background-color: #f4f4f4;
            padding: 20px;
            box-sizing: border-box;
            min-height: 100vh;
        }

        .welcome-header {
            grid-area: 1 / 1 / 2 / 3;
            background-color: transparent;
            padding: 0;
            margin-bottom: 0;
            box-shadow: none;
        }

        .welcome-header h2 {
            font-size: 1.5em;
            color: #333;
            margin: 0;
        }

        .dashboard-cards {
            grid-area: 2 / 1 / 3 / 2;
            /* Cards now occupy only the second row of the first column */
            display: flex;
            gap: 20px;
            /* Maintain spacing between cards horizontally */
        }

        .dashboard-recent-patients {
            grid-area: 3 / 1 / 6 / 2;
            /* Recent patients start from the third row */
        }

        .dashboard-calendar-container {
            grid-area: 2 / 2 / 6 / 3;
            display: flex;
            flex-direction: column;
            gap: 20px;
            /* Spacing between title and calendar */
        }

        .dashboard-calendar-container h2 {
            font-size: 1.2em;
            color: #333;
            margin-bottom: 10px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto auto auto;
                /* Adjust rows for single column */
            }

            .welcome-header {
                grid-area: 1 / 1 / 2 / 2;
            }

            .dashboard-cards {
                grid-area: 2 / 1 / 3 / 2;
                flex-direction: column;
                /* Stack cards vertically on smaller screens */
            }

            .dashboard-calendar-container {
                grid-area: 3 / 1 / 6 / 2;
            }

            .dashboard-recent-patients {
                grid-area: 6 / 1 / 9 / 2;
                /* Adjust row for recent patients in single column */
            }
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <header class="welcome-header">
            <h2>Welcome Mr. John</h2>
        </header>

        <div class="dashboard-cards">
            <?php include 'views/components/nurse_widget.php'; ?>
        </div>

        <div class="dashboard-recent-patients">
            <?php include 'views/components/recent_patients.php'; ?>
        </div>

        <div class="dashboard-calendar-container">
            <?php include 'views/components/calendar.php'; ?>
        </div>
    </div>

</body>

</html>