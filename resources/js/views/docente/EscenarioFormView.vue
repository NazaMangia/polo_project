<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { actualizarEscenario, crearEscenario, obtenerEscenario } from '@/api/admin';

const route = useRoute();
const router = useRouter();

const escenarioId = computed(() => (route.params.id ? Number(route.params.id) : null));
const esEdicion = computed(() => Boolean(escenarioId.value));

const cargando = ref(false);
const guardando = ref(false);
const errorMensaje = ref('');

const seccionesConocimiento = [
    {
        campo: 'informacion_cliente',
        titulo: 'Información conocida por el cliente',
        ayuda: 'Datos concretos sobre la organización (cantidad de empleados, herramientas actuales, volumen de trabajo).',
    },
    {
        campo: 'necesidades',
        titulo: 'Necesidades del cliente',
        ayuda: 'Qué problemas busca resolver o qué objetivos espera lograr con la solución.',
    },
    {
        campo: 'actores',
        titulo: 'Actores involucrados',
        ayuda: 'Personas o roles que interactúan con el proceso actual o usarían el sistema.',
    },
    {
        campo: 'procesos',
        titulo: 'Procesos actuales',
        ayuda: 'Cómo hacen el trabajo hoy paso a paso.',
    },
    {
        campo: 'datos',
        titulo: 'Datos involucrados',
        ayuda: 'Qué información registran o necesitan consultar (ej. datos del paciente, del turno, etc.).',
    },
    {
        campo: 'reglas_negocio',
        titulo: 'Reglas de negocio',
        ayuda: 'Condiciones, políticas y validaciones propias del funcionamiento de la organización.',
    },
    {
        campo: 'restricciones',
        titulo: 'Restricciones',
        ayuda: 'Límites de presupuesto, fechas, equipamiento o características de los usuarios.',
    },
];

const formulario = reactive({
    nombre: '',
    descripcion: '',
    dificultad: 'intermedia',
    activo: true,
    problema_principal: '',
    contexto: '',
    rol_cliente: '',
    personalidad: '',
    mensaje_inicial: '',
    informacion_cliente: [],
    necesidades: [],
    actores: [],
    procesos: [],
    datos: [],
    reglas_negocio: [],
    restricciones: [],
    informacion_reservada: '',
    comportamiento_esperado: '',
    instrucciones_ia: '',
});

function agregarItem(campo) {
    formulario[campo].push({
        id: '',
        texto: '',
        revelacion: 'si_pregunta',
    });
}

function quitarItem(campo, indice) {
    formulario[campo].splice(indice, 1);
}

async function cargar() {
    if (!esEdicion.value) {
        agregarItem('necesidades');
        agregarItem('actores');
        agregarItem('procesos');
        return;
    }

    cargando.value = true;
    try {
        const datos = await obtenerEscenario(escenarioId.value);
        Object.assign(formulario, {
            nombre: datos.nombre || '',
            descripcion: datos.descripcion || '',
            dificultad: datos.dificultad || 'intermedia',
            activo: Boolean(datos.activo),
            problema_principal: datos.problema_principal || '',
            contexto: datos.contexto || '',
            rol_cliente: datos.rol_cliente || '',
            personalidad: datos.personalidad || '',
            mensaje_inicial: datos.mensaje_inicial || '',
            informacion_cliente: datos.informacion_cliente || [],
            necesidades: datos.necesidades || [],
            actores: datos.actores || [],
            procesos: datos.procesos || [],
            datos: datos.datos || [],
            reglas_negocio: datos.reglas_negocio || [],
            restricciones: datos.restricciones || [],
            informacion_reservada: datos.informacion_reservada || '',
            comportamiento_esperado: datos.comportamiento_esperado || '',
            instrucciones_ia: datos.instrucciones_ia || '',
        });
    } catch {
        errorMensaje.value = 'No se pudo cargar el escenario.';
    } finally {
        cargando.value = false;
    }
}

async function guardar() {
    guardando.value = true;
    errorMensaje.value = '';

    try {
        if (esEdicion.value) {
            await actualizarEscenario(escenarioId.value, formulario);
        } else {
            await crearEscenario(formulario);
        }
        await router.push({ name: 'docente-escenarios' });
    } catch (error) {
        const errores = error.response?.data?.errors;
        const primerError = errores ? Object.values(errores).flat()[0] : null;
        errorMensaje.value = primerError || error.response?.data?.message || 'Error al guardar el escenario.';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } finally {
        guardando.value = false;
    }
}

