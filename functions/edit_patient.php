<?php
// Include database connection
require_once '../config/config.php'; // Adjust the path to config.php

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $patientId = intval($_GET['id']);

    // Fetch patient data for editing
    $query = "SELECT id, first_name, last_name, date_of_birth, email, phone
              FROM patients
              WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();
    $stmt->close();

    if ($patient) {
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
            </style>
        </head>

        <body>
            <div class="container">
                <h1>Edit Patient</h1>
                <form action="functions/update_patient.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($patient['id']); ?>">
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
                    <?php if (isset($_GET['error'])): ?>
                        <p class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></p>
                    <?php endif; ?>
                </form>
            </div>
        </body>

        </html>
        <?php
    } else {
        // Patient not found
        header("Location: /it38b-Enterprise/index.php?page=patients&error=Patient not found");
        exit();
    }
} else {
    // Invalid or missing ID
    header("Location: /it38b-Enterprise/index.php?page=patients&error=Invalid patient ID");
    exit();
}

$conn->close();
?>