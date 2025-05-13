<?php

// Include database connection
require_once __DIR__ . '/../../config/config.php';

// Items per page
$itemsPerPage = 10;

// --- Search Functionality ---
$searchTerm = $_GET['search'] ?? '';

// --- Pagination ---
$currentPage = $_GET['page_num'] ?? 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// --- Fetch Users with Roles ---
$whereClause = '';
if (!empty($searchTerm)) {
    $searchTermSafe = mysqli_real_escape_string($conn, $searchTerm);
    $whereClause = "WHERE u.username LIKE '%$searchTermSafe%' OR
                        u.email LIKE '%$searchTermSafe%' OR
                        u.first_name LIKE '%$searchTermSafe%' OR
                        u.last_name LIKE '%$searchTermSafe%' OR
                        r.role_name LIKE '%$searchTermSafe%'";
}

$usersQuery = "SELECT u.*, r.role_name
                    FROM users u
                    LEFT JOIN roles r ON u.role_id = r.role_id
                    $whereClause
                    ORDER BY u.user_id DESC
                    LIMIT $itemsPerPage OFFSET $offset";
$usersResult = mysqli_query($conn, $usersQuery);
$users = mysqli_fetch_all($usersResult, MYSQLI_ASSOC);

// --- Get Total Number of Users (for pagination) ---
$totalUsersQuery = "SELECT COUNT(*) AS total
                        FROM users u
                        LEFT JOIN roles r ON u.role_id = r.role_id
                        $whereClause";
$totalUsersResult = mysqli_query($conn, $totalUsersQuery);
$totalUsers = mysqli_fetch_assoc($totalUsersResult)['total'] ?? 0;
$totalPages = ceil($totalUsers / $itemsPerPage);

// --- Fetch Roles and Specializations for the Add User Modal ---
$rolesQuery = "SELECT * FROM roles";
$rolesResult = mysqli_query($conn, $rolesQuery);
$roles = mysqli_fetch_all($rolesResult, MYSQLI_ASSOC);

$specializationsQuery = "SELECT * FROM specializations";
$specializationsResult = mysqli_query($conn, $specializationsQuery);
$specializations = mysqli_fetch_all($specializationsResult, MYSQLI_ASSOC);

// --- Handle Form Submission for Adding User ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $firstName = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lastName = mysqli_real_escape_string($conn, $_POST['last_name']);
    $roleId = isset($_POST['role_id']) ? intval($_POST['role_id']) : null;
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $insertUserQuery = "INSERT INTO users (username, email, password_hash, first_name, last_name, role_id)
                                    VALUES ('$username', '$email', '$passwordHash', '$firstName', '$lastName', $roleId)";

    if (mysqli_query($conn, $insertUserQuery)) {
        $newUserId = mysqli_insert_id($conn);

        // Determine the role and insert into the corresponding table
        $getRoleNameQuery = "SELECT role_name FROM roles WHERE role_id = $roleId";
        $roleNameResult = mysqli_query($conn, $getRoleNameQuery);
        $roleData = mysqli_fetch_assoc($roleNameResult);

        if ($roleData) {
            $roleName = $roleData['role_name'];

            switch ($roleName) {
                case 'patient':
                    $insertPatientQuery = "INSERT INTO patients (user_id) VALUES ($newUserId)";
                    mysqli_query($conn, $insertPatientQuery);
                    break;
                case 'doctor':
                    $specializationId = isset($_POST['specialization_id']) ? intval($_POST['specialization_id']) : null;
                    $insertDoctorQuery = "INSERT INTO doctors (user_id, specialization_id) VALUES ($newUserId, $specializationId)";
                    mysqli_query($conn, $insertDoctorQuery);
                    break;
                case 'nurse':
                    $insertNurseQuery = "INSERT INTO nurses (user_id) VALUES ($newUserId)";
                    mysqli_query($conn, $insertNurseQuery);
                    break;
                // administrator role doesn't have a separate table
            }
        }

        header("Location: ?page=users&add_success=1");
        exit();
    } else {
        $errorMessage = "Error adding user: " . mysqli_error($conn);
    }
}