onMounted(cargar);
</script>

<template>
    <div class="space-y-6 pb-12">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <RouterLink :to="{ name: 'docente-escenarios' }" class="text-xs font-semibold text-indigo-600 hover:underline">
                    ← Volver a escenarios
                </RouterLink>
                <h1 class="mt-1 text-xl font-bold text-slate-900">
                    {{ esEdicion ? 'Editar escenario' : 'Nuevo escenario' }}
                </h1>
            </div>
        </div>

        <div v-if="errorMensaje" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
            {{ errorMensaje }}
        </div>

        <div v-if="cargando" class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
            Cargando datos del escenario…
        </div>

        <form v-else class="space-y-6" @submit.prevent="guardar">
            <!-- 1. Datos generales -->
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs">
                <h2 class="text-base font-bold text-slate-900">1. Datos generales</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-12">
                    <div class="sm:col-span-8">
                        <label class="block text-xs font-semibold text-slate-700 uppercase">Nombre del escenario *</label>
                        <input
                            v-model="formulario.nombre"
                            type="text"
                            required
                            placeholder="Ej: Sistema de turnos para una clínica"
                            class="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm focus:border-indigo-600 focus:bg-white focus:outline-none"
                        />
                    </div>

                    <div class="sm:col-span-4">
                        <label class="block text-xs font-semibold text-slate-700 uppercase">Dificultad *</label>
                        <select
                            v-model="formulario.dificultad"
                            class="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm focus:border-indigo-600 focus:bg-white focus:outline-none"
                        >
                            <option value="basica">Básica (cliente claro y colaborador)</option>
                            <option value="intermedia">Intermedia (cliente algo disperso)</option>
                            <option value="avanzada">Avanzada (cliente impreciso / requisitos implícitos)</option>
                        </select>
                    </div>

                    <div class="sm:col-span-12">
                        <label class="block text-xs font-semibold text-slate-700 uppercase">
                            Descripción breve (visible para el alumno)
                        </label>
                        <textarea
                            v-model="formulario.descripcion"
                            rows="2"
                            placeholder="Presentación breve de la situación que verá el equipo arriba del chat."
                            class="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm focus:border-indigo-600 focus:bg-white focus:outline-none"
                        ></textarea>
                    </div>

                    <div class="sm:col-span-12">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input v-model="formulario.activo" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600" />
                            Escenario activo (disponible para que los equipos inicien entrevistas)
                        </label>
                    </div>
                </div>
            </section>

            <!-- 2. Perfil del cliente y contexto -->
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs">
                <h2 class="text-base font-bold text-slate-900">2. Perfil del cliente y problema</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase">Rol del cliente *</label>
                        <textarea
                            v-model="formulario.rol_cliente"
                            rows="2"
                            required
                            placeholder="Ej: Laura Méndez, administradora y socia de la clínica."
                            class="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm focus:border-indigo-600 focus:bg-white focus:outline-none"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase">Personalidad del cliente</label>
                        <textarea
                            v-model="formulario.personalidad"
                            rows="2"
                            placeholder="Ej: Amable pero ansiosa; no sabe de informática y suele mezclar quejas con necesidades."
                            class="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm focus:border-indigo-600 focus:bg-white focus:outline-none"
                        ></textarea>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase">Problema principal *</label>
                        <textarea
                            v-model="formulario.problema_principal"
                            rows="2"
                            required
                            placeholder="Resumen del problema central que motiva la consulta."
                            class="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm focus:border-indigo-600 focus:bg-white focus:outline-none"
                        ></textarea>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase">Contexto de la organización</label>
                        <textarea
                            v-model="formulario.contexto"
                            rows="3"
                            placeholder="Historia breve, tamaño, horarios, cómo trabajan en general."
                            class="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm focus:border-indigo-600 focus:bg-white focus:outline-none"
                        ></textarea>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase">
                            Mensaje inicial del cliente (saludo con el que abre el chat) *
                        </label>
                        <textarea
                            v-model="formulario.mensaje_inicial"
                            rows="2"
                            required
                            placeholder="Ej: Hola, soy Laura. Gracias por recibirme, estamos teniendo varios problemas con los turnos..."
                            class="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm focus:border-indigo-600 focus:bg-white focus:outline-none"
                        ></textarea>
                    </div>
                </div>
            </section>

            <!-- 3. Listas de conocimiento con política de revelación -->
            <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs">
                <div>
                    <h2 class="text-base font-bold text-slate-900">
                        3. Información del escenario y política de revelación progresiva
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Elegí para cada punto cuándo puede revelarlo el cliente virtual:
                        <strong>🟢 Lo cuenta solo</strong> ·
                        <strong>🟡 Si le preguntan</strong> ·
                        <strong>🔴 Solo con pregunta precisa</strong>.
                    </p>
                </div>

                <div
                    v-for="sec in seccionesConocimiento"
                    :key="sec.campo"
                    class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">{{ sec.titulo }}</h3>
                            <p class="text-xs text-slate-500">{{ sec.ayuda }}</p>
                        </div>
                        <button
                            type="button"
                            class="cursor-pointer rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-indigo-600 shadow-2xs ring-1 ring-slate-200 hover:bg-indigo-50"
                            @click="agregarItem(sec.campo)"
                        >
                            + Agregar ítem
                        </button>
                    </div>

                    <div v-if="formulario[sec.campo].length === 0" class="mt-3 text-xs italic text-slate-400">
                        Sin ítems cargados en esta sección.
                    </div>

                    <div v-else class="mt-3 space-y-2">
                        <div
                            v-for="(item, idx) in formulario[sec.campo]"
                            :key="idx"
                            class="flex flex-col gap-2 sm:flex-row sm:items-center"
                        >
                            <input
                                v-model="item.texto"
                                type="text"
                                placeholder="Describí el dato, necesidad, regla o restricción…"
                                class="flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-600 focus:outline-none"
                            />
                            <select
                                v-model="item.revelacion"
                                class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 focus:border-indigo-600 focus:outline-none"
                            >
                                <option value="espontaneo">🟢 Lo cuenta solo</option>
                                <option value="si_pregunta">🟡 Si le preguntan</option>
                                <option value="muy_especifico">🔴 Solo con pregunta precisa</option>
                            </select>
                            <button
                                type="button"
                                class="cursor-pointer rounded-lg px-2.5 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50"
                                @click="quitarItem(sec.campo, idx)"
                            >
                                Quitar
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 4. Información reservada e instrucciones adicionales para la IA -->
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs">
                <h2 class="text-base font-bold text-slate-900">4. Comportamiento e instrucciones adicionales para la IA</h2>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase">
                            Información que NO debe revelar espontáneamente (texto libre opcional)
                        </label>
                        <textarea
                            v-model="formulario.informacion_reservada"
                            rows="3"
                            placeholder="Ej: No menciona que hace dos años fracasó un sistema anterior salvo que le pregunten por experiencias previas."
                            class="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm focus:border-indigo-600 focus:bg-white focus:outline-none"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase">Comportamiento esperado</label>
                        <textarea
                            v-model="formulario.comportamiento_esperado"
                            rows="2"
                            placeholder="Pautas sobre cómo reacciona ante preguntas técnicas o vagas."
                            class="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm focus:border-indigo-600 focus:bg-white focus:outline-none"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase">
                            Instrucciones adicionales para Gemini (opcional)
                        </label>
                        <textarea
                            v-model="formulario.instrucciones_ia"
                            rows="2"
                            placeholder="Cualquier indicación extra que quieras inyectar en el prompt de este escenario."
                            class="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-2 text-sm focus:border-indigo-600 focus:bg-white focus:outline-none"
                        ></textarea>
                    </div>
                </div>
            </section>

            <div class="flex justify-end gap-3">
                <RouterLink
                    :to="{ name: 'docente-escenarios' }"
                    class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Cancelar
                </RouterLink>
                <button
                    type="submit"
                    :disabled="guardando"
                    class="cursor-pointer rounded-xl bg-indigo-600 px-6 py-2.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 disabled:opacity-60"
                >
                    {{ guardando ? 'Guardando…' : 'Guardar escenario' }}
                </button>
            </div>
        </form>
    </div>
</template>
