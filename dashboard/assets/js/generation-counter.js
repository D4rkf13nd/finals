document.addEventListener('DOMContentLoaded', function() {
    function animateValue(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const value = Math.floor(progress * (end - start) + start);
            element.textContent = value.toLocaleString();
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const element = entry.target;
                const value = parseInt(element.dataset.value || 0, 10);
                animateValue(element, 0, value, 2000);
                observer.unobserve(element);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.generation-value').forEach(counter => {
        observer.observe(counter);
    });
});