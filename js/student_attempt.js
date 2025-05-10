$(document).ready(function() {
    const token = getToken(); if (!token) return logout();
    const user = parseJWT(token); if (user.role !== 'student') return logout();
    $('#logoutBtn').click(logout);

    const params = new URLSearchParams(window.location.search);
    const attemptId = params.get('attempt_id');
    if (!attemptId) return alert('No attempt specified');

    let quizId;
    // Load attempt info
    apiGet('/api/student_quizzes.php', { attempt_id: attemptId }).done(attempt => {
        quizId = attempt.quiz_id;
        $('#attemptInfo').html(`<p>Status: ${attempt.status}</p>`);
        if (attempt.status === 'graded') {
            $('#submitBtn').hide();
            $('#resultSection').show();
            $('#scoreDisplay').text(attempt.score);
        }
        // Load questions
        apiGet('/api/questions.php', { quiz_id: quizId }).done(questions => renderQuestions(questions, attempt.status));
    }).fail(err => alert(err.responseJSON?.error));

    $('#answerForm').submit(function(e) {
        e.preventDefault();
        const answers = [];
        $('#questionsContainer .question').each(function() {
            const qid = $(this).data('qid');
            const type = $(this).data('type');
            let choice_id = null, answer_text = null;
            if (type === 'multiple_choice' || type === 'true_false') {
                choice_id = $(this).find('input[name="q_'+qid+'"]:checked').val();
            } else if (type === 'fill_blank') {
                answer_text = $(this).find('input[name="q_'+qid+'"]').val();
            }
            answers.push({ question_id: qid, choice_id: choice_id, answer_text: answer_text });
        });
        apiPost('/api/answers.php', { attempt_id: attemptId, answers: answers })
            .done(res => {
                $('#submitBtn').hide();
                $('#resultSection').show();
                $('#scoreDisplay').text(res.score);
            })
            .fail(err => alert(err.responseJSON?.error));
    });
});

function renderQuestions(questions, status) {
    const container = $('#questionsContainer').empty();
    questions.forEach(q => {
        const div = $(`<div class="question" data-qid="${q.id}" data-type="${q.type}"><p>${q.prompt}</p></div>`);
        if (q.type === 'multiple_choice' || q.type === 'true_false') {
            q.choices.forEach(c => {
                const input = $(`<label><input type="radio" name="q_${q.id}" value="${c.id}" ${status!=='in_progress'? 'disabled':''}> ${c.content}</label><br>`);
                div.append(input);
            });
        } else if (q.type === 'fill_blank') {
            const input = $(`<input type="text" name="q_${q.id}" ${status!=='in_progress'? 'disabled':''}><br>`);
            div.append(input);
        }
        container.append(div);
    });
}