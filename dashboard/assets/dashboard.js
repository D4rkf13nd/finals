let chartType = "bar";
let chart;
function renderChart(type) {
    chart = new CanvasJS.Chart("barangayChartContainer", {
        animationEnabled: true,
        theme: "light2",
        title: { text: "Population by Barangay" },
        axisY: { title: "Residents" },
        data: [{
            type: type,
            dataPoints: window.barangayDataPoints
        }]
    });
    chart.render();
}

// Donut chart for gender
function renderGenderChart() {
    let genderChart = new CanvasJS.Chart("genderChartContainer", {
        animationEnabled: true,
        theme: "light2",
        title: { text: "Gender Distribution" },
        data: [{
            type: "doughnut",
            innerRadius: "60%",
            indexLabel: "{label} ({y})",
            dataPoints: window.genderDataPoints,
            // Custom colors for Male, Female, Other
            color: null,
            // Use colorSet for custom colors
            colorSet: "genderColors"
        }]
    });
    // Define the color set for gender
    CanvasJS.addColorSet("genderColors", [
        "#1976D2", // Blue for Male
        "#FFD600", // Yellow for Female
        "#D32F2F"  // Red for Other
    ]);
    genderChart.render();
}

document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("barBtn").onclick = function() { renderChart("bar"); }
    document.getElementById("lineBtn").onclick = function() { renderChart("line"); }
    document.getElementById("pieBtn").onclick = function() { renderChart("pie"); }
    renderChart("bar");
    renderGenderChart();
});