const toggle = document.querySelector(".menu-toggle");
const sidebar = document.querySelector(".sidebar");
function closeMenu() {
  toggle?.setAttribute("aria-expanded", "false");
  toggle?.setAttribute("aria-label", "Buka navigasi");
  sidebar?.classList.remove("is-open");
}
toggle?.addEventListener("click", () => {
  const open = toggle.getAttribute("aria-expanded") !== "true";
  toggle.setAttribute("aria-expanded", String(open));
  toggle.setAttribute("aria-label", open ? "Tutup navigasi" : "Buka navigasi");
  sidebar?.classList.toggle("is-open", open);
});
document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") closeMenu();
});
document.addEventListener("click", (event) => {
  if (!sidebar?.contains(event.target) && !toggle?.contains(event.target))
    closeMenu();
});
document.querySelector(".error-summary")?.focus();
document
  .querySelectorAll(
    ".student-form input:not([type=hidden]), .student-form select, .student-form textarea",
  )
  .forEach((field) => {
    field.addEventListener("blur", () => {
      field.classList.toggle("touched", !field.validity.valid);
    });
  });
