$(document).ready(function() {
    $('#trackSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase().trim();

        $('.track-class-data').each(function() {
            const $wrapper = $(this);
            const $card = $wrapper.find('.track-card');

            const title = ($card.data('search-title') || '').toLowerCase();
            const artist = ($card.data('search-artist') || '').toLowerCase();
            const uploader = ($card.data('search-uploader') || '').toLowerCase();

            const isMatch = title.includes(searchTerm) || artist.includes(searchTerm) || uploader.includes(searchTerm);

            if (searchTerm === '' || isMatch) {
                $wrapper.stop(true, true).fadeIn(250).css('display', '');
            } else {
                $wrapper.stop(true, true).fadeOut(200);
            }
        });
    });
});