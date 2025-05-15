<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../config/config.php';
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure doctor is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: /login.php"); // Adjust login path as needed
    exit();
}

$doctor_id = $_SESSION['doctor_id']; // Assuming doctor_id is stored in session

// Fetch doctor's regular weekly schedule
$weeklySchedule = [];
$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$sqlWeekly = "SELECT * FROM doctor_schedule WHERE doctor_id = ?";
$stmtWeekly = mysqli_prepare($conn, $sqlWeekly);
mysqli_stmt_bind_param($stmtWeekly, "i", $doctor_id);
mysqli_stmt_execute($stmtWeekly);
$resultWeekly = mysqli_stmt_get_result($stmtWeekly);
while ($row = mysqli_fetch_assoc($resultWeekly)) {
    $weeklySchedule[$row['day_of_week']] = $row;
}
mysqli_stmt_close($stmtWeekly);

// Function to generate time slot options
function generateTimeSlotOptions()
{
    $startTime = strtotime('07:00');
    $endTime = strtotime('19:00');
    $intervalMinutes = 30; // 30-minute intervals
    $intervalSeconds = $intervalMinutes * 60;
    $options = [];
    $current = $startTime;
    while ($current < $endTime) {
        $time = date('h:i A', $current);
        $nextTime = date('h:i A', $current + $intervalSeconds);
        $options[] = $time . " - " . $nextTime;
        $current += $intervalSeconds;
    }
    return $options;
}

$timeSlotOptions = generateTimeSlotOptions();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        /* Basic custom styles for elements not easily styled with Tailwind */
        .selected-slot button {
            @apply ml-1 text-blue-500 hover:text-blue-700 focus:outline-none transition duration-200;
        }
    </style>
</head>

