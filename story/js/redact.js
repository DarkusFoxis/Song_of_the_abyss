$(document).ready(function() {
    const editor = $('#storyMarkdown');
    const preview = $('#preview');
    const description = $('#storyDescription');
    const charCount = $('#charCount');
    const ageRating = $('#ageRating');
    const coverUpload = $('#coverUpload');
    const coverPreview = $('#coverPreview');
    const uploadText = $('#uploadText');

    marked.setOptions({
        breaks: true,
        gfm: true,
        langPrefix: 'language-',
        highlight: function(code, lang) {
            return code;
        }
    });

    function renderPreview() {
        preview.html(marked.parse(editor.val()));
    }

    editor.on('input', renderPreview);

    description.on('input', function() {
        charCount.text($(this).val().length);
    });

    coverUpload.on('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        if (!file.type.match('image.*')) {
            showNotification('Пожалуйста, выберите изображение', 'error');
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            showNotification('Размер изображения должен быть меньше 2MB', 'error');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(event) {
            coverPreview.html(`
                <img src="${event.target.result}" alt="Предпросмотр обложки">
                <button class="remove-cover">Удалить</button>
            `);
            uploadText.text('Иконка загружена');
            localStorage.setItem('draftCover', event.target.result);

            $('.remove-cover').on('click', removeCover);
        };
        reader.readAsDataURL(file);
    });

    window.insertMarkdown = function(prefix, suffix) {
        const textarea = editor[0];
        const startPos = textarea.selectionStart;
        const endPos = textarea.selectionEnd;
        const selectedText = textarea.value.substring(startPos, endPos);
        const newText = textarea.value.substring(0, startPos) + 
                       prefix + 
                       selectedText + 
                       suffix + 
                       textarea.value.substring(endPos);
        textarea.value = newText;
        textarea.focus();
        textarea.selectionStart = startPos + prefix.length;
        textarea.selectionEnd = startPos + prefix.length + selectedText.length;

        renderPreview();
    };

    window.saveDraft = function() {
        const title = $('#storyTitle').val().trim();
        const content = editor.val().trim();
        const desc = description.val().trim();
        if (!title || !content || !desc) {
            showNotification('Заполните название, описание и текст рассказа', 'error');
            return;
        }
        try {
            localStorage.setItem('draftTitle', title);
            localStorage.setItem('draftContent', content);
            localStorage.setItem('draftDescription', desc);
            localStorage.setItem('draftAgeRating', ageRating.val());
            showNotification('Черновик успешно сохранен!', 'success');
        } catch (error) {
            console.error('Ошибка сохранения:', error);
            showNotification('Ошибка при сохранении черновика', 'error');
        }
    };

    window.removeCover = function() {
        coverPreview.html('');
        coverUpload.val('');
        uploadText.text('Нажмите для загрузки изображения');
        localStorage.removeItem('draftCover');
    };

    window.publishStory = function() {
        const title = $('#storyTitle').val().trim();
        const content = editor.val().trim();
        const desc = description.val().trim();
        const rating = ageRating.val();
        
        if (!title || !content || !desc) {
            showNotification('Заполните название, описание и текст рассказа', 'error');
            return;
        }
        
        if (desc.length < 30) {
            showNotification('Краткое описание должно содержать не менее 30 символов', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('title', title);
        formData.append('description', desc);
        formData.append('age_limit', rating);
        formData.append('story', content);
        if (coverUpload[0].files[0]) {
            formData.append('icon', coverUpload[0].files[0]);
        }
        $.ajax({
            url: 'upload_core.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showNotification(data.message || 'Рассказ успешно опубликован!', 'success');
                    clearForm();
                } else {
                    showNotification(data.message || 'Ошибка при публикации', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ошибка:', error);
                showNotification('Ошибка сети: ' + error, 'error');
            }
        });
    };

    function clearForm() {
        $('#storyTitle').val('');
        editor.val('');
        description.val('');
        ageRating.val('0');
        coverPreview.html('');
        coverUpload.val('');
        uploadText.text('Нажмите для загрузки изображения');
        charCount.text('0');
        renderPreview();

        localStorage.removeItem('draftTitle');
        localStorage.removeItem('draftContent');
        localStorage.removeItem('draftDescription');
        localStorage.removeItem('draftAgeRating');
        localStorage.removeItem('draftCover');
    }

    function showNotification(message, type = 'success') {
        $('.notification').remove();

        const notification = $(`<div class="notification ${type}">${message}</div>`);
        $('body').append(notification);

        notification.css({
            'opacity': 0,
            'transform': 'translateY(-30px)'
        }).animate({
            'opacity': 1,
            'transform': 'translateY(0)'
        }, 400);

        setTimeout(() => {
            notification.animate({
                'opacity': 0,
                'transform': 'translateY(-30px)'
            }, 400, function() {
                $(this).remove();
            });
        }, 4000);
    }

    function loadDraft() {
        const savedTitle = localStorage.getItem('draftTitle');
        const savedContent = localStorage.getItem('draftContent');
        const savedDesc = localStorage.getItem('draftDescription');
        const savedAgeRating = localStorage.getItem('draftAgeRating');
        const savedCover = localStorage.getItem('draftCover');

        if (savedTitle) $('#storyTitle').val(savedTitle);
        if (savedContent) editor.val(savedContent);
        if (savedDesc) {
            description.val(savedDesc);
            charCount.text(savedDesc.length);
        }
        if (savedAgeRating) ageRating.val(savedAgeRating);

        if (savedCover) {
            coverPreview.html(`
                <img src="${savedCover}" alt="Предпросмотр обложки">
                <button class="remove-cover">Удалить</button>
            `);
            uploadText.text('Иконка загружена');

            $('.remove-cover').on('click', removeCover);
        }
        renderPreview();
    }
    loadDraft();
    renderPreview();
});