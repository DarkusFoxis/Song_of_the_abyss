$(document).ready(function() {
    $('.settings-nav a').click(function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        $('.settings-nav a').removeClass('active');
        $(this).addClass('active');
        
        $('.settings-content > section').hide();
        $(target).show();
    });

    $('.settings-nav a:first').click();

    $('#avatarUpload').change(function(e) {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#avatarPreview').attr('src', e.target.result).show();
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    $('#showPass').change(function() {
        var type = this.checked ? 'text' : 'password';
        $('input[type="password"]').attr('type', type);
    });

    $('form').submit(function() {
        if($(this).find('input[name="new_password"]').length) {
            var newPass = $('input[name="new_password"]').val();
            var confirmPass = $('input[name="new_password_confirm"]').val();
            if(newPass !== confirmPass) {
                alert('Пароли не совпадают!');
                return false;
            }
        }
        return true;
    });
    $('#recipientSelect').change(function() {
        $('#recipientId').val($(this).val());
    });
});