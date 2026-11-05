<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { loginDocente } from '@/api/admin';

const router = useRouter();

const email = ref('');
const password = ref('');
const cargando = ref(false);
const errorMensaje = ref('');

async function ingresar() {
    errorMensaje.value = '';
    cargando.value = true;

    try {
        await loginDocente(email.value.trim(), password.value);
        await router.push({ name: 'docente-sesiones' });
    } catch (error) {
        const errores = error.response?.data?.errors;
        if (errores?.email?.length) {
            errorMensaje.value = errores.email[0];
        } else if (errores?.password?.length) {
            errorMensaje.value = errores.password[0];
        } else if (error.response?.data?.message) {
            errorMensaje.value = error.response.data.message;
        } else {
            errorMensaje.value = 'No se pudo iniciar sesión. Verificá tus datos.';
        }
    } finally {
        cargando.value = false;
    }
}
</script>

<template>
    <div class="flex min-h-screen flex-col justify-center bg-slate-100 p-6">
        <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white p-7 shadow-md">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white">
                    GP
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-900">Panel Docente</h1>
                    <p class="text-xs text-slate-500">Simulador de Entrevistas · Gestión de Proyectos</p>
                </div>
            </div>

            <form class="mt-6 space-y-4" @submit.prevent="ingresar">
                <div>
                    <label for="docente-email" class="block text-xs font-semibold text-slate-700 uppercase">
                        Correo electrónico
                    </label>
                    <input
                        id="docente-email"
                        v-model="email"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="docente@escuela.edu.ar"
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-600/20 focus:outline-none"
                    />
                </div>

                <div>
                    <label for="docente-password" class="block text-xs font-semibold text-slate-700 uppercase">
                        Contraseña
                    </label>
                    <input
                        id="docente-password"
                        v-model="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="mt-1.5 block w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-600/20 focus:outline-none"
                    />
                </div>

                <div
                    v-if="errorMensaje"
                    role="alert"
                    class="rounded-xl border border-red-200 bg-red-50 px-3.5 py-2.5 text-xs text-red-700"
                >
                    {{ errorMensaje }}
                </div>

                <button
                    type="submit"
                    :disabled="cargando"
                    class="w-full cursor-pointer rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition hover:bg-indigo-700 disabled:opacity-60"
                >
                    {{ cargando ? 'Ingresando…' : 'Ingresar al panel' }}
                </button>
            </form>

            <div class="mt-5 border-t border-slate-100 pt-4 text-center">
                <RouterLink :to="{ name: 'inicio' }" class="text-xs font-medium text-indigo-600 hover:underline">
                    ← Volver a la pantalla de alumnos
                </RouterLink>
            </div>
        </div>
    </div>
</template>
