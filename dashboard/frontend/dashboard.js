window.addEventListener('DOMContentLoaded', function() {
    const d = window.dashboardData;

    // Age Groups Chart
    if (document.getElementById('ageChart')) {
        new Chart(document.getElementById('ageChart'), {
            type: 'bar',
            data: {
                labels: ['0-17', '18-59', '60+'],
                datasets: [{
                    label: 'Population by Age Group',
                    data: d.ageGroups,
                    backgroundColor: ['#0d6efd', '#6c757d', '#ffc107']
                }]
            }
        });
    }

    // Barangay Pie Chart
    if (document.getElementById('barangayChart')) {
        new Chart(document.getElementById('barangayChart'), {
            type: 'pie',
            data: {
                labels: d.barangayLabels,
                datasets: [{
                    label: 'Population by Barangay',
                    data: d.barangayData,
                    backgroundColor: ['#0d6efd', '#6c757d', '#ffc107', '#198754', '#dc3545']
                }]
            }
        });
    }

    // Gender Chart
    if (document.getElementById('genderChart')) {
        new Chart(document.getElementById('genderChart'), {
            type: 'doughnut',
            data: {
                labels: ['Male', 'Female'],
                datasets: [{
                    label: 'Gender Ratio',
                    data: d.genderData,
                    backgroundColor: ['#0d6efd', '#dc3545']
                }]
            }
        });
    }

    // Growth Chart
    if (document.getElementById('growthChart')) {
        new Chart(document.getElementById('growthChart'), {
            type: 'line',
            data: {
                labels: ['2021', '2022', '2023', '2024', '2025'],
                datasets: [{
                    label: 'Yearly Population Growth',
                    data: d.growthData,
                    borderColor: '#198754',
                    fill: false
                }]
            }
        });
    }
});