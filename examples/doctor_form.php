<?php
session_start();
require_once '../config/config.php';

// Check authentication (assuming admin role is required)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
    // Redirect to login page
    header('Location: ../index.php?error=unauthorized');
    exit();
}

// Fetch available specializations
$specQuery = "SELECT specialization_id, specialization_name FROM specializations ORDER BY specialization_name";
$specResult = mysqli_query($conn, $specQuery);

if (!$specResult) {
    $error = "Failed to fetch specializations: " . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Doctor</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>
    <div class="container mt-4">
        <h2>Add New Doctor</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form id="addDoctorForm" class="needs-validation" novalidate>
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                            <div class="invalid-feedback">Username is required</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback">Valid email is required</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="invalid-feedback">Password is required</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                required>
                            <div class="invalid-feedback">Passwords must match</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5>Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                            <div class="invalid-feedback">First name is required</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                            <div class="invalid-feedback">Last name is required</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number" required>
                        <div class="invalid-feedback">Phone number is required</div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5>Professional Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="specialization_id" class="form-label">Specialization</label>
                        <select class="form-select" id="specialization_id" name="specialization_id" required>
                            <option value="">Select a specialization</option>
                            <?php if (isset($specResult) && mysqli_num_rows($specResult) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($specResult)): ?>
                                    <option value="<?php echo $row['specialization_id']; ?>">
                                        <?php echo htmlspecialchars($row['specialization_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                        <div class="invalid-feedback">Please select a specialization</div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Office Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                        <div class="invalid-feedback">Address is required</div>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="button" class="btn btn-secondary me-md-2"
                    onclick="window.location.href='../views/admin/doctors.php'">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Doctor</button>
            </div>
        </form>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('addDoctorForm');

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                if (!form.checkValidity()) {
                    event.stopPropagation();
                    form.classList.add('was-validated');
                    return;
                }

                // Check if passwords match
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;

                if (password !== confirmPassword) {
                    document.getElementById('confirm_password').setCustomValidity('Passwords do not match');
                    form.classList.add('was-validated');
                    return;
                }

                // Collect form data
                const formData = {
                    username: document.getElementById('username').value,
                    email: document.getElementById('email').value,
                    password: password,
                    first_name: document.getElementById('first_name').value,
                    last_name: document.getElementById('last_name').value,
                    phone_number: document.getElementById('phone_number').value,
                    specialization_id: document.getElementById('specialization_id').value,
                    address: document.getElementById('address').value
                };

                // Submit data via fetch API
                fetch('../examples/add_doctor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Doctor added successfully!');
                            window.location.href = '../views/admin/doctors.php';
                        } else {
                            alert('Error: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
            });

            // Custom validation for password confirmation
            const confirmPasswordInput = document.getElementById('confirm_password');
            confirmPasswordInput.addEventListener('input', function () {
                const password = document.getElementById('password').value;
                if (this.value !== password) {
                    this.setCustomValidity('Passwords do not match');
                } else {
                    this.setCustomValidity('');
                }
            });
        });
    </script>
</body>

</html>