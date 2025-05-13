<?php
// Include database connection
require '../config/config.php';

// Number of records to display per page
$recordsPerPage = 10;

// Get the current page number from the URL
$page = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;

// Calculate the starting record for the current page
$startIndex = ($page - 1) * $recordsPerPage;

// Fetch the total number of patients
$totalPatientsQuery = "SELECT COUNT(patient_id) AS total FROM patients";
$totalResult = $conn->query($totalPatientsQuery);
$totalRowCount = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRowCount / $recordsPerPage);

// Fetch patients for the current page
$query = "SELECT p.patient_id, u.first_name, u.last_name, p.date_of_birth, u.email, u.phone_number,
          pd.medical_record_number, pd.insurance_provider, pd.insurance_policy_number,
          pd.emergency_contact_name, pd.emergency_contact_phone
          FROM patients p
          JOIN users u ON p.user_id = u.user_id
          LEFT JOIN patient_descriptions pd ON p.patient_id = pd.patient_id
          ORDER BY u.last_name, u.first_name LIMIT ?, ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $startIndex, $recordsPerPage);
$stmt->execute();
$result = $stmt->get_result();
$patients = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Function to generate pagination links
function generatePaginationLinks($currentPage, $totalPages)
{
    $links = '';
    $range = 2; // Number of page links to show before and after the current page

    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $range && $i <= $currentPage + $range)) {
            if ($i == $currentPage) {
                $links .= '<span class="active">' . $i . '</span>';
            } else {
                $links .= '<button onclick="window.location.href=\'?page=patients&page_num=' . $i . '\'">' . $i . '</button>';
            }
        } elseif (($i == $currentPage - $range - 1 && $currentPage - $range > 2) || ($i == $currentPage + $range + 1 && $currentPage + $range < $totalPages - 1)) {
            $links .= '<span>...</span>';
        }
    }
    return $links;
}
?>

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

        .patient-actions {
            text-align: center;
        }

        .patient-actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            text-decoration: none;
            color: #333;
        }

        .patient-actions a span.material-icons {
            margin-right: 5px;
            /* Space between icon and text */
        }

        .patient-actions a:hover {
            color: #6c5dd3;
            /* Change color on hover */
        }

        .add-patient-button {
            background-color: #6c5dd3;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .add-patient-button:hover {
            background-color: #5649a8;
        }

        .search-bar input[type="text"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .patients-list {
            background-color: #fff;
            border-radius: 8px;
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
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            background-color: #fff;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        .pagination button.active {
            background-color: #6c5dd3;
            color: white;
            border-color: #6c5dd3;
        }

        .pagination button:hover {
            background-color: #f0f0f0;
            border-color: #bbb;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .patients-table {
                width: 100%;
            }

            .patients-table thead {
                display: none;
            }

            .patients-table tr {
                display: block;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 6px;
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

        /* Modernized Modal Styles */
        #addPatientModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        #addPatientModal .modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        #addPatientModal h2 {
            color: #333;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.5em;
            font-weight: 500;
        }

        #addPatientModal label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-size: 0.95em;
        }

        #addPatientModal input[type="text"],
        #addPatientModal input[type="email"],
        #addPatientModal input[type="date"] {
            width: calc(100% - 16px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            box-sizing: border-box;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        #addPatientModal .modal-actions {
            text-align: right;
            margin-top: 20px;
        }

        #addPatientModal .modal-actions button {
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        #addPatientModal .modal-actions button:first-child {
            margin-right: 10px;
            background-color: #f0f0f0;
            color: #555;
        }

        #addPatientModal .modal-actions button:first-child:hover {
            background-color: #e0e0f0;
        }

        #addPatientModal .modal-actions button:last-child {
            background-color: #6c5dd3;
            color: #fff;
        }

        #addPatientModal .modal-actions button:last-child:hover {
            background-color: #5649a8;
        }

        /* Notification Styles */
        .notification {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px 20px;
            border-radius: 6px;
            z-index: 1001;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }

        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .notification.show {
            opacity: 1;
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
                <!-- Load Google Material Icons in your <head> section -->
                <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
                <tbody>
                    <?php if (!empty($patients)): ?>
                        <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td data-label="Patient ID"><?php echo htmlspecialchars($patient['patient_id']); ?></td>
                                <td data-label="First Name"><?php echo htmlspecialchars($patient['first_name']); ?></td>
                                <td data-label="Last Name"><?php echo htmlspecialchars($patient['last_name']); ?></td>
                                <td data-label="Date of Birth"><?php echo htmlspecialchars($patient['date_of_birth']); ?></td>
                                <td data-label="Email"><?php echo htmlspecialchars($patient['email']); ?></td>
                                <td data-label="Phone"><?php echo htmlspecialchars($patient['phone_number']); ?></td>
                                <td class="patient-actions" data-label="Actions">
                                    <a
                                        href="functions/view_patient.php?id=<?php echo htmlspecialchars($patient['patient_id']); ?>">
                                        <span class="material-icons">visibility</span> View</a>
                                    <a
                                        href="functions/edit_patient.php?id=<?php echo htmlspecialchars($patient['patient_id']); ?>"><span
                                            class="material-icons">edit</span> Edit</a>
                                    <a href="functions/delete_patient.php?id=<?php echo htmlspecialchars($patient['patient_id']); ?>"
                                        onclick="return confirm('Are you sure?')"><span class="material-icons">delete</span>
                                        Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No patients found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>


            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php echo generatePaginationLinks($page, $totalPages); ?>
            </div>
        <?php endif; ?>
    </div>
    <div id="addPatientModal" class="modal">
        <div class="modal-content">
            <h2>Add New Patient</h2>
            <form action="functions/add_patient.php" method="POST">
                <div>
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div>
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                <div>
                    <label for="dob">Date of Birth:</label>
                    <input type="date" id="dob" name="dob" required>
                </div>
                <div>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div>
                    <label for="phone">Phone:</label>
                    <input type="text" id="phone" name="phone" required>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeAddPatientModal()">Cancel</button>
                    <button type="submit">Add Patient</button>
                </div>
            </form>
        </div>
    </div>

    <div id="notification" class="notification"></div>

    <script>
        const modal = document.getElementById('addPatientModal');
        const addButton = document.querySelector('.add-patient-button');
        const notificationDiv = document.getElementById('notification');

        function openAddPatientModal() {
            modal.style.display = 'flex';
        }

        function closeAddPatientModal() {
            modal.style.display = 'none';
        }

        // Close the modal if the user clicks outside of it
        window.addEventListener('click', function (event) {
            if (event.target == modal) {
                closeAddPatientModal();
            }
        });

        addButton.addEventListener('click', openAddPatientModal);

        // Function to display notification
        function showNotification(message, type = 'success') {
            notificationDiv.textContent = message;
            notificationDiv.className = `notification ${type} show`;
            setTimeout(() => {
                notificationDiv.classList.remove('show');
            }, 3000); // Hide after 3 seconds
        }

        // Check URL parameters for messages
        const urlParams = new URLSearchParams(window.location.search);
        const successMessage = urlParams.get('success');
        const errorMessage = urlParams.get('error');

        if (successMessage) {
            showNotification(successMessage);
            // Clear the success parameter from the URL
            history.replaceState(null, null, window.location.pathname + window.location.search.split('&')[0]);
        }

        if (errorMessage) {
            showNotification(errorMessage, 'error');
            // Clear the error parameter from the URL
            history.replaceState(null, null, window.location.pathname + window.location.search.split('&')[0]);
        }
    </script>

</body>

</html>
<?php
$conn->close();
?>