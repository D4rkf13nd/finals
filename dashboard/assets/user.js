document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("residentSearch");
    const searchBtn = document.getElementById("residentSearchBtn");
    function filterResidents() {
        const filter = searchInput.value.toLowerCase();
        document.querySelectorAll("tbody tr").forEach(function(row) {
            row.style.display = row.textContent.toLowerCase().includes(filter) ? "" : "none";
        });
    }
    if (searchInput) {
        searchInput.addEventListener("keyup", function(e) {
            if (e.key === "Enter") filterResidents();
        });
    }
    if (searchBtn) {
        searchBtn.addEventListener("click", filterResidents);
    }
    var el = document.getElementById("populationCounter");
    var end = parseInt(el.textContent, 10) || 0;
    var start = 0;
    var duration = 1000;
    var startTime = null;
    function animateCounter(ts) {
        if (!startTime) startTime = ts;
        var progress = Math.min((ts - startTime) / duration, 1);
        el.textContent = Math.floor(progress * (end - start) + start);
        if (progress < 1) requestAnimationFrame(animateCounter);
        else el.textContent = end;
    }
    requestAnimationFrame(animateCounter);
});