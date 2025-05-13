<?php
// Ensure this file is in the 'views/patient/' directory
?>

<div class="p-4">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-semibold mb-4">Billing Records</h2>
        <div id="billing-records-list">
            <p class="text-gray-600">Loading billing records...</p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        loadBillingRecords();
    });

    function loadBillingRecords() {
        fetch('/it38b-Enterprise/api/patient/billing.php?action=list')
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.billing_records) {
                    displayBillingRecords(data.billing_records);
                } else {
                    document.getElementById('billing-records-list').innerHTML = `<p class="text-red-500">${data.error || 'Failed to load billing records.'}</p>`;
                }
            })
            .catch(error => {
                console.error('Error fetching billing records:', error);
                document.getElementById('billing-records-list').innerHTML = '<p class="text-red-500">Failed to load billing records. Please try again later.</p>';
            });
    }

    function displayBillingRecords(records) {
        const container = document.getElementById('billing-records-list');
        container.innerHTML = ''; // Clear loading message

        if (records.length > 0) {
            const table = document.createElement('table');
            table.className = 'w-full table-auto';

            const thead = document.createElement('thead');
            thead.className = 'bg-gray-100';
            const headerRow = document.createElement('tr');
            const headers = ['Bill ID', 'Date', 'Amount', 'Status', ''];
            headers.forEach(headerText => {
                const th = document.createElement('th');
                th.className = 'px-4 py-2 text-left';
                th.textContent = headerText;
                headerRow.appendChild(th);
            });
            thead.appendChild(headerRow);
            table.appendChild(thead);

            const tbody = document.createElement('tbody');
            records.forEach(record => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50';

                const billIdCell = document.createElement('td');
                billIdCell.className = 'px-4 py-2';
                billIdCell.textContent = htmlspecialchars(record.bill_id);
                row.appendChild(billIdCell);

                const dateCell = document.createElement('td');
                dateCell.className = 'px-4 py-2';
                dateCell.textContent = record.formatted_date;
                row.appendChild(dateCell);

                const amountCell = document.createElement('td');
                amountCell.className = 'px-4 py-2';
                amountCell.textContent = `₱${record.formatted_amount}`;
                row.appendChild(amountCell);

                const statusCell = document.createElement('td');
                statusCell.className = 'px-4 py-2';
                const statusSpan = document.createElement('span');
                statusSpan.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColorClass(record.payment_status)}`;
                statusSpan.textContent = htmlspecialchars(record.payment_status);
                statusCell.appendChild(statusSpan);
                row.appendChild(statusCell);

                const actionsCell = document.createElement('td');
                actionsCell.className = 'px-4 py-2 text-right';
                const viewDetailsLink = document.createElement('a');
                viewDetailsLink.href = `?page=billing_records&view=details&id=${record.bill_id}`;
                viewDetailsLink.className = 'inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2';
                viewDetailsLink.textContent = 'View Details';
                actionsCell.appendChild(viewDetailsLink);
                row.appendChild(actionsCell);

                tbody.appendChild(row);
            });
            table.appendChild(tbody);
            container.appendChild(table);
        } else {
            container.innerHTML = '<p class="text-gray-500">No billing records found.</p>';
        }
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