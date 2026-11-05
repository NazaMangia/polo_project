<script setup>
import { onMounted, reactive, ref } from 'vue';
import {
    actualizarEquipo,
    crearEquipo,
    eliminarEquipo,
    listarEquipos,
    listarEscenarios,
    regenerarCodigoEquipo,
} from '@/api/admin';

const equipos = ref([]);
const escenarios = ref([]);
const cargando = ref(true);
const errorMensaje = ref('');
const copiadoId = ref(null);

const filtros = reactive({
    buscar: '',
    activo: '',
});

const modalAbierto = ref(false);
const guardando = ref(false);
const errorFormulario = ref('');

const formulario = reactive({
    id: null,
    nombre: '',
    codigo: '',
    escenario_id: '',
    activo: true,
});

async function cargarDatos() {
    cargando.value = true;
    errorMensaje.value = '';

    try {
        const params = {};
        if (filtros.buscar.trim()) params.buscar = filtros.buscar.trim();
        if (filtros.activo !== '') params.activo = filtros.activo;

        const [listaEquipos, listaEscenarios] = await Promise.all([
            listarEquipos(params),
            listarEscenarios(),
        ]);

        equipos.value = listaEquipos;
        escenarios.value = listaEscenarios;
    } catch {
        errorMensaje.value = 'No se pudieron cargar los equipos.';
    } finally {
        cargando.value = false;
    }
}

function abrirNuevo() {
    formulario.id = null;
    formulario.nombre = '';
    formulario.codigo = '';
    formulario.escenario_id = escenarios.value[0]?.id || '';
    formulario.activo = true;
    errorFormulario.value = '';
    modalAbierto.value = true;
}

function abrirEdicion(equipo) {
    formulario.id = equipo.id;
    formulario.nombre = equipo.nombre;
    formulario.codigo = equipo.codigo;
    formulario.escenario_id = equipo.escenario_id || '';
    formulario.activo = equipo.activo;
    errorFormulario.value = '';
    modalAbierto.value = true;
}

async function guardar() {
    guardando.value = true;
    errorFormulario.value = '';

    const payload = {
        nombre: formulario.nombre.trim(),
        codigo: formulario.codigo.trim() || null,
        escenario_id: formulario.escenario_id ? Number(formulario.escenario_id) : null,
        activo: Boolean(formulario.activo),
    };

    try {
        if (formulario.id) {
            await actualizarEquipo(formulario.id, payload);
        } else {
            await crearEquipo(payload);
        }
        modalAbierto.value = false;
        await cargarDatos();
    } catch (error) {
        const errores = error.response?.data?.errors;
        const primerError = errores ? Object.values(errores).flat()[0] : null;
        errorFormulario.value = primerError || error.response?.data?.message || 'No se pudo guardar el equipo.';
    } finally {
        guardando.value = false;
    }
}

async function alternarActivo(equipo) {
    try {
        const actualizado = await actualizarEquipo(equipo.id, {
            nombre: equipo.nombre,
            codigo: equipo.codigo,
            escenario_id: equipo.escenario_id,
            activo: !equipo.activo,
        });
        const idx = equipos.value.findIndex((e) => e.id === equipo.id);
        if (idx !== -1) equipos.value[idx] = actualizado;
    } catch {
        errorMensaje.value = 'No se pudo cambiar el estado del equipo.';
    }
}

async function regenerar(equipo) {
    if (!confirm(`¿Generar un código nuevo para "${equipo.nombre}"? El código actual (${equipo.codigo}) dejará de funcionar.`)) {
        return;
    }
    try {
        const actualizado = await regenerarCodigoEquipo(equipo.id);
        const idx = equipos.value.findIndex((e) => e.id === equipo.id);
        if (idx !== -1) equipos.value[idx] = actualizado;
    } catch {
        errorMensaje.value = 'No se pudo regenerar el código.';
    }
}

async function borrar(equipo) {
    if (!confirm(`¿Eliminar el equipo "${equipo.nombre}"? Las conversaciones históricas se conservarán.`)) {
        return;
    }
    try {
        await eliminarEquipo(equipo.id);
        await cargarDatos();
    } catch {
        errorMensaje.value = 'No se pudo eliminar el equipo.';
    }
}

async function copiarCodigo(equipo) {
    try {
        await navigator.clipboard.writeText(equipo.codigo);
        copiadoId.value = equipo.id;
        setTimeout(() => {
            if (copiadoId.value === equipo.id) copiadoId.value = null;
        }, 1500);
    } catch {
        // Ignorar si el navegador no permite clipboard
    }
}

onMounted(cargarDatos);
</script>

