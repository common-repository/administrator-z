(function () {
    'use strict';

    class Adminz {
        constructor() {
            window.addEventListener('resize', () => this.onWindowResize());
            document.addEventListener('DOMContentLoaded', () => this.onDOMContentLoaded());
        };

        onWindowResize = () =>{
            // adminz_family_tree
            document.querySelectorAll('.adminz_family_tree').forEach(element => {
                this.familyTree_setup(element);
            });
        };

        onDOMContentLoaded = () => {

            //
            document.querySelectorAll('.adminz_countdown').forEach(element => {
                this.countDown_setup(element);
            });

            // 
            document.querySelectorAll('.adminz_lightbox').forEach(element => {
                this.lightbox_setup(element);
            });

            // 
            document.querySelectorAll('.adminz_toggle').forEach(element => {
                this.toggle_setup(element);
            });

            // 
            document.querySelectorAll('.adminz_map').forEach(element => {
                this[element.id] = [];
                this.map_setup(element);
                this.map_run(element, true);
            });

            // readmore
            document.querySelectorAll('.adminz_readmore').forEach(element => {
                this.readmore_setup(element);
            });

            // adminz_woo_search
            document.querySelectorAll('.adminz_woo_search').forEach(element => {
                this.wooSearch_setup(element);
            });

            // adminz_readmore_description
            document.querySelectorAll('.adminz_readmoreContent').forEach(element => {
                this.readMoreContent_setup(element);
            });

            // adminz_min_max_price
            document.querySelectorAll('.adminz_min_max_price').forEach(element => {
                this.minMaxPrice_setup(element);
            });

            // adminz_family_tree
            document.querySelectorAll('.adminz_family_tree').forEach(element => {
                this.familyTree_setup(element);
                element.addEventListener('scroll', () => {
                    this.familyTree_setup(element);
                });
            });

            // adminz_tooltip_box
            document.querySelectorAll('.adminz_tooltip_box').forEach(element => {
                this.adminz_tooltip_box(element);
            });

            // adminz_use_photoswipe
            document.querySelectorAll('.adminz_use_photoswipe').forEach(element => {
                this.adminz_use_photoswipe(element);
            });
        };

        // ---------------- Your custom event here ---------------- //
        adminz_tooltip_box = (element) => {
            if (
                !/Android|webOS|iPhone|iPad|Mac|Macintosh|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
                ) {
                let productBox = document.querySelectorAll('.product-small');
                let tooltipBox = element;

                productBox.forEach(function (box) {
                    box.addEventListener('mousemove', function (event) {
                        let needLeft = event.pageX + 20;
                        let needTop = event.pageY - 20;

                        if ((needLeft + tooltipBox.offsetWidth + 20) > document.documentElement.clientWidth) {
                            needLeft = needLeft - tooltipBox.offsetWidth - 60;
                        }
                        adminzTooltipContent(tooltipBox, box, needLeft, needTop);
                    });
                    box.addEventListener('mouseleave', function () {
                        tooltipBox.style.display = 'none';
                    });
                });

                function adminzTooltipContent(tooltipBox, hoverBox, needLeft, needTop) {
                    let dataBox = hoverBox.querySelector('.tooltip_data');
                    let currentProductId = tooltipBox.getAttribute('data-product_id');
                    if (dataBox && currentProductId !== dataBox.getAttribute('data-product_id')) {
                        tooltipBox.setAttribute('data-product_id', dataBox.getAttribute('data-product_id'));
                        tooltipBox.innerHTML = dataBox.innerHTML;
                        adminzChangeSrc(tooltipBox);
                    }
                    if (tooltipBox.querySelector('*:not(:empty)')) {
                        tooltipBox.style.display = 'inline';
                        tooltipBox.style.left = `${needLeft}px`;
                        tooltipBox.style.top = `${needTop}px`;
                    } else {
                        tooltipBox.style.display = 'none';
                    }
                }

                function adminzChangeSrc(dom) {
                    let images = dom.querySelectorAll('img');
                    images.forEach(function (img) {
                        if (img.classList.contains('lazy-load')) {
                            let domSrc = img.getAttribute('src');
                            let domDataSrc = img.getAttribute('data-src');
                            img.setAttribute('src', domDataSrc);
                            img.setAttribute('data-src', domSrc);
                            img.classList.remove('lazy-load');
                        }
                    });
                }
            }
        }

        adminz_use_photoswipe = (element) => {
            // check all .slider .image-lightbox
            setTimeout(() => {
                element.querySelectorAll(".slider .image-lightbox").forEach(imageLightbox => {
                    const parent = imageLightbox.closest(".img");

                    // Kiểm tra xem đã có .image-tools chưa
                    if (!parent.querySelector('.image-tools')) {
                        const htmlString = '<div class="image-tools absolute bottom left z-3"> <span class="zoom-button button is-outline circle icon hide-for-small image-lightbox"> <i class="icon-expand"></i></span> </div>';
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(htmlString, 'text/html');
                        const imageTools = doc.querySelector('div');
                        parent.appendChild(imageTools);

                        // Thêm sự kiện click vào span để trigger click vào .image-lightbox
                        imageTools.querySelector('span').addEventListener('click', (event) => {
                            event.preventDefault();
                            imageLightbox.click();
                        });
                    }
                });
            }, 1000);

        }

        familyTree_setup = (element) => {
            const svg = element.querySelector('svg');
            svg.innerHTML = '';

            element.querySelectorAll('.item').forEach(parent => {
                const parent_id = parent.getAttribute('data-id');
                const children = document.querySelector(".group-" + parent_id);
                if (children) {
                    const drawLine = (element, parent, child) => {
                        const svg = element.querySelector('svg');
                        const parentRect = parent.getBoundingClientRect();
                        const childRect = child.getBoundingClientRect();
                        const elementRect = element.getBoundingClientRect();

                        // Tính toán lại các tọa độ dựa trên vị trí cuộn hiện tại
                        const scrollLeft = element.scrollLeft;
                        const scrollTop = element.scrollTop;

                        let startX = Math.round(parentRect.left + parentRect.width / 2 - elementRect.left + scrollLeft);
                        let startY = parentRect.bottom - elementRect.top + scrollTop;
                        let endX = Math.round(childRect.left + childRect.width / 2 - elementRect.left + scrollLeft);
                        let endY = childRect.top - elementRect.top - 14 + scrollTop;
                        let midY = startY + 15;

                        const fixY = parent.getAttribute('data-fixy');
                        if (fixY) {
                            midY += 7 * fixY;
                        }

                        let path;
                        if (Math.abs(startX - endX) <= 4) {
                            path = `M${startX},${startY} V${endY}`;
                        } else {
                            const horizontalOffset = 5 * Math.sign(endX - startX);

                            path = `M${startX},${startY} 
                            V${midY - 5} 
                            Q${startX},${midY},${startX + horizontalOffset},${midY} 
                            H${endX - horizontalOffset} 
                            Q${endX},${midY},${endX},${midY + 5} 
                            V${endY}`;
                        }

                        const newPath = document.createElementNS("http://www.w3.org/2000/svg", "path");
                        newPath.classList.add('parent-' + parent_id);
                        newPath.setAttribute("d", path);

                        svg.appendChild(newPath);
                    };

                    drawLine(element, parent, children);
                }

                // hover
                parent.addEventListener('mouseover', (e) => {
                    parent.classList.add('active');
                    if(children){
                        children.classList.add('active');
                    }
                    const path = document.querySelector("path.parent-" + parent_id);
                    if(path){
                        path.classList.add('active');
                        // move path to top
                        path.remove();
                        svg.appendChild(path);
                    }
                });

                parent.addEventListener('mouseout', (e) => {
                    parent.classList.remove('active');
                    if(children){
                        children.classList.remove('active');
                    }
                    const path = document.querySelector("path.parent-" + parent_id);
                    if (path) {
                        path.classList.remove('active');
                    }
                });
            });
        };

        minMaxPrice_setup = (element) => {
            const {
                step,
                woocommerce_currency,
                woocommerce_currency_pos,
                woocommerce_price_thousand_sep,
                woocommerce_price_decimal_sep,
                woocommerce_price_num_decimals
            } = JSON.parse(element.getAttribute('data-woocommerce'));

            const minSlider = element.querySelector('.minSlider');
            const maxSlider = element.querySelector('.maxSlider');
            const minValue = element.querySelector('.minValue');
            const maxValue = element.querySelector('.maxValue');

            // Lấy giá trị số từ các phần tử .minValue và .maxValue
            let minVal = parseFloat(minValue.querySelector('.woocommerce-Price-amount bdi').textContent.trim().replace(woocommerce_currency, '').replace(/[,]/g, ''));
            let maxVal = parseFloat(maxValue.querySelector('.woocommerce-Price-amount bdi').textContent.trim().replace(woocommerce_currency, '').replace(/[,]/g, ''));

            const updateValues = () => {
                minVal = parseInt(minSlider.value);
                maxVal = parseInt(maxSlider.value);

                // Đảm bảo minVal và maxVal luôn đúng về thứ tự
                if (minVal >= maxVal) {
                    if (minSlider === document.activeElement) {
                        maxVal = minVal + step;
                        if (maxVal > maxSlider.max) {
                            maxVal = parseInt(maxSlider.max);
                            minVal = maxVal - step;
                            minSlider.value = minVal;
                        }
                        maxSlider.value = maxVal;
                    } else if (maxSlider === document.activeElement) {
                        minVal = maxVal - step;
                        if (minVal < minSlider.min) {
                            minVal = parseInt(minSlider.min);
                            maxVal = minVal + step;
                            maxSlider.value = maxVal;
                        }
                        minSlider.value = minVal;
                    }
                }

                // Format lại giá trị và cập nhật vào các phần tử .minValue và .maxValue
                let formattedMinVal = `${woocommerce_currency}${minVal.toFixed(woocommerce_price_num_decimals).replace(/\B(?=(\d{3})+(?!\d))/g, woocommerce_price_thousand_sep).replace('.', woocommerce_price_decimal_sep)}`;
                let formattedMaxVal = `${woocommerce_currency}${maxVal.toFixed(woocommerce_price_num_decimals).replace(/\B(?=(\d{3})+(?!\d))/g, woocommerce_price_thousand_sep).replace('.', woocommerce_price_decimal_sep)}`;

                if (woocommerce_currency_pos === 'right') {
                    formattedMinVal = `${minVal.toFixed(woocommerce_price_num_decimals).replace(/\B(?=(\d{3})+(?!\d))/g, woocommerce_price_thousand_sep).replace('.', woocommerce_price_decimal_sep)}${woocommerce_currency}`;
                    formattedMaxVal = `${maxVal.toFixed(woocommerce_price_num_decimals).replace(/\B(?=(\d{3})+(?!\d))/g, woocommerce_price_thousand_sep).replace('.', woocommerce_price_decimal_sep)}${woocommerce_currency}`;
                }

                minValue.querySelector('.woocommerce-Price-amount bdi').textContent = formattedMinVal;
                maxValue.querySelector('.woocommerce-Price-amount bdi').textContent = formattedMaxVal;
            };

            updateValues();

            minSlider.addEventListener('input', updateValues);
            maxSlider.addEventListener('input', updateValues);
        };

        readMoreContent_setup = (element) => {
            const readMore = document.createElement('div');
            readMore.className = 'readmore_bottom';
            const button = document.createElement('button');
            button.classList.add('button', 'white');
            const icon = document.createElement('i');
            icon.classList.add('icon-angle-down');
            const text = document.createElement('span');
            text.innerHTML = adminz_js.i18n.readmore;
            button.appendChild(icon);
            button.appendChild(text);
            readMore.appendChild(button);
            element.appendChild(readMore);
            button.addEventListener('click', function () {
                var icon = this.querySelector('i');
                icon.classList.toggle('icon-angle-down');
                icon.classList.toggle('icon-angle-up');
                element.classList.toggle('toggled');
            });
        };

        wooSearch_empty_field = (select) => {
            for (let i = select.options.length - 1; i >= 0; i--) {
                if (select.options[i].value !== "") {
                    select.remove(i);
                }
            }
            select.setAttribute('disabled', 'disabled');
        };

        wooSearch_fill_data = (select, data) => {
            data.forEach(item => {
                let option = document.createElement('option');
                option.value = item.term_id;
                option.text = item.name;
                select.add(option);
            });
            select.removeAttribute('disabled');
        };

        wooSearch_setup = (element) => {
            // view more
            const viewmoreButton = element.querySelector('button.view_more');
            if (viewmoreButton) {
                viewmoreButton.onclick = () => {
                    const fields = element.querySelectorAll(".col.view_more");
                    if (fields) {
                        fields.forEach(field => {
                            field.classList.toggle('hidden');
                        });
                    }
                }
            }

            // thx

            const fieldTinh = element.querySelector('[name="tinh"]');
            const fieldHuyen = element.querySelector('[name="huyen"]');
            const fieldXa = element.querySelector('[name="xa"]');

            if (fieldTinh && fieldHuyen && fieldXa){
                fieldTinh.addEventListener('change', () => {
                    const parent = fieldTinh.value;
                    // empty
                    this.wooSearch_empty_field(fieldHuyen);
                    this.wooSearch_empty_field(fieldXa);

                    // Fetch 
                    (async () => {
                        try {
                            const url = adminz_js.ajax_url;
                            const formData = new FormData();
                            formData.append('action', 'admiz_tinhhuyenxa_get_data');
                            formData.append('nonce', adminz_js.nonce);
                            formData.append('parent', parent);
                            // console.log('Before Fetch:', formData.get('parent'));

                            const response = await fetch(url, {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }

                            const data = await response.json(); // reponse.text()
                            if (data.success) {
                                //Code here
                                this.wooSearch_fill_data(fieldHuyen, data.data);
                            } else {
                            }
                        } catch (error) {
                            console.error('Fetch error:', error);
                        }
                    })();
                });

                fieldHuyen.addEventListener('change', () => {
                    const parent = fieldHuyen.value;
                    // empty
                    this.wooSearch_empty_field(fieldXa);

                    // Fetch 
                    (async () => {
                        try {
                            const url = adminz_js.ajax_url;
                            const formData = new FormData();
                            formData.append('action', 'admiz_tinhhuyenxa_get_data');
                            formData.append('nonce', adminz_js.nonce);
                            formData.append('parent', parent);
                            // console.log('Before Fetch:', formData.get('parent'));

                            const response = await fetch(url, {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }

                            const data = await response.json(); // reponse.text()
                            if (data.success) {
                                //Code here
                                this.wooSearch_fill_data(fieldXa, data.data);
                            } else {
                            }
                        } catch (error) {
                            console.error('Fetch error:', error);
                        }
                    })();
                });
            }
        };

        readmore_setup = (element) => {
            const inner = element.querySelector('.adminz_readmore_inner');
            const button = element.querySelector('.adminz_button');
            button.onclick = ()=>{
                inner.classList.toggle('unset');
            };
        };

        map_setup = (element) => {
            // init data
            this.map_initData(element);
            this.map_createForm(element);
            this.map_createSelect1(element);
        };

        map_run = (element, auto_focus) => {
            this.map_findItems(element);
            this.map_setList(element);
            this.map_setMap(element, auto_focus);
        };

        map_initData = (element) => {
            
            // doms
            this[element.id].element = element;
            this[element.id].form = element.querySelector('.form');
            this[element.id].list = element.querySelector('.list');
            this[element.id].map = element.querySelector('.map');
            this[element.id].__config = JSON.parse(element.getAttribute('data-map'));

            // items
            const items = element.querySelectorAll('.item');
            const data_selects = [];
            items.forEach(item => {
                const data = JSON.parse(item.getAttribute('data'));
                data_selects.push(data);
                item.onclick = ()=>{
                    this.map_handleClickItem(element, item);
                    
                }
            });
            this[element.id].__items = data_selects;
        };

        map_handleClickItem = (element, item) => {
            const data = JSON.parse(item.getAttribute('data'));
            let latLngParts = data.latlong.split(", ");
            let latlng = {
                lat: parseFloat(latLngParts[0]),
                lng: parseFloat(latLngParts[1]),
            };

            document.dispatchEvent(
                new CustomEvent(
                    'adminz_map_focus_item',
                    {
                        detail: {
                            context: this[element.id],
                            latlng: latlng,
                            auto_focus: false,
                        }
                    }
                )
            );
        };

        map_createForm = (element) => {
            this[element.id].form.textContent = "";

            // h4
            const h4 = document.createElement('h4');
            h4.textContent = this[element.id].__config.search_text;
            h4.classList.add('uppercase');
            this[element.id].h4 = h4;
            this[element.id].form.appendChild(h4);

            // select1
            const select1 = document.createElement('select');
            select1.setAttribute('name', 'address_op_1');
            this.map_addDefaultOption( element, select1, 'field1');
            select1.addEventListener('change', () => { this.map_handleChangeSelect1(element); });
            this[element.id].select1 = select1;
            this[element.id].form.appendChild(select1);


            // select 2
            const select2 = document.createElement('select');
            select2.setAttribute('name', 'address_op_2');
            this.map_addDefaultOption( element, select2, 'field2');
            select2.addEventListener('change', () => { this.map_handleChangeSelect2(element); });
            this[element.id].select2 = select2;
            this[element.id].form.appendChild(select2);
        };

        map_createSelect1 = (element) => {
            const __items = this[element.id].__items;
            let appended = [];
            for (let i = 0; i < __items.length; i++) {
                let name = __items[i].address_opt_1;
                if(!appended.includes(name)){
                    const option = document.createElement('option');
                    option.textContent = name;
                    this[element.id].select1.appendChild(option);
                    appended.push(name);
                }
            }
        };

        map_findItems = (element) => {
            const __items = this[element.id].__items;

            // get values
            const value1 = this[element.id].select1.value;
            const value2 = this[element.id].select2.value;
            let __itemsFound = [];

            let has_value1 = true;
            let has_value2 = true;

            if(!value1){
                has_value1 = false;
            }

            if(!value2){
                has_value2 = false;
            }

            for (let i = 0; i < __items.length; i++) {
                let match_value1 = false;
                let match_value2 = false;

                if (has_value1 && __items[i].address_opt_1 === value1){
                    match_value1 = true;
                }

                if (has_value2 && __items[i].address_opt_2 === value2) {
                    match_value2 = true;
                }

                if(!has_value1){
                    match_value1 = true;
                }

                if (!has_value2) {
                    match_value2 = true;
                }

                if(match_value1 && match_value2){
                    __itemsFound.push(__items[i].id);
                }
            }
            
            this[element.id].__itemsFound = __itemsFound;
            return;
        };

        map_setList = (element) => {
            const items = this[element.id].list.querySelectorAll('.item');
            if (items) {
                items.forEach(item => {
                    item.classList.add('hidden');
                    const data = JSON.parse(item.getAttribute('data'));
                    if (this[element.id].__itemsFound.includes(data.id)) {
                        item.classList.remove('hidden');
                    }
                });
            }
        };

        map_setMap = (element, auto_focus) => {
            document.dispatchEvent(
                new CustomEvent(
                    'adminz_map_initmap',
                    {
                        detail: {
                            context: this[element.id],
                            auto_focus: auto_focus,
                        }
                    }
                )
            );
        };

        map_handleChangeSelect1 = (element) => {
            const select2 = this[element.id].select2;
            select2.textContent = '';
            this.map_addDefaultOption( element, select2, 'field2');
            const value = this[element.id].select1.value;
            // find opt 2
            const __items = this[element.id].__items;
            let appended = [];
            for (let i = 0; i < __items.length; i++) {
                if(__items[i].address_opt_1 === value){
                    let name = __items[i].address_opt_2;
                    if (!appended.includes(name)) {
                        const option = document.createElement('option');
                        option.textContent = name;
                        this[element.id].select2.appendChild(option);
                        appended.push(name);
                    }
                }
            }
            this.map_run(element, false);
        };

        map_handleChangeSelect2 = (element) => {
            this.map_run(element, false);
        };

        map_addDefaultOption = (element, select, text) => {
            const placeholder = this.map_getConfig(element, 'placeholder_text');
            const option_default = document.createElement('option');
            option_default.setAttribute('value', '');
            const textValue = this.map_getConfig(element, text);
            option_default.setAttribute('value', '');
            option_default.textContent = " — " + placeholder + " " + textValue + " — ";
            select.appendChild(option_default);
        };

        map_getConfig = (element, key) => {
            return this[element.id].__config[key];
        };

        toggle_setup = (element) => {
            element.onclick = () => {
                // single structure
                const target = element.getAttribute('data-target');
                const toggleClass = element.getAttribute('data-toggle-class');

                if(target  && toggleClass){
                    document.querySelectorAll(target).forEach(target => {
                        target.classList.toggle(toggleClass);
                    });
                }

                // multiple structure
                if (element.getAttribute('data-target-toggle-class')){
                    const targetToggleClass = JSON.parse(element.getAttribute('data-target-toggle-class'));
                    if(targetToggleClass.length){
                        for (let i = 0; i < targetToggleClass.length; i++) {
                            document.querySelectorAll(targetToggleClass[i].target).forEach(_element => {
                                const className = targetToggleClass[i].class;
                                _element.classList.toggle(className);
                            });
                        }
                    }
                }
            }
        };

        lightbox_setup = (element) => {
            const data = JSON.parse(element.getAttribute('data-lightbox'));
            if (data.auto_open) {
                var closelightbox_custom = flatsomeVars.lightbox.close_markup;
                if (data.close_bottom_text) {
                    var closelightbox_custom = data.close_bottom_text;
                }
                var cookieId = "lightbox_" + data.id;
                var cookieValue = "opened_" + data.version;
                var timer = parseInt(data.auto_timer);
                var closeBtnInside = true;
                var reopen = data.reopen;
                var reopen_timer = parseInt(1000 * data.reopen_timer);
                if (data.auto_show === 'always') {
                    cookie(cookieId, false);
                }
                if (cookie(cookieId) !== cookieValue) {
                    this.lightbox_regeral(data, timer, closeBtnInside, closelightbox_custom);
                    cookie(cookieId, cookieValue, 1);
                    if (reopen == 'true' && reopen_timer) {
                        setInterval(function () {
                            if (!jQuery.magnificPopup.instance.isOpen) {
                                this.lightbox_regeral(data, timer, closeBtnInside, closelightbox_custom);
                            }
                        }, reopen_timer);
                    }
                }
            }
        };

        lightbox_regeral = (data, timer, closeBtnInside, closelightbox_custom) => {
            setTimeout(function () {
                if (jQuery.fn.magnificPopup) jQuery.magnificPopup.close()
            }, timer - 350);
            setTimeout(function () {
                jQuery.loadMagnificPopup().then(function() {
                    jQuery.magnificPopup.open({
                        midClick: true,
                        removalDelay: 300,
                        closeBtnInside: closeBtnInside,
                        closeMarkup: closelightbox_custom,
                        fixedContentPos: true,
                        items: {
                            src: '#' + data.id,
                            type: 'inline'
                        },
                        callbacks: {                                  
                        open: function() {},
                        }
                    })
                })
            }, timer);
        };

        countDown_setup = (element) => {
            const timeleft = parseInt(element.getAttribute('data-timeleft'));
            const name = element.getAttribute('data-name');
            let future;

            if (this.countDown_creatCookie(name)) {
                future = this.countDown_creatCookie(name);
            } else {
                future = new Date().getTime() / 1000 + timeleft * 60;
                this.countDown_creatCookie(name, future, 365);
            }
            this.coutnDown_calculateHMSleft(element, future, name);
            setInterval(() => {
                this.coutnDown_calculateHMSleft(element, future, name);
            }, 1000);
        };

        countDown_creatCookie = (name, value, days) => {
            if (value !== undefined) {
                var expires = "";
                if (days) {
                    var date = new Date();
                    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                    expires = "; expires=" + date.toUTCString();
                }
                document.cookie = name + "=" + (value || "") + expires + "; path=/";
            } else {
                var nameEQ = name + "=";
                var ca = document.cookie.split(';');
                for (var i = 0; i < ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
                }
                return null;
            }
        };

        coutnDown_calculateHMSleft = (element, future, name) => {
            var diff = Math.floor(future - new Date().getTime() / 1000);
            var dayleft = Math.floor((diff / (24 * 60 * 60)));
            var hoursleft = Math.floor((diff % (24 * 60 * 60)) / 60 / 60);
            var minutesleft = Math.floor((diff % (60 * 60)) / 60);
            var secondsleft = Math.floor((diff % (60 * 60)) % 60);

            if (!((dayleft < 0) || (hoursleft < 0) || (minutesleft < 0) || (secondsleft < 0))) {
                if (dayleft < 10) dayleft = "0" + dayleft;
                if (hoursleft < 10) hoursleft = "0" + hoursleft;
                if (minutesleft < 10) minutesleft = "0" + minutesleft;
                if (secondsleft < 10) secondsleft = "0" + secondsleft;

                element.querySelector(".countdown-day").innerHTML = dayleft;
                element.querySelector(".countdown-hour").innerHTML = hoursleft;
                element.querySelector(".countdown-minute").innerHTML = minutesleft;
                element.querySelector(".countdown-second").innerHTML = secondsleft;
            } else {
                this.countDown_creatCookie(name, false);
            }
        };

        ___check_click_element = (element) => {
            element.onclick = function(event){
                console.log(event.currentTarget);
            }
        };
    }

    const adminz = new Adminz();
    window.Adminz = adminz;

})();