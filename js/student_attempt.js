$(document).ready(function() {
    const token = getToken(); if (!token) return logout();
    const user = parseJWT(token); if (user.role !== 'student') return logout();
    $('#logoutBtn').click(logout);

    // Extract attempt ID
    const params = new URLSearchParams(window.location.search);
    const attemptId = params.get('attempt_id'); if (!attemptId) return alert('No attempt specified');

    // State management
    let quizId, questionsData = [], answersMap = {}, currentIndex = 0;
    function loadQuestionsAndInit(status) {
        apiGet('/api/questions.php', { quiz_id: quizId }).done(list => {
            questionsData = list;
            initQuizView(status);
        });
    }

    // Fetch attempt and branch by status
    apiGet('/api/student_quizzes.php', { attempt_id: attemptId })
      .done(attempt => {
        quizId = attempt.quiz_id;
        $('#attemptInfo').text(`Status: ${attempt.status}`);
        if (attempt.status === 'graded') {
          // Show score and hide progress/navigation
          $('.progress-bar, .nav-buttons').hide();
          $('#resultSection').show().find('#scoreDisplay').text(attempt.score);
          // Load answers and questions, then render all
          apiGet('/api/answers.php', { attempt_id: attemptId }).done(ans => {
            ans.forEach(a => answersMap[a.question_id] = a);
            apiGet('/api/questions.php', { quiz_id: quizId }).done(list => renderResults(list, answersMap));
          });
        } else {
          // In-progress flow
          $('.progress-bar').show();
          loadQuestionsAndInit(attempt.status);
        }
      }).fail(err => alert(err.responseJSON?.error));

    function initQuizView(status) {
        $('#prevBtn, #nextBtn, #submitBtn').hide();
        updateProgress();
        showQuestion(0, status);
        $('#prevBtn').click(()=>showQuestion(currentIndex-1, status));
        $('#nextBtn').click(()=>showQuestion(currentIndex+1, status));
    }
    function updateProgress() {
        const percent = ((currentIndex+1)/questionsData.length)*100;
        $('.progress .progress').css('width', percent+'%');
    }
    function showQuestion(idx, status) {
        if (idx<0||idx>=questionsData.length) return;
        currentIndex = idx; updateProgress();
        const q = questionsData[idx];
        const cont = $('#questionsContainer').empty();
        const card = $(`<div class="quiz-card" data-qid="${q.id}" data-type="${q.type}"></div>`);
        card.append(`<div class="question-title">Question ${idx+1} of ${questionsData.length}</div>`);
        // Render choices
        q.choices.forEach(c=>{
            const checked = answersMap[q.id]&&answersMap[q.id].chosen_choice_id==c.id? 'checked':'';
            const disabled = status!=='in_progress'?'disabled':'';
            const opt = $(`<label class="choice-option"><input type="radio" name="q_${q.id}" value="${c.id}" ${checked} ${disabled}><span>${c.content}</span></label>`);
            if(status==='graded'){
                const ans=answersMap[q.id];
                if(c.id==ans.chosen_choice_id) opt.addClass(ans.is_correct?'correct-answer':'incorrect-answer');
                else if(c.is_correct) opt.addClass('correct-answer');
            }
            card.append(opt);
        });
        cont.append(card);
        // Show nav buttons
        if (status==='in_progress') {
            if(idx>0) $('#prevBtn').show();
            if(idx<questionsData.length-1) $('#nextBtn').show(); else $('#submitBtn').show();
        }
    }

    // Form submit
    $('#answerForm').submit(function(e) {
        e.preventDefault();
        // Collect all answers
        const answers = questionsData.map(q=>({question_id:q.id,choice_id:$(`[name="q_${q.id}"]:checked`).val()}));
        apiPost('/api/answers.php',{attempt_id:attemptId,answers}).done(res=>{
            $('#submitBtn').hide(); $('#resultSection').show().find('#scoreDisplay').text(res.score);
        }).fail(err=>alert(err.responseJSON?.error));
    });

    // Render full results list when graded
    function renderResults(questions, answersMap) {
        const cont = $('#questionsContainer').empty();
        questions.forEach((q, idx) => {
            const card = $(`<div class="quiz-card"><div class="question-title">Question ${idx+1} of ${questions.length}</div></div>`);
            // choices
            q.choices.forEach(c => {
                const correct = c.is_correct;
                const chosen = answersMap[q.id] && answersMap[q.id].chosen_choice_id == c.id;
                const opt = $(`<label class="choice-option"><input type="radio" disabled ${chosen?'checked':''}><span>${c.content}</span></label>`);
                // color feedback
                if (chosen) opt.addClass(correct ? 'correct-answer' : 'incorrect-answer');
                else if (correct) opt.addClass('correct-answer');
                card.append(opt);
            });
            cont.append(card);
        });
    }

    // Click handler to select a choice (in-progress only)
    $(document).on('click', '.choice-option', function() {
        const input = $(this).find('input[type=radio]');
        if (input.is(':disabled')) return;
        input.prop('checked', true);
        $(this).addClass('selected').siblings().removeClass('selected');
    });

});