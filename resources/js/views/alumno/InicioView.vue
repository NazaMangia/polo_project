<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { iniciarSesionPorCodigo, obtenerEstadoSimulador } from '@/api/alumno';

const router = useRouter();

const codigo = ref('');
const cargando = ref(false);
const errorMensaje = ref('');

// null mientras se consulta; si la consulta falla se deja intentar igual (el servidor valida).
const simuladorAbierto = ref(null);

onMounted(async () => {
    try {
        simuladorAbierto.value = (await obtenerEstadoSimulador())?.abierto ?? null;
    } catch {
        simuladorAbierto.value = null;
    }
});

async function comenzar() {
    errorMensaje.value = '';
    const codigoLimpio = codigo.value.trim();

    if (!codigoLimpio) {
        errorMensaje.value = 'Ingresá el código que te entregó el docente.';
        return;
    }

    cargando.value = true;

    try {
        const respuesta = await iniciarSesionPorCodigo(codigoLimpio);
        await router.push({
            name: 'simulacion',
            params: { uuid: respuesta.sesion.uuid },
        });
    } catch (error) {
        const status = error.response?.status;
        const errores = error.response?.data?.errors;

        if (status === 423) {
            simuladorAbierto.value = false;
        } else if (status === 422 && errores?.codigo?.length) {
            errorMensaje.value = errores.codigo[0];
        } else if (error.response?.data?.message) {
            errorMensaje.value = error.response.data.message;
        } else {
            errorMensaje.value = 'No se pudo conectar con el servidor. Intentá nuevamente.';
        }
    } finally {
        cargando.value = false;
    }
}
</script>

<template>
    <div class="flex min-h-screen flex-col justify-between bg-gradient-to-b from-slate-50 to-indigo-50/50">
        <!-- Barra superior -->
        <header class="border-b border-slate-200/80 bg-white/80 backdrop-blur">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3.5 sm:px-6">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-sm">
                        GP
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Simulador de Entrevistas</p>
                        <p class="text-xs text-slate-500">Gestión de Proyectos · 6.º año Informática</p>
                    </div>
                </div>

                <RouterLink
                    to="/docente"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100 hover:text-indigo-700"
                >
                    Acceso docente
                </RouterLink>
            </div>
        </header>

        <!-- Contenido principal -->
        <main class="mx-auto flex w-full max-w-4xl flex-1 items-center px-4 py-10 sm:px-6">
            <div class="grid w-full gap-8 md:grid-cols-12 md:items-center">
                <!-- Columna explicativa -->
                <section class="md:col-span-7">
                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-800">
                        Práctica de Relevamiento de Requisitos
                    </span>

                    <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                        Entrevistá a tu cliente y descubrí qué necesita
                    </h1>

                    <p class="mt-3 text-base leading-relaxed text-slate-600">
                        Vas a conversar con un cliente que necesita una solución informática para su organización.
                        El cliente <strong>no es técnico</strong>: no te va a dictar una lista de requisitos, sino que te
                        va a contar su realidad a medida que le hagas buenas preguntas.
                    </p>

                    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                        <h2 class="text-xs font-semibold tracking-wider text-slate-500 uppercase">
                            ¿Qué deberías relevar en la entrevista?
                        </h2>
                        <ul class="mt-3 grid grid-cols-1 gap-2 text-sm text-slate-700 sm:grid-cols-2">
                            <li class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                                Problema principal y necesidades
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                                Actores y roles involucrados
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                                Procesos actuales paso a paso
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                                Datos que se registran y consultan
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                                Reglas de negocio
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                                Restricciones de tiempo o recursos
                            </li>
                        </ul>
                    </div>
                </section>

                <!-- Tarjeta de ingreso del equipo -->
                <section class="md:col-span-5">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-md sm:p-7">
                        <h2 class="text-lg font-bold text-slate-900">Ingresar como equipo</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Escribí el código de tu equipo. Si ya tenían una entrevista en curso, se retomará automáticamente.
                        </p>

                        <div
                            v-if="simuladorAbierto === false"
                            role="status"
                            class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-sm text-amber-800"
                        >
                            <strong>El simulador está cerrado en este momento.</strong>
                            El docente lo habilita durante la clase.
                        </div>

                        <form v-else class="mt-5 space-y-4" @submit.prevent="comenzar">
                            <div>
                                <label for="codigo-equipo" class="block text-xs font-semibold tracking-wide text-slate-700 uppercase">
                                    Código del equipo
                                </label>
                                <input
                                    id="codigo-equipo"
                                    v-model="codigo"
                                    type="text"
                                    autocomplete="off"
                                    autocapitalize="characters"
                                    spellcheck="false"
                                    placeholder="Ej: OMEGA26"
                                    :disabled="cargando"
                                    class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-center font-mono text-lg font-semibold tracking-widest text-slate-900 uppercase placeholder:font-sans placeholder:text-sm placeholder:font-normal placeholder:tracking-normal placeholder:text-slate-400 focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-600/20 focus:outline-none disabled:opacity-60"
                                />
                            </div>

                            <div
                                v-if="errorMensaje"
                                role="alert"
                                class="rounded-xl border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm text-red-700"
                            >
                                {{ errorMensaje }}
                            </div>

                            <button
                                type="submit"
                                :disabled="cargando"
                                class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <span v-if="cargando">Verificando código…</span>
                                <span v-else>Comenzar simulación</span>
                            </button>
                        </form>
                    </div>
                </section>
            </div>
        </main>

        <footer class="border-t border-slate-200/70 bg-white/60 py-4 text-center text-xs text-slate-500">
            Simulador educativo de entrevistas de relevamiento de requisitos
        </footer>
    </div>
</template>
