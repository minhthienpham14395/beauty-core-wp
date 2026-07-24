const slider = document.querySelector<HTMLElement>("#service-slider");
const tabs = [...document.querySelectorAll<HTMLButtonElement>("[data-service-tab]")];
const panels = [...document.querySelectorAll<HTMLElement>("[data-service-panel]")];

const setActiveTab = (id: string) =>
  tabs.forEach((tab) => {
    const active = tab.dataset.serviceTab === id;
    tab.classList.toggle("active", active);
    tab.setAttribute("aria-selected", String(active));
  });

tabs.forEach((tab) =>
  tab.addEventListener("click", () => {
    const panel = document.getElementById(tab.dataset.serviceTab ?? "");

    if (panel && slider) {
      slider.scrollTo({ left: panel.offsetLeft, behavior: "smooth" });
      setActiveTab(panel.id);
    }
  }),
);

let scrollTimer: number | undefined;

slider?.addEventListener(
  "scroll",
  () => {
    window.clearTimeout(scrollTimer);
    scrollTimer = window.setTimeout(() => {
      const closest = panels.reduce(
        (selected, panel) =>
          Math.abs(panel.offsetLeft - slider.scrollLeft) <
          Math.abs(selected.offsetLeft - slider.scrollLeft)
            ? panel
            : selected,
        panels[0],
      );

      if (closest) setActiveTab(closest.dataset.servicePanel ?? "");
    }, 80);
  },
  { passive: true },
);
