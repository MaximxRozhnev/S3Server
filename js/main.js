// <button onclick="showNotification('success', 'Operation successful!')">Show Success</button>
// <button onclick="showNotification('warning', 'This is a warning!')">Show Warning</button>
// <button onclick="showNotification('error', 'An error occurred!')">Show Error</button>
// * [ Функции ] *
    // ** [ Получение данных сессии ] **
        function getSessionField(fieldName, callback) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "/requests/return_session.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        
            xhr.onload = function () {
                if (xhr.status === 200) {
                    callback(xhr.responseText); // Возвращаем результат через callback
                } else {
                    callback(null); // Возвращаем null в случае ошибки
                }
            };
        
            xhr.send("field=" + encodeURIComponent(fieldName));
        }
   // END ** [ Получение данных сессии ] ** -----------
    
    refreshPatientList(); // Загрузка пациентов при включении страницы
    
    
    
    // ** [ Данные ] **
        var user_data = []; // Данные пользователя
        var roleID = 0;
        var nowPage = 1;
        const yourRole = document.getElementById('your-role');
        const logoutButton = document.getElementById('logoutButton');
        const adminButton = document.getElementById('adminButton');
        const addPatientButton = document.getElementById('addPatientButton');
        const addExaminationButton = document.getElementById('addExaminationButton');
        const addConclusionButton = document.getElementById('addConclusionButton');
        const selectModeButton = document.getElementById('selectModeButton');
        const searchPatientInputForExam = document.getElementById('searchPatientInputForExam');
        const searchPatientInputForConclusion = document.getElementById('searchPatientInputForConclusion');
        
        
        getSessionField("bucket", function (result) {
            try {
                bucket = JSON.parse(result); // Преобразуем JSON-строку в объект
            } catch(error) {
                
            }
        });
        getSessionField("user_data", function (result) {
            try {
                user_data = JSON.parse(result); // Преобразуем JSON-строку в объект
                roleID = user_data['Role_id']; // Номер роли пользователя
            } catch (error) {
                showNotification('error', 'Ошибка получения данных! Обратитесь к IT специалисту.');
            }
        });
    // END ** [ Данные ] ** -----------------------------------

