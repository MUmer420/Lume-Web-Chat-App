document.addEventListener("DOMContentLoaded", () => {
  const heroText = document.getElementById("hero-text");
  const heroButtons = document.getElementById("hero-buttons");
  const heroGraphic = document.getElementById("hero-graphic");
  const features = document.querySelectorAll(".feature-item");

  // 1. Reveal Left Text layout
  setTimeout(() => {
    if (heroText) heroText.classList.add("active");
  }, 150);

  // 2. Cascade down to show buttons
  setTimeout(() => {
    if (heroButtons) heroButtons.classList.add("active");
  }, 450);

  // 3. Fade in the right side graphic elements smoothly
  setTimeout(() => {
    if (heroGraphic) heroGraphic.classList.add("active");
  }, 650);

  // 4. Stagger load the bottom features layout cards
  features.forEach((card, index) => {
    setTimeout(() => {
      card.classList.add("active");
    }, 950 + (index * 200));
  });
});