/* ==========================================================
   SLIDER.JS — Slider Journey Tingkat Belajar
   Dipakai di: slide-keunggulan.html
   Fitur:
   - Navigasi prev / next
   - Indikator titik (dots) yang bisa diklik
   - Progress bar tingkat
   - Counter "langkah X dari Y"
   - Keyboard arrow kiri/kanan
   - Swipe (geser) di layar sentuh
   - Autoplay yang bisa dihidup/matikan
   Aktif hanya jika ada elemen #journey-slider.
   ========================================================== */

(function () {
    "use strict";

    const root = document.getElementById("journey-slider");
    if (!root) return;   // halaman lain tidak terpengaruh

    /* ---------- Ambil elemen ---------- */
    const track    = root.querySelector(".slider-track");
    const slides   = Array.from(root.querySelectorAll(".slide"));
    const btnPrev  = root.querySelector(".slider-btn.prev");
    const btnNext  = root.querySelector(".slider-btn.next");
    const dotsWrap = document.getElementById("slider-dots");
    const progress = document.getElementById("journey-progress-bar");
    const curEl    = document.getElementById("slide-current");
    const totalEl  = document.getElementById("slide-total");
    const autoBtn  = document.getElementById("autoplay-toggle");

    const total = slides.length;
    let index = 0;
    let autoplay = true;
    let timer = null;
    const INTERVAL = 4500;   // ms antar slide saat autoplay

    /* ---------- Buat dots otomatis ---------- */
    const dots = [];
    if (dotsWrap) {
        slides.forEach(function (_, i) {
            const dot = document.createElement("button");
            dot.className = "dot" + (i === 0 ? " active" : "");
            dot.setAttribute("aria-label", "Ke slide " + (i + 1));
            dot.addEventListener("click", function () { goTo(i); resetAutoplay(); });
            dotsWrap.appendChild(dot);
            dots.push(dot);
        });
    }
    if (totalEl) totalEl.textContent = total;

    /* ---------- Fungsi inti ---------- */
    function render() {
        // Geser track
        track.style.transform = "translateX(" + (-index * 100) + "%)";

        // Update dots
        dots.forEach(function (d, i) { d.classList.toggle("active", i === index); });

        // Update progress bar (proporsional terhadap jumlah slide)
        if (progress) progress.style.width = ((index + 1) / total * 100) + "%";

        // Update counter
        if (curEl) curEl.textContent = index + 1;
    }

    function goTo(i) {
        index = (i + total) % total;   // wrap-around
        render();
    }
    function next() { goTo(index + 1); }
    function prev() { goTo(index - 1); }

    /* ---------- Autoplay ---------- */
    function startAutoplay() {
        stopAutoplay();
        if (autoplay) timer = setInterval(next, INTERVAL);
    }
    function stopAutoplay() {
        if (timer) { clearInterval(timer); timer = null; }
    }
    function resetAutoplay() { startAutoplay(); }

    function updateAutoBtn() {
        if (!autoBtn) return;
        autoBtn.textContent = autoplay ? "⏸️ Jeda Otomatis" : "▶️ Putar Otomatis";
    }

    /* ---------- Event listeners ---------- */
    if (btnNext) btnNext.addEventListener("click", function () { next(); resetAutoplay(); });
    if (btnPrev) btnPrev.addEventListener("click", function () { prev(); resetAutoplay(); });

    if (autoBtn) {
        autoBtn.addEventListener("click", function () {
            autoplay = !autoplay;
            updateAutoBtn();
            startAutoplay();
        });
    }

    // Keyboard arrow
    document.addEventListener("keydown", function (e) {
        if (e.key === "ArrowRight") { next(); resetAutoplay(); }
        else if (e.key === "ArrowLeft") { prev(); resetAutoplay(); }
    });

    // Jeda autoplay saat kursor di atas slider
    root.addEventListener("mouseenter", stopAutoplay);
    root.addEventListener("mouseleave", startAutoplay);

    // Swipe untuk layar sentuh
    let startX = 0, isTouching = false;
    root.addEventListener("touchstart", function (e) {
        startX = e.touches[0].clientX; isTouching = true; stopAutoplay();
    }, { passive: true });
    root.addEventListener("touchend", function (e) {
        if (!isTouching) return;
        const diff = e.changedTouches[0].clientX - startX;
        if (Math.abs(diff) > 50) { diff < 0 ? next() : prev(); }
        isTouching = false; startAutoplay();
    }, { passive: true });

    /* ---------- Inisialisasi ---------- */
    render();
    updateAutoBtn();
    startAutoplay();

})();
