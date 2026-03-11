$('.refreshTracksBtn').on('click', function() {
    $.ajax({
        url: 'audio_libraly',
        type: 'GET',
        data: { refreshTracks: true },
        success: function(data) {
            $('.track_class_data').remove();
            $('#tracks-container').append(data);
            $('#trackSearch').trigger('keyup');
        },
        error: function(xhr, status, error) {
            console.error('Ошибка при обновлении треков:', error);
            alert('Ошибка при обновлении треков. Пожалуйста, попробуйте позже.');
        }
    });
});