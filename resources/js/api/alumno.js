import http from './http';

const STORAGE_PREFIX = 'simulador_sesion_token:';

export function guardarTokenSesion(uuid, token) {
    if (uuid && token) {
        localStorage.setItem(`${STORAGE_PREFIX}${uuid}`, token);
        localStorage.setItem('simulador_ultima_sesion_uuid', uuid);
    }
}

export function obtenerTokenSesion(uuid) {
    return localStorage.getItem(`${STORAGE_PREFIX}${uuid}`) || '';
}

export function obtenerUltimaSesionUuid() {
    return localStorage.getItem('simulador_ultima_sesion_uuid') || '';
}

function headersSesion(uuid) {
    return {
        'X-Sesion-Token': obtenerTokenSesion(uuid),
    };
}

export async function iniciarSesionPorCodigo(codigo) {
    const { data } = await http.post('/sesiones', { codigo });
    guardarTokenSesion(data.sesion.uuid, data.token);
    return data;
}

export async function obtenerSesion(uuid) {
    const { data } = await http.get(`/sesiones/${uuid}`, {
        headers: headersSesion(uuid),
    });
    return data.data;
}

export async function enviarMensaje(uuid, contenido) {
    const { data } = await http.post(
        `/sesiones/${uuid}/mensajes`,
        { contenido },
        { headers: headersSesion(uuid) },
    );
    return data;
}

export async function finalizarEntrevista(uuid) {
    const { data } = await http.post(
        `/sesiones/${uuid}/finalizar`,
        {},
        { headers: headersSesion(uuid) },
    );
    return data.data;
}

export async function obtenerEstadoSimulador() {
    const { data } = await http.get('/estado');
    return data.simulador;
}
