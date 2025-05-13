<?php
// Ensure this file is in the 'views/patient/' directory

// Get database connection
require_once __DIR__ . '/../../config/config.php';

// Get the appointment ID from the URL
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch appointment details if ID is valid
$appointment = null;
if ($appointment_id > 0) {
    $user_id = $_SESSION['user_id']; // Assuming user_id is in session
    $query = "
        SELECT a.*,
               d.doctor_id,
               u.first_name as doctor_first_name,
               u.last_name as doctor_last_name,
               s.specialization_name,
               DATE_FORMAT(a.appointment_datetime, '%Y-%m-%d') as current_date,
               DATE_FORMAT(a.appointment_datetime, '%H:%i') as current_time_24hr
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users u ON d.user_id = u.user_id
        JOIN specializations s ON d.specialization_id = s.specialization_id
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.appointment_id = ? AND p.user_id = ?
    ";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $appointment = mysqli_fetch_assoc($result);

    if (!$appointment) {
        echo '<div class="p-4"><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">Appointment not found or you are not authorized to reschedule it.</span>
              </div></div>';
        exit();
    }

    // Fetch all doctors for the dropdown
    $doctorQuery = "SELECT d.doctor_id, u.first_name, u.last_name, s.specialization_name
                    FROM doctors d
                    JOIN users u ON d.user_id = u.user_id
                    JOIN specializations s ON d.specialization_id = s.specialization_id
                    ORDER BY u.last_name, u.first_name";
    $doctorResult = mysqli_query($conn, $doctorQuery);

} else {
    echo '<div class="p-4"><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">Invalid appointment ID.</span>
          </div></div>';
    exit();
}
?>