// --- Handle Form Submission for Editing User ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $editUserId = intval($_POST['edit_user_id']);
    $editUsername = mysqli_real_escape_string($conn, $_POST['edit_username']);
    $editEmail = mysqli_real_escape_string($conn, $_POST['edit_email']);
    $editFirstName = mysqli_real_escape_string($conn, $_POST['edit_first_name']);
    $editLastName = mysqli_real_escape_string($conn, $_POST['edit_last_name']);
    $editRoleId = isset($_POST['edit_role_id']) ? intval($_POST['edit_role_id']) : null;

    $updateUserQuery = "UPDATE users
                        SET username = '$editUsername',
                            email = '$editEmail',
                            first_name = '$editFirstName',
                            last_name = '$editLastName',
                            role_id = $editRoleId
                        WHERE user_id = $editUserId";

    if (mysqli_query($conn, $updateUserQuery)) {
        // Handle updates in specific role tables if needed
        header("Location: ?page=users&edit_success=1");
        exit();
    } else {
        $errorMessage = "Error updating user: " . mysqli_error($conn);
    }
}

// --- Handle Form Submission for Deleting User ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $deleteUserId = intval($_POST['delete_user_id']);

    // Perform necessary checks (e.g., if user is associated with other records)

    $deleteUserQuery = "DELETE FROM users WHERE user_id = $deleteUserId";

    if (mysqli_query($conn, $deleteUserQuery)) {
        header("Location: ?page=users&delete_success=1");
        exit();
    } else {
        $errorMessage = "Error deleting user: " . mysqli_error($conn);
    }
}

// --- Success Messages ---
$addSuccess = $_GET['add_success'] ?? 0;
$editSuccess = $_GET['edit_success'] ?? 0;
$deleteSuccess = $_GET['delete_success'] ?? 0;

// --- Fetch all roles again for the edit modal ---
$allRolesQuery = "SELECT * FROM roles";
$allRolesResult = mysqli_query($conn, $allRolesQuery);
$allRoles = mysqli_fetch_all($allRolesResult, MYSQLI_ASSOC);

?>