<template>
    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Equipos de alumnos</h1>
                <p class="text-xs text-slate-500">
                    Creá los equipos, asignales el escenario que van a entrevistar y compartiles su código de acceso.
                </p>
            </div>
            <button
                type="button"
                class="cursor-pointer rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700"
                @click="abrirNuevo"
            >
                + Nuevo equipo
            </button>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs">
            <input
                v-model="filtros.buscar"
                type="text"
                placeholder="Buscar por nombre o código…"
                class="min-w-[220px] flex-1 rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm focus:border-indigo-600 focus:bg-white focus:outline-none"
                @keyup.enter="cargarDatos"
            />
            <select
                v-model="filtros.activo"
                class="rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm focus:border-indigo-600 focus:bg-white focus:outline-none"
                @change="cargarDatos"
            >
                <option value="">Todos los estados</option>
                <option value="true">Solo activos</option>
                <option value="false">Solo inactivos</option>
            </select>
            <button
                type="button"
                class="cursor-pointer rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                @click="cargarDatos"
            >
                Filtrar
            </button>
        </div>

        <div v-if="errorMensaje" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
            {{ errorMensaje }}
        </div>

        <!-- Tabla de equipos -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs">
            <div v-if="cargando" class="p-8 text-center text-sm text-slate-500">Cargando equipos…</div>

            <div v-else-if="equipos.length === 0" class="p-8 text-center text-sm text-slate-500">
                No hay equipos cargados con esos filtros.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold tracking-wider text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Equipo</th>
                            <th class="px-4 py-3">Código de ingreso</th>
                            <th class="px-4 py-3">Escenario asignado</th>
                            <th class="px-4 py-3 text-center">Estado</th>
                            <th class="px-4 py-3 text-center">Sesiones</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="equipo in equipos" :key="equipo.id" class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-semibold text-slate-900">
                                {{ equipo.nombre }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="inline-flex items-center gap-1.5">
                                    <span class="rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs font-bold tracking-wider text-slate-800">
                                        {{ equipo.codigo }}
                                    </span>
                                    <button
                                        type="button"
                                        class="cursor-pointer rounded-md px-2 py-1 text-[11px] font-medium text-indigo-600 hover:bg-indigo-50"
                                        @click="copiarCodigo(equipo)"
                                    >
                                        {{ copiadoId === equipo.id ? '¡Copiado!' : 'Copiar' }}
                                    </button>
                                    <button
                                        type="button"
                                        title="Generar un código nuevo"
                                        class="cursor-pointer rounded-md px-2 py-1 text-[11px] font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-800"
                                        @click="regenerar(equipo)"
                                    >
                                        Regenerar
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                <span v-if="equipo.escenario">{{ equipo.escenario.nombre }}</span>
                                <span v-else class="text-xs italic text-amber-700">Sin escenario asignado</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button
                                    type="button"
                                    class="cursor-pointer rounded-full px-2.5 py-0.5 text-xs font-semibold transition"
                                    :class="
                                        equipo.activo
                                            ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 hover:bg-emerald-100'
                                            : 'bg-slate-100 text-slate-600 ring-1 ring-slate-500/20 hover:bg-slate-200'
                                    "
                                    @click="alternarActivo(equipo)"
                                >
                                    {{ equipo.activo ? 'Activo' : 'Inactivo' }}
                                </button>
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-slate-600">
                                {{ equipo.sesiones_count ?? 0 }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="cursor-pointer rounded-lg px-2.5 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50"
                                        @click="abrirEdicion(equipo)"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        type="button"
                                        class="cursor-pointer rounded-lg px-2.5 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50"
                                        @click="borrar(equipo)"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Crear / Editar equipo -->
        <div
            v-if="modalAbierto"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
                <h2 class="text-base font-bold text-slate-900">
                    {{ formulario.id ? 'Editar equipo' : 'Nuevo equipo' }}
                </h2>

                <form class="mt-4 space-y-4" @submit.prevent="guardar">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase">Nombre del equipo</label>
                        <input
                            v-model="formulario.nombre"
                            type="text"
                            required
                            placeholder="Ej: Equipo Omega"
                            class="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm focus:border-indigo-600 focus:bg-white focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase">
                            Código de acceso (opcional)
                        </label>
                        <input
                            v-model="formulario.codigo"
                            type="text"
                            placeholder="Si lo dejás vacío se genera solo (ej: OMEGA-7K4)"
                            class="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 font-mono text-sm uppercase focus:border-indigo-600 focus:bg-white focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase">Escenario asignado</label>
                        <select
                            v-model="formulario.escenario_id"
                            class="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm focus:border-indigo-600 focus:bg-white focus:outline-none"
                        >
                            <option value="">— Sin escenario asignado —</option>
                            <option v-for="esc in escenarios" :key="esc.id" :value="esc.id">
                                {{ esc.nombre }} ({{ esc.dificultad_etiqueta }}) {{ !esc.activo ? '[Inactivo]' : '' }}
                            </option>
                        </select>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input v-model="formulario.activo" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600" />
                        Equipo habilitado para iniciar simulaciones
                    </label>

                    <div v-if="errorFormulario" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                        {{ errorFormulario }}
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            class="cursor-pointer rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                            @click="modalAbierto = false"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="guardando"
                            class="cursor-pointer rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                        >
                            {{ guardando ? 'Guardando…' : 'Guardar equipo' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
