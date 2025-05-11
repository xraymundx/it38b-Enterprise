<?php
// Include database connection
require_once '../config/config.php'; // Adjust the path to config.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If the form is submitted with POST request, handle the update

    // Sanitize input and perform validation
    $patientId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $dob = filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);

    // Validation
    $errors = [];
    if (empty($firstName))
        $errors[] = "First name is required.";
    if (empty($lastName))
        $errors[] = "Last name is required.";
    if (empty($dob))
        $errors[] = "Date of birth is required.";
    if (empty($email))
        $errors[] = "Email is required.";
    elseif (!$email)
        $errors[] = "Invalid email format.";
    if (empty($phone))
        $errors[] = "Phone number is required.";

    if (empty($errors) && $patientId) {
        // Prepare and execute the update query
        $query = "UPDATE patients SET first_name = ?, last_name = ?, date_of_birth = ?, email = ?, phone = ? WHERE patient_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssi", $firstName, $lastName, $dob, $email, $phone, $patientId);

        if ($stmt->execute()) {
            // Redirect with success message
            header("Location: /it38b-Enterprise/index.php?page=patients&notification=success&message=Patient+updated+successfully");
            exit();
        } else {
            // Redirect with error message
            header("Location: /it38b-Enterprise/index.php?page=patients&notification=error&message=Error+updating+patient");
            exit();
        }
    } else {
        // Redirect with validation errors
        $errorMessage = implode("<br>", $errors);
        header("Location: /it38b-Enterprise/index.php?page=edit_patient&id=" . $patientId . "&error=" . urlencode($errorMessage));
        exit();
    }
} else {
    // If it's a GET request, show the edit form
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $patientId = intval($_GET['id']);

        // Query to get the patient's current data
        $query = "SELECT patient_id, first_name, last_name, date_of_birth, email, phone FROM patients WHERE patient_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $patientId);
        $stmt->execute();
        $result = $stmt->get_result();
        $patient = $result->fetch_assoc();
        $stmt->close();

        if ($patient) {
            // Show the edit form with patient data
            ?>
            <!DOCTYPE html>
            <html lang="en">

            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Edit Patient</title>
                <style>
                    body {
                        font-family: sans-serif;
                        background-color: #f4f6f8;
                        margin: 0;
                        padding: 20px;
                        box-sizing: border-box;
                    }

                    .container {
                        max-width: 600px;
                        margin: 0 auto;
                        background-color: #fff;
                        padding: 30px;
                        border-radius: 8px;
                        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
                    }

                    h1 {
                        color: #333;
                        margin-bottom: 20px;
                    }

                    .form-group {
                        margin-bottom: 15px;
                    }

                    label {
                        display: block;
                        margin-bottom: 5px;
                        color: #555;
                        font-weight: bold;
                    }

                    input[type="text"],
                    input[type="email"],
                    input[type="date"] {
                        width: calc(100% - 12px);
                        padding: 8px;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                        box-sizing: border-box;
                        margin-bottom: 10px;
                    }

                    .actions button {
                        padding: 10px 15px;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 1em;
                        transition: background-color 0.3s ease;
                    }

                    .actions button.save {
                        background-color: #5cb85c;
                        color: white;
                    }

                    .actions button.save:hover {
                        background-color: #4cae4c;
                    }

                    .actions button.cancel {
                        background-color: #f0ad4e;
                        color: white;
                        margin-left: 10px;
                    }

                    .actions button.cancel:hover {
                        background-color: #eea236;
                    }

                    .error-message {
                        color: red;
                        margin-top: 10px;
                    }

                    /* Notification Styles */
                    .notification {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        padding: 15px;
                        background-color: #5cb85c;
                        color: white;
                        border-radius: 4px;
                        opacity: 0;
                        transition: opacity 0.5s ease-in-out;
                        z-index: 9999;
                    }

                    .notification.error {
                        background-color: #d9534f;
                    }

                    .notification.show {
                        opacity: 1;
                    }
                </style>
            </head>

            <body>
                <div class="container">
                    <h1>Edit Patient</h1>
                    <form action="" method="POST">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($patient['patient_id']); ?>">
                        <div class="form-group">
                            <label for="first_name">First Name:</label>
                            <input type="text" id="first_name" name="first_name"
                                value="<?php echo htmlspecialchars($patient['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name:</label>
                            <input type="text" id="last_name" name="last_name"
                                value="<?php echo htmlspecialchars($patient['last_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="dob">Date of Birth:</label>
                            <input type="date" id="dob" name="dob"
                                value="<?php echo htmlspecialchars($patient['date_of_birth']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>"
                                required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone:</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($patient['phone']); ?>"
                                required>
                        </div>
                        <div class="actions">
                            <button type="submit" class="save">Save Changes</button>
                            <button type="button" class="cancel"
                                onclick="window.location.href='/it38b-Enterprise/index.php?page=patients'">Cancel</button>
                        </div>
                    </form>
                </div>

                <?php
                // Check for notification
                if (isset($_GET['notification']) && isset($_GET['message'])) {
                    $notificationType = $_GET['notification'] === 'error' ? 'error' : 'success';
                    $message = htmlspecialchars($_GET['message']);
                    echo "<div id='notification' class='notification $notificationType'>$message</div>";
                }
                ?>

                <script>
                    // Display the notification and remove it after a few seconds
                    window.onload = function () {
                        const notification = document.getElementById('notification');
                        if (notification) {
                            notification.classList.add('show');
                            setTimeout(function () {
                                notification.classList.remove('show');
                            }, 5000); // Hide after 5 seconds
                        }
                    };
                </script>
            </body>

            </html>
            <?php
        } else {
            // Patient not found
            header("Location: /it38b-Enterprise/index.php?page=patients&error=Patient+not+found");
            exit();
        }
    } else {
        // Invalid or missing ID
        header("Location: /it38b-Enterprise/index.php?page=patients&error=Invalid+patient+ID");
        exit();
    }
}

$conn->close();
?>