<style>
    .dashboard-cards-container {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .dashboard-card {
        flex: 1;
        min-width: 200px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        padding: 15px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        box-sizing: border-box;
        color: white;
    }

    .dashboard-card-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        font-size: 2em;
        opacity: 0.8;
    }

    .dashboard-card-icon .material-symbols-outlined {
        font-size: inherit;
        /* Inherit size from parent */
    }

    .dashboard-card-title {
        font-size: 0.9em;
        margin-bottom: 5px;
        opacity: 0.9;
    }

    .dashboard-card-value {
        font-size: 1.8em;
        font-weight: bold;
    }

    /* Specific background colors for each card */
    .card-earnings {
        background-color: #3f51b5;
        /* Blue */
    }

    .card-patients {
        background-color: #ffc107;
        /* Yellow */
    }

    .card-appointments {
        background-color: #4caf50;
        /* Green */
    }

    .card-earnings .dashboard-card-icon {
        background-color: rgba(255, 255, 255, 0.2);
    }

    .card-patients .dashboard-card-icon {
        background-color: rgba(255, 255, 255, 0.47);
    }

    .card-appointments .dashboard-card-icon {
        background-color: rgba(255, 255, 255, 0.2);
    }
</style>

<div class="dashboard-cards-container">
    <div class="dashboard-card card-earnings">
        <div class="dashboard-card-icon">
            <span class="material-symbols-outlined">paid</span>
        </div>
        <div class="dashboard-card-info">
            <div class="dashboard-card-title">Clinic Earnings</div>
            <div class="dashboard-card-value">100 PHP</div>
        </div>
    </div>

    <div class="dashboard-card card-patients">
        <div class="dashboard-card-icon">
            <span class="material-symbols-outlined">person</span>
        </div>
        <div class="dashboard-card-info">
            <div class="dashboard-card-title">Total Patient</div>
            <div class="dashboard-card-value">22</div>
        </div>
    </div>

    <div class="dashboard-card card-appointments">
        <div class="dashboard-card-icon">
            <span class="material-symbols-outlined">calendar_month</span>
        </div>
        <div class="dashboard-card-info">
            <div class="dashboard-card-title">Appointments</div>
            <div class="dashboard-card-value">50</div>
        </div>
    </div>
</div>