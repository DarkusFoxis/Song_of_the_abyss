$(document).ready(function() {
    $('.settings-nav a').click(function(e) {
        e.preventDefault();
        var target = $(this).attr('href');

        $('.settings-nav a').removeClass('active');
        $(this).addClass('active');

        $('.settings-content > section').addClass('hidden');
        $(target).removeClass('hidden');
        if ($(window).width() < 992) {
            $('html, body').animate({
                scrollTop: $(target).offset().top - 100
            }, 300);
        }
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
        $('input[name="old_password"], input[name="new_password"], input[name="new_password_confirm"]').attr('type', type);
    });

    $('form').submit(function(e) {
        if($(this).find('input[name="new_password"]').length) {
            var newPass = $(this).find('input[name="new_password"]').val();
            var confirmPass = $(this).find('input[name="new_password_confirm"]').val();
            if(newPass !== confirmPass) {
                e.preventDefault();
                showToast('error', 'Пароли не совпадают!');
                return false;
            }
        }
        return true;
    });

    $('#recipientSelect').change(function() {
        $('#recipientId').val($(this).val());
        updateTransferInfo();
    });

    function updateTransferInfo() {
        var recipientName = $('#recipientSelect option:selected').data('name') || '';
        var resourceLabel = $('#resourceType option:selected').data('label') || '';
        var amount = $('#transferAmountInput').val() || '';
        $('#transferRecipientName').text(recipientName || '—');
        $('#transferResourceType').text(resourceLabel || '—');
        $('#transferAmount').text(amount ? amount : '—');
        if (recipientName || amount) {
            $('#transferInfo').slideDown(200);
        } else {
            $('#transferInfo').slideUp(200);
        }
    }
    
    $('#resourceType').change(updateTransferInfo);
    $('#transferAmountInput').on('input', updateTransferInfo);
    $('#transferForm').submit(function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');

        if (!$('#recipientId').val()) {
            showToast('error', 'Пожалуйста, выберите получателя');
            return;
        }

        $btn.addClass('loading');
        $btn.prop('disabled', true);

        $.ajax({
            url: 'setting_core',
            type: 'POST',
            data: $form.serialize() + '&action=transfer',
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.status === 'success') {
                    showToast('success', response.message);
                    $form[0].reset();
                    $('#recipientId').val('');
                } else {
                    showToast('error', response.message);
                }
            },
            error: function(xhr, status, error) {
                showToast('error', 'Ошибка при выполнении запроса');
                console.error('Transfer error:', error);
            },
            complete: function() {
                $btn.removeClass('loading');
                $btn.prop('disabled', false);
            }
        });
    });

    $('#promoForm').submit(function(e) {
        e.preventDefault();

        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        $btn.addClass('loading');
        $btn.prop('disabled', true);

        $.ajax({
            url: 'setting_core',
            type: 'POST',
            data: $form.serialize() + '&action=promo',
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.status === 'success') {
                    showToast('success', response.message);
                    // Reset form
                    $form[0].reset();
                } else {
                    showToast('error', response.message);
                }
            },
            error: function(xhr, status, error) {
                showToast('error', 'Ошибка при выполнении запроса');
                console.error('Promo error:', error);
            },
            complete: function() {
                $btn.removeClass('loading');
                $btn.prop('disabled', false);
            }
        });
    });

    function showToast(type, message) {
        var icon = type === 'success' ? '✅' : '❌';
        var toastHtml = `<div class="toast ${type}">
                <span class="toast-icon">${icon}</span>
                <span class="toast-message">${message}</span>
                <button class="toast-close" onclick="closeToast(this)">×</button>
            </div>`;

        var $toast = $(toastHtml);
        $('#toastContainer').append($toast);

        setTimeout(function() {
            $toast.css('animation', 'slideOut 0.3s ease forwards');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 5000);
    }
    window.showToast = showToast;
    $('.radio-item').click(function(e) {
        if (e.target.tagName !== 'INPUT') {
            $(this).find('input').prop('checked', true).trigger('change');
        }
    });

    function handleScrollAnimation() {
        $('.settings-card').each(function() {
            var elementTop = $(this).offset().top;
            var elementBottom = elementTop + $(this).outerHeight();
            var viewportTop = $(window).scrollTop();
            var viewportBottom = viewportTop + $(window).height();
            if (elementBottom > viewportTop && elementTop < viewportBottom) {
                $(this).addClass('visible');
            }
        });
    }

    $(window).on('scroll', handleScrollAnimation);
    handleScrollAnimation();
    $(document).keydown(function(e) {
        if (e.key === 'Escape') {
            $('.toast').each(function() {
                closeToast($(this).find('.toast-close')[0]);
            });
        }
    });
});

function closeToast(btn) {
    var $toast = $(btn).closest('.toast');
    $toast.css('animation', 'slideOut 0.3s ease forwards');
    setTimeout(function() {
        $toast.remove();
    }, 300);
}

$(document).on('focus', '.form-control', function() {
    $(this).parent().addClass('focused');
});
$(document).on('blur', '.form-control', function() {
    $(this).parent().removeClass('focused');
});

$(document).on('click', '.btn', function(e) {
    var $btn = $(this);
    var offset = $btn.offset();
    var x = e.pageX - offset.left;
    var y = e.pageY - offset.top;
    var $ripple = $('<span class="ripple"></span>');
    $ripple.css({
        left: x + 'px',
        top: y + 'px'
    });
    $btn.append($ripple);
    setTimeout(function() {
        $ripple.remove();
    }, 600);
});
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('titleSearch');
    const clearBtn = document.getElementById('clearSearch');
    const noResultsMsg = document.getElementById('noTitlesFound');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const titleItems = document.querySelectorAll('.title-item');
            let visibleCount = 0;

            clearBtn.style.display = searchTerm ? 'block' : 'none';

            titleItems.forEach(function(item) {
                const titleText = item.getAttribute('data-title').toLowerCase();

                if (titleText.includes(searchTerm)) {
                    item.classList.remove('hidden');
                    visibleCount++;
                } else {
                    item.classList.add('hidden');
                }
            });

            if (noResultsMsg) {
                noResultsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
            }

            const titlesGrid = document.querySelector('.titles-grid');
            if (titlesGrid) {
                titlesGrid.style.display = visibleCount === 0 ? 'none' : '';
            }
        });
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
            });
        }

        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                this.dispatchEvent(new Event('input'));
                this.blur();
            }
        });
    }
});