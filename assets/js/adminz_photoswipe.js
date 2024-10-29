jQuery(document).ready(function ($) {
    $(adminz_photoswipe_var.selector).on('click', function (e) {
        e.preventDefault();

        const items = [];
        const index = $(this).closest(".col").index();

        $(adminz_photoswipe_var.selector).each(function () {
            const src = $(this).attr('href');
            let size = $(this).data('size'); // 800x600

            // tìm image bên trong slec
            if (size === undefined) {
                const imgElement = $(this).find('img');
                if (imgElement.length > 0) {
                    const width = imgElement.attr('width');
                    const height = imgElement.attr('height');
                    size = width + "x" + height;
                }
            }

            if (size !== undefined) {
                size = size.split('x');
            }

            if (!size || !size[0] || !parseInt(size[0], 10)) {
                console.log('missing w');
                return;
            }

            const item = {
                src: src,
                w: parseInt(size[0], 10),
                h: parseInt(size[1], 10)
            };

            if (!items.some(existingItem => existingItem.src === item.src)) {
                items.push(item);
            }
        });

        const options = {
            index: index,
            // Cấu hình PhotoSwipe theo ý muốn
        };
        const pswpElement = document.querySelectorAll('.pswp')[0];
        const gallery = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options);
        gallery.init();

    });
})