// * [ Пациент ] *
    // ** [ Загрузка докторов в окно с добавлением пациентов ] **
        async function loadDoctors() {
            try {
                const response = await fetch('/requests/get_data.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'getDoctors'  // Указываем действие для сервера
                    }),
                });
        
                const data = await response.json();
        
                if (data.success) {
                    const doctorCheckboxList = document.getElementById('doctorCheckboxList');
                    doctorCheckboxList.innerHTML = ''; // Очистим список перед добавлением новых чекбоксов
                    data.data.forEach(doctor => {
                        const div = document.createElement('div');
                        div.className = 'doctor-checkbox';
        
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.id = `doctor-${doctor.UserID}`;
                        checkbox.value = doctor.UserID;
                        checkbox.setAttribute("Email", doctor.Email);
                        
                        const label = document.createElement('label');
                        label.htmlFor = `doctor-${doctor.UserID}`;
                        label.textContent = doctor.FIO;
        
                        div.appendChild(checkbox);
                        div.appendChild(label);
                        doctorCheckboxList.appendChild(div);
                    });
                } else {
                    showNotification('error', 'Ошибка при загрузке списка врачей!');
                }
            } catch (error) {
                showNotification('error', 'Ошибка при загрузке списка врачей!');
            }
        }
    // END ** [ Загрузка докторов в окно с добавлением пациентов ] ** ------------------------------
    
    // ** [ Загрузка пациентов ] **
        const examModal = document.getElementById('examModal');
        const closeExamModal = document.getElementById('closeExamModal');
        closeExamModal.onclick = function() {
            examModal.style.display = "none";
        };
        
        var patients_list = [];
        document.getElementById('refreshButton').addEventListener('click', function () {
            refreshPatientList();
        });
        
        function refreshPatientList(page = 1, limit = 20) {
            document.getElementById('searchInput').value = '';
            document.getElementById('birthdayFrom').value = '';
            document.getElementById('birthdayTo').value = '';
            document.getElementById('alphabetOrder').value = '';
            document.getElementById('recentPatients').value = '';
        
            fetch('/requests/get_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'filterPatients',
                    page: page,
                    limit: limit,
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updatePatientList(data.data); // Обновляем список пациентов
                    createPagination(data.totalRecords, page, limit, 'filterPatientsFromServer'); // Создаем пагинацию
                } else {
                    showNotification('error', 'Ошибка сброса фильтров: ' + data.message);
                }
            })
            .catch(error => showNotification('error', 'Ошибка сброса фильтров!'));
        }
        
        // *** [ Загрузка обследований пациента ] ***
            // Функция для создания контекстного меню
            function createContextMenu(examination, isConclusion, x, y) {
                let roleId = roleID; // Предполагается, что roleID доступен в глобальной области видимости
            
                // Удаляем предыдущее контекстное меню, если оно существует
                const existingMenu = document.querySelector('.context-menu');
                if (existingMenu) {
                    existingMenu.remove();
                }
            
                const menu = document.createElement('ul');
                menu.classList.add('context-menu');
            
                // Добавляем кнопку "Скачать"
                const downloadItem = document.createElement('li');
                downloadItem.textContent = isConclusion ? 'Скачать заключение' : 'Скачать обследование';
                downloadItem.addEventListener('click', function () {
                    const filePath = isConclusion ? examination.Result_Path : examination.Path;
                    
                    // Отправляем запрос на сервер для получения временного URL
                    fetch('/requests/get_signed_url.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            filePath: filePath
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.signedUrl) {
                            // Перенаправляем на подписанный URL для скачивания
                            window.location.href = data.signedUrl;
                        } else {
                            alert('Ошибка получения ссылки для скачивания.');
                        }
                    })
                    .catch(error => {
                        console.error('Ошибка:', error);
                        alert('Произошла ошибка при получении ссылки для скачивания.');
                    });
                
                    menu.remove(); // Убираем меню после действия
                });
                menu.appendChild(downloadItem);

                // Проверяем роли для отображения кнопок удаления
                if (roleId == 1 || (roleId == 3 && !isConclusion) || (roleId == 2 && isConclusion)) {
                    const deleteItem = document.createElement('li');
                    deleteItem.textContent = isConclusion ? 'Удалить заключение' : 'Удалить обследование';
                    deleteItem.addEventListener('click', function () {
                        const filePath = examination.Path; // Путь файла обследования
                        const resultPath = examination.Result_Path; // Путь файла заключения
                
                        if (confirm(`Вы уверены, что хотите удалить ${isConclusion ? 'заключение' : 'обследование'}?`)) {
                            const action = isConclusion ? 'deleteConclusion' : 'deleteExamination';
                            const fileToDelete = isConclusion ? resultPath : filePath; // Выбираем путь, в зависимости от типа действия
                
                            fetch('/requests/delete_from_s3.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    action: action,
                                    filePath: fileToDelete,  // Передаем правильный путь для действия
                                    examinationId: examination.ID,
                                    isConclusion: isConclusion,
                                    deleteLocally: false, // Убедитесь, что не удаляете локально, если используете S3
                                }),
                            })
                            .then((response) => response.json())
                            .then((data) => {
                                if (data.success) {
                                    examModal.style.display = "none";
                                    showNotification('success', `${isConclusion ? 'Заключение' : 'Обследование'} успешно удалено.`);
                                } else {
                                    showNotification('error',`Ошибка: ${data.message}`);
                                }
                            })
                            .catch((error) => {
                                alert('Произошла ошибка при удалении.');
                            });
                        }
                
                        menu.remove(); // Убираем меню после действия
                    });
                    menu.appendChild(deleteItem);
                }

            
                // Добавляем меню в документ
                document.body.appendChild(menu);
            
                // Позиционирование меню
                menu.style.left = `${x}px`;
                menu.style.top = `${y}px`;
            
                return menu;
            }

            async function loadExaminations(patientId) {
                try {
                    const url = '/requests/get_data.php';
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'getExaminations', // Указываем действие
                            patientId: patientId,     // Передаем ID пациента
                        }),
                    });
            
                    const data = await response.json();
            
                    if (data.success) {
                        const examinationList = document.getElementById('examinationList');
                        // Очистим текущий список обследований
                        examinationList.innerHTML = '';
                        const dataResult = data.data;
                        
                        if(dataResult.length > 0) {
                            dataResult.forEach(examination => {
                                const fullPath = examination.Path || 'Без названия';
                                const fileName = fullPath.split('/').pop();
                                
                                const rawDate = examination.Upload_date || 'Дата не указана';
                                const rawTime = examination.Upload_time ? ` ${examination.Upload_time}` : '';
                                const formattedDate = rawDate !== 'Дата не указана' 
                                    ? rawDate.split('-').reverse().join('.') 
                                    : rawDate;
                                
                                const details = document.createElement('details');
                                details.classList.add('examination-details');
                                
                                const summary = document.createElement('summary');
                                summary.innerHTML = `<strong>📁 ${fileName}</strong> <span class="date">(${formattedDate}${rawTime})</span>`;
                                
                                if (examination.Result_Path) {
                                    const resultFileName = examination.Result_Path.split('/').pop();
                                    summary.classList.add('has-conclusion');
                                    summary.innerHTML += `<span class="conclusion-indicator">[С заключением]</span>`;
                                    
                                    const conclusion = document.createElement('p');
                                    conclusion.innerHTML = `<strong> 📄 Заключение:</strong> ${resultFileName}`;
                                    details.appendChild(conclusion);
                                    
                                    conclusion.addEventListener('contextmenu', function (e) {
                                        e.preventDefault();
                                        showContextMenu(e, examination, true);
                                    });
                                } else {
                                    summary.classList.add('no-conclusion');
                                    const conclusion = document.createElement('p');
                                    conclusion.textContent = 'Заключение отсутствует.';
                                    details.appendChild(conclusion);
                                }
                                
                                summary.addEventListener('contextmenu', function (e) {
                                    e.preventDefault();
                                    showContextMenu(e, examination, false);
                                });
                                
                                details.appendChild(summary);
                                examinationList.appendChild(details);
                            });
                            
                            // Закрытие контекстного меню при клике в другом месте
                            document.addEventListener('click', function () {
                                const menu = document.querySelector('.context-menu');
                                if (menu) {
                                    menu.remove();
                                }
                            });
                        }
                        else {
                            const div = document.createElement('div');
                            div.textContent = 'Нет обследований';
                            examinationList.appendChild(div);
                        }
            
                        // Показываем модальное окно с обследованиями
                        examModal.style.display = 'block';
                    } else {
                        showNotification('error', 'Ошибка при получении обследований!');
                    }
                } catch (error) {
                    showNotification('error', 'Ошибка при загрузке обследований!');
                }
            }
            
        function showContextMenu(event, examination, isConclusion) {
            const menu = createContextMenu(examination, isConclusion);
            menu.style.left = `${event.pageX}px`;
            menu.style.top = `${event.pageY}px`;
        }

        // END *** [ Загрузка обследований пациента ] *** --------------------------------
        
        async function updatePatientList(patients_list) {
            const patientList = document.getElementById('patientList');
            
            // Очищаем текущий список
            patientList.innerHTML = '';
        
            if (patients_list.length > 0) {
                for (const patient of patients_list) {
                    // Запрос в БД для получения числа обследований (M) и заключений (N)
                    // const response = await fetch(`requests/get_patient_examinations.php?patient_id=${patient.ID}`);
                    // const { examinationsCount, resultsCount } = await response.json(); // Ожидаем JSON-ответ
        
                    // Создаем строку пациента с N/M
                    const li = document.createElement('li');
                    li.className = 'patient-item';
                    li.innerHTML = `
                        <span class="patient-name">${patient.FIO}</span>
                        <span class="patient-date">(${patient.BirthdayFormatted})</span>
                        <span class="patient-status">[${patient.N}/${patient.M}]</span>
                    `;
                    // Определяем цвет строки
                    if (patient.M > 0) {
                        if (patient.N == patient.M) {
                            li.style.backgroundColor = "#d4edda"; // Зеленый
                        } else {
                            li.style.backgroundColor = "#f8d7da"; // Красный
                        }
                    }
                    // Добавляем обработчик клика
                    li.addEventListener('click', () => {
                        loadExaminations(patient.ID);
                        
                        const deletePatientBtn = document.getElementById('deletePatientBtn');

                        if (deletePatientBtn) {
                            deletePatientBtn.onclick = async () => {
                                if (confirm('Вы точно хотите удалить пациента и все его обследования?')) {
                                    try {
                                        const folderPath = `${patient.FIO}-${patient.Birthday}/`; // Убедись, что формат совпадает с сохранённым путём

                                        const response = await fetch('/requests/delete_from_s3.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                            },
                                            body: JSON.stringify({
                                                action: 'deletePatient',
                                                filePath: folderPath,  // Передаем правильный путь для действия
                                                deleteLocally: false, // Убедитесь, что не удаляете локально, если используете S3
                                            })
                                        });
                                        const result = await response.json();

                                        const response_2 = await fetch('/requests/delete_patient.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json'
                                            },
                                            body: JSON.stringify({ patientId: patient.ID })
                                        });
                        
                                        const result2 = await response_2.json();
                        
                                        if (result2.success) {
                                            examModal.style.display = 'none';
                                            refreshPatientList();
                                            showNotification('success', `Пациент удалён.`);
                                        } else {
                                            showNotification('error', `Пациент не удалён. ` + result2.error);
                                        }
                                    } catch (err) {
                                        alert('Ошибка при соединении с сервером');
                                        console.error(err);
                                    }
                                }
                            };
                        }

                        
                    });
        
                    patientList.appendChild(li);
                }
            } else {
                const div = document.createElement('div');
                div.className = 'no-examinations';
                div.textContent = 'Список пациентов пуст';
                patientList.appendChild(div);
            }
        }

        
        
    // END ** [ Загрузка пациентов ] ** -------------------------------------------------
    
    // ** [ Обновление списков по фильтру ] **
        // Функция для фильтрации пациентов с сервера
        
        async function filterPatientsFromServer(page = 1, limit = 20) {
            const searchInput = document.getElementById('searchInput').value;
            const birthdayFrom = document.getElementById('birthdayFrom').value;
            const birthdayTo = document.getElementById('birthdayTo').value;
            const alphabetOrder = document.getElementById('alphabetOrder').value;
            const recentPatients = document.getElementById('recentPatients').value;
        
            fetch('/requests/get_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'filterPatients',
                    searchInput: searchInput,
                    birthdayFrom: birthdayFrom,
                    birthdayTo: birthdayTo,
                    alphabetOrder: alphabetOrder,
                    recentPatients: recentPatients,
                    page: page,
                    limit: limit,
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updatePatientList(data.data);
                    createPagination(data.totalRecords, page, limit, 'filterPatientsFromServer');
                } else {
                    showNotification('error', 'Ошибка фильтрации пациентов!');
                }
            })
            .catch(error => showNotification('error', 'Ошибка фильтрации пациентов!'));
        }
        
        function createPagination(totalRecords, currentPage, limit, callback) {
            const totalPages = Math.ceil(totalRecords / limit);
            const paginationContainer = document.getElementById('paginationContainer');
            paginationContainer.innerHTML = '';
        
            if (totalPages <= 1) return;
        
            // Создаем кнопку "назад"
            const prevButton = document.createElement('button');
            prevButton.innerHTML = '❮';
            prevButton.classList.add('pagination-button');
            if (currentPage === 1) prevButton.disabled = true;
            prevButton.addEventListener('click', () => {
                window[callback](currentPage - 1, limit)
                nowPage = currentPage - 1;
            });
            paginationContainer.appendChild(prevButton);
        
            // Функция для создания кнопки страницы
            function createPageButton(page) {
                const pageButton = document.createElement('button');
                pageButton.textContent = page;
                pageButton.classList.add('pagination-button');
            
                if (page === currentPage) pageButton.classList.add('active');
            
                pageButton.addEventListener('click', () => {
                    nowPage = page;
                    window[callback](page, limit);
                });
            
                paginationContainer.appendChild(pageButton);
            }

        
            // Генерация кнопок страниц с сокращением
            if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) createPageButton(i);
            } else {
                createPageButton(1);
                if (currentPage > 3) paginationContainer.appendChild(document.createTextNode('...'));
        
                let start = Math.max(2, currentPage - 1);
                let end = Math.min(totalPages - 1, currentPage + 1);
        
                for (let i = start; i <= end; i++) createPageButton(i);
        
                if (currentPage < totalPages - 2) paginationContainer.appendChild(document.createTextNode('...'));
                createPageButton(totalPages);
            }
        
            // Создаем кнопку "вперед"
            const nextButton = document.createElement('button');
            nextButton.innerHTML = '❯';
            nextButton.classList.add('pagination-button');
            if (currentPage === totalPages) nextButton.disabled = true;
            nextButton.addEventListener('click', () => {
                window[callback](currentPage + 1, limit)
                nowPage = currentPage + 1;
            });
            paginationContainer.appendChild(nextButton);
        }

        function clearPatientListForModals() {
            const selectPatientForExam = document.getElementById('selectPatientForExam');
            const selectPatientForConclusion = document.getElementById('selectPatientForConclusion');
            
            selectPatientForExam.innerHTML = '<option value="">Выберите пациента</option>';
            selectPatientForConclusion.innerHTML = '<option value="">Выберите пациента</option>';
        }
    
        async function updatePatientListForModals(searchText = "", page = 1, limit = 20) {
            try {
                const response = await fetch('/requests/get_data.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'filterPatients',
                        searchInput: searchText,
                        page: page,
                        limit: limit
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    const selectPatientForExam = document.getElementById('selectPatientForExam');
                    const selectPatientForConclusion = document.getElementById('selectPatientForConclusion');
                    
                    selectPatientForExam.innerHTML = '<option value="">Выберите пациента</option>';
                    selectPatientForConclusion.innerHTML = '<option value="">Выберите пациента</option>';
                    
                    data.data.forEach(patient => {
                        const option = document.createElement('option');
                        option.value = patient.ID;
                        option.setAttribute('data-fio', patient.FIO);
                        option.setAttribute('data-birthday', patient.Birthday);
                        option.textContent = `${patient.FIO} (${patient.Birthday})`;
                    
                        selectPatientForExam.appendChild(option.cloneNode(true));
                        selectPatientForConclusion.appendChild(option);
                    });

                } else {
                    showNotification('error', 'Ошибка загрузки списка пациентов!');
                }
            } catch (error) {
                showNotification('error', 'Ошибка загрузки списка пациентов!');
            }
        }

        // Обработчики поиска пациентов
        ['searchPatientInputForExam', 'searchPatientInputForConclusion'].forEach(id => {
            document.getElementById(id).addEventListener('input', (event) => {
                updatePatientListForModals(event.target.value);
            });
        });
        
        function updatePatientSelect(patients, selectId) {
            const select = document.getElementById(selectId);
            select.innerHTML = '<option value="">Выберите пациента</option>';
            patients.forEach(patient => {
                const option = document.createElement('option');
                option.value = patient.ID;
                option.fio = patient.FIO;
                option.birthday = patient.Birthday;
                option.textContent = `${patient.FIO} (${patient.BirthdayFormatted})`;
                select.appendChild(option);
            });
        }
        
    // END ** [ Обновление списков по фильтру ] ** ------------------------------------------------


    // ** [ Добавление пациента ] **
        const patientModal = document.getElementById('patientModal');
        const patientModalAddDoctors = document.getElementById('patientModalAddDoctors');
        
        const closePatientModal = document.getElementById('closePatientModal');
        const closePatientModalAddDoctors = document.getElementById('closePatientModalAddDoctors');
        
        const savePatientButton = document.getElementById('savePatientButton');
        const openPatientModalAddDoctorsButton = document.getElementById('openPatientModalAddDoctorsButton');
        const backPatientButton = document.getElementById('backPatientButton');
        
        if(backPatientButton) {
            backPatientButton.onclick = function() {
                patientModalAddDoctors.style.display = "none";
                patientModal.style.display = "block";
            }
        }
        if(openPatientModalAddDoctorsButton) {
            openPatientModalAddDoctorsButton.onclick = function() {
                const name = document.getElementById('patientName').value.trim();
                const dob = document.getElementById('patientDob').value;
            
                if (!name || !dob) {
                    showNotification('error', 'Пожалуйста, заполните все поля!');
                    return;
                }
                
                patientModal.style.display = "none";
                patientModalAddDoctors.style.display = "block";
                loadDoctors();
            }
        }
        
        if(closePatientModalAddDoctors) {
            closePatientModalAddDoctors.onclick = function() {
                patientModalAddDoctors.style.display = "none";
            }
        }
        
        if(addPatientButton) {
            addPatientButton.onclick = function() {
                patientModal.style.display = "block";
            };
        }
        if(closePatientModal) {
            closePatientModal.onclick = function() {
                patientModal.style.display = "none";
            };
            
            document.getElementById('cancelPatientModal').addEventListener('click', () => {
                patientModal.style.display = 'none';
            });
        }
        if(savePatientButton) {
            savePatientButton.addEventListener('click', function () {
                let doctor_ids = [];
                const name = document.getElementById('patientName').value.trim();
                const dob = document.getElementById('patientDob').value;
                // Получаем ID выбранных врачей
                document.querySelectorAll('#doctorCheckboxList input[type="checkbox"]:checked').forEach(checkbox => {
                    doctor_ids.push(checkbox.value);
                });
            
            
                fetch('/requests/add_patient.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        "dob": dob,
                        "name": name,
                        "doctor_ids": doctor_ids
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('success', data.message);
                        document.getElementById('patientModal').style.display = 'none';
                        document.getElementById('patientModalAddDoctors').style.display = 'none';
                    }
                    else {
                        showNotification('error', data.message);
                    }
                })
                .catch(error => {
                    showNotification('error', 'Произошла ошибка при попытке сохранить пациента!');
                });
            });
        }
    // END ** [ Добавление пациента ] ** -------------------------------------------
