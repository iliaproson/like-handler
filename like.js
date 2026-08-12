document.addEventListener('DOMContentLoaded', () => {
    // Ищем все кнопки лайков на странице (их может быть несколько в списке новостей)
    const likeButtons = document.querySelectorAll('.js-like-btn');

    likeButtons.forEach(button => {
        button.addEventListener('click', async (event) => {
            event.preventDefault();

            // Защита от мультиклика: если запрос уже в процессе, игнорируем клик
            if (button.classList.contains('is-loading')) return;

            const elementId = button.dataset.id;
            
            // Визуально показываем, что пошел процесс
            button.classList.add('is-loading');

            try {
                const formData = new FormData();
                formData.append('id', elementId);

                // Отправляем AJAX запрос
                const response = await fetch('/ajax/like.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest' // Обязательный заголовок для проверки isAjaxRequest() в Битрикс
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.status === 'success') {
                    // Обновляем DOM элементы
                    const countSpan = button.querySelector('.like-btn__count');
                    const iconSpan = button.querySelector('.like-btn__icon');

                    // Анимированно или просто заменяем текст
                    countSpan.textContent = result.likes_count;

                    if (result.is_liked) {
                        button.classList.add('is-active');
                        iconSpan.textContent = '❤️';
                    } else {
                        button.classList.remove('is-active');
                        iconSpan.textContent = '🤍';
                    }
                } else {
                    // Обработка логических ошибок (например, элемент не найден)
                    console.error('Ошибка логики сервера:', result.message);
                    alert(result.message || 'Произошла ошибка при обработке лайка.');
                }

            } catch (error) {
                // Обработка сетевых ошибок (интернет пропал, 500 ошибка сервера)
                console.error('Сетевая ошибка:', error);
                alert('Не удалось отправить запрос. Проверьте соединение с интернетом.');
            } finally {
                // В любом случае снимаем блокировку с кнопки
                button.classList.remove('is-loading');
            }
        });
    });
});