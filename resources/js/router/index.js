import { createRouter, createWebHistory } from 'vue-router';
import { limpiarSesionDocente, obtenerTokenDocente } from '@/api/admin';
import http from '@/api/http';

const routes = [
    {
        path: '/',
        name: 'inicio',
        component: () => import('@/views/alumno/InicioView.vue'),
    },
    {
        path: '/simulacion/:uuid',
        name: 'simulacion',
        component: () => import('@/views/alumno/SimulacionView.vue'),
    },
    {
        path: '/docente/login',
        name: 'docente-login',
        component: () => import('@/views/docente/LoginDocenteView.vue'),
    },
    {
        path: '/docente',
        component: () => import('@/layouts/DocenteLayout.vue'),
        meta: { requiereDocente: true },
        redirect: { name: 'docente-sesiones' },
        children: [
            {
                path: 'sesiones',
                name: 'docente-sesiones',
                component: () => import('@/views/docente/SesionesView.vue'),
            },
            {
                path: 'sesiones/:id',
                name: 'docente-conversacion',
                component: () => import('@/views/docente/ConversacionView.vue'),
            },
            {
                path: 'equipos',
                name: 'docente-equipos',
                component: () => import('@/views/docente/EquiposView.vue'),
            },
            {
                path: 'escenarios',
                name: 'docente-escenarios',
                component: () => import('@/views/docente/EscenariosView.vue'),
            },
            {
                path: 'escenarios/nuevo',
                name: 'docente-escenario-nuevo',
                component: () => import('@/views/docente/EscenarioFormView.vue'),
            },
            {
                path: 'escenarios/:id/editar',
                name: 'docente-escenario-editar',
                component: () => import('@/views/docente/EscenarioFormView.vue'),
            },
        ],
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'no-encontrada',
        component: () => import('@/views/NoEncontradaView.vue'),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Si una ruta del panel docente requiere sesión y no hay token, redirige a /docente/login.
router.beforeEach((to) => {
    if (to.matched.some((r) => r.meta?.requiereDocente) && !obtenerTokenDocente()) {
        return { name: 'docente-login' };
    }
    if (to.name === 'docente-login' && obtenerTokenDocente()) {
        return { name: 'docente-sesiones' };
    }
    return true;
});

// Si el token del docente venció o fue revocado (401 en /admin o /docente), limpia y envía al login.
http.interceptors.response.use(
    (response) => response,
    (error) => {
        const url = error.config?.url || '';
        if (error.response?.status === 401 && (url.includes('/admin') || url.includes('/docente/yo'))) {
            limpiarSesionDocente();
            router.push({ name: 'docente-login' });
        }
        return Promise.reject(error);
    },
);

export default router;
