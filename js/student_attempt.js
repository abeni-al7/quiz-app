$(document).ready(function() {
    const questions = $('.question');
    const total = questions.length;
    let current = 0;

    function showQuestion(index) {
        questions.hide();
        $(questions[index]).show();
        // progress bar
        const percent = ((index) / total) * 100;
        $('.progress').css('width', percent + '%');
        // buttons
        $('#prevBtn').toggle(index > 0);
        if (index < total - 1) {
            $('#nextBtn').show();
            $('#submitBtn').hide();
        } else {
            $('#nextBtn').hide();
            $('#submitBtn').show();
        }
    }

    // initialize
    questions.hide();
    showQuestion(0);

    // Handle choice selection
    $('.choice-option input[type=radio]').change(function() {
        const container = $(this).closest('.choice-option');
        // clear selection on siblings
        container.siblings().removeClass('selected');
        container.addClass('selected');
    });

    $('#nextBtn').click(function() {
        if (current < total - 1) {
            current++;
            showQuestion(current);
        }
    });

    $('#prevBtn').click(function() {
        if (current > 0) {
            current--;
            showQuestion(current);
        }
    });

    // animate progress bar on load
    setTimeout(() => {
        $('.progress-bar').fadeIn(300);
    }, 100);
});