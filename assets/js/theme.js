(function () {
  "use strict";

  const header = document.querySelector("#site-header");
  const toggle = document.querySelector("#mobile-toggle");
  const nav = document.querySelector("#main-nav");
  const isHomePage = header?.classList.contains("hero-mode") ?? false;

  const updateHeader = () => {
    if (!header) return;
    if (isHomePage) header.classList.toggle("scrolled", window.scrollY > 24);
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

  document.querySelectorAll("[data-beautycore-booking-form]").forEach((form) => {
    const branch = form.querySelector('[name="branch_id"]');
    const service = form.querySelector('[name="service_id"]');
    if (!branch || !service) return;

    const refreshServices = () => {
      const branchId = branch.value;
      [...service.options].forEach((option) => {
        if (!option.value || option.value === "0") return;
        const branchIds = (option.dataset.branches || "").split(",").filter(Boolean);
        const unavailable = !branchId || (branchIds.length > 0 && !branchIds.includes(branchId));
        option.disabled = unavailable;
        option.hidden = unavailable;
      });
      if (service.selectedOptions[0]?.disabled) service.value = "0";
    };

    branch.addEventListener("change", refreshServices);
    refreshServices();
  });

  if ("IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      (entries) =>
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("visible");
            observer.unobserve(entry.target);
          }
        }),
      { threshold: 0.12 },
    );
    document.querySelectorAll(".fade-in").forEach((item) => observer.observe(item));
  } else {
    document.querySelectorAll(".fade-in").forEach((item) => item.classList.add("visible"));
  }

  const slider = document.querySelector("#service-slider");
  const tabs = [...document.querySelectorAll("[data-service-tab]")];
  const panels = [...document.querySelectorAll("[data-service-panel]")];
  const setActiveTab = (id) =>
    tabs.forEach((tab) => {
      const active = tab.dataset.serviceTab === id;
      tab.classList.toggle("active", active);
      tab.setAttribute("aria-selected", String(active));
    });

  tabs.forEach((tab) =>
    tab.addEventListener("click", () => {
      const panel = document.getElementById(tab.dataset.serviceTab || "");
      if (panel && slider) {
        slider.scrollTo({ left: panel.offsetLeft, behavior: "smooth" });
        setActiveTab(panel.id);
      }
    }),
  );

  let scrollTimer;
  slider?.addEventListener(
    "scroll",
    () => {
      window.clearTimeout(scrollTimer);
      scrollTimer = window.setTimeout(() => {
        if (!panels.length) return;
        const closest = panels.reduce(
          (selected, panel) =>
            Math.abs(panel.offsetLeft - slider.scrollLeft) < Math.abs(selected.offsetLeft - slider.scrollLeft)
              ? panel
              : selected,
          panels[0],
        );
        if (closest) setActiveTab(closest.dataset.servicePanel || "");
      }, 80);
    },
    { passive: true },
  );

  const storageKey = "beautycore-cookie-consent";
  const banner = document.querySelector("#cookie-banner");
  if (!window.localStorage.getItem(storageKey)) banner?.removeAttribute("hidden");
  banner?.querySelectorAll("[data-cookie-choice]").forEach((button) =>
    button.addEventListener("click", () => {
      window.localStorage.setItem(storageKey, button.dataset.cookieChoice || "necessary");
      banner.hidden = true;
    }),
  );

  document.addEventListener("click", (event) => {
    const link = event.target instanceof Element ? event.target.closest("[data-track]") : null;
    const eventName = link?.dataset.track;
    if (eventName && typeof window.gtag === "function") {
      window.gtag("event", eventName, { link_url: link.href });
    }
  });

  const aiContact = document.querySelector("#ai-contact");
  const trigger = aiContact?.querySelector(".ai-contact__trigger");
  const popup = aiContact?.querySelector(".ai-contact__popup");
  const closeButton = aiContact?.querySelector(".ai-contact__close");
  const form = aiContact?.querySelector(".ai-contact__form");
  const input = aiContact?.querySelector("#ai-contact-input");
  const messagesElement = aiContact?.querySelector(".ai-contact__messages");
  const sendButton = aiContact?.querySelector(".ai-contact__send");
  const quickPromptButtons = [...(aiContact?.querySelectorAll("[data-ai-prompt]") || [])];
  const messages = [];
  const imagePattern = /\/images\/[^\s]+?\.(?:avif|gif|jpe?g|png|webp)(?:\?[^\s]*)?/gi;
  const contactPattern = /https:\/\/booking\.easysalon\.vn\/beautycore|281\/31\/11 Lê Văn Sỹ, Phường Tân Sơn Hòa, TP\. Hồ Chí Minh|0387\s?972\s?769|Zalo/gi;
  const responseContentPattern = new RegExp(`${imagePattern.source}|${contactPattern.source}`, "gi");

  const appendAssistantContent = (element, text) => {
    let lastIndex = 0;
    for (const match of text.matchAll(responseContentPattern)) {
      const matchIndex = match.index || 0;
      element.append(document.createTextNode(text.slice(lastIndex, matchIndex)));
      const label = match[0];
      if (imagePattern.test(label)) {
        imagePattern.lastIndex = 0;
        const imageLink = document.createElement("a");
        imageLink.className = "ai-contact__image-link";
        imageLink.href = label;
        imageLink.target = "_blank";
        imageLink.rel = "noopener noreferrer";
        const image = document.createElement("img");
        image.className = "ai-contact__image";
        image.src = label;
        image.alt = "Hình ảnh do Beauty Core chia sẻ";
        image.loading = "lazy";
        imageLink.append(image);
        element.append(imageLink);
      } else {
        const link = document.createElement("a");
        link.className = "ai-contact__link";
        link.textContent = label;
        if (label === aiContact?.dataset.bookingUrl) {
          link.href = label;
          link.target = "_blank";
          link.rel = "noopener noreferrer";
        } else if (label.toLowerCase() === "zalo") {
          link.href = aiContact?.dataset.zaloUrl || "#";
          link.target = "_blank";
          link.rel = "noopener noreferrer";
        } else if (label.replace(/\s/g, "") === "0387972769") {
          link.href = `tel:${aiContact?.dataset.phone || ""}`;
        } else {
          link.href = aiContact?.dataset.mapUrl || "#";
          link.target = "_blank";
          link.rel = "noopener noreferrer";
        }
        element.append(link);
      }
      lastIndex = matchIndex + label.length;
    }
    element.append(document.createTextNode(text.slice(lastIndex)));
  };

  const formatAssistantResponse = (text) =>
    text
      .replace(/\*\*(.*?)\*\*/g, "$1")
      .replace(/__(.*?)__/g, "$1")
      .replace(/^\s*[-*+]\s+/gm, "• ")
      .replace(/[*#_`]/g, "")
      .replace(/\n{3,}/g, "\n\n")
      .trim();

  const addMessage = (role, text) => {
    const message = document.createElement("p");
    message.className = `ai-contact__message ai-contact__message--${role}`;
    if (role === "assistant") appendAssistantContent(message, text);
    else message.textContent = text;
    messagesElement?.append(message);
    if (messagesElement) messagesElement.scrollTo({ top: messagesElement.scrollHeight, behavior: "smooth" });
  };

  const closePopup = () => {
    if (!popup || !trigger) return;
    popup.hidden = true;
    trigger.setAttribute("aria-expanded", "false");
  };

  trigger?.addEventListener("click", () => {
    if (!popup) return;
    const isOpen = popup.hidden;
    popup.hidden = !isOpen;
    trigger.setAttribute("aria-expanded", String(isOpen));
  });
  closeButton?.addEventListener("click", closePopup);

  const sendMessage = async (message) => {
    const text = message.trim();
    if (!text || !input || !sendButton || !window.BEAUTYCORE_CONFIG) return;
    messages.push({ role: "user", text });
    addMessage("user", text);
    if (input.value.trim() === text) input.value = "";
    input.disabled = true;
    sendButton.disabled = true;
    sendButton.textContent = "...";
    quickPromptButtons.forEach((button) => (button.disabled = true));

    try {
      const body = new URLSearchParams({
        action: "beautycore_chat",
        nonce: window.BEAUTYCORE_CONFIG.nonce,
        messages: JSON.stringify(messages),
      });
      const result = await fetch(window.BEAUTYCORE_CONFIG.ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body,
      });
      const data = await result.json();
      if (!result.ok || !data.text) throw new Error(data.error || "AI error");
      const responseText = formatAssistantResponse(data.text);
      messages.push({ role: "assistant", text: responseText });
      addMessage("assistant", responseText);
    } catch {
      addMessage("assistant", "Beauty Core chưa thể phản hồi lúc này. Bạn vui lòng thử lại hoặc nhắn Zalo nhé.");
    } finally {
      input.disabled = false;
      sendButton.disabled = false;
      sendButton.textContent = "Gửi";
      quickPromptButtons.forEach((button) => (button.disabled = false));
      input.focus();
    }
  };

  form?.addEventListener("submit", (event) => {
    event.preventDefault();
    void sendMessage(input?.value || "");
  });
  quickPromptButtons.forEach((button) => button.addEventListener("click", () => void sendMessage(button.dataset.aiPrompt || "")));
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") closePopup();
  });
})();
