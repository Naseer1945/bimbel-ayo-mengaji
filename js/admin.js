/* ==========================================================
   ADMIN.JS — Bimbel Ayo Mengaji
   Interaktivitas panel admin/manager/pengajar/client:
   1. Sidebar toggle (mobile overlay)
   2. Modal open/close (data-modal-target / data-modal-close)
   3. Toast notification (dari flash message PHP)
   4. Konfirmasi aksi hapus (data-confirm)
   5. Dynamic entitas rows (client/daftar.php)
   ========================================================== */
(function () {
    "use strict";

    /* 1. SIDEBAR TOGGLE -------------------------------------- */
    const sidebar = document.getElementById("admin-sidebar");
    const toggle  = document.getElementById("sidebar-toggle");
    const overlay = document.getElementById("sidebar-overlay");
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove("open");
        if (overlay) overlay.classList.remove("active");
    }
    if (toggle && sidebar) {
        toggle.addEventListener("click", function () {
            sidebar.classList.toggle("open");
            if (overlay) overlay.classList.toggle("active");
        });
    }
    if (overlay) overlay.addEventListener("click", closeSidebar);

    /* 2. MODAL ----------------------------------------------- */
    document.querySelectorAll("[data-modal-target]").forEach(function (btn) {
        btn.addEventListener("click", function () {
            const m = document.getElementById(btn.getAttribute("data-modal-target"));
            if (m) m.classList.add("open");
        });
    });
    document.querySelectorAll("[data-modal-close]").forEach(function (btn) {
        btn.addEventListener("click", function () {
            const m = btn.closest(".modal-overlay");
            if (m) m.classList.remove("open");
        });
    });
    document.querySelectorAll(".modal-overlay").forEach(function (ov) {
        ov.addEventListener("click", function (e) {
            if (e.target === ov) ov.classList.remove("open");
        });
    });
    // Expose helper untuk modal edit yang mengisi field via JS
    window.openModal = function (id) {
        const m = document.getElementById(id);
        if (m) m.classList.add("open");
    };

    /* 3. TOAST (dari flash message) -------------------------- */
    let stack = document.querySelector(".toast-stack");
    if (!stack) {
        stack = document.createElement("div");
        stack.className = "toast-stack";
        document.body.appendChild(stack);
    }
    window.showToast = function (type, message) {
        const t = document.createElement("div");
        t.className = "toast " + (type || "info");
        t.textContent = message;
        stack.appendChild(t);
        setTimeout(function () {
            t.style.opacity = "0";
            t.style.transform = "translateX(40px)";
            setTimeout(function () { t.remove(); }, 350);
        }, 3000);
    };
    // Ubah flash-toast (render PHP) menjadi toast
    document.querySelectorAll(".flash-toast").forEach(function (f) {
        window.showToast(f.getAttribute("data-type") || "info", f.textContent);
        f.remove();
    });

    /* 4. KONFIRMASI AKSI ------------------------------------- */
    document.querySelectorAll("[data-confirm]").forEach(function (el) {
        el.addEventListener("click", function (e) {
            if (!window.confirm(el.getAttribute("data-confirm"))) {
                e.preventDefault();
            }
        });
    });

    /* 5. DYNAMIC ENTITAS ROWS (client/daftar.php) ------------ */
    const addBtn  = document.getElementById("add-entitas");
    const wrap    = document.getElementById("entitas-wrap");
    if (addBtn && wrap) {
        const template = function (i) {
            return '' +
            '<div class="entitas-row">' +
              '<span class="remove-row" title="Hapus baris">✕</span>' +
              '<div class="form-col"><label class="form-label">Nama Santri</label>' +
                '<input type="text" name="nama_entitas[]" class="form-input" required></div>' +
              '<div class="form-col"><label class="form-label">Usia</label>' +
                '<input type="number" name="usia[]" class="form-input" min="4" max="25"></div>' +
              '<div class="form-col"><label class="form-label">Jenis Kelamin</label>' +
                '<select name="jenis_kelamin[]" class="form-input"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></div>' +
              '<div class="form-col"><label class="form-label">Hari</label>' +
                '<input type="text" name="jadwal_hari[]" class="form-input" placeholder="Senin, Rabu"></div>' +
              '<div class="form-col"><label class="form-label">Jam</label>' +
                '<input type="time" name="jadwal_jam[]" class="form-input"></div>' +
            '</div>';
        };
        addBtn.addEventListener("click", function () {
            wrap.insertAdjacentHTML("beforeend", template());
        });
        wrap.addEventListener("click", function (e) {
            if (e.target.classList.contains("remove-row")) {
                const rows = wrap.querySelectorAll(".entitas-row");
                if (rows.length > 1) e.target.closest(".entitas-row").remove();
                else window.showToast("warning", "Minimal satu santri harus didaftarkan.");
            }
        });
    }

})();
