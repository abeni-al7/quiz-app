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

    // Question type change
    $('#questionType').change(renderChoiceInputs);
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
    $('.section').hide();
    $('#' + name).show();
}

function loadSubjects() {
    apiGet('/api/subjects.php').done(list => {
        const tbody = $('#subjectsTable tbody').empty();
        list.forEach(s => tbody.append(`<tr><td>${s.id}</td><td>${s.title}</td><td>${s.description}</td></tr>`));
        // Populate subject dropdowns
        const subjSel = $('#quizForm select[name=subject_id]').empty();
        list.forEach(s => subjSel.append(`<option value="${s.id}">${s.title}</option>`));
    });
}

function loadQuizzes() {
    apiGet('/api/quizzes.php').done(list => {
        const tbody = $('#quizzesTable tbody').empty();
        list.forEach(q => tbody.append(`<tr><td>${q.id}</td><td>${q.title}</td><td>${q.subject_title}</td></tr>`));
        const select = $('#quizSelect').empty().append('<option value="">--Select--</option>');
        list.forEach(q => select.append(`<option value="${q.id}">${q.title}</option>`));
    });
}

function renderChoiceInputs() {
    const type = this.value;
    const wrapper = $('#choicesWrapper').empty();
    if (type === 'multiple_choice') {
        for (let i=0; i<4; i++) {
            wrapper.append(`<div><input type="text" class="choice-text" data-idx="${i}" placeholder="Choice ${i+1}"><label>Correct?<input type="checkbox" id="choice_${i}_correct"></label></div>`);
        }
    } else if (type === 'true_false') {
        ['True','False'].forEach((txt,i) => {
            wrapper.append(`<div><input type="hidden" class="choice-text" data-idx="${i}" value="${txt}"><span>${txt}</span><label>Correct?<input type="checkbox" id="choice_${i}_correct"></label></div>`);
        });
    }
}

function loadQuestions(quizId) {
    if (!quizId) return;
    apiGet('/api/questions.php', { quiz_id: quizId }).done(list => {
        const tbody = $('#questionsTable tbody').empty();
        list.forEach(q => tbody.append(`<tr><td>${q.id}</td><td>${q.type}</td><td>${q.prompt}</td></tr>`));
    });
}

function loadAttempts() {
    apiGet('/api/admin/attempts.php').done(list => {
        const tbody = $('#attemptsTable tbody').empty();
        list.forEach(a => {
            let btn = '';
            if (a.status === 'completed') btn = `<button data-id="${a.id}" class="gradeBtn">Grade</button>`;
            else btn = `<span>${a.status}</span>`;
            tbody.append(`<tr><td>${a.id}</td><td>${a.student_name}</td><td>${a.quiz_title}</td><td>${a.status}</td><td>${btn}</td></tr>`);
        });
        $('.gradeBtn').click(function() { location.href = `/admin/grade.html?attempt_id=${$(this).data('id')}`; });
    });
}

function loadLeaderboard() {
    apiGet('/api/leaderboard.php').done(list => {
        const tbody = $('#leaderboardTable tbody').empty();
        list.forEach((u,i) => tbody.append(`<tr><td>${i+1}</td><td>${u.name}</td><td>${u.total_score}</td></tr>`));
    });
}