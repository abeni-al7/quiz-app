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
        // If graded, hide submit and show result
        let answersMap = {};
        if (attempt.status === 'graded') {
            $('#submitBtn').hide();
            $('#resultSection').show();
            $('#scoreDisplay').text(attempt.score);
            // Fetch student answers for highlighting
            apiGet('/api/answers.php', { attempt_id: attemptId }).done(answers => {
                answers.forEach(a => { answersMap[a.question_id] = a; });
                // Now load and render questions with answersMap
                apiGet('/api/questions.php', { quiz_id: quizId }).done(questions => renderQuestions(questions, attempt.status, answersMap));
            });
        } else {
            // Not graded: just load questions
            apiGet('/api/questions.php', { quiz_id: quizId }).done(questions => renderQuestions(questions, attempt.status));
        }
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

// Handle click on choice-option to select whole box
$(document).on('click', '.choice-option', function(e) {
    const input = $(this).find('input');
    if (input.is(':disabled')) return;
    if (input.attr('type') === 'radio') {
        input.prop('checked', true);
        // Highlight selection
        $(this).siblings('.choice-option').removeClass('selected');
        $(this).addClass('selected');
    } else if (input.attr('type') === 'text') {
        input.focus();
        $(this).addClass('selected');
    }
});

function renderQuestions(questions, status, answersMap = {}) {
    const container = $('#questionsContainer').empty();
    questions.forEach(q => {
        const div = $(`<div class="question" data-qid="${q.id}" data-type="${q.type}"><p>${q.prompt}</p></div>`);
        if (q.type === 'multiple_choice' || q.type === 'true_false') {
            q.choices.forEach(c => {
                const checked = answersMap[q.id] && answersMap[q.id].chosen_choice_id == c.id ? 'checked' : '';
                const disabled = status !== 'in_progress' ? 'disabled' : '';
                // Wrap in full-width choice box
                const option = $(`
                    <label class="choice-option">
                        <input type="radio" name="q_${q.id}" value="${c.id}" ${checked} ${disabled}>
                        <span>${c.content}</span>
                    </label>
                `);
                // Highlight correct and wrong when graded
                if (status === 'graded') {
                    const ans = answersMap[q.id];
                    if (c.id == ans.chosen_choice_id) {
                        option.addClass(ans.is_correct ? 'correct-answer' : 'incorrect-answer');
                    } else if (c.is_correct) {
                        option.addClass('correct-answer');
                    }
                }
                div.append(option);
            });
        } else if (q.type === 'fill_blank') {
            const answerText = answersMap[q.id] ? answersMap[q.id].answer_text : '';
            const disabled = status !== 'in_progress' ? 'disabled' : '';
            // Wrap fill blank in choice-style box for uniformity
            const wrapper = $(`<div class="choice-option"></div>`);
            const input = $(`<input type="text" name="q_${q.id}" value="${answerText}" ${disabled}>`);
            wrapper.append(input);
            if (status === 'graded') {
                const ans = answersMap[q.id];
                wrapper.addClass(ans.is_correct ? 'fill-correct' : 'fill-incorrect');
            }
            div.append(wrapper);
        }
        container.append(div);
    });
}