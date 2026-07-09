/* ==========================================================
   CORE.JS — Bimbel Ayo Mengaji
   Dipakai di SEMUA halaman.
   1. Dark / Light Mode  (SINKRON antar halaman via localStorage)
   2. Floating WhatsApp  (muncul saat scroll + "kedip" menarik perhatian)
   3. Hamburger Menu (mobile)
   4. Navbar scroll effect
   5. Reveal on scroll (animasi muncul)
   6. Auto-highlight menu navigasi aktif

   Konvensi id/class di HTML:
   - Tombol tema   -> #theme-toggle
   - Hamburger     -> #hamburger
   - Wrapper menu  -> #nav-menu  (berisi <ul class="nav-list">)
   - Floating WA   -> .floating-wa
   - Animasi masuk -> class "reveal"
   ========================================================== */

(function () {
    "use strict";

    /* ======================================================
       1. DARK / LIGHT MODE (SINKRON ANTAR HALAMAN)
       Satu kunci localStorage "bimbel-theme" dibaca semua
       halaman -> tema konsisten saat berpindah halaman.
       ====================================================== */
    const THEME_KEY = "bimbel-theme";
    const root = document.documentElement;          // <html data-theme="...">
    const themeToggleBtn = document.getElementById("theme-toggle");

    function applyTheme(theme) {
        root.setAttribute("data-theme", theme);
        if (themeToggleBtn) themeToggleBtn.textContent = theme === "dark" ? "🌙" : "☀️";
    }

    // Terapkan tema tersimpan (default mengikuti preferensi sistem bila ada)
    let initial = localStorage.getItem(THEME_KEY);
    if (!initial && window.matchMedia) {
        initial = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
    }
    applyTheme(initial || "light");

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener("click", function () {
            const next = root.getAttribute("data-theme") === "dark" ? "light" : "dark";
            applyTheme(next);
            localStorage.setItem(THEME_KEY, next);
        });
    }

    // Sinkron real-time jika tema diubah di tab lain
    window.addEventListener("storage", function (e) {
        if (e.key === THEME_KEY && e.newValue) applyTheme(e.newValue);
    });

    /* ======================================================
       2. FLOATING WHATSAPP
       - Muncul mulus setelah pengguna sedikit men-scroll.
       - Sesekali "mengembang" otomatis untuk menarik perhatian.
       ====================================================== */
    const floatingWa = document.querySelector(".floating-wa");
    if (floatingWa) {
        // Animasi "tease": teks bubble mengembang sebentar tiap 8 detik
        setInterval(function () {
            floatingWa.classList.add("wa-tease");
            setTimeout(function () { floatingWa.classList.remove("wa-tease"); }, 2200);
        }, 8000);
    }

    /* ======================================================
       3. HAMBURGER MENU (MOBILE)
       ====================================================== */
    const hamburger = document.getElementById("hamburger");
    const navMenu = document.getElementById("nav-menu");
    const navList = navMenu ? navMenu.querySelector(".nav-list") : null;

    if (hamburger && navList) {
        hamburger.addEventListener("click", function () {
            hamburger.classList.toggle("active");
            navList.classList.toggle("active");
        });
        navList.querySelectorAll(".nav-link").forEach(function (link) {
            link.addEventListener("click", function () {
                hamburger.classList.remove("active");
                navList.classList.remove("active");
            });
        });
    }

    /* ======================================================
       4. NAVBAR SCROLL EFFECT
       ====================================================== */
    const navbar = document.getElementById("navbar");
    if (navbar) {
        const onScroll = function () {
            navbar.classList.toggle("scrolled", window.scrollY > 40);
        };
        window.addEventListener("scroll", onScroll, { passive: true });
        onScroll();
    }

    /* ======================================================
       5. REVEAL ON SCROLL (IntersectionObserver)
       ====================================================== */
    const revealEls = document.querySelectorAll(".reveal");
    if (revealEls.length) {
        if ("IntersectionObserver" in window) {
            const observer = new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("is-visible");
                        obs.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15, rootMargin: "0px 0px -40px 0px" });
            revealEls.forEach(function (el) { observer.observe(el); });
        } else {
            revealEls.forEach(function (el) { el.classList.add("is-visible"); });
        }
    }

    /* ======================================================
       6. AUTO-HIGHLIGHT MENU NAVIGASI AKTIF
       ====================================================== */
    const currentPage = (location.pathname.split("/").pop() || "index.html").toLowerCase();
    document.querySelectorAll(".nav-link").forEach(function (link) {
        const href = (link.getAttribute("href") || "").toLowerCase();
        link.classList.toggle("active", href === currentPage);
    });

})();
