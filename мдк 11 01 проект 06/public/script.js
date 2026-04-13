document.addEventListener('DOMContentLoaded', () => {
  const navBurger = document.querySelector('.nav__burger');
  const navList = document.querySelector('.nav__list');

  const openRequestModalBtn = document.getElementById('openRequestModal');
  const requestModal = document.getElementById('requestModal');
  const closeRequestModal = document.getElementById('closeRequestModal');
  const closeRequestModalBtn = document.getElementById('closeRequestModalBtn');
  const requestForm = document.getElementById('requestForm');

  const currentYear = document.getElementById('current-year');
  const searchInput = document.querySelector('.landing__search-input');

  const sliderTrack = document.querySelector('.slider__track');
  const slides = document.querySelectorAll('.slide');
  const dots = document.querySelectorAll('.slider__dot');
  const prevBtn = document.querySelector('.slider__control--prev');
  const nextBtn = document.querySelector('.slider__control--next');
  const pauseBtn = document.querySelector('.slider__pause');

  let currentSlide = 0;
  let sliderInterval = null;
  let isPaused = false;

  if (currentYear) {
    currentYear.textContent = new Date().getFullYear();
  }

  // Бургер-меню
  if (navBurger && navList) {
    navBurger.addEventListener('click', () => {
      navList.classList.toggle('nav__list--open');
      navBurger.classList.toggle('nav__burger--active');
      navBurger.setAttribute(
        'aria-expanded',
        navList.classList.contains('nav__list--open') ? 'true' : 'false'
      );
    });

    document.querySelectorAll('.nav__link').forEach((link) => {
      link.addEventListener('click', () => {
        navList.classList.remove('nav__list--open');
        navBurger.classList.remove('nav__burger--active');
        navBurger.setAttribute('aria-expanded', 'false');
      });
    });
  }

  // Модальное окно
  function openModal() {
    if (!requestModal) return;
    requestModal.classList.add('modal--open');
    requestModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
  }

  function closeModal() {
    if (!requestModal) return;
    requestModal.classList.remove('modal--open');
    requestModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
  }

  window.openModalWithObject = function (title) {
    const commentField = document.querySelector('#requestForm textarea[name="comment"]');
    if (commentField) {
      commentField.value = `Интересует объект: ${title}`;
    }
    openModal();
  };

  if (openRequestModalBtn) {
    openRequestModalBtn.addEventListener('click', openModal);
  }

  if (closeRequestModal) {
    closeRequestModal.addEventListener('click', closeModal);
  }

  if (closeRequestModalBtn) {
    closeRequestModalBtn.addEventListener('click', closeModal);
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeModal();
    }
  });

  // Отправка формы заявки
  if (requestForm) {
    requestForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const formData = new FormData(requestForm);
      const payload = {
        name: formData.get('name') || '',
        phone: formData.get('phone') || '',
        email: formData.get('email') || '',
        comment: formData.get('comment') || ''
      };

      try {
        const response = await fetch('/request', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (data.success) {
          alert('Заявка успешно отправлена');
          requestForm.reset();
          closeModal();
        } else {
          alert('Не удалось отправить заявку');
        }
      } catch (error) {
        console.error('Ошибка отправки формы:', error);
        alert('Ошибка при отправке заявки');
      }
    });
  }

  // Слайдер
  function updateSlider() {
    if (!sliderTrack || !slides.length) return;

    sliderTrack.style.transform = `translateX(-${currentSlide * 100}%)`;

    slides.forEach((slide, index) => {
      slide.classList.toggle('slide--active', index === currentSlide);
    });

    dots.forEach((dot, index) => {
      dot.classList.toggle('slider__dot--active', index === currentSlide);
    });
  }

  function goToSlide(index) {
    if (!slides.length) return;
    currentSlide = (index + slides.length) % slides.length;
    updateSlider();
  }

  function nextSlide() {
    goToSlide(currentSlide + 1);
  }

  function prevSlide() {
    goToSlide(currentSlide - 1);
  }

  function startSlider() {
    if (!slides.length) return;
    stopSlider();
    sliderInterval = setInterval(() => {
      if (!isPaused) {
        nextSlide();
      }
    }, 5000);
  }

  function stopSlider() {
    if (sliderInterval) {
      clearInterval(sliderInterval);
      sliderInterval = null;
    }
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', prevSlide);
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', nextSlide);
  }

  dots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
      goToSlide(index);
    });
  });

  if (pauseBtn) {
    pauseBtn.addEventListener('click', () => {
      isPaused = !isPaused;
      pauseBtn.textContent = isPaused ? 'Продолжить' : 'Пауза';
      pauseBtn.setAttribute('aria-pressed', isPaused ? 'true' : 'false');
    });
  }

  updateSlider();
  startSlider();

  // Загрузка объектов недвижимости
  fetch('/properties')
    .then((res) => res.json())
    .then((data) => {
      const list = document.getElementById('propertyList');
      if (!list) return;

      list.innerHTML = '';

      data.forEach((item) => {
        const isSale = item.price > 1000000;
        const typeLabel = isSale ? 'Продажа' : 'Аренда';

        const card = document.createElement('article');
        card.className = 'property-card';

        card.innerHTML = `
          <div class="property-card__top">
            <span class="property-card__badge">${typeLabel}</span>
          </div>
          <h3>${item.title}</h3>
          <p class="property-card__address">${item.address}</p>
          <p class="property-card__price">
            ${isSale
              ? Number(item.price).toLocaleString('ru-RU') + ' ₽'
              : Number(item.price).toLocaleString('ru-RU') + ' ₽ / мес'}
          </p>
          <button class="pill-button property-card__button" type="button">
            Оставить заявку
          </button>
        `;

        const button = card.querySelector('.property-card__button');
        if (button) {
          button.addEventListener('click', () => {
            window.openModalWithObject(item.title);
          });
        }

        list.appendChild(card);
      });

      // Поиск по объявлениям
      if (searchInput) {
        searchInput.addEventListener('input', function () {
          const value = this.value.toLowerCase();
          const cards = document.querySelectorAll('.property-card');

          cards.forEach((card) => {
            const text = card.innerText.toLowerCase();
            card.style.display = text.includes(value) ? '' : 'none';
          });
        });
      }
    })
    .catch((err) => {
      console.error('Ошибка загрузки объявлений:', err);
    });

  // Форма обратной связи
  const contactForm = document.querySelector('.contact-form');
  if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
      e.preventDefault();
      alert('Сообщение отправлено');
      contactForm.reset();
    });
  }
});