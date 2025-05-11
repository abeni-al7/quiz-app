$(document).ready(function() {
    const token = getToken();
    if (!token) return logout();
    const user = parseJWT(token);
    if (user.role !== 'student') return logout();

    $('#logoutBtn').click(logout);
    $('#leaderboardBtn').click(function() { window.location.href = '/student/leaderboard.html'; });

    // Load subjects for start and filter
    apiGet('/api/subjects.php').done(list => {
        const startSub = $('#startForm select[name=subject_sel]').empty().append('<option value="">Select Subject</option>');
        const filtSub = $('#filterForm select[name=subject_id]').empty().append('<option value="">All Subjects</option>');
        list.forEach(s => {
            startSub.append(`<option value="${s.id}">${s.title}</option>`);
            filtSub.append(`<option value="${s.id}">${s.title}</option>`);
        });
    });

    // On subject change, load quizzes
    $('#startForm select[name=subject_sel]').change(function() {
        const sid = this.value;
        const qs = $('#startForm select[name=quiz_sel]').empty().append('<option value="">Select Quiz</option>');
        if (!sid) return;
        apiGet('/api/quizzes.php', { subject_id: sid }).done(list => {
            list.forEach(q => qs.append(`<option value="${q.id}">${q.title}</option>`));
        });
    });

    // Start quiz
    $('#startForm').submit(function(e) {
        e.preventDefault();
        const qid = this.quiz_sel.value;
        if (!qid) return alert('Select a quiz');
        apiPost('/api/student_quizzes.php', { quiz_id: qid })
            .done(res => window.location.href = `/student/attempt.html?attempt_id=${res.attempt_id}`)
            .fail(err => alert(err.responseJSON?.error));
    });

    // Filter attempts
    $('#filterForm').submit(function(e) {
        e.preventDefault();
        loadAttempts($(this).serializeArray());
    });

    // Initial load
    loadAttempts();
});

function loadAttempts(filters) {
    const params = {};
    if (filters) filters.forEach(f => { if (f.value) params[f.name] = f.value; });
    apiGet('/api/student_quizzes.php', params).done(list => {
        const tbody = $('#quizListTable tbody').empty();
        list.forEach(a => {
            let action = '';
            if (a.status === 'in_progress') action = `<button data-id="${a.id}" class="contBtn">Continue</button>`;
            else if (a.status === 'completed') action = 'Awaiting grade';
            else if (a.status === 'graded') action = `<button data-id="${a.id}" class="viewBtn">View Result</button>`;
            tbody.append(`<tr><td>${a.quiz_title}</td><td>${a.subject_title}</td><td>${a.status}</td><td>${a.score}</td><td>${action}</td></tr>`);
        });
        $('.contBtn, .viewBtn').click(function() {
            const id = $(this).data('id');
            window.location.href = `/student/attempt.html?attempt_id=${id}`;
        });
    });
}