<div class="p-4" x-data="rescheduleAppointment({ appointment: <?php echo json_encode($appointment); ?> })">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold">Reschedule Appointment</h2>
            <a href="?page=appointments" class="text-blue-500 hover:text-blue-600">
                Back to Appointments
            </a>
        </div>

        <form @submit.prevent="submitReschedule" class="space-y-6">
            <div>
                <label for="doctor_id" class="block text-sm font-medium text-gray-700 mb-1">Select Doctor</label>
                <select x-model="formData.doctor_id" id="doctor_id" required
                    class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    @change="loadAvailableTimeSlots">
                    <option value="">Select a doctor</option>
                    <?php while ($doctor = mysqli_fetch_assoc($doctorResult)): ?>
                        <option value="<?= $doctor['doctor_id'] ?>"
                            :selected="formData.doctor_id == '<?= $doctor['doctor_id'] ?>'">
                            Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?>
                            (<?= htmlspecialchars($doctor['specialization_name']) ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
                <template x-if="!formData.doctor_id">
                    <p class="text-red-500 text-sm mt-1">Please select a doctor.</p>
                </template>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Date</label>
                    <input type="date" x-model="formData.appointment_date" required :min="today"
                        class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        @change="loadAvailableTimeSlots">
                    <template x-if="!formData.appointment_date">
                        <p class="text-red-500 text-sm mt-1">Please select a date.</p>
                    </template>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Time</label>
                    <select x-model="formData.appointment_time" required
                        class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select a time</option>
                        <template x-for="slot in availableTimeSlots" :key="slot">
                            <option :value="slot" x-text="slot"></option>
                        </template>
                    </select>
                    <template x-if="!formData.appointment_time && availableTimeSlots.length > 0">
                        <p class="text-red-500 text-sm mt-1">Please select a time.</p>
                    </template>
                    <template x-if="availableTimeSlots.length === 0 && formData.doctor_id && formData.appointment_date">
                        <p class="text-yellow-500 text-sm mt-1">No available time slots for the selected doctor and
                            date.</p>
                    </template>
                </div>
            </div>

            <div>
                <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Reason for Visit</label>
                <input type="text" x-model="formData.reason" id="reason" required
                    class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :value="formData.reason" readonly>
                <p class="text-gray-500 text-sm mt-1">Reason cannot be changed during rescheduling.</p>
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Additional Notes
                    (Optional)</label>
                <textarea x-model="formData.notes" id="notes" rows="3"
                    class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Any additional information..."></textarea>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="?page=appointments"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                    Cancel
                </a>
                <button type="submit"
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200"
                    :disabled="isSubmitting || availableTimeSlots.length === 0">
                    <span x-show="!isSubmitting">Reschedule Appointment</span>
                    <span x-show="isSubmitting">Rescheduling...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function rescheduleAppointment(initialData) {
        return {
            formData: {
                appointment_id: initialData.appointment ? initialData.appointment.appointment_id : '',
                doctor_id: initialData.appointment ? initialData.appointment.doctor_id : '',
                appointment_date: initialData.appointment ? initialData.appointment.current_date : '',
                appointment_time: '',
                reason: initialData.appointment ? initialData.appointment.reason_for_visit : '',
                notes: initialData.appointment ? initialData.appointment.notes : '',
            },
            today: new Date().toISOString().split('T')[0],
            isSubmitting: false,
            availableTimeSlots: [],

            init() {
                // If initial time is available, try to select it (format might need adjustment)
                if (initialData.appointment && initialData.appointment.current_time_24hr) {
                    // Convert 24hr format to h:i A for comparison with time slots
                    const timeParts = initialData.appointment.current_time_24hr.split(':');
                    let hour = parseInt(timeParts[0]);
                    const minute = timeParts[1];
                    const ampm = hour >= 12 ? 'PM' : 'AM';
                    hour = hour % 12;
                    hour = hour ? hour : 12; // the hour '0' should be '12'
                    const formattedTime = `${hour}:${minute} ${ampm}`;

                    // Load available time slots for the initial date and doctor
                    this.loadAvailableTimeSlots().then(() => {
                        // Try to pre-select the closest available time slot
                        let closestSlot = null;
                        let minDifference = Infinity;

                        this.availableTimeSlots.forEach(slot => {
                            const slotTime = this.convertTo24Hour(slot);
                            const initialTime24 = initialData.appointment.current_time_24hr;
                            const difference = Math.abs(new Date(`2000-01-01T${slotTime}`) - new Date(`2000-01-01T${initialTime24}`));

                            if (difference < minDifference) {
                                minDifference = difference;
                                closestSlot = slot;
                            }
                        });

                        if (closestSlot) {
                            this.formData.appointment_time = closestSlot;
                        }
                    });
                } else if (this.formData.doctor_id && this.formData.appointment_date) {
                    this.loadAvailableTimeSlots();
                }
            },

            convertTo24Hour(time12h) {
                const [time, modifier] = time12h.split(' ');
                let [hours, minutes] = time.split(':');

                if (hours === '12') {
                    hours = '00';
                }

                if (modifier === 'PM') {
                    hours = parseInt(hours, 10) + 12;
                }

                return `${hours}:${minutes}`;
            },

            loadAvailableTimeSlots() {
                this.availableTimeSlots = [];
                if (this.formData.doctor_id && this.formData.appointment_date) {
                    return fetch(`/it38b-Enterprise/api/doctor/availability.php?doctor_id=${this.formData.doctor_id}&date=${this.formData.appointment_date}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.available) {
                                this.availableTimeSlots = data.timeSlots || [];
                            } else {
                                this.availableTimeSlots = [];
                                console.error('Failed to load availability:', data.error || 'Doctor not available');
                                // Optionally display a message to the user about unavailability
                            }
                        })
                        .catch(error => {
                            console.error('Error loading availability:', error);
                            alert('Failed to load doctor availability.');
                            this.availableTimeSlots = [];
                        });
                }
                return Promise.resolve(); // Resolve if doctor or date is not selected
            },

            submitReschedule() {
                if (!this.formData.doctor_id || !this.formData.appointment_date || !this.formData.appointment_time) {
                    alert('Please select a doctor, date, and time to reschedule.');
                    return;
                }

                this.isSubmitting = true;
                const rescheduleData = {
                    appointment_id: this.formData.appointment_id,
                    appointment_datetime: `${this.formData.appointment_date} ${this.convertTo24Hour(this.formData.appointment_time)}`,
                    // Reason and notes are sent as well, though reason is readonly
                    reason: this.formData.reason,
                    notes: this.formData.notes || ''
                };

                fetch('/it38b-Enterprise/api/patient/appointments.php?action=reschedule', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(rescheduleData)
                })
                    .then(response => response.json())
                    .then(data => {
                        this.isSubmitting = false;
                        if (data.success) {
                            alert('Appointment rescheduled successfully!');
                            window.location.href = '?page=appointments';
                        } else {
                            alert('Failed to reschedule appointment: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        this.isSubmitting = false;
                        console.error('Error:', error);
                        alert('Failed to reschedule appointment.');
                    });
            }
        };
    }
</script>