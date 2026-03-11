$('form').on('submit', function(e) {
    e.preventDefault();
    const form = $(this);
    const result = form.find('.result');
    result.html('<span class="processing">Обработка...</span>');

    const formData = {
        action: form.data('action'),
        count: form.find('input[name="count"]').val() || 1
    };

    $.ajax({
        url: './pay_core',
        type: 'POST',
        data: formData,
        success: function(response) {
            result.html(response);
            setTimeout(update_price, 1000);
        },
        error: function(xhr) {
            result.html('Ошибка: ' + xhr.statusText);
        }
    });
});