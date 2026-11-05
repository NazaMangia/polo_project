<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { logoutDocente, obtenerDocenteGuardado } from '@/api/admin';

const router = useRouter();
const docente = ref(obtenerDocenteGuardado());

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
        </header>

        <!-- Contenido de cada sección -->
        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
            <RouterView />
        </main>
    </div>
</template>
