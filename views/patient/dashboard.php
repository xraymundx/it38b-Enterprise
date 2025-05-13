<?php
// Ensure this file is in the 'views/patient/' directory
?>

<div class="p-6">
    <div class="mb-8">
        <h2 class="text-3xl font-semibold text-gray-800 mb-2">Welcome,
            <?php echo htmlspecialchars($_SESSION['first_name']); ?>
        </h2>
        <p class="text-lg text-gray-600">Your personalized patient portal overview.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-xl font-semibold text-gray-700">
                    <i class="fas fa-calendar-alt mr-2 text-blue-500"></i> Upcoming Appointments
                </h3>
                <a href="?page=appointments" class="text-blue-600 hover:text-blue-800 text-sm">View All</a>
            </div>
            <div id="upcoming-appointments" class="p-6">
                <p class="text-gray-600">Loading appointments...</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-xl font-semibold text-gray-700">
                    <i class="fas fa-file-medical mr-2 text-green-500"></i> Recent Medical Records
                </h3>
                <a href="?page=medical_records" class="text-green-600 hover:text-green-800 text-sm">View All</a>
            </div>
            <div id="recent-records" class="p-6">
                <p class="text-gray-600">Loading records...</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-xl font-semibold text-gray-700">
                    <i class="fas fa-file-invoice-dollar mr-2 text-indigo-500"></i> Recent Billing
                </h3>
                <a href="?page=billing_records" class="text-indigo-600 hover:text-indigo-800 text-sm">View All</a>
            </div>
            <div id="recent-billing" class="p-6">
                <p class="text-gray-600">Loading billing information...</p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        fetch('/it38b-Enterprise/api/patient/dashboard.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                displayUpcomingAppointments(data.upcoming_appointments);
                displayRecentRecords(data.recent_records);
                displayRecentBilling(data.recent_billing);
            })
            .catch(error => {
                console.error('Error fetching dashboard data:', error);
                document.getElementById('upcoming-appointments').innerHTML = '<p class="text-red-500">Failed to load appointments.</p>';
                document.getElementById('recent-records').innerHTML = '<p class="text-red-500">Failed to load medical records.</p>';
                document.getElementById('recent-billing').innerHTML = '<p class="text-red-500">Failed to load billing information.</p>';
            });

        function displayUpcomingAppointments(appointments) {
            const container = document.getElementById('upcoming-appointments');
            container.innerHTML = '';
            if (appointments && appointments.length > 0) {
                const ul = document.createElement('ul');
                appointments.forEach(appointment => {
                    const li = document.createElement('li');
                    li.className = 'py-3 border-b border-gray-200 last:border-b-0 flex items-center space-x-4';
                    li.innerHTML = `
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-gray-400"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">${appointment.formatted_date} at ${appointment.formatted_time}</p>
                            <p class="text-sm text-gray-600">With Dr. ${appointment.doctor_first_name} ${appointment.doctor_last_name}</p>
                            <p class="text-sm text-gray-600">Reason: ${appointment.reason}</p>
                            <span class="inline-block mt-1 px-2 py-1 text-xs rounded-full
                                ${appointment.status === 'Completed' ? 'bg-green-100 text-green-800' :
                            (appointment.status === 'Scheduled' ? 'bg-yellow-100 text-yellow-800' :
                                'bg-red-100 text-red-800')}">
                                ${appointment.status ? ucfirst(appointment.status) : ''}
                            </span>
                        </div>
                    `;
                    ul.appendChild(li);
                });
                container.appendChild(ul);
            } else {
                container.innerHTML = '<p class="text-gray-500">No upcoming appointments.</p>';
            }
        }

        function displayRecentRecords(records) {
            const container = document.getElementById('recent-records');
            container.innerHTML = '';
            if (records && records.length > 0) {
                const ul = document.createElement('ul');
                records.forEach(record => {
                    const li = document.createElement('li');
                    li.className = 'py-3 border-b border-gray-200 last:border-b-0 flex items-center space-x-4';
                    li.innerHTML = `
                        <div class="flex-shrink-0">
                            <i class="fas fa-file-alt text-gray-400"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">${record.formatted_date}</p>
                            <p class="text-sm text-gray-600">By Dr. ${record.doctor_first_name} ${record.doctor_last_name}</p>
                            <p class="text-sm text-gray-600">${record.short_diagnosis ? htmlspecialchars(record.short_diagnosis) : 'No diagnosis summary'}${record.diagnosis && record.diagnosis.length > 100 ? '...' : ''}</p>
                            <a href="?page=medical_records&view=details&id=${record.record_id}" class="text-blue-500 hover:underline text-sm">View Details</a>
                        </div>
                    `;
                    ul.appendChild(li);
                });
                container.appendChild(ul);
            } else {
                container.innerHTML = '<p class="text-gray-500">No recent medical records available.</p>';
            }
        }

        function displayRecentBilling(billing) {
            const container = document.getElementById('recent-billing');
            container.innerHTML = '';
            if (billing && billing.length > 0) {
                const ul = document.createElement('ul');
                billing.forEach(bill => {
                    const li = document.createElement('li');
                    li.className = 'py-3 border-b border-gray-200 last:border-b-0 flex items-center space-x-4';
                    li.innerHTML = `
                        <div class="flex-shrink-0">
                            <i class="fas fa-money-bill-wave-alt text-gray-400"></i>
                        </div>
                        <div>
                            <div class="flex justify-between">
                                <p class="font-semibold text-gray-800">Invoice #${htmlspecialchars(bill.billing_id)}</p>
                                <p class="text-gray-600 text-sm">${bill.formatted_date}</p>
                            </div>
                            <p class="text-sm text-gray-600">Amount: ₱${bill.formatted_amount}</p>
                            <span class="inline-block mt-1 px-2 py-1 text-xs rounded-full
                                ${bill.status === 'Paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                                ${bill.status ? ucfirst(bill.status) : ''}
                            </span>
                            <a href="?page=billing_records&view=details&id=${bill.billing_id}" class="text-blue-500 hover:underline text-sm">View Details</a>
                        </div>
                    `;
                    ul.appendChild(li);
                });
                container.appendChild(ul);
            } else {
                container.innerHTML = '<p class="text-gray-500">No recent billing information available.</p>';
            }
        }

        function ucfirst(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function htmlspecialchars(str) {
            if (typeof str !== 'string') return str;
            return str.replace(/[&<>"']/g, function (match) {
                switch (match) {
                    case '&': return '&amp;';
                    case '<': return '&lt;';
                    case '>': return '&gt;';
                    case '"': return '&quot;';
                    case "'": return '&#039;';
                    default: return match;
                }
            });
        }
    });
</script>