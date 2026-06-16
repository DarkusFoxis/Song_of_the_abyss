function ratePost(postId, vote) {
    const formData = new FormData();
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    formData.append('post_id', postId);
    formData.append('vote', vote);
    if (csrfToken) {
        formData.append('csrf_token', csrfToken);
    }
    
    fetch('./rate_post', {
        method: 'POST',
        headers: csrfToken ? { 'X-CSRF-Token': csrfToken } : {},
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const ratingElement = document.getElementById(`rating-${postId}`);
            if (ratingElement) {
                ratingElement.textContent = data.rating;
            }

            const btnPlus = document.getElementById(`btn-plus-${postId}`);
            const btnMinus = document.getElementById(`btn-minus-${postId}`);

            btnPlus.classList.remove('active', 'voted-plus');
            btnMinus.classList.remove('active', 'voted-minus');
            
            if (data.user_vote === 1) {
                btnPlus.classList.add('active', 'voted-plus');
            } else if (data.user_vote === -1) {
                btnMinus.classList.add('active', 'voted-minus');
            }

            showNotification(`Ваш голос учтён! (вес: ${data.vote_weight})`, 'success');
        } else {
            showNotification(data.error || 'Ошибка при голосовании', 'error');
        }
        loadUserVotes();
    })
    .catch(error => {
        console.error('Ошибка:', error);
        showNotification('Ошибка соединения с сервером', 'error');
    });
}

function loadUserVotes() {
    const ratingContainers = document.querySelectorAll('.rating-container');

    ratingContainers.forEach(container => {
        const btnPlus = container.querySelector('.rating-btn-plus');
        if (!btnPlus) return;

        const postId = btnPlus.id.replace('btn-plus-', '');

        fetch(`./get_user_rating?post_id=${postId}`)
            .then(response => response.json())
            .then(data => {
                if (data.voted) {
                    const btnPlus = document.getElementById(`btn-plus-${postId}`);
                    const btnMinus = document.getElementById(`btn-minus-${postId}`);

                    if (data.vote_value === 1) {
                        btnPlus.classList.add('active', 'voted-plus');
                    } else if (data.vote_value === -1) {
                        btnMinus.classList.add('active', 'voted-minus');
                    }
                }
            })
            .catch(error => console.error('Ошибка загрузки состояния голоса:', error));
    });
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#4CAF50' : '#f44336'};
        color: white;
        border-radius: 5px;
        z-index: 10000;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Система рейтинга загружена');
    loadUserVotes();
});
