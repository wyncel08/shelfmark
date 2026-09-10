document.addEventListener('DOMContentLoaded', function () {
    const profileMenu = document.querySelector('.profile-menu');
    const profileTrigger = document.querySelector('.profile-trigger');
    if (profileMenu && profileTrigger) {
        profileTrigger.addEventListener('click', function () {
            const isOpen = profileMenu.classList.toggle('open');
            profileTrigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function (event) {
            if (!profileMenu.contains(event.target)) {
                profileMenu.classList.remove('open');
                profileTrigger.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Interactive star rating picker on add/edit forms.
    document.querySelectorAll('.star-picker').forEach(function (picker) {
        const hiddenInputId = picker.dataset.input;
        const hiddenInput = document.getElementById(hiddenInputId);
        const stars = picker.querySelectorAll('.star-pick');
        const clearBtn = picker.querySelector('.star-clear');

        function paint(value) {
            stars.forEach(function (star) {
                star.classList.toggle('filled', parseInt(star.dataset.value, 10) <= value);
            });
        }

        stars.forEach(function (star) {
            star.addEventListener('click', function () {
                const value = parseInt(star.dataset.value, 10);
                hiddenInput.value = value;
                paint(value);
            });
            star.addEventListener('mouseenter', function () {
                paint(parseInt(star.dataset.value, 10));
            });
        });

        picker.addEventListener('mouseleave', function () {
            paint(parseInt(hiddenInput.value, 10) || 0);
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                hiddenInput.value = '';
                paint(0);
            });
        }
    });

    // Live preview of an uploaded cover image before submitting.
    const coverInput = document.getElementById('cover_image');
    const coverPreview = document.getElementById('cover-preview');
    if (coverInput && coverPreview) {
        coverInput.addEventListener('change', function () {
            const file = coverInput.files[0];
            if (!file) {
                coverPreview.style.display = 'none';
                return;
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                coverPreview.src = e.target.result;
                coverPreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    }
});