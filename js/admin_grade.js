$(document).ready(function() {
    const token = getToken(); if (!token) return logout();
    const user = parseJWT(token); if (user.role !== 'admin') return logout();
    $('#logoutBtn').click(logout);

    const params = new URLSearchParams(window.location.search);
    const attemptId = params.get('attempt_id');
    if (!attemptId) return alert('No attempt specified');

    $('#gradeForm').hide();
    apiGet('/api/answers.php', { attempt_id: attemptId }).done(answers => {
        if (!answers.length) return $('#attemptInfo').text('No answers found');
        $('#gradeForm').show();
        const tbody = $('#gradeTable tbody').empty();
        answers.forEach(a => {
            const answerText = a.type === 'fill_blank' ? a.answer_text : a.choice_content;
            const checked = a.is_correct ? 'checked' : '';
            const row = $(
                `<tr data-answer-id="${a.answer_id}">
                    <td>${a.prompt}</td>
                    <td>${answerText}</td>
                    <td><input type="checkbox" class="grade-checkbox" ${checked}></td>
                </tr>`
            );
            tbody.append(row);
        });
    }).fail(err => alert(err.responseJSON?.error));

    $('#gradeForm').submit(function(e) {
        e.preventDefault();
        const grades = [];
        $('#gradeTable tbody tr').each(function() {
            const id = $(this).data('answer-id');
            const isCorrect = $(this).find('.grade-checkbox').is(':checked');
            grades.push({ answer_id: id, is_correct: isCorrect });
        });
        apiPut('/api/answers.php', { attempt_id: attemptId, grades: grades })
            .done(res => { alert('Grades submitted'); window.location.href = '/admin/index.html'; })
            .fail(err => alert(err.responseJSON?.error));
    });
});