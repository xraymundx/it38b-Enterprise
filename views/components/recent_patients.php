<style>
    .recent-patients-container {
        border: 1px solid #333;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        padding: 20px;
        margin-bottom: 20px;
    }

    .recent-patients-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .recent-patients-header h2 {
        margin: 0;
        font-size: 1.2em;
        color: #333;
    }

    .recent-patients-table {
        width: 100%;
        border-collapse: collapse;
    }

    .recent-patients-table thead th {
        padding: 10px;
        text-align: left;
        font-size: 0.9em;
        color: #777;
        border-bottom: 1px solid #ccc;
    }

    .recent-patients-table tbody td {
        padding: 15px 10px;
        text-align: left;
        font-size: 0.95em;
        color: #333;
        border-bottom: 1px solid #ccc;
    }

    .recent-patients-table tbody tr:last-child td {
        border-bottom: none;
    }

    .patient-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .patient-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        /* You would set the actual image as a background-image in your CSS,
        using a URL from your database or a default.  For this example, we
        will just use a color. */
        background-color: #ddd;
        /* Placeholder color */
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2em;
        color: #666;
    }

    .patient-name {
        font-weight: 500;
    }

    .see-more-link {
        text-align: center;
        margin-top: 10px;
    }

    .see-more-link a {
        display: inline-block;

        padding: 8px 15px;
        color: #fff;

        background-color: #007bff;
        border: none;
        border-radius: 20px;

        text-decoration: none;
        font-size: 0.9em;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .see-more-link a:hover {
        background-color: #0056b3;
        /* Darker blue on hover */
        text-decoration: none;
        /* Remove underline on hover for button style */
    }
</style>

<div class="recent-patients-container">
    <div class="recent-patients-header">
        <h2>Recent Patients</h2>
    </div>
    <table class="recent-patients-table">
        <thead>
            <tr>
                <th>Patient</th>
                <th>Date</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="patient-info">
                        <div class="patient-avatar">
                            <?php
                            //  In a real application, you'd fetch the patient's
                            //  initials or an image URL from the database.
                            echo "DJ";  // Example: Display initials
                            ?>
                        </div>
                        <div class="patient-name">Dan Mark Javier</div>
                    </div>
                </td>
                <td>3/24/2025</td>
                <td>Common Cold</td>
            </tr>
            <tr>
                <td>
                    <div class="patient-info">
                        <div class="patient-avatar">
                            <?php echo "DJ"; ?>
                        </div>
                        <div class="patient-name">Dan Mark Javier</div>
                    </div>
                </td>
                <td>3/24/2025</td>
                <td>Common Cold</td>
            </tr>
            <tr>
                <td>
                    <div class="patient-info">
                        <div class="patient-avatar">
                            <?php echo "DJ"; ?>
                        </div>
                        <div class="patient-name">Dan Mark Javier</div>
                    </div>
                </td>
                <td>3/24/2025</td>
                <td>Common Cold</td>
            </tr>
        </tbody>
    </table>
    <div class="see-more-link">
        <a href="appointments.php">See More...</a>
    </div>
</div>