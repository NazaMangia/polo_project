<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import {
    abrirSimulador,
    cerrarSimulador,
    logoutDocente,
    obtenerDocenteGuardado,
    obtenerEstadoSimulador,
} from '@/api/admin';

const router = useRouter();
const docente = ref(obtenerDocenteGuardado());

// Interruptor general: los alumnos solo pueden ingresar y preguntar mientras esté abierto.
const simulador = ref(null);
const horasApertura = ref(2);
const cambiandoSimulador = ref(false);
let intervaloSimulador = null;

async function actualizarSimulador() {
    try {
        simulador.value = await obtenerEstadoSimulador();
    } catch {
        // Si falla (ej. token vencido) el interceptor del router ya redirige al login.
    }
}

async function alternarSimulador() {
    if (cambiandoSimulador.value) return;
    cambiandoSimulador.value = true;
    try {
        simulador.value = simulador.value?.abierto
            ? await cerrarSimulador()
            : await abrirSimulador(horasApertura.value);
    } finally {
        cambiandoSimulador.value = false;
    }
}

function formatearHora(iso) {
    return iso ? new Date(iso).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' }) : '';
}

onMounted(() => {
    actualizarSimulador();
    // Refresca cada minuto para reflejar el cierre automático al vencer el horario.
    intervaloSimulador = setInterval(actualizarSimulador, 60000);
});

onUnmounted(() => clearInterval(intervaloSimulador));

async function salir() {
    await logoutDocente();
    router.push({ name: 'docente-login' });
}
</script>

<template>
    <div class="min-h-screen bg-slate-100">
        <!-- Barra de navegación superior -->
        <header class="border-b border-slate-200 bg-white shadow-2xs">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <div class="flex items-center gap-6">
                    <RouterLink :to="{ name: 'docente-sesiones' }" class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-xs font-bold text-white">
                            GP
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900">Panel Docente</p>
                            <p class="text-[11px] text-slate-500">Simulador de Entrevistas</p>
                        </div>
                    </RouterLink>

                    <nav class="flex items-center gap-1">
                        <RouterLink
                            :to="{ name: 'docente-sesiones' }"
                            class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                            active-class="bg-indigo-50 text-indigo-700 font-semibold"
                        >
                            Sesiones
                        </RouterLink>
                        <RouterLink
                            :to="{ name: 'docente-equipos' }"
                            class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                            active-class="bg-indigo-50 text-indigo-700 font-semibold"
                        >
                            Equipos
                        </RouterLink>
                        <RouterLink
                            :to="{ name: 'docente-escenarios' }"
                            class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                            active-class="bg-indigo-50 text-indigo-700 font-semibold"
                        >
                            Escenarios
                        </RouterLink>
                    </nav>
                </div>

                <div class="flex items-center gap-3">
                    <RouterLink
                        :to="{ name: 'inicio' }"
                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                    >
                        Vista del alumno
                    </RouterLink>
                    <span v-if="docente?.nombre" class="hidden text-xs text-slate-500 sm:inline">
                        {{ docente.nombre }}
                    </span>
                    <button
                        type="button"
                        class="cursor-pointer rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-200"
                        @click="salir"
                    >
                        Cerrar sesión
                    </button>
                </div>
            </div>
            <!-- Interruptor del simulador -->
            <div
                v-if="simulador"
                class="border-t"
                :class="simulador.abierto ? 'border-emerald-100 bg-emerald-50' : 'border-slate-100 bg-slate-50'"
            >
                <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-2.5 sm:px-6">
                    <div class="flex items-center gap-2.5 text-sm">
                        <span
                            class="h-2.5 w-2.5 rounded-full"
                            :class="simulador.abierto ? 'animate-pulse bg-emerald-500' : 'bg-slate-400'"
                        ></span>
                        <span v-if="simulador.abierto" class="text-emerald-800">
                            <strong>Simulador abierto</strong> · los equipos pueden ingresar y preguntar hasta las
                            {{ formatearHora(simulador.abierto_hasta) }} (se cierra solo).
                        </span>
                        <span v-else class="text-slate-700">
                            <strong>Simulador cerrado</strong> · los equipos no pueden ingresar ni hacer preguntas.
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <label v-if="!simulador.abierto" class="flex items-center gap-1.5 text-xs text-slate-600">
                            Abrir por
                            <select
                                v-model.number="horasApertura"
                                :disabled="cambiandoSimulador"
                                class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs"
                            >
                                <option v-for="h in [1, 2, 3, 4]" :key="h" :value="h">{{ h }} {{ h === 1 ? 'hora' : 'horas' }}</option>
                            </select>
                        </label>
                        <button
                            type="button"
                            :disabled="cambiandoSimulador"
                            class="cursor-pointer rounded-lg px-3.5 py-1.5 text-xs font-semibold text-white transition disabled:opacity-60"
                            :class="simulador.abierto ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700'"
                            @click="alternarSimulador"
                        >
                            {{ simulador.abierto ? 'Cerrar ahora' : 'Abrir simulador' }}
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Contenido de cada sección -->
        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
            <RouterView />
        </main>
    </div>
</template>
