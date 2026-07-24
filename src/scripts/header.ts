const header = document.querySelector<HTMLElement>("#site-header");
const toggle = document.querySelector<HTMLButtonElement>("#mobile-toggle");
const nav = document.querySelector<HTMLElement>("#main-nav");

const isHomePage = header?.classList.contains("hero-mode") ?? false;

const updateHeader = () => {
  if (isHomePage) {
    header?.classList.toggle("scrolled", window.scrollY > 24);
  }
};

updateHeader();
window.addEventListener("scroll", updateHeader, { passive: true });

toggle?.addEventListener("click", () => {
  const isOpen = nav?.classList.toggle("open") ?? false;

  toggle.classList.toggle("open", isOpen);
  toggle.setAttribute("aria-expanded", String(isOpen));
  document.body.classList.toggle("menu-open", isOpen);
});

nav?.querySelectorAll("a").forEach((link) =>
  link.addEventListener("click", () => {
    nav.classList.remove("open");
    toggle?.classList.remove("open");
    toggle?.setAttribute("aria-expanded", "false");
    document.body.classList.remove("menu-open");
  }),
);
