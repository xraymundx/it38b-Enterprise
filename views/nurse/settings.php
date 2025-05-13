<?php
require '../config/config.php';
require_once __DIR__ . '/../../config/config.php';

// Get current user info
$user_id = $_SESSION['user_id'];
$query = "SELECT u.*, n.nurse_id FROM users u 
          LEFT JOIN nurses n ON u.user_id = n.user_id 
          WHERE u.user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check which form was submitted
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone_number = mysqli_real_escape_string($conn, $_POST['phone']);

        $update_query = "UPDATE users SET 
                        first_name = ?, 
                        last_name = ?, 
                        email = ?, 
                        phone_number = ? 
                        WHERE user_id = ?";

        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ssssi", $first_name, $last_name, $email, $phone_number, $user_id);

        if (mysqli_stmt_execute($update_stmt)) {
            $success_message = 'Profile updated successfully!';
            // Update session values
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;

            // Refresh user data
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
        } else {
            $error_message = 'Failed to update profile: ' . mysqli_error($conn);
        }
    } elseif (isset($_POST['update_password'])) {
        // Update password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Verify current password
        $password_query = "SELECT password_hash FROM users WHERE user_id = ?";
        $password_stmt = mysqli_prepare($conn, $password_query);
        mysqli_stmt_bind_param($password_stmt, "i", $user_id);
        mysqli_stmt_execute($password_stmt);
        $password_result = mysqli_stmt_get_result($password_stmt);
        $user_password = mysqli_fetch_assoc($password_result)['password_hash'];

        if (!password_verify($current_password, $user_password)) {
            $error_message = 'Current password is incorrect';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'New passwords do not match';
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_pass_query = "UPDATE users SET password_hash = ? WHERE user_id = ?";
            $update_pass_stmt = mysqli_prepare($conn, $update_pass_query);
            mysqli_stmt_bind_param($update_pass_stmt, "si", $hashed_password, $user_id);

            if (mysqli_stmt_execute($update_pass_stmt)) {
                $success_message = 'Password updated successfully!';
            } else {
                $error_message = 'Failed to update password: ' . mysqli_error($conn);
            }
        }
    } elseif (isset($_POST['upload_avatar'])) {
        // Handle profile picture upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../uploads/avatars/';

            // Create the directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Get file information
            $tmp_name = $_FILES['avatar']['tmp_name'];
            $file_name = $user_id . '_' . time() . '_' . basename($_FILES['avatar']['name']);
            $file_path = $upload_dir . $file_name;

            // Check if file is an image
            $image_info = getimagesize($tmp_name);
            if ($image_info === false) {
                $error_message = 'Uploaded file is not an image';
            } else {
                // Move the uploaded file
                if (move_uploaded_file($tmp_name, $file_path)) {
                    // Check if profile_image column exists in users table
                    $check_column_query = "SHOW COLUMNS FROM users LIKE 'profile_image'";
                    $check_column_result = mysqli_query($conn, $check_column_query);

                    if (mysqli_num_rows($check_column_result) > 0) {
                        // Column exists, proceed with update
                        $relative_path = '/it38b-Enterprise/uploads/avatars/' . $file_name;
                        $update_avatar_query = "UPDATE users SET profile_image = ? WHERE user_id = ?";
                        $update_avatar_stmt = mysqli_prepare($conn, $update_avatar_query);
                        mysqli_stmt_bind_param($update_avatar_stmt, "si", $relative_path, $user_id);

                        if (mysqli_stmt_execute($update_avatar_stmt)) {
                            $success_message = 'Profile image updated successfully!';
                            // Refresh user data
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            $user = mysqli_fetch_assoc($result);
                        } else {
                            $error_message = 'Failed to update profile image in database: ' . mysqli_error($conn);
                        }
                    } else {
                        // Column doesn't exist, notify user to run the migration
                        $error_message = 'The profile_image column does not exist in the users table. Please run the database migration: database/migrations/add_profile_image_column.sql';
                    }
                } else {
                    $error_message = 'Failed to upload the image';
                }
            }
        } else {
            $error_message = 'Please select an image file to upload';
        }
    }
}
?>

