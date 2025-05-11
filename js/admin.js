$(document).ready(function() {
    const token = getToken();
    if (!token) return logout();
    const user = parseJWT(token);
    if (user.role !== 'admin') return logout();

    $('#logoutBtn').click(logout);
    $('nav a').click(function(e) {
        e.preventDefault();
        const sec = $(this).data('section');
        showSection(sec);
    });

    // Initialize
    loadSubjects();
    loadQuizzes();
    loadQuestions();
    loadAttempts();
    loadLeaderboard();

    // Subject form
    $('#subjectForm').submit(function(e) {
        e.preventDefault();
        const data = { title: this.title.value, description: this.description.value };
        apiPost('/api/subjects.php', data).done(() => loadSubjects());
    });

    // Quiz form
    $('#quizForm').submit(function(e) {
        e.preventDefault();
        const data = { subject_id: this.subject_id.value, title: this.title.value, description: this.description.value };
        apiPost('/api/quizzes.php', data).done(() => loadQuizzes());
    });

    // Question type change and initial render
    $('#questionType').change(renderChoiceInputs).trigger('change');
    $('#quizSelect').change(function() {
        $('#questionForm [name=quiz_id]').val(this.value);
        loadQuestions(this.value);
    });
    $('#questionForm').submit(function(e) {
        e.preventDefault();
        const form = this;
        const data = { quiz_id: form.quiz_id.value, type: form.type.value, prompt: form.prompt.value };
        const choices = [];
        $('#choicesWrapper input.choice-text').each(function() {
            const idx = $(this).data('idx');
            const isCorrect = $('#choice_' + idx + '_correct').is(':checked');
            choices.push({ content: this.value, is_correct: isCorrect });
        });
        if (choices.length) data.choices = choices;
        apiPost('/api/questions.php', data).done(() => loadQuestions(form.quiz_id.value));
    });
});

function showSection(name) {
    // deactivate nav and activate current
    $('nav a').removeClass('active');
    $(`nav a[data-section="${name}"]`).addClass('active');
    // animate switch
    const current = $('.section:visible');
    if (current.length) {
        current.fadeOut(200, () => { $(`#${name}`).fadeIn(200); });
    } else {
        $(`#${name}`).fadeIn(200);
    }
}

function loadSubjects() {
    const tbody = $('#subjectsTable tbody');
    tbody.parent().addClass('loading');
    apiGet('/api/subjects.php').done(list => {
        tbody.empty();
        list.forEach(s => {
            tbody.append(
              `<tr style="display:none" data-id="${s.id}"><td>${s.id}</td><td>${s.title}</td><td>${s.description}</td>` +
              `<td><button class="editSub">Edit</button><button class="delSub">Delete</button></td></tr>`
            );
        });
        tbody.find('tr').each((i, row) => $(row).delay(i*50).fadeIn(200));
        tbody.find('.editSub').click(function() {
            const row = $(this).closest('tr');
            const id = row.data('id');
            const newTitle = prompt('New title', row.find('td').eq(1).text());
            if (newTitle === null) return;
            const newDesc = prompt('New description', row.find('td').eq(2).text());
            apiPut('/api/subjects.php', { id, title: newTitle, description: newDesc }).done(loadSubjects);
        });
        tbody.find('.delSub').click(function() {
            const id = $(this).closest('tr').data('id');
            if (confirm('Delete this subject?')) apiDelete('/api/subjects.php', { id }).done(loadSubjects);
        });
        // Populate dropdown
        const subjSel = $('#quizForm select[name=subject_id]').empty();
        list.forEach(s => subjSel.append(`<option value="${s.id}">${s.title}</option>`));
    }).always(() => tbody.parent().removeClass('loading')); 
}

function loadQuizzes() {
    const tbody = $('#quizzesTable tbody');
    tbody.parent().addClass('loading');
    apiGet('/api/quizzes.php').done(list => {
        tbody.empty();
        list.forEach(q => {
            tbody.append(
              `<tr style="display:none" data-id="${q.id}" data-sub="${q.subject_id}">` +
              `<td>${q.id}</td><td>${q.title}</td><td>${q.subject_title}</td>` +
              `<td><button class="editQuiz">Edit</button><button class="delQuiz">Delete</button></td></tr>`
            );
        });
        tbody.find('tr').each((i, row) => $(row).delay(i*50).fadeIn(200));
        tbody.find('.editQuiz').click(function() {
            const row = $(this).closest('tr'); const id = row.data('id');
            const newTitle = prompt('New title', row.find('td').eq(1).text()); if (newTitle===null) return;
            const newDesc = prompt('New description', row.find('td').eq(2).text());
            apiPut('/api/quizzes.php', { id, title: newTitle, description: newDesc, subject_id: row.data('sub') }).done(loadQuizzes);
        });
        tbody.find('.delQuiz').click(function() {
            const id = $(this).closest('tr').data('id');
            if (confirm('Delete this quiz?')) apiDelete('/api/quizzes.php', { id }).done(loadQuizzes);
        });
        const select = $('#quizSelect').empty().append('<option value="">--Select--</option>');
        list.forEach(q => select.append(`<option value="${q.id}">${q.title}</option>`));
        // Auto-select the first quiz to enable Add Question form
        if (list.length > 0) {
            select.val(list[0].id).trigger('change');
        }
    }).always(() => tbody.parent().removeClass('loading'));
}

