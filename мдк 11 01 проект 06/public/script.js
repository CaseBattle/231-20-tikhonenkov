// Динамический год
const yearSpan = document.getElementById('current-year');
if (yearSpan) yearSpan.textContent = new Date().getFullYear();

// Плавный скролл по якорям
document.querySelectorAll('a[href^="#"]').forEach((link) => {
  link.addEventListener('click', (e) => {
    const targetId = link.getAttribute('href');
    if (!targetId || targetId === '#') return;

    const targetEl = document.querySelector(targetId);
    if (!targetEl) return;

    e.preventDefault();
    const elementPosition = targetEl.getBoundingClientRect().top + window.scrollY;

    window.scrollTo({
      top: elementPosition - 12,
      behavior: 'smooth',
    });

    closeMobileMenu();
  });
});

// Бургер-меню
const burgerBtn = document.querySelector('.nav__burger');
const navList = document.querySelector('.nav__list');

function closeMobileMenu() {
  if (!burgerBtn || !navList) return;
  burgerBtn.classList.remove('nav__burger--active');
  navList.classList.remove('nav__list--open');
  burgerBtn.setAttribute('aria-expanded', 'false');
}

if (burgerBtn && navList) {
  burgerBtn.addEventListener('click', () => {
    const isOpen = burgerBtn.classList.toggle('nav__burger--active');
    navList.classList.toggle('nav__list--open', isOpen);
    burgerBtn.setAttribute('aria-expanded', String(isOpen));
  });
}

// Изменение стиля хедера при скролле
const header = document.querySelector('.header');
window.addEventListener('scroll', () => {
  if (!header) return;
  if (window.scrollY > 10) header.classList.add('header--scrolled');
  else header.classList.remove('header--scrolled');
});

// Слайдер
const sliderTrack = document.querySelector('.slider__track');
const slides = Array.from(document.querySelectorAll('.slide'));
const prevBtn = document.querySelector('.slider__control--prev');
const nextBtn = document.querySelector('.slider__control--next');
const dots = Array.from(document.querySelectorAll('.slider__dot'));
const pauseBtn = document.querySelector('.slider__pause');

let currentIndex = 0;
let isPaused = false;
let autoSlideInterval = null;

function updateSlider(index) {
  if (!sliderTrack || slides.length === 0) return;
  currentIndex = (index + slides.length) % slides.length;
  sliderTrack.style.transform = `translateX(${-currentIndex * 100}%)`;
  slides.forEach((slide, i) => slide.classList.toggle('slide--active', i === currentIndex));
  dots.forEach((dot, i) => dot.classList.toggle('slider__dot--active', i === currentIndex));
}

function nextSlide() {
  updateSlider(currentIndex + 1);
}

function prevSlide() {
  updateSlider(currentIndex - 1);
}

if (nextBtn) nextBtn.addEventListener('click', nextSlide);
if (prevBtn) prevBtn.addEventListener('click', prevSlide);

dots.forEach((dot) =>
  dot.addEventListener('click', () => {
    const idx = Number(dot.dataset.slide || 0);
    updateSlider(idx);
  })
);

// Автопрокрутка
function startAutoSlide() {
  if (autoSlideInterval || isPaused) return;
  autoSlideInterval = setInterval(nextSlide, 5000);
}

function stopAutoSlide() {
  if (!autoSlideInterval) return;
  clearInterval(autoSlideInterval);
  autoSlideInterval = null;
}

if (pauseBtn) {
  pauseBtn.addEventListener('click', () => {
    isPaused = !isPaused;
    pauseBtn.textContent = isPaused ? 'Старт' : 'Пауза';
    pauseBtn.setAttribute('aria-pressed', String(isPaused));
    if (isPaused) stopAutoSlide();
    else startAutoSlide();
  });
}

// Свайпы
let startX = 0;
let touching = false;
if (sliderTrack) {
  sliderTrack.addEventListener('touchstart', (e) => {
    if (e.touches.length !== 1) return;
    touching = true;
    startX = e.touches[0].clientX;
  });

  sliderTrack.addEventListener('touchmove', (e) => {
    if (!touching) return;
    if (Math.abs(e.touches[0].clientX - startX) > 10) e.preventDefault();
  });

  sliderTrack.addEventListener('touchend', (e) => {
    if (!touching) return;
    const diff = e.changedTouches[0].clientX - startX;
    if (Math.abs(diff) > 40) {
      if (diff < 0) nextSlide();
      else prevSlide();
    }
    touching = false;
  });
}

window.addEventListener('load', () => {
  updateSlider(0);
  startAutoSlide();
});
