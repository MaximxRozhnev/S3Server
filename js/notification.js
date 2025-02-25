// Функция для показа уведомлений
function showNotification(type, message) {
    const container = document.getElementById('notificationContainer');

    // Создаем элемент уведомления
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        ${getIcon(type)}
        <span>${message}</span>
    `;

    // Добавляем уведомление в контейнер
    container.appendChild(notification);

    // Анимация появления
    setTimeout(() => {
        notification.style.opacity = '1';
    }, 10);

    // Удаляем уведомление через 5 секунд
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.addEventListener('transitionend', () => {
            notification.remove();
        });
    }, 5000);
}

// Функция для получения иконки по типу уведомления
function getIcon(type) {
    switch (type) {
        case 'success':
            return `<i class="fas fa-check-circle"></i>`;
        case 'warning':
            return `<i class="fas fa-exclamation-triangle"></i>`;
        case 'error':
            return `<i class="fas fa-times-circle"></i>`;
        default:
            return '';
    }
}

function showNotificationWithDelay(type, message) {
    // Сохраняем сообщение в localStorage
    localStorage.setItem('notification', JSON.stringify({ type, message }));

}

window.addEventListener('load', () => {
    // Проверяем, есть ли уведомление в localStorage
    const notification = localStorage.getItem('notification');

    if (notification) {
        // Преобразуем данные обратно в объект
        const { type, message } = JSON.parse(notification);

        // Показываем уведомление
        showNotification(type, message);

        // Удаляем уведомление из localStorage после отображения
        localStorage.removeItem('notification');
    }
});
