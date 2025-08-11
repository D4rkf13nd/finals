let barangayChart;
let genderChart;

function initializeCharts() {
    const ctx = document.getElementById('barangayChart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (barangayChart) {
        barangayChart.destroy();
    }

    barangayChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: window.barangayDataPoints.map(point => point.label),
            datasets: [{
                label: 'Population by Barangay',
                data: window.barangayDataPoints.map(point => point.y),
                backgroundColor: [
                    'rgba(250, 0, 0, 1)',
                    'rgba(255, 128, 0, 1)',
                    'rgba(255, 204, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(54, 163, 235, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(125, 127, 133, 1)',
                    'rgba(250, 0, 54, 1)'
                ],
                borderColor: [
                    'rgba(243, 0, 53, 1)',
                    'rgba(253, 127, 0, 1)',
                    'rgba(245, 171, 0, 1)',
                    'rgba(0, 255, 255, 1)',
                    'rgb(54, 162, 235)',
                    'rgba(85, 0, 255, 1)',
                    'rgb(201, 203, 207)',
                    'rgb(255, 99, 132)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Add gender chart initialization
    const genderCtx = document.getElementById('genderChart').getContext('2d');
    
    // Destroy existing gender chart if it exists
    if (genderChart) {
        genderChart.destroy();
    }

    genderChart = new Chart(genderCtx, {
        type: 'doughnut',
        data: {
            labels: window.genderDataPoints.map(point => point.label),
            datasets: [{
                label: 'Gender Distribution',
                data: window.genderDataPoints.map(point => point.y),
                backgroundColor: [
                    'rgba(252, 0, 55, 1)',
                    'rgba(4, 0, 253, 1)',
                    'rgb(255, 205, 86)'
                ],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Gender Distribution'
                }
            }
        }
    });
}

// Add event listeners for chart type buttons
document.getElementById('barBtn').addEventListener('click', () => updateChartType('bar'));
document.getElementById('lineBtn').addEventListener('click', () => updateChartType('line'));
document.getElementById('pieBtn').addEventListener('click', () => updateChartType('pie'));

function updateChartType(type) {
    if (barangayChart) {
        barangayChart.destroy();
    }
    
    const ctx = document.getElementById('barangayChart').getContext('2d');
    barangayChart = new Chart(ctx, {
        type: type,
        data: {
            labels: window.barangayDataPoints.map(point => point.label),
            datasets: [{
                label: 'Population by Barangay',
                data: window.barangayDataPoints.map(point => point.y),
                backgroundColor: [
                   'rgba(250, 0, 0, 1)',
                    'rgba(255, 128, 0, 1)',
                    'rgba(255, 204, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(54, 163, 235, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(125, 127, 133, 1)',
                    'rgba(250, 0, 54, 1)'
                ],
                borderColor: [
                    'rgba(250, 0, 55, 1)',
                    'rgba(255, 128, 0, 1)',
                    'rgba(255, 204, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(54, 163, 235, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(125, 127, 133, 1)',
                    'rgba(250, 0, 54, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Keep gender chart as doughnut
    if (genderChart) {
        genderChart.destroy();
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        genderChart = new Chart(genderCtx, {
            type: 'doughnut',
            data: {
                labels: window.genderDataPoints.map(point => point.label),
                datasets: [{
                    label: 'Gender Distribution',
                    data: window.genderDataPoints.map(point => point.y),
                    backgroundColor: [
                        'rgba(250, 0, 55, 1)',
                        'rgba(4, 0, 253, 1)',
                        'rgba(255, 204, 86, 1)'
                    ],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Gender Distribution'
                    }
                }
            }
        });
    }
}

// Add after the existing chart initialization code
function calculateGenerations(residents) {
    const generations = {
        Gen_Z: 0,
        Millennials: 0,
        Gen_X: 0,
        Boomers: 0
    };

    residents.forEach(resident => {
        const birthYear = new Date(resident.birthday).getFullYear();
        
        if (birthYear >= 1997 && birthYear <= 2012) {
            generations.Gen_Z++;
        } else if (birthYear >= 1981 && birthYear <= 1996) {
            generations.Millennials++;
        } else if (birthYear >= 1965 && birthYear <= 1980) {
            generations.Gen_X++;
        } else if (birthYear >= 1946 && birthYear <= 1964) {
            generations.Boomers++;
        }
    });

    // Update the generation counters in the DOM
    document.querySelector('#genZ-count').textContent = generations.Gen_Z;
    document.querySelector('#millennials-count').textContent = generations.Millennials;
    document.querySelector('#genX-count').textContent = generations.Gen_X;
    document.querySelector('#boomers-count').textContent = generations.Boomers;
}

// Add this to your DOMContentLoaded event listener
document.addEventListener('DOMContentLoaded', () => {
    initializeCharts();
    
    // Calculate generations if data is available
    if (window.residentsData) {
        calculateGenerations(window.residentsData);
    }
});

// Export functionality
document.getElementById('exportBtn').addEventListener('click', function() {
    // Show loading state
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Exporting...';

    // Perform export
    fetch('backend/export.php')
        .then(response => {
            if (!response.ok) throw new Error('Export failed');
            return response.blob();
        })
        .then(blob => {
            // Create download link
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `population_data_${new Date().toISOString().slice(0,10)}.csv`;
            
            // Trigger download
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            
            // Reset button state
            this.disabled = false;
            this.innerHTML = 'Export';
        })
        .catch(error => {
            console.error('Export error:', error);
            alert('Failed to export data. Please try again.');
            
            // Reset button state
            this.disabled = false;
            this.innerHTML = 'Export';
        });
});

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        editable: true,
        dayMaxEvents: true,
        eventColor: {
            red: '#ad2121',
            blue: '#1e90ff',
            yellow: '#e3bc08'
        },
        events: window.events || [],
        eventClick: function(info) {
            handleEventClick(info.event);
        },
        eventDrop: function(info) {
            handleEventDrop(info.event);
        },
        eventResize: function(info) {
            handleEventResize(info.event);
        },
        dateClick: function(info) {
            handleDateClick(info.date);
        }
    });
    calendar.render();

    // Event handlers
    function handleEventClick(event) {
        if (confirm(`Do you want to delete '${event.title}'?`)) {
            // Send delete request to server
            fetch(`backend/delete_event.php?id=${event.id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        event.remove();
                    } else {
                        alert('Failed to delete event');
                    }
                });
        }
    }

    function handleEventDrop(event) {
        updateEvent(event);
    }

    function handleEventResize(event) {
        updateEvent(event);
    }

    function handleDateClick(date) {
        const title = prompt('Enter event title:');
        if (title) {
            // Send create request to server
            fetch('backend/add_event.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    title: title,
                    start: date.toISOString(),
                    end: date.toISOString(),
                    color: '#1e90ff'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    calendar.addEvent({
                        id: data.id,
                        title: title,
                        start: date,
                        color: '#1e90ff'
                    });
                } else {
                    alert('Failed to create event');
                }
            });
        }
    }

    function updateEvent(event) {
        // Send update request to server
        fetch('backend/update_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: event.id,
                title: event.title,
                start: event.start.toISOString(),
                end: event.end ? event.end.toISOString() : event.start.toISOString()
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('Failed to update event');
                calendar.refetchEvents();
            }
        });
    }

    // Add Event Button Handler
    document.getElementById('addEventBtn')?.addEventListener('click', function() {
        const title = prompt('Enter event title:');
        if (title) {
            const now = new Date();
            calendar.addEvent({
                title: title,
                start: now,
                color: '#1e90ff'
            });
        }
    });
});