// END * [ Пациент ] * --------------------------------------
    

// * [ Обследование ] *
    const examinationModal = document.getElementById('examinationModal');
    const closeExaminationModal = document.getElementById('closeExaminationModal');
    const saveExaminationButton = document.getElementById('saveExaminationButton');
    if(addExaminationButton) {
        addExaminationButton.onclick = function() {
            updatePatientListForModals("", nowPage);
            examinationModal.style.display = "block";
        };
    }
    
    function clearExaminationFields() {
        searchPatientInputForExam.value = '';
        const fileInput = document.getElementById('fileInput');
        selectPatientForExam.value = "";    // Сбрасываем выбор пациента
        fileInput.value = "";               // Очищаем выбранный файл
    }
    
    if(closeExaminationModal) {
        closeExaminationModal.onclick = function() {
            clearExaminationFields();
            examinationModal.style.display = "none";
        };
        document.getElementById('cancelExaminationModal').addEventListener('click', () => {
            clearExaminationFields();
            examinationModal.style.display = "none";
        });
    }
    // ** [ Загрузка обследования ] **
    
        if (saveExaminationButton) {
            saveExaminationButton.addEventListener('click', async function () {
                const select = document.getElementById('selectPatientForExam');
                const fileInput = document.getElementById('fileInput');
                const progressOverlay = document.getElementById('progressOverlay');
                const progress = document.getElementById('progress');
                const progressText = document.getElementById('progressText');
        
                // Блокируем кнопку, чтобы предотвратить повторные клики
                saveExaminationButton.disabled = true;
        
                if (!select.value) {
                    showNotification('error', 'Выберите пациента!');
                    saveExaminationButton.disabled = false;
                    return;
                }
                if (fileInput.files.length === 0) {
                    showNotification('error', 'Выберите файл для загрузки!');
                    saveExaminationButton.disabled = false;
                    return;
                }
        
                const file = fileInput.files[0];
                const selectedOption = document.querySelector('#selectPatientForExam option:checked');
                const fio = selectedOption.getAttribute('data-fio');
                const date = selectedOption.getAttribute('data-birthday');
                const patientPath = `${fio}-${date}/${file.name}`;
                const userId = user_data.ID;
        
                try {
                    const checkPathResponse = await fetch('/requests/get_data.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'checkPath', patientPath: patientPath })
                    });
        
                    const checkPathResult = await checkPathResponse.json();
                    if (checkPathResult.success && Number(checkPathResult.data.count) > 0) {
                        showNotification('error', 'Данное обследование уже есть у пациента.');
                        saveExaminationButton.disabled = false;
                        return;
                    }
        
                    progressOverlay.style.display = 'flex';
        
                   // Ожидаем результат загрузки в зависимости от размера файла
                    try {
                        if (file.size > 100 * 1024 * 1024) {
                            // Файл больше 100 МБ, загружаем с использованием Pre-Signed URL
                            await uploadWithPresignedUrl(file, patientPath, progress, progressText);
                        } else {
                            // Файл меньше 100 МБ, загружаем напрямую
                            await uploadDirect(file, patientPath, progress, progressText);
                        }
                    } catch (error) {
                        console.error('Ошибка загрузки:', error);
                        showNotification('error', 'Ошибка при загрузке файла!');
                        progressOverlay.style.display = 'none';
                        return;  // Прерываем выполнение в случае ошибки
                    }
        
                    const insertResponse = await fetch('/requests/get_data.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'insertExamination',
                            patientId: selectedOption.value,
                            patientPath: patientPath,
                            uploadBy: userId
                        })
                    });
        
                    const insertResult = await insertResponse.json();
                    if (!insertResult.success) {
                        showNotification('error', 'Ошибка при добавлении записи в БД!');
                        saveExaminationButton.disabled = false;
                        return;
                    }
        
                    const emailsResponse = await fetch('/requests/get_data.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'getEmailsForPatient', patientId: selectedOption.value })
                    });
        
                    const emailsResult = await emailsResponse.json();
                    if (!emailsResult.success) {
                        showNotification('error', 'Ошибка при получении Email докторов пациента!');
                        saveExaminationButton.disabled = false;
                        return;
                    }
        
                    let doctors_email = emailsResult.data.map(item => item.Email);
        
                    await fetch('/requests/send_notif.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            emails: JSON.stringify(doctors_email),
                            subject: "Новое обследование!",
                            message: "Для пациента загружено новое обследование."
                        })
                    });
        
                    showNotification('success', 'Обследование успешно добавлено!');
                    fileInput.value = '';
                    progressOverlay.style.display = 'none';
                } catch (error) {
                    console.error('Ошибка:', error);
                    showNotification('error', 'Произошла ошибка при обработке запроса.');
                    progressOverlay.style.display = 'none';
                } finally {
                    // Снимаем блокировку с кнопки, независимо от результата
                    saveExaminationButton.disabled = false;
                }
            });
        }
        
        async function uploadDirect(file, patientPath, progress, progressText) {
            const uploadUrl = '/requests/upload_to_s3.php';
            const formData = new FormData();
            formData.append('file', file);
            formData.append('path', patientPath);
        
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', uploadUrl, true);
        
                let startTime = Date.now(); // Запоминаем время начала загрузки
        
                xhr.upload.addEventListener('progress', function (event) {
                    if (event.lengthComputable) {
                        const percent = (event.loaded / event.total) * 100;
                        const loadedMB = (event.loaded / 1024 / 1024).toFixed(2);
                        const totalMB = (event.total / 1024 / 1024).toFixed(2);
        
                        // Вычисление скорости загрузки
                        const elapsedTime = (Date.now() - startTime) / 1000; // Время в секундах
                        const speedMBps = event.loaded / 1024 / 1024 / elapsedTime; // Скорость в MB/s
                        const remainingMB = (event.total - event.loaded) / 1024 / 1024; // Оставшийся объем файла в MB
                        const remainingTime = speedMBps > 0 ? (remainingMB / speedMBps) : 0; // Время до завершения
        
                        progress.value = percent;
                        progressText.innerText = `${percent.toFixed(2)}% (${loadedMB} MB из ${totalMB} MB) - Осталось: ${remainingTime.toFixed(1)} сек`;
                    }
                });
        
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        resolve();
                    } else {
                        reject('Ошибка при загрузке файла.');
                    }
                };
        
                xhr.onerror = function () {
                    reject('Ошибка сети.');
                };
        
                xhr.send(formData);
            });
        }

        
        async function uploadWithPresignedUrl(file, patientPath, progress, progressText) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '/requests/get_presigned_url.php', true);
                xhr.setRequestHeader('Content-Type', 'application/json');
        
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        const data = JSON.parse(xhr.responseText);
        
                        if (!data.url) {
                            showNotification('error', 'URL для загрузки не был получен.');
                            reject('Ошибка при получении Pre-Signed URL');
                            return;
                        }
        
                        const preSignedUrl = data.url;
                        const uploadRequest = new XMLHttpRequest();
                        uploadRequest.open('PUT', preSignedUrl, true);
                        uploadRequest.setRequestHeader('Content-Type', file.type);
        
                        let startTime = Date.now(); // Запоминаем время начала загрузки
        
                        uploadRequest.upload.onprogress = function (e) {
                            if (e.lengthComputable) {
                                const percent = (e.loaded / e.total) * 100;
                                const loadedMB = (e.loaded / 1024 / 1024).toFixed(2);
                                const totalMB = (e.total / 1024 / 1024).toFixed(2);
        
                                // Вычисление скорости загрузки
                                const elapsedTime = (Date.now() - startTime) / 1000; // Время в секундах
                                const speedMBps = e.loaded / 1024 / 1024 / elapsedTime; // Скорость в MB/s
                                const remainingMB = (e.total - e.loaded) / 1024 / 1024; // Оставшийся объем файла в MB
                                const remainingTime = speedMBps > 0 ? (remainingMB / speedMBps) : 0; // Время до завершения
        
                                progress.value = percent;
                                progressText.innerText = `${percent.toFixed(2)}% (${loadedMB} MB из ${totalMB} MB) - Осталось: ${remainingTime.toFixed(1)} сек`;
                            }
                        };
        
                        uploadRequest.onload = function () {
                            if (uploadRequest.status === 200) {
                                showNotification('success', 'Файл успешно загружен на S3!');
                                resolve();
                            } else {
                                showNotification('error', 'Ошибка при загрузке файла.');
                                reject('Ошибка при загрузке файла');
                            }
                            progressOverlay.style.display = 'none';
                        };
        
                        uploadRequest.onerror = function () {
                            showNotification('error', 'Ошибка при загрузке файла.');
                            progressOverlay.style.display = 'none';
                            reject('Ошибка при загрузке файла');
                        };
        
                        uploadRequest.send(file);
                    } else {
                        showNotification('error', 'Ошибка при получении Pre-Signed URL.');
                        reject('Ошибка при получении Pre-Signed URL');
                    }
                };
        
                xhr.onerror = function () {
                    showNotification('error', 'Ошибка при получении Pre-Signed URL.');
                    reject('Ошибка при получении Pre-Signed URL');
                };
        
                const requestData = JSON.stringify({ 
                    filename: file.name,
                    patientPath: patientPath
                });
                xhr.send(requestData);
            });
        }









        
        const patientSelect = document.getElementById('selectPatientForConclusion');
        const examinationSelect = document.getElementById('selectExaminationForConclusion');
    
        // Обработчик выбора пациента
        if(patientSelect) {
            patientSelect.addEventListener('change', async () => {
                const patientId = patientSelect.value;
                // Очистка списка обследований
                examinationSelect.innerHTML = '<option value="">Выберите обследование</option>';
        
                if (!patientId) return; // Если пациент не выбран, выходим
        
                try {
                    const response = await fetch('/requests/get_data.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'getExaminations', // Укажите соответствующее действие на сервере
                            patientId: patientId,
                        }),
                    });
        
                    const data = await response.json();
                    if (data.success) {
                        // Заполняем выпадающий список обследований
                        data.data.forEach(examination => {
                            const fileName = examination.Path.split('/').pop(); // Извлекаем имя файла после "/"
                            const option = document.createElement('option');
                            option.value = examination.ID;
                            option.textContent = `${examination.Upload_date} - ${fileName}`;
                            examinationSelect.appendChild(option);
                        });
                    } else {
                        console.error('Ошибка загрузки обследований:', data.message);
                    }
                } catch (error) {
                    console.error('Ошибка запроса:', error);
                }
            });
        }
        
    
        const saveConclusionButton = document.getElementById('saveConclusionButton');
        const selectPatientForConclusion = document.getElementById('selectPatientForConclusion');
    
        if(saveConclusionButton) {
            saveConclusionButton.addEventListener('click', async () => {
                const fileInput = document.getElementById('fileInputConclusion');
                const patientId = selectPatientForConclusion.value;
                const examinationId = selectExaminationForConclusion.value;
                const file = fileInput.files[0];
        
                if (fileInput.files.length === 0) {
                    showNotification('error', 'Выберите файл для загрузки!');
                    return;
                }
                if (!patientId) {
                    showNotification('error', 'Выберите пациента!');
                    return;
                }
                if(!examinationId ) {
                    showNotification('error', 'Выберите обследование!');
                    return;
                }
                
                try {
                    // 1. Проверяем, есть ли уже заключение для обследования
                    const checkResponse = await fetch('/requests/get_data.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'checkConclusion',
                            examinationId: examinationId,
                        }),
                    });
                
                    const checkResult = await checkResponse.json();
                    if (checkResult.success && checkResult.data.length > 0) {
                        showNotification('error', 'Заключение для данного обследования уже существует!');
                        return;
                    }
                
                    const fio = selectPatientForConclusion.options[selectPatientForConclusion.selectedIndex].getAttribute("data-fio"); // Получаем ФИО пациента
                    const date = selectPatientForConclusion.options[selectPatientForConclusion.selectedIndex].getAttribute("data-birthday");
                    const patientPath = `${fio}-${date}/results/${file.name}`;
                
                    // 2. Проверяем, существует ли файл на S3
                    const fileCheckResponse = await fetch('/requests/check_file_exists.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            filePath: patientPath,
                        }),
                    });
                
                    const fileCheckResult = await fileCheckResponse.json();
                    if (fileCheckResult.success && fileCheckResult.exists) {
                        showNotification('error', 'Файл с таким названием уже загружен.');
                        return;
                    }
                
                    const uploadUrl = '/requests/upload_to_s3.php'; // URL вашего PHP-скрипта для загрузки в S3
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('path', patientPath); // Указываем путь для S3
                
                    // Показываем прогресс-бар поверх всех окон
                    progressOverlay.style.display = 'flex';
                
                    // Используем XMLHttpRequest для отслеживания прогресса
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', uploadUrl, true);
                
                    // Обработчик прогресса
                    xhr.upload.addEventListener('progress', function(event) {
                        if (event.lengthComputable) {
                            const percent = (event.loaded / event.total) * 100;
                            progress.value = percent;
                
                            const uploadedMB = (event.loaded / (1024 * 1024)).toFixed(2); // в MB
                            const totalMB = (event.total / (1024 * 1024)).toFixed(2); // в MB
                
                            progressText.innerText = `${percent.toFixed(2)}% (${uploadedMB} MB из ${totalMB} MB)`;
                        }
                    });
                
                    // Отправка данных
                    xhr.send(formData);
                
                    // Обработчик ответа от сервера
                    xhr.onload = async function () { // Делаем функцию асинхронной
                        if (xhr.status === 200) {
                            try {
                                const uploadResult = JSON.parse(xhr.responseText);
                                if (!uploadResult.success) {
                                    alert('Ошибка при загрузке файла.');
                                    return;
                                }
                
                                // 3. Записываем в базу данных
                                const dbResponse = await fetch('/requests/get_data.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        action: 'insertConclusion',
                                        path: patientPath,
                                        examinationId: examinationId,
                                        uploadBy: user_data.ID, // Берем ID из текущей сессии
                                    }),
                                });
                
                                const dbResult = await dbResponse.json();
                                if (dbResult.success) {
                                    clearConclusionFields();
                                    showNotification('success', 'Заключение успешно добавлено!');
                                    fileInput.value = ''; // Очищаем поле загрузки файла
                                    const examinationSelect = document.getElementById('selectExaminationForConclusion');
                                    examinationSelect.innerHTML = '<option value="">Выберите обследование</option>';
                                } else {
                                    alert('Ошибка записи в базу данных: ' + dbResult.message);
                                    return;
                                }
                                fileInput.value = ''; // Сбрасываем поле загрузки
                                progressOverlay.style.display = 'none'; // Прячем прогресс-бар
                            } catch (error) {
                                alert('Произошла ошибка при обработке данных.');
                            }
                        } else {
                            alert('Произошла ошибка при загрузке файла.');
                        }
                        progressOverlay.style.display = 'none'; // Скрываем прогресс-бар при ошибке
                        conclusionModal.style.display = "none";
                    };
                
                    xhr.onerror = function () {
                        alert('Произошла ошибка при обработке запроса.');
                        progressOverlay.style.display = 'none'; // Скрываем прогресс-бар при ошибке
                    };
                
                } catch (error) {
                    alert('Произошла ошибка при обработке запроса.');
                    progressOverlay.style.display = 'none'; // Скрываем прогресс-бар при ошибке
                }
    
            });
        }
        
        
    // END ** [ Загрузка обследования ] ** ------------------------------------------------
    if(adminButton) {
        adminButton.addEventListener('click', function() {
            const securityKey = 'thisisprivatekey'; // Замените на реальный ключ
            window.location.href = `admin.php?key=${encodeURIComponent(securityKey)}`;
        });
    }
    

    // * [ Заключение ] *
    const conclusionModal = document.getElementById('conclusionModal');
    const closeConclusionModal = document.getElementById('closeConclusionModal');

    if(addConclusionButton) {
        addConclusionButton.onclick = function() {
            updatePatientListForModals("", nowPage);
            conclusionModal.style.display = "block";
        };
    }
    
    // Функция для очистки полей модального окна
    function clearConclusionFields() {
        const selectPatientForConclusion = document.getElementById("selectPatientForConclusion");
        const selectExaminationForConclusion = document.getElementById("selectExaminationForConclusion");
        const fileInputConclusion = document.getElementById("fileInputConclusion");
        searchPatientInputForConclusion.value = ""; // Очищаем поле поиска пациента
        selectPatientForConclusion.value = "";    // Сбрасываем выбор пациента
        selectExaminationForConclusion.value = ""; // Сбрасываем выбор обследования
        fileInputConclusion.value = "";           // Очищаем выбранный файл
    }

    if(closeConclusionModal) {
        closeConclusionModal.onclick = function() {
            clearConclusionFields();
            conclusionModal.style.display = "none";
        };
        
        document.getElementById('cancelConclusionModal').addEventListener('click', () => {
            clearConclusionFields();
            conclusionModal.style.display = "none";
        });
    }
    
    
    
    document.getElementById('applyFilters').addEventListener('click', () => {
        const searchInput = document.getElementById('searchInput').value;
        const birthdayFrom = document.getElementById('birthdayFrom').value;
        const birthdayTo = document.getElementById('birthdayTo').value;
        const alphabetOrder = document.getElementById('alphabetOrder').value;
        const recentPatients = document.getElementById('recentPatients').value;
    
        const page = 1; // При фильтрации начинаем с первой страницы
        const limit = 20; // Количество записей на странице
    
        fetch('/requests/get_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'filterPatients',
                searchInput: searchInput,
                birthdayFrom: birthdayFrom,
                birthdayTo: birthdayTo,
                alphabetOrder: alphabetOrder,
                recentPatients: recentPatients,
                page: page,
                limit: limit,
            }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updatePatientList(data.data); // Обновляем список пациентов
                    createPagination(data.totalRecords, page, limit, 'filterPatientsFromServer'); // Создаем пагинацию
                } else {
                    showNotification('error', 'Ошибка применения фильтров: ' + data.message);
                }
            })
            .catch(error => showNotification('error', 'Ошибка применения фильтров!'));
    });

    
    document.getElementById('resetFilters').addEventListener('click', () => {
        document.getElementById('searchInput').value = '';
        document.getElementById('birthdayFrom').value = '';
        document.getElementById('birthdayTo').value = '';
        document.getElementById('alphabetOrder').value = '';
        document.getElementById('recentPatients').value = '';
    
        const page = 1; // Сброс также начинается с первой страницы
        const limit = 20; // Количество записей на странице
    
        fetch('/requests/get_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'filterPatients',
                page: page,
                limit: limit,
            }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updatePatientList(data.data); // Обновляем список пациентов
                    createPagination(data.totalRecords, page, limit, 'filterPatientsFromServer'); // Создаем пагинацию
                } else {
                    showNotification('error', 'Ошибка сброса фильтров: ' + data.message);
                }
            })
            .catch(error => showNotification('error', 'Ошибка сброса фильтров!'));
    });
    
    const modeSelect = document.getElementById('modeSelect');
    
    if(selectModeButton) {
        selectModeButton.addEventListener('click', function() {
            // Переключаем видимость выпадающего списка
            modeSelect.style.display = modeSelect.style.display === 'none' ? 'block' : 'none';
        });
    }
    if(modeSelect) {
        // Обработчик изменения выбора режима
        document.getElementById('modeSelect').addEventListener('change', function() {
            const selectedModeId = this.value; // Получаем ID выбранного режима
            const selectedModeText = this.options[this.selectedIndex].textContent; // Текст выбранного режима
        
            fetch('/requests/update_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'updateBucket',
                    bucketId: selectedModeId, // Отправляем ID выбранного режима
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Подсвечиваем выбранный режим
                    document.querySelectorAll('#modeSelect option').forEach(option => {
                        if (option.value === selectedModeId) {
                            option.style.backgroundColor = '#4CAF50'; // Подсвечиваем выбранный режим зеленым
                            option.style.color = 'white';
                        } else {
                            option.style.backgroundColor = ''; // Сбрасываем фон у невыбранных
                            option.style.color = '';
                        }
                    });
                    //refreshPatientList();
                    location.reload();
                } else {
                    alert('Ошибка при обновлении выбранного режима');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Ошибка при связи с сервером');
            });
        });
    }
    
    document.addEventListener('keydown', (event) => {
        if (event.key === "Escape") {
            if (patientModalAddDoctors.style.display === "block") {
                patientModalAddDoctors.style.display = "none";
                patientModal.style.display = "block"; // Показываем другое окно
            } else if (patientModal.style.display === "block") {
                patientModal.style.display = "none"; // Просто закрываем patientModal
            } else {
                // Закрываем все остальные модальные окна
                document.querySelectorAll('.modal').forEach(modal => {
                    if (modal.style.display === "block") {
                        modal.style.display = "none";
                    }
                });
            }
        }
    });

