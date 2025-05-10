// Helper to get JWT from storage
function getToken() {
    return localStorage.getItem('token');
}

// Generic AJAX request
function apiRequest(method, url, data) {
    return $.ajax({
        url: url,
        method: method,
        contentType: 'application/json',
        data: data ? JSON.stringify(data) : null,
        dataType: 'json',
        headers: {
            'Authorization': 'Bearer ' + getToken()
        }
    });
}

// GET with optional params
function apiGet(url, params) {
    if (params) url += '?' + $.param(params);
    return apiRequest('GET', url);
}

// POST
function apiPost(url, data) {
    return apiRequest('POST', url, data);
}

// PUT
function apiPut(url, data) {
    return apiRequest('PUT', url, data);
}