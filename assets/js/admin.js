(function () {
    'use strict';

    const Adminz_admin = {
        init() {
            this.script_debug = adminz_js.script_debug;
            window.addEventListener('resize', () => this.onWindowResize());
            document.addEventListener('DOMContentLoaded', () => this.onDOMContentLoaded());
        },

        onWindowResize() {
            // Something here
        },

        onDOMContentLoaded() {

            // adminz_click_to_copy
            document.querySelectorAll('.adminz_click_to_copy').forEach(element => {
                this.click_to_copy_init(element);
            });

            // adminz_fetch
            document.querySelectorAll('.adminz_fetch').forEach(element => {
                this.fetch_init(element);
            });

            // adminz_toggle
            document.querySelectorAll('.adminz_toggle').forEach(element => {
                this.toggle_init(element);
            });

            // adminz_upload_image
            document.querySelectorAll('.adminz_upload_image').forEach(element => {
                this.upload_image(element);
            });

            if (this.script_debug) {
                console.log(this);
            }

            // options page
            this.setup_option_page();

            // crawl
            this.setup_crawl();
        },

        // ---------------- Your custom event here ---------------- //
        upload_image(element){
            element.addEventListener('change', function(){
                const action = element.getAttribute('data-action');
                const _response = document.querySelector(element.getAttribute('data-response'));
                
                if (!action) {
                    alert(' action is required!');
                    return;
                }

                if (!_response) {
                    alert(' response is required!');
                    return;
                }

                if (element.files.length === 0) {
                    alert('No file selected!');
                    return;
                }

                const file = element.files[0];
                _response.textContent = '';
                element.setAttribute('disabled', 'disabled');
                
                // Fetch 
                (async () => {
                    try {
                        const url = adminz_js.ajax_url;
                        const formData = new FormData();
                        formData.append('action', action);
                        formData.append('file', file);
                        formData.append('nonce', adminz_js.nonce);
                        //console.log('Before Fetch:', formData.get('data');
                
                        const response = await fetch(url, {
                            method: 'POST',
                            body: formData,
                        });
                        element.removeAttribute('disabled');
                
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                
                        const data = await response.json(); // reponse.text()
                        console.log(data);
                        if (data.success) {
                            //Code here
                            console.log(_response); 
                            _response.innerHTML = data.data;
                        } else {
                        }
                    } catch (error) {
                        console.error('Fetch error:', error);
                    }
                })();
            });
        },

        toggle_init(element){
            element.onclick = function () {
                const selector = element.getAttribute('data-toggle'); 
                if(!selector){
                    alert('selector not found!');
                    return;
                }
                const target = document.querySelector(selector);
                if (target.style.display === "none") {
                    target.style.display = "block";
                } else {
                    target.style.display = "none";
                }
            }
        },

        fetch_init(element){
            element.onclick = function () {
                const _action = element.getAttribute('data-action');
                console.log("Action:",_action); 
                const _response = document.querySelector(element.getAttribute('data-response'));

                if (!_response) { alert('no response to fetch'); return; }
                if (!_action) { alert('no action to fetch'); return; }

                _response.textContent = '';

                // clear all other
                document.querySelectorAll(".adminz_response").forEach(item=>{
                    item.innerHTML = "";
                })
                element.setAttribute('disabled' , 'disabled');

                // Fetch 
                (async () => {
                    try {
                        const url = adminz_js.ajax_url;
                        const form = element.closest("form");
                        const formData = new FormData(form);
                        
                        formData.append('action', _action);
                        formData.append('nonce', adminz_js.nonce);
                        console.log("FETCH INIT ------------- ", formData); 
                
                        const response = await fetch(url, {
                            method: 'POST',
                            body: formData,
                        });
                        element.removeAttribute('disabled');

                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                
                        const data = await response.json(); // reponse.text()
                        console.log(data);
                        console.log(_action);
                        if (data.success) {
                            //Code here
                            _response.innerHTML = data.data;

                            document.dispatchEvent(
                                new CustomEvent(
                                    _action,
                                    {
                                        detail: {
                                            context: this,
                                            data: data,
                                        }
                                    }
                                )
                            );
                        } else {
                        }
                    } catch (error) {
                        console.error('Fetch error:', error);
                    }
                })();
                
            }
        },

        click_to_copy_init(element) {
            element.onclick = function () {
                const text = element.getAttribute('data-text');
                if (text) {
                    const textArea = document.createElement("textarea");
                    textArea.value = text;
                    textArea.style.position = "fixed";  // Tránh việc textarea làm thay đổi layout trang web
                    textArea.style.opacity = "0";  // Làm cho textarea vô hình
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    try {
                        document.execCommand('copy');
                        alert('Copied to clipboard: \n' + text);
                    } catch (err) {
                        alert('Error to copy!');
                    }
                    document.body.removeChild(textArea);
                }
            }
        },

        setup_option_page(){
            const adminz_wrap_h2 = document.querySelectorAll('.adminz_wrap form h2');
            if (adminz_wrap_h2){
                adminz_wrap_h2.forEach(h2 => {
                    h2.style.position = 'relative';

                    const toggleSpan = document.createElement('span');
                    toggleSpan.textContent = '';
                    toggleSpan.classList.add('dashicons');
                    toggleSpan.classList.add('dashicons-sort');
                    toggleSpan.style.cursor = 'pointer';
                    toggleSpan.style.marginLeft = '10px';
                    toggleSpan.style.color = '#2271b1';
                    toggleSpan.style.position = 'absolute';
                    toggleSpan.style.right = '0px';
                    toggleSpan.style.fontSize = '2';
                    toggleSpan.style.fontWeight = '400';

                    h2.appendChild(toggleSpan);

                    const nextTable = h2.nextElementSibling;
                    if (nextTable && nextTable.tagName.toLowerCase() === 'table') {
                        toggleSpan.addEventListener('click', () => {
                            nextTable.style.display = nextTable.style.display === 'none' ? 'table' : 'none';
                        });
                    }
                });
            }
        },

        setup_crawl() {

            document.addEventListener('run_adminz_import_from_category', function (event) {
                const action = 'run_adminz_import_from_post';
                const button = event.detail.context;
                this.setup_crawl_run_category(action, button);
            }.bind(this));

            document.addEventListener('run_adminz_import_from_product_category', function (event) {
                const action = 'run_adminz_import_from_product';
                const button = event.detail.context;
                this.setup_crawl_run_category(action, button);
            }.bind(this));
        },

        setup_crawl_run_category(action, button){
            const wrap = document.querySelector(button.getAttribute('data-response'));
            const rows = wrap.querySelectorAll('tr');
            let rowUrlPairs = [];

            if (rows) {
                rows.forEach(row => {
                    const url = row.getAttribute('data-url');
                    if (url) {
                        rowUrlPairs.push({ row, url });
                    }

                    const runButton = row.querySelector('.run');
                    runButton.onclick = () => {
                        this.setup_crawl_run_single(row, url, action);
                    }
                });
            }

            // Khởi động xử lý tuần tự các URL
            let sequence = Promise.resolve();
            rowUrlPairs.forEach(({ row, url }) => {
                sequence = sequence.then(() => this.setup_crawl_run_single(row, url, action)
                    .catch(error => {
                        console.error(`Error processing ${url}:`, error);
                        // Tiếp tục chuỗi Promise ngay cả khi có lỗi
                        return Promise.resolve();
                    })
                );
            });

            sequence.then(() => {
                console.log("All URLs have been processed.");
            });
        },

        setup_crawl_run_single(row, url, action) {
            return new Promise((resolve, reject) => {
                try {
                    const apiUrl = adminz_js.ajax_url;
                    const formData = new FormData();

                    formData.append('action', action);
                    formData.append('url', url);
                    formData.append('nonce', adminz_js.nonce);

                    fetch(apiUrl, {
                        method: 'POST',
                        body: formData,
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log(`Fetched data from ${url}:`, data);
                        if (data.success) {
                            row.querySelector('.result').innerHTML = data.data;
                            // row.querySelector('button').setAttribute('disabled', 'disabled');
                            resolve();
                        } else {
                            reject(`Failed to fetch data from ${url}`);
                        }
                    })
                    .catch(error => {
                        console.error(`Fetch error for ${url}:`, error);
                        reject(error);
                    });
                } catch (error) {
                    console.error('Fetch error:', error);
                    reject(error);
                }
            });
        },

        // ---------------- Default Methods ----------------------- //

        ___check_click_element(element) {
            element.onclick = function(event){
                console.log(event.currentTarget); 
            }
        },

        _setDemoData(element) {
            const demoData = {
                'text': 'Demo Text',
                'checkbox': true,
                'radio': 'option2',
                'password': 'DemoPassword123',
                'email': 'demo@example.com',
                'tel': '123-456-7890',
                'number': 42,
                'date': '2024-01-17',
                'time': '12: 34',
                'url': 'https: //www.example.com',
                'search': 'Search query',
                'color': '#3498db',
                'textarea': 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Aspernatur consequuntur deserunt nam veniam aliquid libero porro ullam.',
            };
            const forms = element.querySelectorAll('form');
            forms.forEach(form => {
                const formFields = form.querySelectorAll('input, textarea, select');
                formFields.forEach(field => {
                    const fieldType = field.tagName.toLowerCase() === 'textarea' ? 'textarea' : field.tagName.toLowerCase() === 'select' ? 'select' : field.getAttribute('type');
                    const fieldValue = demoData[fieldType
                    ];
                    if (fieldType && !field.value) {
                        switch (fieldType) {
                            case 'search':
                                field.value = fieldValue !== undefined ? fieldValue : '';
                                break;
                            case 'checkbox':
                                field.checked = fieldValue || false;
                                break;
                            case 'radio':
                                if (field.value === fieldValue) {
                                    field.checked = true;
                            }
                                break;
                            case 'color':
                                field.value = fieldValue || '#ffffff';
                                break;
                            case 'textarea':
                                field.value = fieldValue !== undefined ? fieldValue : '';
                                break;
                            case 'select':
                                const options = field.querySelectorAll('option');
                                options.forEach(option => {
                                    option.selected = true;
                            });
                                break;
                            default:
                                field.value = fieldValue;
                                break;
                        }
                    }
                });
            });
        },
    };

    Adminz_admin.init();
    window.Adminz_admin = Adminz_admin;
})();