function renderChoiceInputs() {
    const type = this.value;
    const wrapper = $('#choicesWrapper').empty();
    if (type === 'multiple_choice') {
        const initialCount = 4;
        wrapper.data('nextIdx', initialCount);
        for (let i = 0; i < initialCount; i++) {
            wrapper.append(
                `<div><input type="text" class="choice-text" data-idx="${i}" placeholder="Choice ${i+1}">` +
                `<label>Correct?<input type="checkbox" id="choice_${i}_correct"></label></div>`
            );
        }
        // Add button to allow more choices
        wrapper.append(`<button type="button" id="addChoiceBtn">Add Choice</button>`);
        // Handle dynamic addition
        wrapper.off('click', '#addChoiceBtn').on('click', '#addChoiceBtn', function() {
            const idx = wrapper.data('nextIdx');
            wrapper.find('#addChoiceBtn').before(
                `<div><input type="text" class="choice-text" data-idx="${idx}" placeholder="Choice ${idx+1}">` +
                `<label>Correct?<input type="checkbox" id="choice_${idx}_correct"></label></div>`
            );
            wrapper.data('nextIdx', idx + 1);
        });
    } else if (type === 'true_false') {
        ['True','False'].forEach((txt,i) => {
            wrapper.append(
                `<div><input type="hidden" class="choice-text" data-idx="${i}" value="${txt}">` +
                `<span>${txt}</span><label>Correct?<input type="checkbox" id="choice_${i}_correct"></label></div>`
            );
        });
    }
}

function loadQuestions(quizId) {
    if (!quizId) return;
    const tbody = $('#questionsTable tbody');
    tbody.parent().addClass('loading');
    apiGet('/api/questions.php', { quiz_id: quizId }).done(list => {
        tbody.empty();
        list.forEach(q => {
            tbody.append(
              `<tr style="display:none" data-id="${q.id}">` +
              `<td>${q.id}</td><td>${q.type}</td><td>${q.prompt}</td>` +
              `<td><button class="editQ">Edit</button><button class="delQ">Delete</button></td></tr>`
            );
        });
        tbody.find('tr').each((i, row) => $(row).delay(i*50).fadeIn(200));
        tbody.find('.editQ').click(function() {
            const row = $(this).closest('tr'); const id = row.data('id');
            const newPrompt = prompt('New prompt', row.find('td').eq(2).text()); if (newPrompt===null) return;
            const newType = prompt('New type (multiple_choice, true_false, fill_blank)', row.find('td').eq(1).text()); if (newType===null) return;
            apiPut('/api/questions.php', { id, prompt: newPrompt, type: newType }).done(() => loadQuestions($('#quizSelect').val()));
        });
        tbody.find('.delQ').click(function() {
            const id = $(this).closest('tr').data('id');
            if (confirm('Delete this question?')) apiDelete('/api/questions.php', { id }).done(() => loadQuestions($('#quizSelect').val()));
        });
    }).always(() => tbody.parent().removeClass('loading'));
}

function loadAttempts() {
    const tbody = $('#attemptsTable tbody');
    tbody.parent().addClass('loading');
    apiGet('/api/admin/attempts.php').done(list => {
        tbody.empty();
        list.forEach(a => {
            let btn = '';
            if (a.status === 'completed') btn = `<button data-id="${a.id}" class="gradeBtn">Grade</button>`;
            else btn = `<span>${a.status}</span>`;
            tbody.append(`<tr style="display:none"><td>${a.id}</td><td>${a.student_name}</td><td>${a.quiz_title}</td><td>${a.status}</td><td>${btn}</td></tr>`);
        });
        tbody.find('tr').each((i, row) => $(row).delay(i*50).fadeIn(200));
        $('.gradeBtn').click(function() { location.href = `/admin/grade.html?attempt_id=${$(this).data('id')}`; });
    }).always(() => tbody.parent().removeClass('loading'));
}

function loadLeaderboard() {
    const tbody = $('#leaderboardTable tbody');
    tbody.parent().addClass('loading');
    apiGet('/api/leaderboard.php').done(list => {
        tbody.empty();
        list.forEach((u,i) => tbody.append(`<tr style="display:none"><td>${i+1}</td><td>${u.name}</td><td>${u.total_score}</td></tr>`));
        tbody.find('tr').each((i, row) => $(row).delay(i*50).fadeIn(200));
    }).always(() => tbody.parent().removeClass('loading'));
}