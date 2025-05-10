// Save JWT to localStorage
function saveToken(token) {
    localStorage.setItem('token', token);
}

// Parse JWT payload
function parseJWT(token) {
    const payload = token.split('.')[1];
    const decoded = atob(payload.replace(/-/g, '+').replace(/_/g, '/'));
    return JSON.parse(decoded);
}

// Logout user
function logout() {
    localStorage.removeItem('token');
    window.location.href = 'login.html';
}