<div class="container mx-auto">
    <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
        <h2 class="text-2xl font-bold text-blue-800 mb-6 border-b pb-2">User Settings</h2>

        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow" role="alert">
                <div class="flex">
                    <div class="py-1"><svg class="h-6 w-6 text-green-500 mr-4" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg></div>
                    <div>
                        <p class="font-bold">Success!</p>
                        <p><?php echo $success_message; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow" role="alert">
                <div class="flex">
                    <div class="py-1"><svg class="h-6 w-6 text-red-500 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg></div>
                    <div>
                        <p class="font-bold">Error!</p>
                        <p><?php echo $error_message; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Profile Image Section -->
            <div class="bg-gray-50 p-6 rounded-lg shadow-md transition-all duration-300 hover:shadow-lg">
                <h3 class="text-lg font-semibold text-blue-700 mb-4 border-b pb-2">Profile Image</h3>
                <div class="flex flex-col items-center">
                    <div
                        class="w-40 h-40 rounded-full overflow-hidden mb-6 bg-gray-200 shadow-md border-4 border-white">
                        <?php
                        // Check if profile_image exists in the user array
                        $has_profile_image = isset($user['profile_image']) && !empty($user['profile_image']);

                        // Ensure path has the correct prefix
                        if ($has_profile_image) {
                            $profile_image = $user['profile_image'];
                            // If the path doesn't start with the prefix, add it
                            if (strpos($profile_image, '/it38b-Enterprise/') !== 0) {
                                $profile_image = '/it38b-Enterprise' . $profile_image;
                            }
                        }

                        if ($has_profile_image):
                            ?>
                            <img src="<?php echo $profile_image; ?>" alt="Profile Image" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-blue-100 text-blue-500">
                                <span class="material-symbols-outlined text-6xl">person</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form method="post" enctype="multipart/form-data" class="w-full">
                        <div class="mb-4">
                            <label for="avatar" class="block text-sm font-medium text-gray-700 mb-2">Choose
                                Image</label>
                            <input type="file" name="avatar" id="avatar" accept="image/*" class="w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-blue-50 file:text-blue-700
                                hover:file:bg-blue-100
                                cursor-pointer">
                        </div>
                        <button type="submit" name="upload_avatar"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors shadow-md">
                            Upload Image
                        </button>
                    </form>
                </div>
            </div>

            <!-- Profile Information Section -->
            <div class="md:col-span-2 bg-gray-50 p-6 rounded-lg shadow-md transition-all duration-300 hover:shadow-lg">
                <h3 class="text-lg font-semibold text-blue-700 mb-4 border-b pb-2">Profile Information</h3>
                <form method="post" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First
                                Name</label>
                            <input type="text" name="first_name" id="first_name"
                                value="<?php echo htmlspecialchars($user['first_name']); ?>" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last
                                Name</label>
                            <input type="text" name="last_name" id="last_name"
                                value="<?php echo htmlspecialchars($user['last_name']); ?>" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" name="email" id="email"
                            value="<?php echo htmlspecialchars($user['email']); ?>" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="text" name="phone" id="phone"
                            value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <div class="pt-4">
                        <button type="submit" name="update_profile"
                            class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-md transition-colors shadow-md">
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password Section -->
            <div class="md:col-span-3 bg-gray-50 p-6 rounded-lg shadow-md transition-all duration-300 hover:shadow-lg">
                <h3 class="text-lg font-semibold text-blue-700 mb-4 border-b pb-2">Change Password</h3>
                <form method="post" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current
                                Password</label>
                            <input type="password" name="current_password" id="current_password" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New
                                Password</label>
                            <input type="password" name="new_password" id="new_password" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm
                                New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" name="update_password"
                            class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-6 rounded-md transition-colors shadow-md">
                            Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>