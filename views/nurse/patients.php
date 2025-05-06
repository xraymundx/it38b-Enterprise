<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        .patients-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .add-patient-button {
            background-color: #6c5dd3;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        .add-patient-button:hover {
            background-color: #5649a8;
        }

        .search-bar input[type="text"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1em;
        }

        .patients-list {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }

        .patients-table {
            width: 100%;
            border-collapse: collapse;
        }

        .patients-table th,
        .patients-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .patients-table th {
            background-color: #f9f9f9;
            color: #555;
            font-weight: bold;
        }

        .patients-table tbody tr:last-child td {
            border-bottom: none;
        }

        .patient-actions a {
            margin-right: 10px;
            text-decoration: none;
            color: #6c5dd3;
            font-size: 0.9em;
        }

        .patient-actions a:hover {
            text-decoration: underline;
        }

        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }

        .pagination button,
        .pagination span {
            padding: 8px 12px;
            margin: 0 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            background-color: #fff;
        }

        .pagination button.active {
            background-color: #6c5dd3;
            color: white;
            border-color: #6c5dd3;
        }

        .pagination button:hover {
            background-color: #f0f0f0;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .patients-table {
                width: 100%;
            }

            .patients-table thead {
                display: none;
                /* Hide table headers on small screens */
            }

            .patients-table tr {
                display: block;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }

            .patients-table td {
                display: block;
                text-align: right;
                padding-left: 50%;
                position: relative;
                border-bottom: none;
            }

            .patients-table td:before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                text-align: left;
                font-weight: bold;
            }

            .patients-actions {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }

            .search-bar {
                width: 100%;
            }

            .search-bar input[type="text"] {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Patients</h1>

        <div class="patients-actions">
            <button class="add-patient-button">+ Add New Patient</button>
            <div class="search-bar">
                <input type="text" placeholder="Search patients...">
            </div>
        </div>

        <div class="patients-list">
            <table class="patients-table">
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Date of Birth</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Replace this with your actual patient data retrieval logic
                    $patients = [
                        ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe', 'dob' => '1985-03-15', 'email' => 'john.doe@example.com', 'phone' => '123-456-7890'],
                        ['id' => 2, 'first_name' => 'Jane', 'last_name' => 'Smith', 'dob' => '1992-07-20', 'email' => 'jane.smith@example.com', 'phone' => '987-654-3210'],
                        ['id' => 3, 'first_name' => 'Peter', 'last_name' => 'Jones', 'dob' => '1988-11-01', 'email' => 'peter.jones@example.com', 'phone' => '555-123-4567'],
                        // Add more patients here
                    ];

                    foreach ($patients as $patient) {
                        echo '<tr>';
                        echo '<td data-label="Patient ID">' . htmlspecialchars($patient['id']) . '</td>';
                        echo '<td data-label="First Name">' . htmlspecialchars($patient['first_name']) . '</td>';
                        echo '<td data-label="Last Name">' . htmlspecialchars($patient['last_name']) . '</td>';
                        echo '<td data-label="Date of Birth">' . htmlspecialchars($patient['dob']) . '</td>';
                        echo '<td data-label="Email">' . htmlspecialchars($patient['email']) . '</td>';
                        echo '<td data-label="Phone">' . htmlspecialchars($patient['phone']) . '</td>';
                        echo '<td class="patient-actions" data-label="Actions">';
                        echo '<a href="view_patient.php?id=' . htmlspecialchars($patient['id']) . '">View</a>';
                        echo '<a href="edit_patient.php?id=' . htmlspecialchars($patient['id']) . '">Edit</a>';
                        echo '<a href="delete_patient.php?id=' . htmlspecialchars($patient['id']) . '" onclick="return confirm(\'Are you sure?\')">Delete</a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <button>&lt;</button>
            <span class="active">1</span>
            <button>2</button>
            <button>3</button>
            <button>&gt;</button>
        </div>
    </div>
</body>

</html>