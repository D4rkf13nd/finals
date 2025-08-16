let barangayChart;
let genderChart;

// Utility functions
function chunkArray(array, size) {
    const chunks = [];
    for (let i = 0; i < array.length; i += size) {
        chunks.push(array.slice(i, i + size));
    }
    return chunks;
}

function aggregateData(data, key) {
    return data.reduce((acc, curr) => {
        acc[curr[key]] = (acc[curr[key]] || 0) + 1;
        return acc;
    }, {});
}

// Initialize charts
async function initializeCharts() {
    const barangayCtx = document.getElementById('barangayChart').getContext('2d');
    
    // Create chart configuration
    const chartConfig = {
        type: 'bar',
        data: {
            labels: chartData.barangayLabels,
            datasets: [{
                label: 'Population',
                data: chartData.barangayData,
                backgroundColor: [
                  'rgba(54, 163, 235, 1)',
        'rgba(243, 0, 53, 1)',
        'rgba(255, 207, 86, 1)',
        'rgba(0, 255, 255, 1)',
        'rgba(56, 0, 168, 0.8)',
        'rgba(238, 119, 0, 1)',
        'rgba(253, 0, 97, 1)',
        'rgba(0, 115, 187, 1)'
                ],
                borderColor: 'white',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    };

    // Initialize chart
    barangayChart = new Chart(barangayCtx, chartConfig);

    // Add click handlers for toggle buttons
    document.querySelectorAll('.chart-toggle .btn').forEach(button => {
        button.addEventListener('click', function() {
            const chartType = this.dataset.chart;
            
            // Update active button state
            document.querySelectorAll('.chart-toggle .btn').forEach(btn => 
                btn.classList.remove('active'));
            this.classList.add('active');

            // Update chart configuration based on type
            updateChartType(chartType);
        });
    });
}

function updateChartType(chartType) {
    if (!barangayChart) return;

    // Destroy existing chart
    barangayChart.destroy();

    const ctx = document.getElementById('barangayChart').getContext('2d');
    const colors = [
        'rgba(54, 163, 235, 1)',
        'rgba(243, 0, 53, 1)',
        'rgba(255, 207, 86, 1)',
        'rgba(0, 255, 255, 1)',
        'rgba(56, 0, 168, 0.8)',
        'rgba(238, 119, 0, 1)',
        'rgba(253, 0, 97, 1)',
        'rgba(0, 115, 187, 1)'
    ];

    // Configure chart based on type
    const config = {
        type: chartType,
        data: {
            labels: chartData.barangayLabels,
            datasets: [{
                label: 'Population',
                data: chartData.barangayData,
                backgroundColor: chartType === 'line' ? colors[0] : colors,
                borderColor: chartType === 'line' ? colors[0] : 'white',
                borderWidth: chartType === 'line' ? 2 : 1,
                fill: chartType === 'line' ? false : true,
                tension: chartType === 'line' ? 0.4 : 0,
                pointBackgroundColor: chartType === 'line' ? colors[0] : undefined,
                pointRadius: chartType === 'line' ? 4 : undefined
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: chartType === 'pie'
                }
            },
            scales: chartType !== 'pie' ? {
                y: {
                    beginAtZero: true
                }
            } : undefined
        }
    };

    // Create new chart
    barangayChart = new Chart(ctx, config);
}

// Keep existing counter functions
function initCounters() {
    // Counter animation function
    function animateValue(obj, start, end, duration = 2000) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const currentValue = Math.floor(progress * (end - start) + start);
            obj.textContent = currentValue.toLocaleString();
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    // Animate total population counter
    const totalCounter = document.querySelector('.counter-number');
    if (totalCounter) {
        animateValue(totalCounter, 0, parseInt(totalCounter.dataset.value));
    }

    // Animate percentage counters
    document.querySelectorAll('.counter-percent').forEach(counter => {
        const container = counter.closest('[data-target]');
        if (container) {
            const target = parseFloat(container.dataset.target);
            animateValue(counter, 0, target, 2000, true);
        }
    });

    // Animate generation counters
    document.querySelectorAll('.generation-value').forEach(counter => {
        const value = parseInt(counter.textContent);
        if (!isNaN(value)) {
            animateValue(counter, 0, value);
        }
    });
}

// Update chart toggle handlers
function initChartToggles() {
    document.querySelectorAll('.chart-toggle .btn').forEach(button => {
        button.addEventListener('click', function() {
            const chartType = this.dataset.chart;
            
            // Update active button state
            document.querySelectorAll('.chart-toggle .btn').forEach(btn => 
                btn.classList.remove('active'));
            this.classList.add('active');

            // Destroy existing chart
            if (barangayChart) {
                barangayChart.destroy();
            }

            const ctx = document.getElementById('barangayChart').getContext('2d');

            // Create new configuration
            const newConfig = {
                type: chartType,
                data: {
                    labels: chartData.barangayLabels,
                    datasets: [{
                        label: 'Population by Barangay',
                        data: chartData.barangayData,
                        backgroundColor: chartType === 'line' ? 'rgba(59, 130, 246, 0.2)' : 
                            chartType === 'pie' ? [
                                'rgba(230, 0, 50, 1)',
                                'rgba(0, 107, 179, 1)',
                                'rgba(243, 97, 0, 1)',
                                'rgba(0, 255, 255, 1)',
                                'rgba(57, 0, 172, 1)',
                                'rgba(255, 160, 64, 1)'
                            ] : [
                                'rgba(189, 0, 41, 1)',
                                'rgba(54, 163, 235, 1)',
                                'rgba(255, 207, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 160, 64, 1)'
                            ],
                        borderColor: chartType === 'line' ? 'rgba(38, 0, 207, 1)' : 
                            chartType === 'pie' ? 'white' : 'white',
                        borderWidth: chartType === 'line' ? 2 : 1,
                        tension: chartType === 'line' ? 0.4 : 0,
                        fill: chartType === 'line'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: chartType === 'pie',
                            position: 'bottom'
                        }
                    },
                    scales: chartType !== 'pie' ? {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    } : undefined
                }
            };

            // Create new chart
            barangayChart = new Chart(ctx, newConfig);
        });
    });
}

function initGenderChart() {
    const genderCtx = document.getElementById('genderChart').getContext('2d');
    
    // Create gradients
    const maleGradient = genderCtx.createLinearGradient(0, 0, 0, 400);
    maleGradient.addColorStop(0, 'rgba(0, 65, 170, 1)');
    maleGradient.addColorStop(1, 'rgba(0, 34, 187, 1)');
    
    const femaleGradient = genderCtx.createLinearGradient(0, 0, 0, 400);
    femaleGradient.addColorStop(0, 'rgba(182, 0, 91, 1)');
    femaleGradient.addColorStop(1, 'rgba(121, 0, 90, 1)');

    genderChart = new Chart(genderCtx, {
        type: 'doughnut',
        data: {
            labels: ['Male', 'Female'],
            datasets: [{
                data: chartData.genderData,
                backgroundColor: [maleGradient, femaleGradient],
                borderWidth: 0,
                cutout: '70%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    });
}

// Initialize everything on load
document.addEventListener('DOMContentLoaded', () => {
    initializeCharts();
    initGenderChart();
    initCounters();
    initChartToggles();
});