<div class="container mx-auto p-6 bg-gray-50 rounded-md shadow-md">
    <h1 class="text-2xl font-semibold mb-6 text-gray-800">User Management</h1>

    <div class="mb-4 flex justify-between items-center">
        <form method="get" class="flex items-center">
            <input type="hidden" name="page" value="users">
            <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>"
                   class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm-text-sm border-gray-300 rounded-md mr-2"
                   placeholder="Search Users">
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <span class="material-symbols-outlined align-middle">search</span>
            </button>
        </form>
        <button data-modal-target="add-user-modal" data-modal-toggle="add-user-modal"
                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                type="button">
            Add New User
        </button>
    </div>

    <?php if ($addSuccess): ?>
        <div class="bg-green-200 border-green-600 text-green-600 px-4 py-3 rounded-md mb-4" role="alert">
            User added successfully!
        </div>
    <?php endif; ?>

    <?php if ($editSuccess): ?>
        <div class="bg-green-200 border-green-600 text-green-600 px-4 py-3 rounded-md mb-4" role="alert">
            User updated successfully!
        </div>
    <?php endif; ?>

    <?php if ($deleteSuccess): ?>
        <div class="bg-green-200 border-green-600 text-green-600 px-4 py-3 rounded-md mb-4" role="alert">
            User deleted successfully!
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div class="bg-red-200 border-red-600 text-red-600 px-4 py-3 rounded-md mb-4" role="alert">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($users) && !empty($searchTerm)): ?>
        <p class="text-gray-600 italic">No users found matching your search term.</p>
    <?php elseif (empty($users)): ?>
        <p class="text-gray-600 italic">No users found.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full leading-normal">
                <thead class="bg-gray-100">
                <tr>
                    <th
                        class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        ID
                    </th>
                    <th
                        class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Username
                    </th>
                    <th
                        class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Email
                    </th>
                    <th
                        class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Name
                    </th>
                    <th
                        class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Role
                    </th>
                    <th
                        class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Created At
                    </th>
                    <th
                        class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr class="<?php echo $loop % 2 === 0 ? 'bg-gray-50' : ''; ?>">
                        <td class="px-5 py-3 border-b border-gray-200 text-sm">
                            <?php echo htmlspecialchars($user['user_id']); ?>
                        </td>
                        <td class="px-5 py-3 border-b border-gray-200 text-sm">
                            <?php echo htmlspecialchars($user['username']); ?>
                        </td>
                        <td class="px-5 py-3 border-b border-gray-200 text-sm">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </td>
                        <td class="px-5 py-3 border-b border-gray-200 text-sm">
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </td>
                        <td class="px-5 py-3 border-b border-gray-200 text-sm">
                            <?php echo htmlspecialchars($user['role_name'] ?? 'N/A'); ?>
                        </td>
                        <td class="px-5 py-3 border-b border-gray-200 text-sm">
                            <?php echo htmlspecialchars($user['created_at']); ?>
                        </td>
                        <td class="px-5 py-3 border-b border-gray-200 text-sm">
                            <button data-modal-target="edit-user-modal-<?php echo $user['user_id']; ?>" data-modal-toggle="edit-user-modal-<?php echo $user['user_id']; ?>"
                                    class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
                            <button data-modal-target="delete-user-modal-<?php echo $user['user_id']; ?>" data-modal-toggle="delete-user-modal-<?php echo $user['user_id']; ?>"
                                    class="text-red-600 hover:text-red-900">Delete</button>

                            <div id="edit-user-modal-<?php echo $user['user_id']; ?>" tabindex="-1" aria-hidden="true"
                                 class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
                                <div class="relative w-full max-w-md max-h-full">
                                    <div class="relative bg-white rounded-lg shadow">
                                        <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                                            <h3 class="text-lg font-semibold text-gray-900">
                                                Edit User
                                            </h3>
                                            <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="edit-user-modal-<?php echo $user['user_id']; ?>">
                                                <span class="material-symbols-outlined">close</span>
                                                <span class="sr-only">Close modal</span>
                                            </button>
                                        </div>
                                        <div class="p-4 md:p-5">
                                            <form class="space-y-4" method="post" action="">
                                                <input type="hidden" name="edit_user" value="1">
                                                <input type="hidden" name="edit_user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
                                                <div>
                                                    <label for="edit_username_<?php echo $user['user_id']; ?>" class="block mb-2 text-sm font-medium text-gray-900">Username</label>
                                                    <input type="text" name="edit_username" id="edit_username_<?php echo $user['user_id']; ?>" value="<?php echo htmlspecialchars($user['username']); ?>"
                                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                                           required>
                                                </div>
                                                <div>
                                                    <label for="edit_email_<?php echo $user['user_id']; ?>" class="block mb-2 text-sm font-medium text-gray-900">Email</label>
                                                    <input type="email" name="edit_email" id="edit_email_<?php echo $user['user_id']; ?>" value="<?php echo htmlspecialchars($user['email']); ?>"
                                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"required>
                                                </div>
                                                <div>
                                                    <label for="edit_first_name_<?php echo $user['user_id']; ?>" class="block mb-2 text-sm font-medium text-gray-900">First Name</label>
                                                    <input type="text" name="edit_first_name" id="edit_first_name_<?php echo $user['user_id']; ?>" value="<?php echo htmlspecialchars($user['first_name']); ?>"
                                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                                           required>
                                                </div>
                                                <div>
                                                    <label for="edit_last_name_<?php echo $user['user_id']; ?>" class="block mb-2 text-sm font-medium text-gray-900">Last Name</label>
                                                    <input type="text" name="edit_last_name" id="edit_last_name_<?php echo $user['user_id']; ?>" value="<?php echo htmlspecialchars($user['last_name']); ?>"
                                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                                           required>
                                                </div>
                                                <div>
                                                    <label for="edit_role_id_<?php echo $user['user_id']; ?>" class="block mb-2 text-sm font-medium text-gray-900">Role</label>
                                                    <select name="edit_role_id" id="edit_role_id_<?php echo $user['user_id']; ?>"
                                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                                        <option value="">Select Role</option>
                                                        <?php foreach ($allRoles as $role): ?>
                                                                <option value="<?php echo htmlspecialchars($role['role_id']); ?>" <?php if ($user['role_id'] == $role['role_id'])
                                                                       echo 'selected'; ?>>
                                                                    <?php echo htmlspecialchars($role['role_name']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <button type="submit"
                                                        class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                                                    Save Changes
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="delete-user-modal-<?php echo $user['user_id']; ?>" tabindex="-1" aria-hidden="true"
                                 class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
                                <div class="relative w-full max-w-md max-h-full">
                                    <div class="relative bg-white rounded-lg shadow">
                                        <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                                            <h3 class="text-lg font-semibold text-gray-900">
                                                Confirm Delete
                                            </h3>
                                            <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="delete-user-modal-<?php echo $user['user_id']; ?>">
                                                <span class="material-symbols-outlined">close</span>
                                                <span class="sr-only">Close modal</span>
                                            </button>
                                        </div>
                                        <div class="p-4 md:p-5">
                                            <p class="mb-4 text-gray-800">Are you sure you want to delete the user: <strong><?php echo htmlspecialchars($user['username']); ?></strong>?</p>
                                            <form class="space-y-4" method="post" action="">
                                                <input type="hidden" name="delete_user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
                                                <button type="submit"
                                                        class="w-full text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                                                    Delete User
                                                </button>
                                                <button type="button" class="w-full text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10" data-modal-hide="delete-user-modal-<?php echo $user['user_id']; ?>">
                                                    Cancel
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="mt-4 flex justify-center">
                <?php if ($currentPage > 1): ?>
                        <a href="?page=users&page_num=<?php echo $currentPage - 1; ?><?php if (!empty($searchTerm))
                                 echo '&search=' . htmlspecialchars($searchTerm); ?>"
                           class="px-4 py-2 mx-1 bg-gray-200 rounded hover:bg-gray-300">Previous</a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);

                if ($startPage > 1)
                    echo '<span class="mx-1">...</span>';

                for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?page=users&page_num=<?php echo $i; ?><?php if (!empty($searchTerm))
                               echo '&search=' . htmlspecialchars($searchTerm); ?>"
                           class="px-4 py-2 mx-1 <?php echo ($i == $currentPage) ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?> rounded"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages)
                    echo '<span class="mx-1">...</span>'; ?>

                <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=users&page_num=<?php echo $currentPage + 1; ?><?php if (!empty($searchTerm))
                                 echo '&search=' . htmlspecialchars($searchTerm); ?>"
                           class="px-4 py-2 mx-1 bg-gray-200 rounded hover:bg-gray-300">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div id="add-user-modal" tabindex="-1" aria-hidden="true"
         class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Add New User
                    </h3>
                    <button type="button"
                            class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center"
                            data-modal-hide="add-user-modal">
                        <span class="material-symbols-outlined">close</span>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <div class="p-4 md:p-5">
                    <form class="space-y-4" method="post" action="">
                        <input type="hidden" name="add_user" value="1">
                        <div>
                            <label for="username"
                                   class="block mb-2 text-sm font-medium text-gray-900">Username</label>
                            <input type="text" name="username" id="username"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   required>
                        </div>
                        <div>
                            <label for="email"
                                   class="block mb-2 text-sm font-medium text-gray-900">Email</label>
                            <input type="email" name="email" id="email"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   required>
                        </div>
                        <div>
                            <label for="password"
                                   class="block mb-2 text-sm font-medium text-gray-900">Password</label>
                            <input type="password" name="password" id="password" placeholder="••••••••"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   required>
                        </div>
                        <div>
                            <label for="first_name"
                                   class="block mb-2 text-sm font-medium text-gray-900">First Name</label>
                            <input type="text" name="first_name" id="first_name"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   required>
                        </div>
                        <div>
                            <label for="last_name"
                                   class="block mb-2 text-sm font-medium text-gray-900">Last Name</label>
                            <input type="text" name="last_name" id="last_name"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   required>
                        </div>
                        <div>
                            <label for="role_id"
                                   class="block mb-2 text-sm font-medium text-gray-900">Role</label>
                            <select name="role_id" id="role_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                    onchange="toggleSpecialization()">
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo htmlspecialchars($role['role_id']); ?>">
                                        <?php echo htmlspecialchars($role['role_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="specialization_div" style="display: none;">
                            <label for="specialization_id"
                                   class="block mb-2 text-sm font-medium text-gray-900">Specialization</label><select name="specialization_id" id="specialization_id"
                                     class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                <option value="">Select Specialization</option>
                                <?php foreach ($specializations as $specialization): ?>
                                    <option value="<?php echo htmlspecialchars($specialization['specialization_id']); ?>">
                                        <?php echo htmlspecialchars($specialization['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit"
                                class="w-full text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            Add User
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleSpecialization() {
        const roleSelect = document.getElementById('role_id');
        const specializationDiv = document.getElementById('specialization_div');
        const selectedRole = roleSelect.options[roleSelect.selectedIndex].text.toLowerCase();

        if (selectedRole === 'doctor') {
            specializationDiv.style.display = 'block';
        } else {
            specializationDiv.style.display = 'none';
        }
    }
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />