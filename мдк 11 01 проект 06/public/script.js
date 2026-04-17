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
            <a href="my-requests.html" class="pill-button pill-button--ghost">Мои заявки</a>
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
    const commentField = document.querySelector('#requestForm textarea');
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

      const inputs = requestForm.querySelectorAll('input, textarea');

      const body = {
        name: inputs[0].value.trim(),
        phone: inputs[1].value.trim(),
        email: inputs[2].value.trim(),
        comment: inputs[3].value.trim(),
        propertyId: currentPropertyId
      };

      try {
        const res = await fetch('/request', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(body)
        });

        const data = await res.json();

        alert(data.message || 'Заявка отправлена');

        if (data.success) {
          requestForm.reset();
          closeModal();
        }
      } catch (error) {
        alert('Ошибка отправки заявки');
      }
    });
  }

  async function loadProperties() {
    const propertyList = document.getElementById('propertyList');
    if (!propertyList) return;

    try {
      const res = await fetch('/properties');
      const items = await res.json();

      propertyList.innerHTML = '';

      items.forEach((item) => {
        const card = document.createElement('div');
        card.className = 'property-card';

        const isSale = item.type === 'sale';

        card.innerHTML = `
          <div class="property-badge">
            ${isSale ? 'Продажа' : 'Аренда'}
          </div>

          <h3>${item.title}</h3>
          <p>${item.address}</p>
          <strong>
            ${Number(item.price).toLocaleString('ru-RU')} ₽
            ${isSale ? '' : ' / мес'}
          </strong>

          <button class="pill-button property-btn">
            Подать заявку
          </button>
        `;

        const btn = card.querySelector('.property-btn');
        btn.addEventListener('click', () => {
          openModalWithObject(item.title, item.id);
        });

        propertyList.appendChild(card);
      });
    } catch (error) {
      console.error('Ошибка загрузки объявлений');
    }
  }

  loadProperties();

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      const value = searchInput.value.toLowerCase();
      const cards = document.querySelectorAll('.property-card');

      cards.forEach((card) => {
        const text = card.innerText.toLowerCase();
        card.style.display = text.includes(value) ? '' : 'none';
      });
    });
  }

  function showSlide(index) {
    if (!slides.length) return;

    if (index < 0) index = slides.length - 1;
    if (index >= slides.length) index = 0;

    currentSlide = index;

    if (sliderTrack) {
      sliderTrack.style.transform = `translateX(-${index * 100}%)`;
    }

    slides.forEach((slide, i) => {
      slide.classList.toggle('slide--active', i === index);
    });

    dots.forEach((dot, i) => {
      dot.classList.toggle('slider__dot--active', i === index);
    });
  }

  function nextSlide() {
    showSlide(currentSlide + 1);
  }

  function startSlider() {
    stopSlider();
    sliderInterval = setInterval(nextSlide, 5000);
  }

  function stopSlider() {
    if (sliderInterval) {
      clearInterval(sliderInterval);
      sliderInterval = null;
    }
  }

  if (slides.length) {
    showSlide(0);
    startSlider();
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      showSlide(currentSlide - 1);
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      showSlide(currentSlide + 1);
    });
  }

  dots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
      showSlide(index);
    });
  });

  if (pauseBtn) {
    pauseBtn.addEventListener('click', () => {
      isPaused = !isPaused;

      if (isPaused) {
        stopSlider();
        pauseBtn.textContent = 'Продолжить';
      } else {
        startSlider();
        pauseBtn.textContent = 'Пауза';
      }
    });
  }

  const contactForm = document.querySelector('.contact-form');

  if (contactForm) {
    contactForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const name = contactForm.querySelector('input[type="text"]').value.trim();
      const email = contactForm.querySelector('input[type="email"]').value.trim();
      const message = contactForm.querySelector('textarea').value.trim();

      try {
        const res = await fetch('/feedback', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            name,
            email,
            message
          })
        });

        const data = await res.json();

        alert(data.message || 'Сообщение отправлено');

        if (data.success) {
          contactForm.reset();
        }
      } catch (error) {
        alert('Ошибка подключения к серверу');
      }
    });
  }
});