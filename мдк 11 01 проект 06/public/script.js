document.addEventListener('DOMContentLoaded', () => {
  const yearSpan = document.getElementById('current-year');
  if (yearSpan) {
    yearSpan.textContent = new Date().getFullYear();
  }

  const burgerBtn = document.querySelector('.nav__burger');
  const navList = document.querySelector('.nav__list');
  const header = document.querySelector('.header');
  const navLinks = document.querySelectorAll('.nav__link[href^="#"]');

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

  navLinks.forEach((link) => {
    link.addEventListener('click', (e) => {
      const targetId = link.getAttribute('href');
      if (!targetId || targetId === '#') return;

      const targetEl = document.querySelector(targetId);
      if (!targetEl) return;

      e.preventDefault();
      const top = targetEl.getBoundingClientRect().top + window.scrollY - 80;
      window.scrollTo({ top, behavior: 'smooth' });
      closeMobileMenu();
    });
  });

  window.addEventListener('scroll', () => {
    if (header) {
      header.classList.toggle('header--scrolled', window.scrollY > 10);
    }

    const sections = document.querySelectorAll('section[id]');
    let currentId = '';

    sections.forEach((section) => {
      const top = section.offsetTop - 120;
      const bottom = top + section.offsetHeight;
      if (window.scrollY >= top && window.scrollY < bottom) {
        currentId = section.id;
      }
    });

    navLinks.forEach((link) => {
      link.classList.toggle('nav__link--active', link.getAttribute('href') === `#${currentId}`);
    });
  });

  const searchInput = document.querySelector('.landing__search-input');
  const searchTargets = [
    ...document.querySelectorAll('.about-card, .info-card, .slide, .contact-panel')
  ];

  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      const value = e.target.value.trim().toLowerCase();

      searchTargets.forEach((item) => item.classList.remove('search-hit'));

      if (!value) return;

      searchTargets.forEach((item) => {
        const text = item.textContent.toLowerCase();
        if (text.includes(value)) {
          item.classList.add('search-hit');
        }
      });
    });
  }

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
    sliderTrack.style.transform = `translateX(-${currentIndex * 100}%)`;

    dots.forEach((dot, i) => {
      dot.classList.toggle('slider__dot--active', i === currentIndex);
    });
  }

  function nextSlide() {
    updateSlider(currentIndex + 1);
  }

  function prevSlide() {
    updateSlider(currentIndex - 1);
  }

  function startAutoSlide() {
    if (autoSlideInterval || isPaused || slides.length < 2) return;
    autoSlideInterval = setInterval(nextSlide, 4000);
  }

  function stopAutoSlide() {
    if (!autoSlideInterval) return;
    clearInterval(autoSlideInterval);
    autoSlideInterval = null;
  }

  if (nextBtn) nextBtn.addEventListener('click', nextSlide);
  if (prevBtn) prevBtn.addEventListener('click', prevSlide);

  dots.forEach((dot) => {
    dot.addEventListener('click', () => {
      const index = Number(dot.dataset.slide || 0);
      updateSlider(index);
    });
  });

  if (pauseBtn) {
    pauseBtn.addEventListener('click', () => {
      isPaused = !isPaused;
      pauseBtn.textContent = isPaused ? 'Продолжить' : 'Пауза';
      pauseBtn.setAttribute('aria-pressed', String(isPaused));

      if (isPaused) stopAutoSlide();
      else startAutoSlide();
    });
  }

  startAutoSlide();

  const forms = document.querySelectorAll('form');
  forms.forEach((form) => {
    form.addEventListener('submit', (e) => {
      if (form.id === 'requestForm') return;
      e.preventDefault();

      const submitButton = form.querySelector('button[type="submit"]');
      const originalText = submitButton ? submitButton.textContent : '';

      if (submitButton) {
        submitButton.textContent = 'Отправлено';
        submitButton.disabled = true;
      }

      setTimeout(() => {
        alert('Данные успешно отправлены. Демонстрационный режим.');
        form.reset();

        if (submitButton) {
          submitButton.textContent = originalText;
          submitButton.disabled = false;
        }
      }, 500);
    });
  });

  const openRequestModalBtn = document.getElementById('openRequestModal');
  const requestModal = document.getElementById('requestModal');
  const closeRequestModal = document.getElementById('closeRequestModal');
  const closeRequestModalBtn = document.getElementById('closeRequestModalBtn');
  const requestForm = document.getElementById('requestForm');

  function closeModal() {
    if (!requestModal) return;
    requestModal.classList.remove('modal--open');
    requestModal.setAttribute('aria-hidden', 'true');
  }

  if (openRequestModalBtn && requestModal) {
    openRequestModalBtn.addEventListener('click', () => {
      requestModal.classList.add('modal--open');
      requestModal.setAttribute('aria-hidden', 'false');
    });
  }

  if (closeRequestModal) {
    closeRequestModal.addEventListener('click', closeModal);
  }

  if (closeRequestModalBtn) {
    closeRequestModalBtn.addEventListener('click', closeModal);
  }

  if (requestForm) {
    requestForm.addEventListener('submit', (e) => {
      e.preventDefault();
      alert('Заявка успешно отправлена. Демонстрационный режим.');
      requestForm.reset();
      closeModal();
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeModal();
    }
  });
});