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
  const authArea = document.getElementById('authArea');

  const sliderTrack = document.querySelector('.slider__track');
  const slides = document.querySelectorAll('.slide');
  const dots = document.querySelectorAll('.slider__dot');
  const prevBtn = document.querySelector('.slider__control--prev');
  const nextBtn = document.querySelector('.slider__control--next');
  const pauseBtn = document.querySelector('.slider__pause');

  let currentSlide = 0;
  let sliderInterval = null;
  let isPaused = false;
  let currentPropertyId = null;

  if (currentYear) {
    currentYear.textContent = new Date().getFullYear();
  }

  async function renderAuthState() {
    if (!authArea) return;

    try {
      const res = await fetch('/me');
      const data = await res.json();

      if (data.userEmail) {
        if (data.isAdmin) {
          authArea.innerHTML = `
            <span class="feature-chip">${data.userEmail}</span>
            <a href="admin.html" class="pill-button pill-button--ghost">Админ-панель</a>
            <button class="pill-button pill-button--ghost" id="logoutHeaderBtn" type="button">Выйти</button>
          `;
        } else {
          authArea.innerHTML = `
            <span class="feature-chip">${data.userEmail}</span>
            <button class="pill-button pill-button--ghost" id="logoutHeaderBtn" type="button">Выйти</button>
          `;
        }

        const logoutBtn = document.getElementById('logoutHeaderBtn');
        if (logoutBtn) {
          logoutBtn.addEventListener('click', async () => {
            await fetch('/logout', { method: 'POST' });
            window.location.reload();
          });
        }
      } else {
        authArea.innerHTML = `
          <a href="register.html" class="pill-button">Регистрация</a>
          <a href="login.html" class="pill-button">Вход</a>
        `;
      }
    } catch (error) {
      console.error('Ошибка получения состояния пользователя:', error);
    }
  }

  renderAuthState();

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
    currentPropertyId = null;
  }

  window.openModalWithObject = function (title, propertyId) {
    const commentField = document.querySelector('#requestForm textarea[name="comment"]');
    if (commentField) {
      commentField.value = `Интересует объект: ${title}`;
    }
    currentPropertyId = propertyId || null;
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

  if (requestForm) {
    requestForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const formData = new FormData(requestForm);
      const payload = {
        name: formData.get('name') || '',
        phone: formData.get('phone') || '',
        email: formData.get('email') || '',
        comment: formData.get('comment') || '',
        propertyId: currentPropertyId
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
          alert(data.message || 'Не удалось отправить заявку');
        }
      } catch (error) {
        console.error('Ошибка отправки формы:', error);
        alert('Ошибка при отправке заявки');
      }
    });
  }

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

  if (prevBtn) prevBtn.addEventListener('click', prevSlide);
  if (nextBtn) nextBtn.addEventListener('click', nextSlide);

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

  fetch('/properties')
    .then((res) => res.json())
    .then((data) => {
      const list = document.getElementById('propertyList');
      if (!list) return;

      list.innerHTML = '';

      data.forEach((item) => {
        const isSale = item.type === 'sale' || Number(item.price) > 1000000;
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
            window.openModalWithObject(item.title, item.id);
          });
        }

        list.appendChild(card);
      });

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

  const contactForm = document.querySelector('.contact-form');
  if (contactForm) {
    contactForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const nameInput = contactForm.querySelector('input[type="text"]');
      const emailInput = contactForm.querySelector('input[type="email"]');
      const messageInput = contactForm.querySelector('textarea');

      const payload = {
        name: nameInput?.value || '',
        email: emailInput?.value || '',
        message: messageInput?.value || ''
      };

      try {
        const response = await fetch('/feedback', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (data.success) {
          alert('Сообщение отправлено');
          contactForm.reset();
        } else {
          alert(data.message || 'Ошибка отправки сообщения');
        }
      } catch (error) {
        console.error(error);
        alert('Ошибка подключения к серверу');
      }
    });
  }
});