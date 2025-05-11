$(document).ready(function() {
    const token = getToken();
    if (!token) return logout();
    const user = parseJWT(token);
    if (user.role !== 'student') return logout();

    $('#logoutBtn').click(logout);
    $('#dashboardBtn').click(() => window.location.href = '/student/index.html');

    apiGet('/api/leaderboard.php').done(list => {
        const tbody = $('#leaderboardTable tbody').empty();
        list.forEach((u, i) => {
            tbody.append(
                `<tr><td>${i+1}</td><td>${u.name}</td><td>${u.total_score}</td></tr>`
            );
        });
    }).fail(err => alert(err.responseJSON?.error || 'Failed to load leaderboard'));
});