import axios from 'axios';

/**
 * Cliente HTTP único para toda la app.
 * Todas las llamadas van a Laravel (/api/...); nunca directamente a Gemini.
 */
const http = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true, // necesario para la sesión del docente (Sanctum SPA)
    withXSRFToken: true,
});

export default http;
