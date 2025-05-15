<?php
require_once __DIR__ . '/../../config/config.php';

// Ensure the user is logged in and is a doctor (context from index.php)
$user_id = $_SESSION['user_id'];

// Fetch user data from the users table
$user_query = "SELECT first_name, last_name, email, phone_number FROM users WHERE user_id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$userData = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($user_stmt);

// Fetch doctor's current specialization
$doctor_query = "SELECT d.doctor_id, s.specialization_name, d.specialization_id AS current_specialization_id
        FROM doctors d
        LEFT JOIN specializations s ON d.specialization_id = s.specialization_id
        WHERE d.user_id = ?"; // Assuming link via user_id
$doctor_stmt = mysqli_prepare($conn, $doctor_query);
mysqli_stmt_bind_param($doctor_stmt, "i", $user_id);
mysqli_stmt_execute($doctor_stmt);
$doctor_result = mysqli_stmt_get_result($doctor_stmt);
$doctorData = mysqli_fetch_assoc($doctor_result);
mysqli_stmt_close($doctor_stmt);

// Fetch all available specializations
$sql_specializations = "SELECT specialization_id, specialization_name FROM specializations";
$result_specializations = $conn->query($sql_specializations);
$specializations = [];
if ($result_specializations->num_rows > 0) {
    while ($row = $result_specializations->fetch_assoc()) {
        $specializations[] = $row;
    }
}

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Profile Information
    if (isset($_POST['update_profile'])) {
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone_number = mysqli_real_escape_string($conn, $_POST['phone']);

        $update_profile_query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone_number = ? WHERE user_id = ?";
        $update_profile_stmt = mysqli_prepare($conn, $update_profile_query);
        mysqli_stmt_bind_param($update_profile_stmt, "ssssi", $first_name, $last_name, $email, $phone_number, $user_id);

        if (mysqli_stmt_execute($update_profile_stmt)) {
            $success_message = 'Profile updated successfully!';
            // Update session values if needed
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
        } else {
            $error_message = 'Failed to update profile: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($update_profile_stmt);
    }

    // Update Password
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        $password_query = "SELECT password_hash FROM users WHERE user_id = ?";
        $password_stmt = mysqli_prepare($conn, $password_query);
        mysqli_stmt_bind_param($password_stmt, "i", $user_id);
        mysqli_stmt_execute($password_stmt);
        $password_result = mysqli_stmt_get_result($password_stmt);
        $user_password = mysqli_fetch_assoc($password_result)['password_hash'];
        mysqli_stmt_close($password_stmt);

        if (!password_verify($current_password, $user_password)) {
            $error_message = 'Current password is incorrect';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'New passwords do not match';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_pass_query = "UPDATE users SET password_hash = ? WHERE user_id = ?";
            $update_pass_stmt = mysqli_prepare($conn, $update_pass_query);
            mysqli_stmt_bind_param($update_pass_stmt, "si", $hashed_password, $user_id);

            if (mysqli_stmt_execute($update_pass_stmt)) {
                $success_message = 'Password updated successfully!';
            } else {
                $error_message = 'Failed to update password: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($update_pass_stmt);
        }
    }

    // Update Specialization
    if (isset($_POST['update_specialization'])) {
        $new_specialization_id = mysqli_real_escape_string($conn, $_POST['specialization']);

        $update_specialization_query = "UPDATE doctors SET specialization_id = ? WHERE user_id = ?"; // Assuming link via user_id
        $update_specialization_stmt = mysqli_prepare($conn, $update_specialization_query);
        mysqli_stmt_bind_param($update_specialization_stmt, "ii", $new_specialization_id, $user_id);

        if (mysqli_stmt_execute($update_specialization_stmt)) {
            $success_message = 'Specialization updated successfully!';
            // Refresh doctor data if needed for display on the settings page
            mysqli_stmt_execute($doctor_stmt);
            $doctor_result = mysqli_stmt_get_result($doctor_stmt);
            $doctorData = mysqli_fetch_assoc($doctor_result);
        } else {
            $error_message = 'Failed to update specialization: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($update_specialization_stmt);
    }
}

mysqli_close($conn);
?>

<div class="p-6 bg-white shadow rounded-md">
    <h2 class="text-xl font-semibold mb-4 text-gray-800">Account Settings</h2>

    <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                <?= htmlspecialchars($success_message) ?>
            </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                <?= htmlspecialchars($error_message) ?>
            </div>
    <?php endif; ?>

    <div class="mb-8 border-b pb-4">
        <h3 class="text-lg font-semibold mb-2 text-gray-700">Edit Profile Information</h3>
        <form method="post" class="space-y-4">
            <div>
                <label for="first_name" class="block text-gray-700 text-sm font-bold mb-2">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($userData['first_name'] ?? '') ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div>
                <label for="last_name" class="block text-gray-700 text-sm font-bold mb-2">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($userData['last_name'] ?? '') ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div>
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email Address</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div>
                <label for="phone" class="block text-gray-700 text-sm font-bold mb-2">Phone Number</label>
                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($userData['phone_number'] ?? '') ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <button type="submit" name="update_profile" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Update Profile
            </button>
        </form>
    </div>

    <div class="mb-8 border-b pb-4">
        <h3 class="text-lg font-semibold mb-2 text-gray-700">Change Password</h3>
        <form method="post" class="space-y-4">
            <div>
                <label for="current_password" class="block text-gray-700 text-sm font-bold mb-2">Current Password</label>
                <input type="password" id="current_password" name="current_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div>
                <label for="new_password" class="block text-gray-700 text-sm font-bold mb-2">New Password</label>
                <input type="password" id="new_password" name="new_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div>
                <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <button type="submit" name="update_password" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Change Password
            </button>
        </form>
    </div>

    <div>
        <h3 class="text-lg font-semibold mb-2 text-gray-700">Edit Specialization</h3>
        <form method="post" class="space-y-4">
            <div>
                <label for="specialization" class="block text-gray-700 text-sm font-bold mb-2">
                    Specialization
                </label>
                <select id="specialization" name="specialization" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">-- Select Specialization --</option>
                    <?php foreach ($specializations as $specialization): ?>
                            <option value="<?= htmlspecialchars($specialization['specialization_id']) ?>"
                                <?= ($doctorData['current_specialization_id'] == $specialization['specialization_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($specialization['specialization_name']) ?>
                            </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" name="update_specialization" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Save Specialization
                </button>
            </div>
        </form>
    </div>
</div>