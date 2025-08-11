document.addEventListener("DOMContentLoaded", function() {
    // Apply darkmode if set in localStorage
    if (localStorage.getItem("darkmode") === "on") {
        document.body.classList.add("darkmode");
    }

    // Add darkmode button event if it exists
    const btn = document.getElementById("darkmodeBtn");
    if (btn) {
        btn.addEventListener("click", function() {
            document.body.classList.toggle("darkmode");
            if (document.body.classList.contains("darkmode")) {
                localStorage.setItem("darkmode", "on");
            } else {
                localStorage.setItem("darkmode", "off");
            }
        });
    }
});