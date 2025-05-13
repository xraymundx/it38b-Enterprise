<?php
// Ensure this file is in the 'views/patient/' directory

// Check if a bill ID is provided in the query parameters
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div class="p-4"><div class="bg-white rounded-lg shadow p-6"><p class="text-red-500">Invalid billing record ID.</p><p class="mt-2"><a href="?page=billing_records" class="text-blue-500 hover:underline">Back to Billing Records</a></p></div></div>';
    exit();
}

$billId = intval($_GET['id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Record Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Billing Record Details</h1>
                <a href="/it38b-Enterprise/routes/dashboard_router.php?page=billing_records"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Billing Records
                </a>
            </div>

            <div id="billing-record-details">
                <p class="text-gray-600">Loading billing record details...</p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            loadRecordDetails(<?php echo $billId; ?>);
        });

        function loadRecordDetails(billId) {
            fetch(`/it38b-Enterprise/api/patient/billing.php?action=view&id=${billId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP error ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.billing) {
                        displayRecordDetails(data.billing);
                    } else {
                        document.getElementById('billing-record-details').innerHTML = `<p class="text-red-500">${data.error || 'Failed to load billing record details.'}</p><p class="mt-2"><a href="/it38b-Enterprise/routes/dashboard_router.php?page=billing_records" class="text-blue-500 hover:underline">Back to Billing Records</a></p>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching record details:', error);
                    document.getElementById('billing-record-details').innerHTML = '<p class="text-red-500">Failed to load billing record details. Please try again later.</p><p class="mt-2"><a href="/it38b-Enterprise/routes/dashboard_router.php?page=billing_records" class="text-blue-500 hover:underline">Back to Billing Records</a></p>';
                });
        }

        function displayRecordDetails(record) {
            const container = document.getElementById('billing-record-details');
            container.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Billing Information</h2>
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-1">Bill ID</p>
                            <p class="font-medium">${htmlspecialchars(record.bill_id)}</p>
                        </div>
                        ${record.invoice_number ? `
                            <div class="mb-4">
                                <p class="text-sm text-gray-500 mb-1">Invoice Number</p>
                                <p class="font-medium">${htmlspecialchars(record.invoice_number)}</p>
                            </div>
                        ` : ''}
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-1">Date</p>
                            <p class="font-medium">${record.formatted_date}</p>
                        </div>
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-1">Amount</p>
                            <p class="font-medium">₱${record.formatted_amount}</p>
                        </div>
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-1">Payment Status</p>
                            <span class="${getStatusColorClass(record.payment_status)} inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">${htmlspecialchars(record.payment_status)}</span>
                        </div>
                        ${record.payment_method ? `
                            <div class="mb-4">
                                <p class="text-sm text-gray-500 mb-1">Payment Method</p>
                                <p class="font-medium">${htmlspecialchars(record.payment_method)}</p>
                            </div>
                        ` : ''}
                        ${record.payment_date ? `
                            <div class="mb-4">
                                <p class="text-sm text-gray-500 mb-1">Payment Date</p>
                                <p class="font-medium">${record.payment_date}</p>
                            </div>
                        ` : ''}
                        ${record.appointment_id ? `
                            <div class="mb-4">
                                <p class="text-sm text-gray-500 mb-1">Appointment</p>
                                <p class="font-medium">#${record.appointment_id} (${record.appointment_date || 'N/A'})</p>
                            </div>
                        ` : ''}
                        ${record.record_id ? `
                            <div class="mb-4">
                                <p class="text-sm text-gray-500 mb-1">Medical Record</p>
                                <p class="font-medium">#${record.record_id}</p>
                            </div>
                        ` : ''}
                    </div>

                    ${record.doctor_first_name ? `
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Doctor Information</h2>
                            <div class="mb-4">
                                <p class="text-sm text-gray-500 mb-1">Doctor</p>
                                <p class="font-medium">Dr. ${htmlspecialchars(record.doctor_first_name + ' ' + record.doctor_last_name)} ${record.specialization_name ? `(${htmlspecialchars(record.specialization_name)})` : ''}</p>
                            </div>
                        </div>
                    ` : ''}
                </div>

                <div class="bg-gray-50 p-6 rounded-lg mb-8">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Description & Notes</h2>
                    <div class="mb-4">
                        <p class="text-sm text-gray-500 mb-1">Description</p>
                        <div class="bg-white p-3 rounded border border-gray-200">
                            ${htmlspecialchars(record.description) || 'N/A'}
                        </div>
                    </div>
                    ${record.notes ? `
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-1">Notes</p>
                            <div class="bg-white p-3 rounded border border-gray-200">
                                ${htmlspecialchars(record.notes)}
                            </div>
                        </div>
                    ` : ''}
                </div>

                <div class="text-left">
                    <a href="/it38b-Enterprise/routes/dashboard_router.php?page=billing_records" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">Back to Billing Records</a>
                </div>
            `;
        }

        function getStatusColorClass(status) {
            switch (status) {
                case 'Paid':
                    return 'bg-green-100 text-green-800';
                case 'Partial':
                    return 'bg-yellow-100 text-yellow-800';
                case 'Unpaid':
                    return 'bg-red-100 text-red-800';
                case 'Cancelled':
                    return 'bg-gray-300 text-gray-700';
                default:
                    return 'bg-gray-100 text-gray-800';
            }
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
    </script>
</body>

</html>