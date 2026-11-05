<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import {
    actualizarEscenario,
    duplicarEscenario,
    eliminarEscenario,
    listarEscenarios,
    obtenerPromptEscenario,
} from '@/api/admin';

const router = useRouter();
const escenarios = ref([]);
const cargando = ref(true);
const errorMensaje = ref('');

const modalPrompt = ref(false);
const promptActual = ref({ nombre: '', prompt: '' });
const cargandoPrompt = ref(false);

async function cargar() {
    cargando.value = true;
    errorMensaje.value = '';
    try {
        escenarios.value = await listarEscenarios();
    } catch {
        errorMensaje.value = 'No se pudieron cargar los escenarios.';
    } finally {
        cargando.value = false;
    }
}

async function alternarActivo(esc) {
    try {
        const actualizado = await actualizarEscenario(esc.id, {
            ...esc,
            activo: !esc.activo,
        });
        const idx = escenarios.value.findIndex((e) => e.id === esc.id);
        if (idx !== -1) escenarios.value[idx] = actualizado;
    } catch {
        errorMensaje.value = 'No se pudo cambiar el estado del escenario.';
    }
}

async function duplicar(esc) {
    try {
        await duplicarEscenario(esc.id);
        await cargar();
    } catch {
        errorMensaje.value = 'No se pudo duplicar el escenario.';
    }
}

async function verPrompt(esc) {
    modalPrompt.value = true;
    cargandoPrompt.value = true;
    promptActual.value = { nombre: esc.nombre, prompt: '' };
    try {
        promptActual.value = await obtenerPromptEscenario(esc.id);
    } catch {
        promptActual.value = { nombre: esc.nombre, prompt: 'Error al generar la vista previa del prompt.' };
    } finally {
        cargandoPrompt.value = false;
    }
}

async function borrar(esc) {
    if (!confirm(`¿Eliminar el escenario "${esc.nombre}"? Las sesiones históricas se conservarán.`)) {
        return;
    }
    try {
        await eliminarEscenario(esc.id);
        await cargar();
    } catch {
        errorMensaje.value = 'No se pudo eliminar el escenario.';
    }
}

onMounted(cargar);
</script>

<template>
    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Escenarios de simulación</h1>
                <p class="text-xs text-slate-500">
                    Configurá el rol del cliente, la información del caso, las reglas de negocio y el nivel de dificultad.
                </p>
            </div>
            <button
                type="button"
                class="cursor-pointer rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700"
                @click="router.push({ name: 'docente-escenario-nuevo' })"
            >
                + Nuevo escenario
            </button>
        </div>

        <div v-if="errorMensaje" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
            {{ errorMensaje }}
        </div>

        <div v-if="cargando" class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
            Cargando escenarios…
        </div>

        <div v-else-if="escenarios.length === 0" class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
            Todavía no hay escenarios cargados.
        </div>

        <div v-else class="grid gap-4">
            <div
                v-for="esc in escenarios"
                :key="esc.id"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs transition hover:border-slate-300"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-bold text-slate-900">{{ esc.nombre }}</h2>
                            <span
                                class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="{
                                    'bg-sky-50 text-sky-700 ring-1 ring-sky-600/20': esc.dificultad === 'basica',
                                    'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20': esc.dificultad === 'intermedia',
                                    'bg-purple-50 text-purple-700 ring-1 ring-purple-600/20': esc.dificultad === 'avanzada',
                                }"
                            >
                                Dificultad: {{ esc.dificultad_etiqueta }}
                            </span>
                            <button
                                type="button"
                                class="cursor-pointer rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="
                                    esc.activo
                                        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20'
                                        : 'bg-slate-100 text-slate-600 ring-1 ring-slate-500/20'
                                "
                                @click="alternarActivo(esc)"
                            >
                                {{ esc.activo ? 'Activo' : 'Inactivo' }}
                            </button>
                        </div>

                        <p v-if="esc.descripcion" class="mt-1.5 text-sm text-slate-600">
                            {{ esc.descripcion }}
                        </p>
                        <p class="mt-2 text-xs text-slate-500">
                            <strong>Rol del cliente:</strong> {{ esc.rol_cliente }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            class="cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100"
                            @click="verPrompt(esc)"
                        >
                            Ver prompt IA
                        </button>
                        <button
                            type="button"
                            class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                            @click="duplicar(esc)"
                        >
                            Duplicar
                        </button>
                        <button
                            type="button"
                            class="cursor-pointer rounded-xl bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                            @click="router.push({ name: 'docente-escenario-editar', params: { id: esc.id } })"
                        >
                            Editar
                        </button>
                        <button
                            type="button"
                            class="cursor-pointer rounded-xl px-2.5 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50"
                            @click="borrar(esc)"
                        >
                            Eliminar
                        </button>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-4 border-t border-slate-100 pt-3 text-xs text-slate-500">
                    <span>Equipos asignados: <strong>{{ esc.equipos_count ?? 0 }}</strong></span>
                    <span>Sesiones realizadas: <strong>{{ esc.sesiones_count ?? 0 }}</strong></span>
                    <span>Necesidades: <strong>{{ esc.necesidades?.length ?? 0 }}</strong></span>
                    <span>Actores: <strong>{{ esc.actores?.length ?? 0 }}</strong></span>
                    <span>Reglas de negocio: <strong>{{ esc.reglas_negocio?.length ?? 0 }}</strong></span>
                </div>
            </div>
        </div>

        <!-- Modal Vista Previa del Prompt -->
        <div
            v-if="modalPrompt"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
        >
            <div class="flex max-h-[85vh] w-full max-w-3xl flex-col rounded-2xl bg-white shadow-xl ring-1 ring-slate-200">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Prompt enviado a Gemini</h3>
                        <p class="text-xs text-slate-500">{{ promptActual.nombre }}</p>
                    </div>
                    <button
                        type="button"
                        class="cursor-pointer rounded-lg px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                        @click="modalPrompt = false"
                    >
                        Cerrar
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-6">
                    <p v-if="cargandoPrompt" class="text-sm text-slate-500">Generando prompt…</p>
                    <pre
                        v-else
                        class="rounded-xl bg-slate-900 p-4 font-mono text-xs leading-relaxed whitespace-pre-wrap text-slate-100"
                    >{{ promptActual.prompt }}</pre>
                </div>
            </div>
        </div>
    </div>
</template>
