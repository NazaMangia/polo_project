<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { enviarMensaje, finalizarEntrevista, obtenerSesion } from '@/api/alumno';

const route = useRoute();
const router = useRouter();
const uuid = computed(() => String(route.params.uuid || ''));

const sesion = ref(null);
const mensajes = ref([]);
const pregunta = ref('');

const cargandoSesion = ref(true);
const enviando = ref(false);
const finalizando = ref(false);
const confirmarCierre = ref(false);

const errorCarga = ref('');
const errorEnvio = ref('');

const contenedorMensajes = ref(null);
const inputPregunta = ref(null);

const estaActiva = computed(() => sesion.value?.estado === 'activa');
const maxCaracteres = computed(() => sesion.value?.limites?.max_caracteres_mensaje || 1000);
const maxMensajes = computed(() => sesion.value?.limites?.max_mensajes_sesion || 80);

function irAlFinal(suave = false) {
    nextTick(() => {
        const el = contenedorMensajes.value;
        if (!el) return;
        el.scrollTo({
            top: el.scrollHeight,
            behavior: suave ? 'smooth' : 'auto',
        });
    });
}

function formatearHora(iso) {
    if (!iso) return '';
    try {
        return new Date(iso).toLocaleTimeString('es-AR', {
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return '';
    }
}

async function cargar() {
    cargandoSesion.value = true;
    errorCarga.value = '';

    try {
        const datos = await obtenerSesion(uuid.value);
        sesion.value = datos;
        mensajes.value = datos.mensajes || [];
        irAlFinal(false);
    } catch (error) {
        const status = error.response?.status;
        if (status === 403 || status === 404) {
            errorCarga.value =
                error.response?.data?.message ||
                'No tenés acceso a esta sesión. Volvé al inicio e ingresá el código de tu equipo.';
        } else {
            errorCarga.value = 'No se pudo cargar la entrevista. Verificá tu conexión e intentá de nuevo.';
        }
    } finally {
        cargandoSesion.value = false;
    }
}

async function enviar() {
    const texto = pregunta.value.trim();
    if (!texto || enviando.value || !estaActiva.value) return;

    errorEnvio.value = '';
    enviando.value = true;

    // Mostramos la pregunta del alumno de inmediato de forma optimista;
    // si la IA falla, la quitamos y devolvemos el texto al campo para que pueda reintentar.
    const temporalId = `temp-${Date.now()}`;
    mensajes.value.push({
        id: temporalId,
        rol: 'alumno',
        rol_etiqueta: 'Alumno',
        contenido: texto,
        created_at: new Date().toISOString(),
        temporal: true,
    });
    pregunta.value = '';
    irAlFinal(true);

    try {
        const respuesta = await enviarMensaje(uuid.value, texto);
        const indice = mensajes.value.findIndex((m) => m.id === temporalId);
        if (indice !== -1) {
            mensajes.value.splice(indice, 1, respuesta.mensaje_alumno);
        } else {
            mensajes.value.push(respuesta.mensaje_alumno);
        }
        mensajes.value.push(respuesta.mensaje_cliente);
        irAlFinal(true);
    } catch (error) {
        const indice = mensajes.value.findIndex((m) => m.id === temporalId);
        if (indice !== -1) {
            mensajes.value.splice(indice, 1);
        }
        pregunta.value = texto;

        const errores = error.response?.data?.errors;
        if (errores?.contenido?.length) {
            errorEnvio.value = errores.contenido[0];
        } else if (error.response?.data?.message) {
            errorEnvio.value = error.response.data.message;
        } else {
            errorEnvio.value = 'No se pudo enviar el mensaje. Intentá nuevamente.';
        }

        if (error.response?.status === 409) {
            await cargar();
        }
    } finally {
        enviando.value = false;
        nextTick(() => inputPregunta.value?.focus());
    }
}

function manejarTecla(evento) {
    if (evento.key === 'Enter' && !evento.shiftKey) {
        evento.preventDefault();
        enviar();
    }
}

async function confirmarFinalizacion() {
    if (finalizando.value) return;
    finalizando.value = true;
    errorEnvio.value = '';

    try {
        const datos = await finalizarEntrevista(uuid.value);
        sesion.value = datos;
        mensajes.value = datos.mensajes || mensajes.value;
        confirmarCierre.value = false;
        irAlFinal(true);
    } catch (error) {
        errorEnvio.value = error.response?.data?.message || 'No se pudo finalizar la entrevista.';
    } finally {
        finalizando.value = false;
    }
}

onMounted(cargar);
</script>

<template>
    <div class="flex h-screen flex-col bg-slate-100">
        <!-- Estado de carga inicial -->
        <div v-if="cargandoSesion" class="flex flex-1 items-center justify-center p-6">
            <div class="rounded-2xl bg-white px-8 py-6 text-center shadow-sm ring-1 ring-slate-200">
                <p class="text-sm font-medium text-slate-700">Cargando entrevista…</p>
            </div>
        </div>

        <!-- Error de acceso o sesión inexistente -->
        <div v-else-if="errorCarga" class="flex flex-1 items-center justify-center p-6">
            <div class="max-w-md rounded-2xl bg-white p-7 text-center shadow-sm ring-1 ring-slate-200">
                <p class="text-base font-semibold text-slate-900">No se pudo abrir la sesión</p>
                <p class="mt-2 text-sm text-slate-600">{{ errorCarga }}</p>
                <button
                    type="button"
                    class="mt-5 cursor-pointer rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                    @click="router.push({ name: 'inicio' })"
                >
                    Volver al inicio
                </button>
            </div>
        </div>

        <!-- Pantalla de simulación -->
        <template v-else-if="sesion">
            <!-- Encabezado con información del escenario y equipo -->
            <header class="border-b border-slate-200 bg-white shadow-2xs">
                <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-md bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-600/20">
                                {{ sesion.equipo?.nombre }} ({{ sesion.equipo?.codigo }})
                            </span>
                            <span
                                class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="
                                    estaActiva
                                        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20'
                                        : 'bg-slate-100 text-slate-600 ring-1 ring-slate-500/20'
                                "
                            >
                                {{ sesion.estado_etiqueta }}
                            </span>
                        </div>
                        <h1 class="mt-1 truncate text-base font-bold text-slate-900 sm:text-lg">
                            {{ sesion.escenario?.nombre }}
                        </h1>
                        <p v-if="sesion.escenario?.rol_cliente" class="truncate text-xs text-slate-500">
                            Cliente entrevistado: <strong class="font-medium text-slate-700">{{ sesion.escenario.rol_cliente }}</strong>
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            v-if="estaActiva"
                            type="button"
                            class="cursor-pointer rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100"
                            @click="confirmarCierre = true"
                        >
                            Finalizar entrevista
                        </button>
                        <RouterLink
                            to="/"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                        >
                            Salir
                        </RouterLink>
                    </div>
                </div>

                <!-- Descripción breve del escenario -->
                <div v-if="sesion.escenario?.descripcion" class="border-t border-slate-100 bg-slate-50/70">
                    <div class="mx-auto max-w-5xl px-4 py-2 text-xs text-slate-600 sm:px-6">
                        {{ sesion.escenario.descripcion }}
                    </div>
                </div>
            </header>

            <!-- Historial de mensajes -->
            <main ref="contenedorMensajes" class="flex-1 overflow-y-auto px-4 py-6 sm:px-6">
                <div class="mx-auto max-w-3xl space-y-4">
                    <template v-for="mensaje in mensajes" :key="mensaje.id">
                        <!-- Aviso del sistema -->
                        <div v-if="mensaje.rol === 'sistema'" class="flex justify-center py-2">
                            <span class="rounded-full bg-slate-200/80 px-4 py-1 text-xs font-medium text-slate-600">
                                {{ mensaje.contenido }}
                            </span>
                        </div>

                        <!-- Burbuja de cliente o alumno -->
                        <div
                            v-else
                            class="flex flex-col"
                            :class="mensaje.rol === 'alumno' ? 'items-end' : 'items-start'"
                        >
                            <div class="mb-1 flex items-center gap-2 px-1 text-xs text-slate-500">
                                <span class="font-semibold" :class="mensaje.rol === 'alumno' ? 'text-indigo-700' : 'text-slate-700'">
                                    {{ mensaje.rol === 'alumno' ? sesion.equipo?.nombre : 'Cliente' }}
                                </span>
                                <span v-if="mensaje.created_at">{{ formatearHora(mensaje.created_at) }}</span>
                            </div>

                            <div
                                class="max-w-[85%] rounded-2xl px-4 py-3 text-sm leading-relaxed whitespace-pre-wrap shadow-2xs sm:max-w-[78%]"
                                :class="
                                    mensaje.rol === 'alumno'
                                        ? 'rounded-br-sm bg-indigo-600 text-white'
                                        : 'rounded-bl-sm border border-slate-200 bg-white text-slate-800'
                                "
                            >
                                {{ mensaje.contenido }}
                            </div>
                        </div>
                    </template>

                    <!-- Indicador de que la IA está procesando -->
                    <div v-if="enviando" class="flex flex-col items-start">
                        <span class="mb-1 px-1 text-xs font-semibold text-slate-700">Cliente</span>
                        <div class="inline-flex items-center gap-2.5 rounded-2xl rounded-bl-sm border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 shadow-2xs">
                            <span class="flex gap-1">
                                <span class="h-2 w-2 animate-bounce rounded-full bg-indigo-500 [animation-delay:-0.3s]"></span>
                                <span class="h-2 w-2 animate-bounce rounded-full bg-indigo-500 [animation-delay:-0.15s]"></span>
                                <span class="h-2 w-2 animate-bounce rounded-full bg-indigo-500"></span>
                            </span>
                            <span>El cliente está pensando su respuesta…</span>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Barra inferior para escribir o cartel de entrevista finalizada -->
            <footer class="border-t border-slate-200 bg-white">
                <div class="mx-auto max-w-3xl px-4 py-3 sm:px-6">
                    <div
                        v-if="errorEnvio"
                        role="alert"
                        class="mb-2.5 rounded-xl border border-red-200 bg-red-50 px-3.5 py-2 text-xs text-red-700"
                    >
                        {{ errorEnvio }}
                    </div>

                    <div v-if="estaActiva">
                        <form class="flex items-end gap-2.5" @submit.prevent="enviar">
                            <div class="flex-1">
                                <label for="campo-pregunta" class="sr-only">Escribí tu pregunta para el cliente</label>
                                <textarea
                                    id="campo-pregunta"
                                    ref="inputPregunta"
                                    v-model="pregunta"
                                    rows="2"
                                    :maxlength="maxCaracteres"
                                    :disabled="enviando"
                                    placeholder="Escribí una pregunta para el cliente… (Enter para enviar, Shift+Enter para nueva línea)"
                                    class="block w-full resize-none rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-600/20 focus:outline-none disabled:opacity-60"
                                    @keydown="manejarTecla"
                                ></textarea>
                            </div>

                            <button
                                type="submit"
                                :disabled="enviando || !pregunta.trim()"
                                class="cursor-pointer rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-xs transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {{ enviando ? 'Enviando…' : 'Enviar' }}
                            </button>
                        </form>

                        <div class="mt-1.5 flex items-center justify-between text-[11px] text-slate-400">
                            <span>Mensajes en la sesión: {{ mensajes.length }} / {{ maxMensajes }}</span>
                            <span>{{ pregunta.length }} / {{ maxCaracteres }} caracteres</span>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-100 px-4 py-3 text-sm text-slate-700"
                    >
                        <span>
                            Esta entrevista fue finalizada. Podés revisar toda la conversación arriba para armar tu
                            informe de relevamiento.
                        </span>
                        <RouterLink
                            to="/"
                            class="rounded-lg bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700"
                        >
                            Volver al inicio
                        </RouterLink>
                    </div>
                </div>
            </footer>

            <!-- Modal de confirmación para finalizar la entrevista -->
            <div
                v-if="confirmarCierre"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
            >
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
                    <h2 class="text-base font-bold text-slate-900">¿Finalizar la entrevista?</h2>
                    <p class="mt-2 text-sm text-slate-600">
                        Una vez finalizada, no podrán enviar más preguntas en esta sesión. La conversación quedará
                        guardada para que puedan revisarla junto con el docente.
                    </p>
                    <div class="mt-5 flex justify-end gap-2.5">
                        <button
                            type="button"
                            :disabled="finalizando"
                            class="cursor-pointer rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                            @click="confirmarCierre = false"
                        >
                            Seguir entrevistando
                        </button>
                        <button
                            type="button"
                            :disabled="finalizando"
                            class="cursor-pointer rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700 disabled:opacity-60"
                            @click="confirmarFinalizacion"
                        >
                            {{ finalizando ? 'Finalizando…' : 'Sí, finalizar entrevista' }}
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
