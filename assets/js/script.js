document.addEventListener('DOMContentLoaded', function() {
    // Preloader
    const preloader = document.getElementById('preloader');
    if (preloader) {
        window.addEventListener('load', () => setTimeout(() => preloader.classList.add('hidden'), 400));
        setTimeout(() => preloader.classList.add('hidden'), 2000);
    }
    // Navbar scroll
    const navbar = document.getElementById('mainNav');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 40);
        });
    }
    // Back to top
    const btt = document.getElementById('backToTop');
    if (btt) {
        window.addEventListener('scroll', () => btt.classList.toggle('show', window.scrollY > 350));
        btt.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    }
    // AOS
    if (typeof AOS !== 'undefined') {
        AOS.init({ duration: 700, once: true, offset: 40, disable: window.innerWidth < 768 });
    }
    // Counters
    document.querySelectorAll('.counter').forEach(el => {
        const target = parseInt(el.dataset.target);
        const obs = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting) {
                let cur = 0;
                const step = target / 80;
                const tick = () => {
                    cur += step;
                    if (cur < target) { el.textContent = Math.floor(cur) + '+'; requestAnimationFrame(tick); }
                    else el.textContent = target + '+';
                };
                tick();
                obs.unobserve(el);
            }
        }, { threshold: 0.5 });
        obs.observe(el);
    });
    // Mobile nav close
    document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
        link.addEventListener('click', () => {
            const col = document.querySelector('.navbar-collapse.show');
            if (col) bootstrap.Collapse.getInstance(col)?.hide();
        });
    });
});
