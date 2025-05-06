<style>
    .dashboard-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        grid-template-rows: auto auto auto;
        /* Adjust rows as needed */
        gap: 20px;
        width: 100%;
        /* Ensure it takes full width of the .main container */
        height: 100%;
        /* Ensure it takes full height of the .main container */
        box-sizing: border-box;
        /* Important for padding and border to be inside width/height */
    }

    .welcome-header {
        grid-area: 1 / 1 / 2 / 3;
        background-color: transparent;
        padding: 0;
        margin-bottom: 20px;
        /* Add some margin below the header */
        box-shadow: none;
    }

    .welcome-header h2 {
        font-size: 1.5em;
        color: #333;
        margin: 0;
    }

    .dashboard-cards {
        grid-area: 2 / 1 / 3 / 2;
        display: flex;
        gap: 20px;
        width: 100%;
        /* Make cards take full width of their grid cell */
    }

    .dashboard-recent-patients {
        grid-area: 3 / 1 / 4 / 2;
        /* Adjust row span as needed */
        width: 100%;
        /* Make recent patients take full width */
    }

    .dashboard-calendar-container {
        grid-area: 2 / 2 / 4 / 3;
        /* Adjust row span to match other content */
        display: flex;
        flex-direction: column;
        gap: 20px;
        width: 100%;
        /* Make calendar take full width */
        height: 100%;
        /* Make calendar take full height of its grid cell */
        box-sizing: border-box;
        /* Important for calendar styling */
    }

    .dashboard-calendar-container h2 {
        font-size: 1.2em;
        color: #333;
        margin-bottom: 10px;
    }

    /* You might need to adjust styles for the calendar itself within calendar.php */

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .dashboard-container {
            grid-template-columns: 1fr;
            grid-template-rows: auto auto auto auto;
        }

        .welcome-header {
            grid-column: 1;
        }

        .dashboard-cards {
            grid-row: 2;
            grid-column: 1;
            flex-direction: column;
        }

        .dashboard-calendar-container {
            grid-row: 3;
            grid-column: 1;
            height: auto;
            /* Reset height for single column */
        }

        .dashboard-recent-patients {
            grid-row: 4;
            grid-column: 1;
        }
    }
</style>

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