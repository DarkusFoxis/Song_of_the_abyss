function sellStikers(stikerId) {
    $.ajax({
    url: '../items_core',
    type: 'POST',
    data: {stikerId: stikerId, action: "stiker_delete"},
    success: function(response) {
        $('#result').html(response);
        loadStiker();
    },
    error: function(xhr, status, error) {
        alert('Произошла ошибка при продаже стикера.');
        console.log(error);
    }
    });
}