<body class="bg-gray-100 p-6 font-sans">
    <div class="container mx-auto max-w-3xl bg-white shadow-md rounded-lg overflow-hidden">
        <div class="bg-gray-50 py-4 px-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800 tracking-tight">Doctor Availability</h2>
        </div>
        <div class="p-6">
            <section class="mb-8">
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Set Availability for a Specific Date</h3>
                <div class="flex items-center mb-4">
                    <label for="availability_date" class="block text-sm font-medium text-gray-700 w-32">Select
                        Date:</label>
                    <input type="text" id="availability_date"
                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md ml-2"
                        placeholder="Select Date">
                    <button id="mark_unavailable_btn"
                        class="inline-flex items-center bg-red-400 hover:bg-red-500 text-white font-bold py-2 px-4 rounded ml-4 transition duration-200">
                        <i class="fas fa-ban mr-2"></i> Mark Unavailable
                    </button>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-700 mb-3">Available Time Slots</h4>
                    <div id="available_time_slots" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                    </div>

                    <h4 class="font-semibold text-gray-700 mt-4 mb-3">Selected Time Slots</h4>
                    <div id="selected_time_slots" class="flex flex-wrap gap-2">
                    </div>

                    <div class="mb-4 mt-4">
                        <label for="availability_notes" class="block text-sm font-medium text-gray-700 mb-2">Notes
                            (Optional):</label>
                        <textarea id="availability_notes"
                            class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                            rows="3" placeholder="Add notes about this day's schedule"></textarea>
                    </div>

                    <div class="flex gap-2">
                        <button id="save_availability"
                            class="inline-flex items-center bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                            <i class="fas fa-save mr-2"></i> Save Settings
                        </button>
                        <button id="clear_all_specific"
                            class="inline-flex items-center bg-red-400 hover:bg-red-500 text-white font-bold py-2 px-4 rounded transition duration-200">
                            <i class="fas fa-trash-alt mr-2"></i> Clear All
                        </button>
                    </div>
                </div>
            </section>

            <section class="mb-8 border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Regular Weekly Schedule</h3>
                <?php foreach ($daysOfWeek as $day): ?>
                    <div class="mb-4 bg-gray-100 rounded-md p-4">
                        <h4 class="text-md font-semibold text-gray-800 mb-2"><?php echo $day; ?></h4>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 items-center">
                            <div class="col-span-2 sm:col-span-1">
                                <label for="edit_<?php echo strtolower($day); ?>_start_time"
                                    class="block text-sm font-medium text-gray-700">Start Time:</label>
                                <select id="edit_<?php echo strtolower($day); ?>_start_time"
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">-- Select --</option>
                                    <?php foreach (array_unique(array_map(function ($slot) {
                                        return trim(explode('-', $slot)[0]);
                                    }, $timeSlotOptions)) as $time): ?>
                                        <option value="<?php echo date('H:i:s', strtotime($time)); ?>" <?php if (isset($weeklySchedule[$day]) && $weeklySchedule[$day]['start_time'] === date('H:i:s', strtotime($time)))
                                                echo 'selected'; ?>><?php echo $time; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label for="edit_<?php echo strtolower($day); ?>_end_time"
                                    class="block text-sm font-medium text-gray-700 mt-2 sm:mt-0">End Time:</label>
                                <select id="edit_<?php echo strtolower($day); ?>_end_time"
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">-- Select --</option>
                                    <?php foreach (array_unique(array_map(function ($slot) {
                                        return trim(explode('-', $slot)[1]);
                                    }, $timeSlotOptions)) as $time): ?>
                                        <option value="<?php echo date('H:i:s', strtotime($time)); ?>" <?php if (isset($weeklySchedule[$day]) && $weeklySchedule[$day]['end_time'] === date('H:i:s', strtotime($time)))
                                                echo 'selected'; ?>><?php echo $time; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="sm:col-span-1 flex items-center justify-end">
                                <label class="inline-flex items-center text-sm text-gray-700">
                                    <input type="checkbox"
                                        class="form-checkbox h-5 w-5 text-green-500 focus:ring-indigo-500 focus:border-indigo-500 rounded"
                                        id="edit_<?php echo strtolower($day); ?>_available" value="1" <?php if (isset($weeklySchedule[$day]) && $weeklySchedule[$day]['is_available'])
                                               echo 'checked'; ?>>
                                    <span class="ml-2">Available</span>
                                </label>
                                <button
                                    class="inline-flex items-center bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-3 rounded text-sm ml-2 transition duration-200"
                                    onclick="saveWeeklySchedule('<?php echo $day; ?>')">
                                    <i class="fas fa-save mr-1"></i> Save
                                </button>
                            </div>
                        </div>
                        <?php if (isset($weeklySchedule[$day])): ?>
                            <p class="text-xs text-gray-500 mt-1">Currently:
                                <?php echo date('h:i A', strtotime($weeklySchedule[$day]['start_time'])); ?> -
                                <?php echo date('h:i A', strtotime($weeklySchedule[$day]['end_time'])); ?>, Available:
                                <?php echo $weeklySchedule[$day]['is_available'] ? 'Yes' : 'No'; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        </div>
    </div>

    <script>
        console.log("Doctor ID from session (PHP): <?php echo $_SESSION['doctor_id']; ?>");

        flatpickr("#availability_date", {
            dateFormat: "Y-m-d",
            onChange: function (selectedDates, dateStr, instance) {
                if (dateStr) {
                    fetchDoctorAvailability(dateStr);
                } else {
                    document.getElementById('available_time_slots').innerHTML = '';
                    document.getElementById('selected_time_slots').innerHTML = '';
                    document.getElementById('availability_notes').value = '';
                }
            }
        });

        const availableTimeSlotsDiv = document.getElementById('available_time_slots');
        const selectedTimeSlotsDiv = document.getElementById('selected_time_slots');
        const availabilityNotesInput = document.getElementById('availability_notes');
        const availabilityDateInput = document.getElementById('availability_date');
        const markUnavailableBtn = document.getElementById('mark_unavailable_btn');
        const saveAvailabilityBtn = document.getElementById('save_availability');
        const clearAllSpecificBtn = document.getElementById('clear_all_specific');

        let selectedSlots = [];

        function fetchDoctorAvailability(date) {
            const doctorId = <?php echo $doctor_id; ?>;
            fetch(`/it38b-Enterprise/api/doctor/availability.php?doctor_id=${doctorId}&date=${date}`)
                .then(response => response.json())
                .then(data => {
                    availableTimeSlotsDiv.innerHTML = '';
                    selectedTimeSlotsDiv.innerHTML = '';
                    selectedSlots = [];
                    availabilityNotesInput.value = data.notes || '';

                    if (data.success && data.available) {
                        data.timeSlots.forEach(slot => {
                            const button = document.createElement('button');
                            button.classList.add('inline-block', 'bg-green-500', 'hover:bg-green-700', 'text-white', 'font-bold', 'py-2', 'px-4', 'rounded', 'mr-2', 'mb-2', 'cursor-pointer', 'transition', 'duration-200');
                            button.textContent = slot;
                            button.addEventListener('click', () => selectTimeSlot(slot));
                            availableTimeSlotsDiv.appendChild(button);
                        });
                        markUnavailableBtn.classList.remove('bg-red-500', 'hover:bg-red-700');
                        markUnavailableBtn.classList.add('bg-red-400', 'hover:bg-red-500');
                        markUnavailableBtn.innerHTML = '<i class="fas fa-ban mr-2"></i> Mark Unavailable';
                        markUnavailableBtn.removeEventListener('click', markAsUnavailable);
                        markUnavailableBtn.addEventListener('click', () => markAsUnavailable(date));
                    } else if (data.success && !data.available) {
                        const unavailableMessage = document.createElement('p');
                        unavailableMessage.classList.add('text-red-600', 'font-semibold');
                        unavailableMessage.textContent = 'Doctor is marked as unavailable for this day.';
                        availableTimeSlotsDiv.appendChild(unavailableMessage);
                        markUnavailableBtn.classList.add('inline-flex', 'items-center', 'bg-green-500', 'hover:bg-green-700', 'text-white', 'font-bold', 'py-2', 'px-4', 'rounded', 'transition', 'duration-200');
                        markUnavailableBtn.classList.remove('bg-red-400', 'hover:bg-red-500');
                        markUnavailableBtn.innerHTML = '<i class="fas fa-check mr-2"></i> Mark Available';
                        markUnavailableBtn.removeEventListener('click', () => markAsUnavailable(date));
                        markUnavailableBtn.addEventListener('click', () => markAsAvailable(date));
                    } else if (data.error) {
                        const errorMessage = document.createElement('p');
                        errorMessage.classList.add('text-red-600', 'font-semibold');
                        errorMessage.textContent = `Error fetching availability: ${data.error}`;
                        availableTimeSlotsDiv.appendChild(errorMessage);
                        markUnavailableBtn.classList.remove('bg-red-500', 'hover:bg-red-700');
                        markUnavailableBtn.classList.add('inline-flex', 'items-center', 'bg-red-400', 'hover:bg-red-500', 'text-white', 'font-bold', 'py-2', 'px-4', 'rounded', 'transition', 'duration-200');
                        markUnavailableBtn.innerHTML = '<i class="fas fa-ban mr-2"></i> Mark Unavailable';
                        markUnavailableBtn.removeEventListener('click', markAsAvailable);
                        markUnavailableBtn.addEventListener('click', () => markAsUnavailable(date));
                    }
                })
                .catch(error => {
                    console.error('Error fetching availability:', error);
                    availableTimeSlotsDiv.innerHTML = '<p class="text-red-600 font-semibold">Failed to fetch availability.</p>';
                });
        }

        function selectTimeSlot(slot) {
            if (!selectedSlots.includes(slot)) {
                selectedSlots.push(slot);
                renderSelectedSlots();
            }
        }

        function removeSelectedSlot(slot) {
            selectedSlots = selectedSlots.filter(s => s !== slot);
            renderSelectedSlots();
        }

        function renderSelectedSlots() {
            selectedTimeSlotsDiv.innerHTML = '';
            selectedSlots.forEach(slot => {
                const slotDiv = document.createElement('div');
                slotDiv.classList.add('inline-flex', 'items-center', 'bg-blue-200', 'text-blue-800', 'font-semibold', 'py-1', 'px-2', 'rounded', 'mr-2', 'mb-2', 'transition', 'duration-200');
                slotDiv.textContent = slot;
                const removeButton = document.createElement('button');
                removeremoveButton.innerHTML = '<i class="fas fa-times"></i>';
                removeButton.addEventListener('click', () => removeSelectedSlot(slot));
                slotDiv.appendChild(removeButton);
                selectedTimeSlotsDiv.appendChild(slotDiv);
            });
        }

        saveAvailabilityBtn.addEventListener('click', () => {
            const date = availabilityDateInput.value;
            if (!date) {
                alert('Please select a date.');
                return;
            }
            const doctorId = <?php echo $doctor_id; ?>;
            const notes = availabilityNotesInput.value;

            fetch('/it38b-Enterprise/api/doctor/availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ doctor_id: doctorId, date: date, timeSlots: selectedSlots, notes: notes }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        fetchDoctorAvailability(date); // Refresh availability
                    } else {
                        alert(`Error saving availability: ${data.error}`);
                    }
                })
                .catch(error => {
                    console.error('Error saving availability:', error);
                    alert('Failed to save availability.');
                });
        });

        clearAllSpecificBtn.addEventListener('click', () => {
            selectedSlots = [];
            renderSelectedSlots();
        });

        function markAsUnavailable(date) {
            if (!date) {
                alert('Please select a date.');
                return;
            }
            const doctorId = <?php echo $doctor_id; ?>;
            fetch('/it38b-Enterprise/api/doctor/availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ doctor_id: doctorId, date: date, timeSlots: [], notes: availabilityNotesInput.value }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        fetchDoctorAvailability(date); // Refresh availability
                    } else {
                        alert(`Error marking unavailable: ${data.error}`);
                    }
                })
                .catch(error => {
                    console.error('Error marking unavailable:', error);
                    alert('Failed to mark as unavailable.');
                });
        }

        function markAsAvailable(date) {
            if (!date) {
                alert('Please select a date.');
                return;
            }
            const doctorId = <?php echo $doctor_id; ?>;
            fetch(`/it38b-Enterprise/api/doctor/availability.php?doctor_id=${doctorId}&date=${date}`, {
                method: 'DELETE',
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        fetchDoctorAvailability(date); // Refresh availability
                    } else {
                        alert(`Error marking available: ${data.error}`);
                    }
                })
                .catch(error => {
                    console.error('Error marking available:', error);
                    alert('Failed to mark as available.');
                });
        }

        // function saveWeeklySchedule(dayOfWeek) {
        //     const doctorId = <?php echo $doctor_id; ?>;
        //     const startTime = document.getElementById(`edit_${dayOfWeek.toLowerCase()}_start_time`).value;
        //     const endTime = document.getElementById(`edit_${dayOfWeek.toLowerCase()}_end_time`).value;
        //     const isAvailable = document.getElementById(`edit_${dayOfWeek.toLowerCase()}_available`).checked ? 1 : 0;

        //     fetch('/it38b-Enterprise/api/doctor/schedule.php', {
        //         method: 'POST',
        //         headers: {
        //             'Content-Type': 'application/json',
        //         },
        //         body: JSON.stringify({
        //             doctor_id: doctorId,
        //             day_of_week: dayOfWeek,
        //             start_time: startTime,
        //             end_time: endTime,
        //             is_available: isAvailable
        //         }),
        //     })
        //         .then(response => {
        //             response.text().then(text => {
        //                 console.log("Raw response from server:", text); // Log the raw text
        //                 try {
        //                     const data = JSON.parse(text);
        //                     if (data.success) {
        //                         alert(data.message);
        //                         window.location.reload();
        //                     } else {
        //                         alert(`Error saving ${dayOfWeek} schedule: ${data.error}`);
        //                     }
        //                 } catch (error) {
        //                     console.error("Error parsing JSON:", error);
        //                     alert(`Error processing server response: ${text}`); // Show the raw response in the alert
        //                 }
        //             });
        //         })
        //         .catch(error => {
        //             console.error(`Error saving ${dayOfWeek} schedule:`, error);
        //             alert(`Failed to save ${dayOfWeek} schedule.`);
        //         });
        // }
        function saveWeeklySchedule(dayOfWeek) {
            const doctorId = <?php echo $doctor_id; ?>;
            const startTime = document.getElementById(`edit_${dayOfWeek.toLowerCase()}_start_time`).value;
            const endTime = document.getElementById(`edit_${dayOfWeek.toLowerCase()}_end_time`).value;
            const isAvailable = document.getElementById(`edit_${dayOfWeek.toLowerCase()}_available`).checked ? 1 : 0;

            console.log("Saving schedule for:", dayOfWeek);
            console.log("Doctor ID:", doctorId);
            console.log("Start Time:", startTime);
            console.log("End Time:", endTime);
            console.log("Is Available:", isAvailable);

            fetch('/it38b-Enterprise/api/doctor/schedule.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    doctor_id: doctorId,
                    day_of_week: dayOfWeek,
                    start_time: startTime,
                    end_time: endTime,
                    is_available: isAvailable
                }),
            })
                .then(response => {
                    console.log("Server response received.");
                    response.text().then(text => {
                        console.log("Raw response from server:", text); // Log the raw text
                        try {
                            const data = JSON.parse(text);
                            if (data.success) {
                                console.log("Schedule saved successfully:", data.message);
                                alert(data.message);
                                window.location.reload();
                            } else {
                                console.error(`Error saving ${dayOfWeek} schedule:`, data.error);
                                alert(`Error saving ${dayOfWeek} schedule: ${data.error}`);
                            }
                        } catch (error) {
                            console.error("Error parsing JSON:", error);
                            alert(`Error processing server response: ${text}`); // Show the raw response in the alert
                        }
                    });
                })
                .catch(error => {
                    console.error(`Error sending request to save ${dayOfWeek} schedule:`, error);
                    alert(`Failed to save ${dayOfWeek} schedule.`);
                });
        }
    </script>
</body>

</html>