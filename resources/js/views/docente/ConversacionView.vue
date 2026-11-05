<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { finalizarSesionAdmin, obtenerSesionAdmin, reabrirSesionAdmin } from '@/api/admin';

const route = useRoute();
const sesionId = computed(() => Number(route.params.id));

const sesion = ref(null);
const cargando = ref(true);
const finalizando = ref(false);
const errorMensaje = ref('');

const preguntasAlumnosCount = computed(() =>
    (sesion.value?.mensajes || []).filter((m) => m.rol === 'alumno').length,
);

function formatearFechaHora(iso) {
    if (!iso) return '—';
    try {
        return new Date(iso).toLocaleString('es-AR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    } catch {
        return iso;
    }
}

async function cargar() {
    cargando.value = true;
    errorMensaje.value = '';
    try {
        sesion.value = await obtenerSesionAdmin(sesionId.value);
    } catch {
        errorMensaje.value = 'No se pudo cargar la conversación.';
    } finally {
        cargando.value = false;
    }
}

async function cerrarSesion() {
    if (!confirm('¿Finalizar esta entrevista? El equipo ya no podrá enviar más preguntas.')) {
        return;
    }
    finalizando.value = true;
    try {
        sesion.value = await finalizarSesionAdmin(sesionId.value);
    } catch {
        errorMensaje.value = 'No se pudo finalizar la sesión.';
    } finally {
        finalizando.value = false;
    }
}

async function reabrirSesion() {
    if (!confirm('¿Reabrir esta entrevista? El equipo podrá seguir la conversación al volver a ingresar con su código.')) {
        return;
    }
    finalizando.value = true;
    errorMensaje.value = '';
    try {
        sesion.value = await reabrirSesionAdmin(sesionId.value);
    } catch (error) {
        errorMensaje.value = error.response?.data?.message || 'No se pudo reabrir la sesión.';
    } finally {
        finalizando.value = false;
    }
}

onMounted(cargar);
</script>

<template>
    <div class="space-y-5 pb-10">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <RouterLink :to="{ name: 'docente-sesiones' }" class="text-xs font-semibold text-indigo-600 hover:underline">
                    ← Volver a sesiones
                </RouterLink>
                <h1 class="mt-1 text-xl font-bold text-slate-900">
                    Revisión de entrevista
                </h1>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                    @click="cargar"
                >
                    Actualizar
                </button>
                <button
                    v-if="sesion?.estado === 'activa'"
                    type="button"
                    :disabled="finalizando"
                    class="cursor-pointer rounded-xl bg-rose-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-rose-700 disabled:opacity-60"
                    @click="cerrarSesion"
                >
                    {{ finalizando ? 'Finalizando…' : 'Finalizar sesión' }}
                </button>
                <button
                    v-else-if="sesion"
                    type="button"
                    :disabled="finalizando"
                    class="cursor-pointer rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-60"
                    @click="reabrirSesion"
                >
                    {{ finalizando ? 'Reabriendo…' : 'Reabrir sesión' }}
                </button>
            </div>
        </div>

        <div v-if="errorMensaje" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
            {{ errorMensaje }}
        </div>

        <div v-if="cargando" class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
            Cargando conversación…
        </div>

        <template v-else-if="sesion">
            <!-- Resumen de la sesión -->
            <div class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs sm:grid-cols-4">
                <div>
                    <p class="text-xs font-medium text-slate-500">Equipo</p>
                    <p class="mt-0.5 font-bold text-slate-900">
                        {{ sesion.equipo?.nombre }}
                        <span class="font-mono text-xs font-normal text-slate-500">({{ sesion.equipo?.codigo }})</span>
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-500">Escenario</p>
                    <p class="mt-0.5 font-semibold text-slate-900">{{ sesion.escenario?.nombre }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-500">Inicio / Fin</p>
                    <p class="mt-0.5 text-xs text-slate-700">
                        Inicio: {{ formatearFechaHora(sesion.inicio) }}<br />
                        Fin: {{ formatearFechaHora(sesion.fin) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-500">Estado y actividad</p>
                    <div class="mt-1 flex items-center gap-2">
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                            :class="
                                sesion.estado === 'activa'
                                    ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20'
                                    : 'bg-slate-100 text-slate-600 ring-1 ring-slate-500/20'
                            "
                        >
                            {{ sesion.estado_etiqueta }}
                        </span>
                        <span class="text-xs text-slate-600">
                            {{ preguntasAlumnosCount }} preguntas del equipo
                        </span>
                    </div>
                </div>
            </div>

            <!-- Transcripción cronológica fácil de leer -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs">
                <h2 class="border-b border-slate-100 pb-3 text-sm font-bold text-slate-800">
                    Conversación completa ({{ sesion.mensajes?.length || 0 }} mensajes)
                </h2>

                <div class="mt-4 divide-y divide-slate-100">
                    <div
                        v-for="mensaje in sesion.mensajes"
                        :key="mensaje.id"
                        class="py-4 first:pt-0 last:pb-0"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span
                                class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold tracking-wide uppercase"
                                :class="{
                                    'bg-indigo-50 text-indigo-700': mensaje.rol === 'alumno',
                                    'bg-emerald-50 text-emerald-800': mensaje.rol === 'cliente',
                                    'bg-slate-100 text-slate-600': mensaje.rol === 'sistema',
                                }"
                            >
                                {{ mensaje.rol === 'alumno' ? `ALUMNO (${sesion.equipo?.nombre})` : mensaje.rol_etiqueta.toUpperCase() }}
                            </span>

                            <div class="flex items-center gap-3 text-[11px] text-slate-400">
                                <span v-if="mensaje.metadata?.modelo">Modelo: {{ mensaje.metadata.modelo }}</span>
                                <span v-if="mensaje.metadata?.latencia_ms">{{ mensaje.metadata.latencia_ms }} ms</span>
                                <span>{{ formatearFechaHora(mensaje.created_at) }}</span>
                            </div>
                        </div>

                        <p class="mt-2 text-sm leading-relaxed whitespace-pre-wrap text-slate-800">
                            {{ mensaje.contenido }}
                        </p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
