<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { listarEquipos, listarEscenarios, listarSesiones } from '@/api/admin';

const router = useRouter();

const sesiones = ref([]);
const equipos = ref([]);
const escenarios = ref([]);
const cargando = ref(true);
const errorMensaje = ref('');

const filtros = reactive({
    equipo_id: '',
    escenario_id: '',
    estado: '',
});

function formatearFecha(iso) {
    if (!iso) return '—';
    try {
        return new Date(iso).toLocaleString('es-AR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return iso;
    }
}

async function cargar() {
    cargando.value = true;
    errorMensaje.value = '';

    try {
        const params = {};
        if (filtros.equipo_id) params.equipo_id = filtros.equipo_id;
        if (filtros.escenario_id) params.escenario_id = filtros.escenario_id;
        if (filtros.estado) params.estado = filtros.estado;

        const [listaSesiones, listaEquipos, listaEscenarios] = await Promise.all([
            listarSesiones(params),
            listarEquipos(),
            listarEscenarios(),
        ]);

        sesiones.value = listaSesiones;
        equipos.value = listaEquipos;
        escenarios.value = listaEscenarios;
    } catch {
        errorMensaje.value = 'No se pudieron cargar las sesiones.';
    } finally {
        cargando.value = false;
    }
}

onMounted(cargar);
</script>

<template>
    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Sesiones de entrevista</h1>
                <p class="text-xs text-slate-500">
                    Revisá las entrevistas en curso o finalizadas de cada equipo y abrí la conversación completa.
                </p>
            </div>
            <button
                type="button"
                class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                @click="cargar"
            >
                Actualizar lista
            </button>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs">
            <select
                v-model="filtros.equipo_id"
                class="rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm focus:border-indigo-600 focus:bg-white focus:outline-none"
                @change="cargar"
            >
                <option value="">Todos los equipos</option>
                <option v-for="eq in equipos" :key="eq.id" :value="eq.id">
                    {{ eq.nombre }} ({{ eq.codigo }})
                </option>
            </select>

            <select
                v-model="filtros.escenario_id"
                class="rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm focus:border-indigo-600 focus:bg-white focus:outline-none"
                @change="cargar"
            >
                <option value="">Todos los escenarios</option>
                <option v-for="esc in escenarios" :key="esc.id" :value="esc.id">
                    {{ esc.nombre }}
                </option>
            </select>

            <select
                v-model="filtros.estado"
                class="rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm focus:border-indigo-600 focus:bg-white focus:outline-none"
                @change="cargar"
            >
                <option value="">Todos los estados</option>
                <option value="activa">Activas</option>
                <option value="finalizada">Finalizadas</option>
            </select>
        </div>

        <div v-if="errorMensaje" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
            {{ errorMensaje }}
        </div>

        <!-- Tabla Equipo | Escenario | Inicio | Estado | Mensajes -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs">
            <div v-if="cargando" class="p-8 text-center text-sm text-slate-500">Cargando sesiones…</div>

            <div v-else-if="sesiones.length === 0" class="p-8 text-center text-sm text-slate-500">
                Todavía no hay sesiones registradas con esos filtros.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold tracking-wider text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Equipo</th>
                            <th class="px-4 py-3">Escenario</th>
                            <th class="px-4 py-3">Inicio</th>
                            <th class="px-4 py-3 text-center">Estado</th>
                            <th class="px-4 py-3 text-center">Mensajes</th>
                            <th class="px-4 py-3 text-right">Conversación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="sesion in sesiones" :key="sesion.id" class="hover:bg-slate-50/70">
                            <td class="px-4 py-3">
                                <span class="font-semibold text-slate-900">{{ sesion.equipo?.nombre }}</span>
                                <span class="ml-1.5 rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-600">
                                    {{ sesion.equipo?.codigo }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                {{ sesion.escenario?.nombre }}
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">
                                {{ formatearFecha(sesion.inicio) }}
                            </td>
                            <td class="px-4 py-3 text-center">
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
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-slate-700">
                                {{ sesion.mensajes_count ?? 0 }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    class="cursor-pointer rounded-xl bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100"
                                    @click="router.push({ name: 'docente-conversacion', params: { id: sesion.id } })"
                                >
                                    Ver conversación
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
