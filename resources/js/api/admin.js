import http from './http';

const DOCENTE_TOKEN_KEY = 'simulador_docente_token';
const DOCENTE_INFO_KEY = 'simulador_docente_info';

export function guardarSesionDocente(token, docente) {
    if (token) {
        localStorage.setItem(DOCENTE_TOKEN_KEY, token);
    }
    if (docente) {
        localStorage.setItem(DOCENTE_INFO_KEY, JSON.stringify(docente));
    }
}

export function limpiarSesionDocente() {
    localStorage.removeItem(DOCENTE_TOKEN_KEY);
    localStorage.removeItem(DOCENTE_INFO_KEY);
}

export function obtenerTokenDocente() {
    return localStorage.getItem(DOCENTE_TOKEN_KEY) || '';
}

export function obtenerDocenteGuardado() {
    try {
        const raw = localStorage.getItem(DOCENTE_INFO_KEY);
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

function authConfig(params = undefined) {
    const token = obtenerTokenDocente();
    return {
        ...(params ? { params } : {}),
        headers: token ? { Authorization: `Bearer ${token}` } : {},
    };
}

// --- Autenticación ---

export async function loginDocente(email, password) {
    const { data } = await http.post('/docente/login', { email, password });
    guardarSesionDocente(data.token, data.docente);
    return data;
}

export async function logoutDocente() {
    try {
        await http.post('/docente/logout', {}, authConfig());
    } finally {
        limpiarSesionDocente();
    }
}

export async function obtenerPerfilDocente() {
    const { data } = await http.get('/docente/yo', authConfig());
    return data;
}

// --- Equipos ---

export async function listarEquipos(filtros = {}) {
    const { data } = await http.get('/admin/equipos', authConfig(filtros));
    return data.data;
}

export async function crearEquipo(payload) {
    const { data } = await http.post('/admin/equipos', payload, authConfig());
    return data.data;
}

export async function actualizarEquipo(id, payload) {
    const { data } = await http.put(`/admin/equipos/${id}`, payload, authConfig());
    return data.data;
}

export async function eliminarEquipo(id) {
    const { data } = await http.delete(`/admin/equipos/${id}`, authConfig());
    return data;
}

export async function regenerarCodigoEquipo(id) {
    const { data } = await http.post(`/admin/equipos/${id}/regenerar-codigo`, {}, authConfig());
    return data.data;
}

// --- Escenarios ---

export async function listarEscenarios(filtros = {}) {
    const { data } = await http.get('/admin/escenarios', authConfig(filtros));
    return data.data;
}

export async function obtenerEscenario(id) {
    const { data } = await http.get(`/admin/escenarios/${id}`, authConfig());
    return data.data;
}

export async function crearEscenario(payload) {
    const { data } = await http.post('/admin/escenarios', payload, authConfig());
    return data.data;
}

export async function actualizarEscenario(id, payload) {
    const { data } = await http.put(`/admin/escenarios/${id}`, payload, authConfig());
    return data.data;
}

export async function eliminarEscenario(id) {
    const { data } = await http.delete(`/admin/escenarios/${id}`, authConfig());
    return data;
}

export async function duplicarEscenario(id) {
    const { data } = await http.post(`/admin/escenarios/${id}/duplicar`, {}, authConfig());
    return data.data;
}

export async function obtenerPromptEscenario(id) {
    const { data } = await http.get(`/admin/escenarios/${id}/prompt`, authConfig());
    return data;
}

// --- Sesiones ---

export async function listarSesiones(filtros = {}) {
    const { data } = await http.get('/admin/sesiones', authConfig(filtros));
    return data.data;
}

export async function obtenerSesionAdmin(id) {
    const { data } = await http.get(`/admin/sesiones/${id}`, authConfig());
    return data.data;
}

export async function finalizarSesionAdmin(id) {
    const { data } = await http.post(`/admin/sesiones/${id}/finalizar`, {}, authConfig());
    return data.data;
}

// --- Interruptor del simulador ---

export async function obtenerEstadoSimulador() {
    const { data } = await http.get('/admin/simulador', authConfig());
    return data;
}

export async function abrirSimulador(horas) {
    const { data } = await http.post('/admin/simulador/abrir', { horas }, authConfig());
    return data;
}

export async function cerrarSimulador() {
    const { data } = await http.post('/admin/simulador/cerrar', {}, authConfig());
    return data;
}
