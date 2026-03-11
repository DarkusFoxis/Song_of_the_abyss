$(document).ready(function() {
    $('#trackSearch').on('keyup', function() {
        let searchTerm = $(this).val().toLowerCase();

        $('.track-card').each(function() {
            let trackTitle = $(this).find('.track-title').text().toLowerCase();
            let trackAuthor = $(this).find('.track-artist').text().toLowerCase();
            let trackUploader = $(this).find('.track-uploader').text().toLowerCase();
            if (trackTitle.includes(searchTerm) || trackAuthor.includes(searchTerm) || trackUploader.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});