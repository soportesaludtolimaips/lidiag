<template>
    <div class="row">
        <!-- Resumen del Paciente y Estado de la Atención -->
        <div class="col-12 mb-3">
            <div class="card card-outline card-primary shadow-sm mb-0">
                <div class="card-body p-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div class="d-flex align-items-center mb-2 mb-md-0">
                            <div class="mr-3 text-center">
                                <i
                                    class="fa fa-user-circle fa-3x text-primary"
                                    aria-hidden="true"
                                ></i>
                                <span
                                    class="badge d-block mt-1"
                                    :class="
                                        atencion?.tipo_recaudo?.codigo === 'PARTICULAR'
                                            ? 'badge-primary'
                                            : 'badge-info'
                                    "
                                >
                                    {{ atencion?.tipo_recaudo?.nombre || "Atención" }}
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-1 font-weight-bold text-dark">
                                    {{ atencion?.paciente?.nombres || "Cargando paciente..." }}
                                </h5>
                                <div class="text-muted small">
                                    <span class="mr-3">
                                        <i class="fa fa-id-card-o text-primary mr-1"></i>Doc:
                                        <strong>{{ atencion?.paciente?.num_docu || "-" }}</strong>
                                    </span>
                                    <span class="mr-3">
                                        <i class="fa fa-venus-mars mr-1"></i>Sexo:
                                        <strong>{{
                                            atencion?.paciente?.sexo === "M"
                                                ? "Masculino"
                                                : atencion?.paciente?.sexo === "F"
                                                  ? "Femenino"
                                                  : "Otro"
                                        }}</strong>
                                    </span>
                                    <span class="mr-3">
                                        <i class="fa fa-calendar mr-1"></i>Edad:
                                        <strong>{{ calcularEdad() }}</strong>
                                    </span>
                                    <span>
                                        <i class="fa fa-phone mr-1"></i>Tel:
                                        <strong>{{
                                            atencion?.paciente?.cel ||
                                            atencion?.paciente?.tel ||
                                            "-"
                                        }}</strong>
                                    </span>
                                </div>
                                <div class="text-muted small mt-1">
                                    <span class="mr-3">
                                        Aseguradora:
                                        <strong>{{
                                            atencion?.admin_salud?.nom_admin || "PARTICULAR"
                                        }}</strong>
                                    </span>
                                    <span>
                                        Atención SAHI:
                                        <strong class="text-primary"
                                            >#{{ atencion?.atencion_id || "-" }}</strong
                                        >
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Estado y Acciones Rápidas -->
                        <div class="text-right">
                            <div class="mb-2">
                                <span
                                    v-if="autoguardadoActivo"
                                    class="badge badge-light border text-muted mr-2"
                                >
                                    <i class="fa fa-floppy-o text-success mr-1"></i
                                    >{{ mensajeAutoguardado }}
                                </span>
                                <span
                                    class="badge font-weight-bold py-1 px-2"
                                    :class="
                                        consulta?.estado_consulta === 'FINALIZADA'
                                            ? 'badge-success'
                                            : 'badge-warning text-white'
                                    "
                                >
                                    {{ consulta?.estado_consulta || "EN EDICIÓN" }}
                                </span>
                            </div>
                            <div class="d-flex gap-2">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary mr-2"
                                    @click="guardarBorrador()"
                                    :disabled="guardando"
                                >
                                    <i
                                        class="fa mr-1"
                                        :class="guardando ? 'fa-spinner fa-spin' : 'fa-save'"
                                    ></i
                                    >Guardar Borrador
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-success"
                                    @click="confirmarFinalizar()"
                                    :disabled="guardando"
                                >
                                    <i class="fa fa-check-circle mr-1"></i>Finalizar Consulta
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestañas de la Historia Clínica Oftalmológica -->
        <div class="col-12">
            <div class="card card-tabs shadow-sm">
                <div class="card-header p-0 pt-1 border-bottom-0">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a
                                class="nav-link font-weight-bold"
                                :class="{ active: tabActiva === 'anamnesis' }"
                                href="#"
                                @click.prevent="tabActiva = 'anamnesis'"
                            >
                                <i class="fa fa-comments-o mr-1"></i>1. Anamnesis
                            </a>
                        </li>
                        <li class="nav-item">
                            <a
                                class="nav-link font-weight-bold"
                                :class="{ active: tabActiva === 'examen' }"
                                href="#"
                                @click.prevent="tabActiva = 'examen'"
                            >
                                <i class="fa fa-eye mr-1"></i>2. Examen Físico Oftalmológico
                            </a>
                        </li>
                        <li class="nav-item">
                            <a
                                class="nav-link font-weight-bold"
                                :class="{ active: tabActiva === 'diagnosticos' }"
                                href="#"
                                @click.prevent="tabActiva = 'diagnosticos'"
                            >
                                <i class="fa fa-stethoscope mr-1"></i>3. Diagnósticos (CIE-10)
                                <span
                                    v-if="form.diagnosticos.length > 0"
                                    class="badge badge-primary ml-1"
                                >
                                    {{ form.diagnosticos.length }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a
                                class="nav-link font-weight-bold"
                                :class="{ active: tabActiva === 'plan' }"
                                href="#"
                                @click.prevent="tabActiva = 'plan'"
                            >
                                <i class="fa fa-clipboard mr-1"></i>4. Concepto y Plan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a
                                class="nav-link font-weight-bold"
                                :class="{ active: tabActiva === 'ordenes' }"
                                href="#"
                                @click.prevent="tabActiva = 'ordenes'"
                            >
                                <i class="fa fa-medkit mr-1"></i>5. Procedimientos y Medicamentos
                                <span
                                    v-if="form.procedimientos.length + form.medicamentos.length > 0"
                                    class="badge badge-success ml-1"
                                >
                                    {{ form.procedimientos.length + form.medicamentos.length }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a
                                class="nav-link font-weight-bold"
                                :class="{ active: tabActiva === 'anexos' }"
                                href="#"
                                @click.prevent="tabActiva = 'anexos'"
                            >
                                <i class="fa fa-file-text-o mr-1"></i>6. Remisión / Incapacidad
                                <span
                                    v-if="
                                        form.remision.institucion_receptora ||
                                        form.incapacidad.dias_incapacidad > 0
                                    "
                                    class="badge badge-warning text-white ml-1"
                                >
                                    !
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-3">
                    <!-- Tab 1: Anamnesis -->
                    <div v-show="tabActiva === 'anamnesis'">
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label class="small font-weight-bold">Motivo de Consulta:</label>
                                <textarea
                                    v-model="form.motivo_consulta"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Describa el motivo de la consulta oftalmológica..."
                                ></textarea>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label class="small font-weight-bold">Enfermedad Actual:</label>
                                <textarea
                                    v-model="form.enfermedad_actual"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Evolución del cuadro clínico ocular..."
                                ></textarea>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label class="small font-weight-bold">Antecedentes Médicos:</label>
                                <textarea
                                    v-model="form.antecedentes_medicos"
                                    class="form-control"
                                    rows="2"
                                    placeholder="HTA, Diabetes, etc."
                                ></textarea>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label class="small font-weight-bold"
                                    >Antecedentes Quirúrgicos Oftalmológicos:</label
                                >
                                <textarea
                                    v-model="form.antecedentes_quirurgicos_oft"
                                    class="form-control"
                                    rows="2"
                                    placeholder="Cirugías refractivas, cataratas, etc."
                                ></textarea>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label class="small font-weight-bold"
                                    >Antecedentes Alérgicos:</label
                                >
                                <textarea
                                    v-model="form.antecedentes_alergicos"
                                    class="form-control"
                                    rows="2"
                                    placeholder="Medicamentos, colirios, etc."
                                ></textarea>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label class="small font-weight-bold"
                                    >Otros Antecedentes (Familiares / Tóxicos):</label
                                >
                                <textarea
                                    v-model="form.antecedentes_otros"
                                    class="form-control"
                                    rows="2"
                                ></textarea>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label class="small font-weight-bold">Revisión por Sistemas:</label>
                                <textarea
                                    v-model="form.revision_sistemas"
                                    class="form-control"
                                    rows="2"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Examen Físico Oftalmológico -->
                    <div v-show="tabActiva === 'examen'">
                        <!-- Agudeza Visual (Grid Estructurado OD / OI) -->
                        <div class="card card-outline card-info mb-3">
                            <div class="card-header p-2 bg-light font-weight-bold">
                                <i class="fa fa-table mr-1"></i>Agudeza Visual (AV)
                            </div>
                            <div class="card-body p-2">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered text-center mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="width: 25%">Lateralidad</th>
                                                <th style="width: 25%">Sin Corrección (SC)</th>
                                                <th style="width: 25%">Con Corrección (CC)</th>
                                                <th style="width: 25%">Agujero Estenopeico (PH)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="font-weight-bold align-middle bg-light">
                                                    Ojo Derecho (OD)
                                                </td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        v-model="form.av_sc_od"
                                                        class="form-control form-control-sm text-center font-weight-bold"
                                                        placeholder="Ej: 20/20"
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        v-model="form.av_cc_od"
                                                        class="form-control form-control-sm text-center font-weight-bold"
                                                        placeholder="Ej: 20/20"
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        v-model="form.av_ph_od"
                                                        class="form-control form-control-sm text-center font-weight-bold"
                                                        placeholder="Ej: 20/20"
                                                    />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="font-weight-bold align-middle bg-light">
                                                    Ojo Izquierdo (OI)
                                                </td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        v-model="form.av_sc_oi"
                                                        class="form-control form-control-sm text-center font-weight-bold"
                                                        placeholder="Ej: 20/20"
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        v-model="form.av_cc_oi"
                                                        class="form-control form-control-sm text-center font-weight-bold"
                                                        placeholder="Ej: 20/20"
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        v-model="form.av_ph_oi"
                                                        class="form-control form-control-sm text-center font-weight-bold"
                                                        placeholder="Ej: 20/20"
                                                    />
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Evaluación del Aparato Ocular -->
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label class="small font-weight-bold"
                                    >Motilidad Ocular Extrínseca (MOE):</label
                                >
                                <textarea
                                    v-model="form.motilidad_ocular"
                                    class="form-control form-control-sm"
                                    rows="2"
                                    placeholder="Ortoforia, ducciones y versiones..."
                                ></textarea>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label class="small font-weight-bold">Pupilas:</label>
                                <textarea
                                    v-model="form.pupilas"
                                    class="form-control form-control-sm"
                                    rows="2"
                                    placeholder="Isocóricas, normorreactivas a la luz..."
                                ></textarea>
                            </div>

                            <div class="col-md-6 form-group mb-3">
                                <label class="small font-weight-bold"
                                    >Biomicroscopía OD (Segmento Anterior):</label
                                >
                                <textarea
                                    v-model="form.biomicroscopia_od"
                                    class="form-control form-control-sm"
                                    rows="2"
                                    placeholder="Córnea transparente, cámara anterior amplia..."
                                ></textarea>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label class="small font-weight-bold"
                                    >Biomicroscopía OI (Segmento Anterior):</label
                                >
                                <textarea
                                    v-model="form.biomicroscopia_oi"
                                    class="form-control form-control-sm"
                                    rows="2"
                                    placeholder="Córnea transparente, cámara anterior amplia..."
                                ></textarea>
                            </div>

                            <div class="col-md-3 form-group mb-3">
                                <label class="small font-weight-bold"
                                    >Presión Intraocular OD (PIO):</label
                                >
                                <input
                                    type="text"
                                    v-model="form.presion_intraocular_od"
                                    class="form-control form-control-sm"
                                    placeholder="Ej: 14 mmHg"
                                />
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label class="small font-weight-bold"
                                    >Presión Intraocular OI (PIO):</label
                                >
                                <input
                                    type="text"
                                    v-model="form.presion_intraocular_oi"
                                    class="form-control form-control-sm"
                                    placeholder="Ej: 14 mmHg"
                                />
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label class="small font-weight-bold">Fondo de Ojo OD:</label>
                                <input
                                    type="text"
                                    v-model="form.fondo_ojo_od"
                                    class="form-control form-control-sm"
                                    placeholder="Papila con bordes nítidos..."
                                />
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label class="small font-weight-bold">Fondo de Ojo OI:</label>
                                <input
                                    type="text"
                                    v-model="form.fondo_ojo_oi"
                                    class="form-control form-control-sm"
                                    placeholder="Papila con bordes nítidos..."
                                />
                            </div>

                            <div class="col-12 form-group mb-2">
                                <label class="small font-weight-bold"
                                    >Inspección General / Observaciones del Examen Físico:</label
                                >
                                <textarea
                                    v-model="form.examen_fisico_general"
                                    class="form-control form-control-sm"
                                    rows="2"
                                    placeholder="Detalles complementarios del examen físico..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Diagnósticos CIE-10 -->
                    <div v-show="tabActiva === 'diagnosticos'">
                        <!-- Buscador de Diagnóstico -->
                        <div class="row mb-3">
                            <div class="col-md-8 col-sm-12 mb-2">
                                <label class="small font-weight-bold mb-1"
                                    >Buscar Diagnóstico (CIE-10 o Nombre):</label
                                >
                                <div class="input-group input-group-sm">
                                    <input
                                        type="text"
                                        v-model="busquedaDiag"
                                        class="form-control"
                                        placeholder="Escriba código CIE-10 o nombre de la patología..."
                                        @keyup.enter="buscarDiagnosticos()"
                                    />
                                    <div class="input-group-append">
                                        <button
                                            type="button"
                                            class="btn btn-primary"
                                            @click="buscarDiagnosticos()"
                                            :disabled="buscandoDiag"
                                        >
                                            <i
                                                class="fa mr-1"
                                                :class="
                                                    buscandoDiag
                                                        ? 'fa-spinner fa-spin'
                                                        : 'fa-search'
                                                "
                                            ></i
                                            >Buscar
                                        </button>
                                    </div>
                                </div>

                                <!-- Resultados de la búsqueda -->
                                <ul
                                    v-if="resultadosDiag.length > 0"
                                    class="list-group shadow-sm mt-1 position-absolute w-100"
                                    style="z-index: 1050; max-height: 220px; overflow-y: auto"
                                >
                                    <li
                                        v-for="d in resultadosDiag"
                                        :key="d.id"
                                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2"
                                        style="cursor: pointer"
                                        @click="agregarDiagnostico(d)"
                                    >
                                        <div>
                                            <span class="badge badge-primary mr-2">{{
                                                d.cod_diagnos
                                            }}</span>
                                            <span class="small font-weight-bold">{{
                                                d.nom_diagnos
                                            }}</span>
                                        </div>
                                        <i class="fa fa-plus-circle text-success"></i>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Tabla de Diagnósticos Seleccionados -->
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 8%" class="text-center">Principal</th>
                                        <th style="width: 12%">CIE-10</th>
                                        <th style="width: 32%">Descripción</th>
                                        <th style="width: 18%">Tipo</th>
                                        <th style="width: 16%">Lateralidad</th>
                                        <th style="width: 14%">Observación</th>
                                        <th style="width: 6%" class="text-center">Quitar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="form.diagnosticos.length === 0">
                                        <td colspan="7" class="text-center py-3 text-muted">
                                            <i class="fa fa-stethoscope fa-2x text-muted mb-1"></i>
                                            <p class="mb-0">
                                                No ha asociado diagnósticos a esta consulta. Utilice
                                                el buscador superior.
                                            </p>
                                        </td>
                                    </tr>
                                    <tr v-for="(d, idx) in form.diagnosticos" :key="idx">
                                        <td class="text-center align-middle">
                                            <input
                                                type="radio"
                                                name="diag_principal"
                                                :checked="d.es_principal"
                                                @change="marcarPrincipal(idx)"
                                            />
                                        </td>
                                        <td class="font-weight-bold text-primary align-middle">
                                            {{ d.cod_cie10 }}
                                        </td>
                                        <td class="align-middle">
                                            {{ d.nombre_diagnostico }}
                                        </td>
                                        <td>
                                            <select
                                                v-model="d.tipo_diagnostico"
                                                class="form-control form-control-sm"
                                            >
                                                <option value="CONFIRMADO_NUEVO">
                                                    Confirmado nuevo
                                                </option>
                                                <option value="CONFIRMADO_REPETIDO">
                                                    Confirmado repetido
                                                </option>
                                                <option value="IMPRESION_DIAGNOSTICA">
                                                    Impresión diagnóstica
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <select
                                                v-model="d.lateralidad"
                                                class="form-control form-control-sm"
                                            >
                                                <option value="NO_APLICA">No aplica</option>
                                                <option value="OJO_DERECHO">
                                                    Ojo Derecho (OD)
                                                </option>
                                                <option value="OJO_IZQUIERDO">
                                                    Ojo Izquierdo (OI)
                                                </option>
                                                <option value="BILATERAL">
                                                    Bilateral (Ambos ojos)
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <input
                                                type="text"
                                                v-model="d.observaciones"
                                                class="form-control form-control-sm"
                                                placeholder="Nota..."
                                            />
                                        </td>
                                        <td class="text-center align-middle">
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger btn-xs"
                                                @click="eliminarDiagnostico(idx)"
                                            >
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 4: Concepto y Plan -->
                    <div v-show="tabActiva === 'plan'">
                        <div class="row">
                            <div class="col-12 form-group mb-3">
                                <label class="small font-weight-bold"
                                    >Concepto y Plan de Manejo Clínico:</label
                                >
                                <textarea
                                    v-model="form.concepto_plan"
                                    class="form-control"
                                    rows="6"
                                    placeholder="Indique el plan de tratamiento, recomendaciones, control oftálmico..."
                                ></textarea>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label class="small font-weight-bold">Destino del Paciente:</label>
                                <select
                                    v-model="form.destino_paciente"
                                    class="form-control form-control-sm"
                                >
                                    <option value="Ambulatorio">Ambulatorio</option>
                                    <option value="Control Consulta Externa">
                                        Control Consulta Externa
                                    </option>
                                    <option value="Remitido">
                                        Remitido a otra IPS / Especialidad
                                    </option>
                                    <option value="Urgencias">Traslado a Urgencias</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 5: Procedimientos y Medicamentos -->
                    <div v-show="tabActiva === 'ordenes'">
                        <!-- Subsección 5.1: Órdenes de Procedimientos CUPS -->
                        <div class="card card-outline card-success mb-3">
                            <div
                                class="card-header p-2 bg-light font-weight-bold d-flex justify-content-between align-items-center"
                            >
                                <span
                                    ><i class="fa fa-list-alt mr-1"></i>Órdenes de Procedimientos y
                                    Exámenes (CUPS)</span
                                >
                            </div>
                            <div class="card-body p-2">
                                <div class="row mb-2">
                                    <div class="col-md-8 col-sm-12">
                                        <div class="input-group input-group-sm">
                                            <input
                                                type="text"
                                                v-model="busquedaProdProc"
                                                class="form-control"
                                                placeholder="Buscar procedimiento CUPS (ej: 890302, ecografía, tonometría)..."
                                                @keyup.enter="buscarProductosProcedimientos()"
                                            />
                                            <div class="input-group-append">
                                                <button
                                                    type="button"
                                                    class="btn btn-success"
                                                    @click="buscarProductosProcedimientos()"
                                                    :disabled="buscandoProdProc"
                                                >
                                                    <i
                                                        class="fa mr-1"
                                                        :class="
                                                            buscandoProdProc
                                                                ? 'fa-spinner fa-spin'
                                                                : 'fa-search'
                                                        "
                                                    ></i
                                                    >Buscar CUPS
                                                </button>
                                            </div>
                                        </div>
                                        <!-- Resultados de procedimientos -->
                                        <ul
                                            v-if="resultadosProdProc.length > 0"
                                            class="list-group shadow-sm mt-1 position-absolute w-100"
                                            style="
                                                z-index: 1050;
                                                max-height: 200px;
                                                overflow-y: auto;
                                            "
                                        >
                                            <li
                                                v-for="p in resultadosProdProc"
                                                :key="p.id"
                                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2"
                                                style="cursor: pointer"
                                                @click="agregarProcedimiento(p)"
                                            >
                                                <div>
                                                    <span class="badge badge-success mr-2">{{
                                                        p.cod_cups
                                                    }}</span>
                                                    <span class="small font-weight-bold">{{
                                                        p.nom_produc
                                                    }}</span>
                                                </div>
                                                <i class="fa fa-plus-circle text-success"></i>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-hover mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="width: 15%">Código CUPS</th>
                                                <th style="width: 35%">Procedimiento</th>
                                                <th style="width: 10%" class="text-center">
                                                    Cantidad
                                                </th>
                                                <th style="width: 34%">
                                                    Justificación / Observaciones
                                                </th>
                                                <th style="width: 6%" class="text-center">
                                                    Quitar
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="form.procedimientos.length === 0">
                                                <td colspan="5" class="text-center py-2 text-muted">
                                                    No se han ordenado procedimientos para esta
                                                    consulta.
                                                </td>
                                            </tr>
                                            <tr v-for="(p, idx) in form.procedimientos" :key="idx">
                                                <td
                                                    class="font-weight-bold text-success align-middle"
                                                >
                                                    {{ p.cod_cups }}
                                                </td>
                                                <td class="align-middle">
                                                    {{ p.nombre_procedimiento }}
                                                </td>
                                                <td class="text-center">
                                                    <input
                                                        type="number"
                                                        v-model.number="p.cantidad"
                                                        min="1"
                                                        class="form-control form-control-sm text-center font-weight-bold"
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        v-model="p.justificacion_clinica"
                                                        class="form-control form-control-sm"
                                                        placeholder="Justificación clínica..."
                                                    />
                                                </td>
                                                <td class="text-center align-middle">
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-danger btn-xs"
                                                        @click="form.procedimientos.splice(idx, 1)"
                                                    >
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Subsección 5.2: Formulación de Medicamentos -->
                        <div class="card card-outline card-primary mb-3">
                            <div
                                class="card-header p-2 bg-light font-weight-bold d-flex justify-content-between align-items-center"
                            >
                                <span
                                    ><i class="fa fa-medkit mr-1"></i>Formulación de
                                    Medicamentos</span
                                >
                            </div>
                            <div class="card-body p-2">
                                <div class="row mb-2">
                                    <div class="col-md-8 col-sm-12">
                                        <div class="input-group input-group-sm">
                                            <input
                                                type="text"
                                                v-model="busquedaProdMed"
                                                class="form-control"
                                                placeholder="Buscar colirio / fármaco oftálmico (ej: tobramicina, lágrimas, gotas)..."
                                                @keyup.enter="buscarProductosMedicamentos()"
                                            />
                                            <div class="input-group-append">
                                                <button
                                                    type="button"
                                                    class="btn btn-primary"
                                                    @click="buscarProductosMedicamentos()"
                                                    :disabled="buscandoProdMed"
                                                >
                                                    <i
                                                        class="fa mr-1"
                                                        :class="
                                                            buscandoProdMed
                                                                ? 'fa-spinner fa-spin'
                                                                : 'fa-search'
                                                        "
                                                    ></i
                                                    >Buscar Fármaco
                                                </button>
                                            </div>
                                        </div>
                                        <!-- Resultados de medicamentos -->
                                        <ul
                                            v-if="resultadosProdMed.length > 0"
                                            class="list-group shadow-sm mt-1 position-absolute w-100"
                                            style="
                                                z-index: 1050;
                                                max-height: 200px;
                                                overflow-y: auto;
                                            "
                                        >
                                            <li
                                                v-for="m in resultadosProdMed"
                                                :key="m.id"
                                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2"
                                                style="cursor: pointer"
                                                @click="agregarMedicamento(m)"
                                            >
                                                <div>
                                                    <span class="badge badge-primary mr-2">{{
                                                        m.cod_cups
                                                    }}</span>
                                                    <span class="small font-weight-bold">{{
                                                        m.nom_produc
                                                    }}</span>
                                                </div>
                                                <i class="fa fa-plus-circle text-primary"></i>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-hover mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="width: 25%">Medicamento</th>
                                                <th style="width: 15%">Dosis</th>
                                                <th style="width: 18%">Frecuencia / Vía</th>
                                                <th style="width: 14%">Duración</th>
                                                <th style="width: 8%" class="text-center">Cant</th>
                                                <th style="width: 14%">Indicaciones</th>
                                                <th style="width: 6%" class="text-center">
                                                    Quitar
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="form.medicamentos.length === 0">
                                                <td colspan="7" class="text-center py-2 text-muted">
                                                    No se han formulado medicamentos en esta
                                                    consulta.
                                                </td>
                                            </tr>
                                            <tr v-for="(m, idx) in form.medicamentos" :key="idx">
                                                <td class="align-middle">
                                                    <span
                                                        class="font-weight-bold text-dark d-block"
                                                        >{{ m.nombre_medicamento }}</span
                                                    >
                                                    <small class="text-muted">{{
                                                        m.forma_farmaceutica
                                                    }}</small>
                                                </td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        v-model="m.dosis"
                                                        class="form-control form-control-sm"
                                                        placeholder="Ej: 1 gota"
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        v-model="m.frecuencia_horario"
                                                        class="form-control form-control-sm mb-1"
                                                        placeholder="Ej: Cada 8 horas"
                                                    />
                                                    <input
                                                        type="text"
                                                        v-model="m.via_administracion"
                                                        class="form-control form-control-sm"
                                                        placeholder="Oftálmica"
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        v-model="m.duracion_tratamiento"
                                                        class="form-control form-control-sm"
                                                        placeholder="Ej: 15 días"
                                                    />
                                                </td>
                                                <td class="text-center">
                                                    <input
                                                        type="number"
                                                        v-model.number="m.cantidad_entregar"
                                                        min="1"
                                                        class="form-control form-control-sm text-center font-weight-bold"
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        v-model="m.indicaciones"
                                                        class="form-control form-control-sm"
                                                        placeholder="Instrucciones..."
                                                    />
                                                </td>
                                                <td class="text-center align-middle">
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-danger btn-xs"
                                                        @click="form.medicamentos.splice(idx, 1)"
                                                    >
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 6: Remisión e Incapacidad -->
                    <div v-show="tabActiva === 'anexos'">
                        <div class="row">
                            <!-- Remisión -->
                            <div class="col-md-6 mb-3">
                                <div class="card card-outline card-warning h-100">
                                    <div class="card-header p-2 bg-light font-weight-bold">
                                        <i class="fa fa-share-square-o mr-1"></i>Remisión Médica
                                        (Opcional)
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold mb-1"
                                                >Institución Receptora (IPS / Hospital):</label
                                            >
                                            <input
                                                type="text"
                                                v-model="form.remision.institucion_receptora"
                                                class="form-control form-control-sm"
                                                placeholder="Ej: Hospital Federico Lleras Acosta..."
                                            />
                                        </div>
                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold mb-1"
                                                >Especialidad Requerida:</label
                                            >
                                            <input
                                                type="text"
                                                v-model="form.remision.especialidad_solicitada"
                                                class="form-control form-control-sm"
                                                placeholder="Ej: Retinología, Glaucoma, Córnea..."
                                            />
                                        </div>
                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold mb-1"
                                                >Motivo de Remisión:</label
                                            >
                                            <input
                                                type="text"
                                                v-model="form.remision.motivo_remision"
                                                class="form-control form-control-sm"
                                                placeholder="Causa de la remisión..."
                                            />
                                        </div>
                                        <div class="form-group mb-0">
                                            <label class="small font-weight-bold mb-1"
                                                >Resumen Clínico para Remisión:</label
                                            >
                                            <textarea
                                                v-model="form.remision.resumen_clinico"
                                                class="form-control form-control-sm"
                                                rows="3"
                                                placeholder="Resumen del cuadro, hallazgos y tratamiento previo..."
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Incapacidad -->
                            <div class="col-md-6 mb-3">
                                <div class="card card-outline card-danger h-100">
                                    <div class="card-header p-2 bg-light font-weight-bold">
                                        <i class="fa fa-calendar-times-o mr-1"></i>Incapacidad
                                        Médica (Opcional)
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row">
                                            <div class="col-6 form-group mb-2">
                                                <label class="small font-weight-bold mb-1"
                                                    >Fecha Inicio:</label
                                                >
                                                <input
                                                    type="date"
                                                    v-model="form.incapacidad.fec_inicio"
                                                    class="form-control form-control-sm"
                                                />
                                            </div>
                                            <div class="col-6 form-group mb-2">
                                                <label class="small font-weight-bold mb-1"
                                                    >Días de Incapacidad:</label
                                                >
                                                <input
                                                    type="number"
                                                    v-model.number="
                                                        form.incapacidad.dias_incapacidad
                                                    "
                                                    min="0"
                                                    class="form-control form-control-sm font-weight-bold text-center"
                                                />
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6 form-group mb-2">
                                                <label class="small font-weight-bold mb-1"
                                                    >Tipo Incapacidad:</label
                                                >
                                                <select
                                                    v-model="form.incapacidad.tipo_incapacidad"
                                                    class="form-control form-control-sm"
                                                >
                                                    <option value="Ambulatoria">Ambulatoria</option>
                                                    <option value="Hospitalaria">
                                                        Hospitalaria
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-6 form-group mb-2">
                                                <label class="small font-weight-bold mb-1"
                                                    >Origen:</label
                                                >
                                                <select
                                                    v-model="form.incapacidad.origen_incapacidad"
                                                    class="form-control form-control-sm"
                                                >
                                                    <option value="Enfermedad General">
                                                        Enfermedad General
                                                    </option>
                                                    <option value="Accidente de Trabajo">
                                                        Accidente de Trabajo
                                                    </option>
                                                    <option value="Accidente de Tránsito">
                                                        Accidente de Tránsito
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold mb-1"
                                                >Empresa / Empleador:</label
                                            >
                                            <input
                                                type="text"
                                                v-model="form.incapacidad.empresa"
                                                class="form-control form-control-sm"
                                                placeholder="Nombre de la empresa..."
                                            />
                                        </div>
                                        <div class="form-group mb-0">
                                            <label class="small font-weight-bold mb-1"
                                                >Observaciones Incapacidad:</label
                                            >
                                            <textarea
                                                v-model="form.incapacidad.observaciones"
                                                class="form-control form-control-sm"
                                                rows="2"
                                                placeholder="Recomendaciones laborales o de reposo..."
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, onBeforeUnmount } from "vue";
import { useRoute } from "vue-router";
import axios from "axios";
import { alertError, alertSuccess, confirmAction, toastMessage } from "@/helpers/alerts.js";

const route = useRoute();
const atencionId = ref(route.params.id || route.query.atencion_id || null);

const tabActiva = ref("anamnesis");
const atencion = ref(null);
const consulta = ref(null);
const guardando = ref(false);

const autoguardadoActivo = ref(false);
const mensajeAutoguardado = ref("");
let timerAutoguardado = null;

const busquedaDiag = ref("");
const buscandoDiag = ref(false);
const resultadosDiag = ref([]);

const busquedaProdProc = ref("");
const buscandoProdProc = ref(false);
const resultadosProdProc = ref([]);

const busquedaProdMed = ref("");
const buscandoProdMed = ref(false);
const resultadosProdMed = ref([]);

const form = reactive({
    atencion_id: null,
    motivo_consulta: "",
    enfermedad_actual: "",
    antecedentes_medicos: "",
    antecedentes_quirurgicos_oft: "",
    antecedentes_alergicos: "",
    antecedentes_otros: "",
    revision_sistemas: "",

    av_sc_od: "",
    av_sc_oi: "",
    av_cc_od: "",
    av_cc_oi: "",
    av_ph_od: "",
    av_ph_oi: "",
    motilidad_ocular: "",
    pupilas: "",
    biomicroscopia_od: "",
    biomicroscopia_oi: "",
    presion_intraocular_od: "",
    presion_intraocular_oi: "",
    fondo_ojo_od: "",
    fondo_ojo_oi: "",
    examen_fisico_general: "",

    signos_fc: "",
    signos_pas: "",
    signos_pad: "",
    signos_fr: "",
    signos_spo2: "",
    signos_peso: null,
    signos_talla: null,
    signos_imc: null,

    concepto_plan: "",
    destino_paciente: "Ambulatorio",
    diagnosticos: [],
    procedimientos: [],
    medicamentos: [],
    remision: {
        institucion_receptora: "",
        especialidad_solicitada: "",
        motivo_remision: "",
        resumen_clinico: "",
        observaciones: "",
    },
    incapacidad: {
        fec_inicio: new Date().toISOString().slice(0, 10),
        dias_incapacidad: 0,
        tipo_incapacidad: "Ambulatoria",
        origen_incapacidad: "Enfermedad General",
        empresa: "",
        observaciones: "",
        es_prorroga: false,
    },
});

const cargarDatos = async () => {
    if (!atencionId.value) return;
    try {
        const res = await axios.get(`/api/oftalmologia/consultas/${atencionId.value}`);
        if (res.data?.success) {
            atencion.value = res.data.data?.atencion;
            consulta.value = res.data.data?.consulta;

            form.atencion_id = atencion.value?.id;

            if (consulta.value) {
                Object.assign(form, {
                    motivo_consulta: consulta.value.motivo_consulta || "",
                    enfermedad_actual: consulta.value.enfermedad_actual || "",
                    antecedentes_medicos: consulta.value.antecedentes_medicos || "",
                    antecedentes_quirurgicos_oft: consulta.value.antecedentes_quirurgicos_oft || "",
                    antecedentes_alergicos: consulta.value.antecedentes_alergicos || "",
                    antecedentes_otros: consulta.value.antecedentes_otros || "",
                    revision_sistemas: consulta.value.revision_sistemas || "",

                    av_sc_od: consulta.value.av_sc_od || "",
                    av_sc_oi: consulta.value.av_sc_oi || "",
                    av_cc_od: consulta.value.av_cc_od || "",
                    av_cc_oi: consulta.value.av_cc_oi || "",
                    av_ph_od: consulta.value.av_ph_od || "",
                    av_ph_oi: consulta.value.av_ph_oi || "",
                    motilidad_ocular: consulta.value.motilidad_ocular || "",
                    pupilas: consulta.value.pupilas || "",
                    biomicroscopia_od: consulta.value.biomicroscopia_od || "",
                    biomicroscopia_oi: consulta.value.biomicroscopia_oi || "",
                    presion_intraocular_od: consulta.value.presion_intraocular_od || "",
                    presion_intraocular_oi: consulta.value.presion_intraocular_oi || "",
                    fondo_ojo_od: consulta.value.fondo_ojo_od || "",
                    fondo_ojo_oi: consulta.value.fondo_ojo_oi || "",
                    examen_fisico_general: consulta.value.examen_fisico_general || "",

                    concepto_plan: consulta.value.concepto_plan || "",
                    destino_paciente: consulta.value.destino_paciente || "Ambulatorio",
                    diagnosticos: (consulta.value.diagnosticos || []).map((d) => ({
                        diagnostico_id: d.diagnostico_id,
                        cod_cie10: d.cod_cie10,
                        nombre_diagnostico: d.nombre_diagnostico,
                        es_principal: Boolean(d.es_principal),
                        tipo_diagnostico: d.tipo_diagnostico,
                        lateralidad: d.lateralidad,
                        observaciones: d.observaciones || "",
                    })),
                    procedimientos: (consulta.value.procedimientos || []).map((p) => ({
                        producto_id: p.producto_id,
                        cod_cups: p.cod_cups,
                        nombre_procedimiento: p.nombre_procedimiento,
                        lateralidad: p.lateralidad || "NO_APLICA",
                        cantidad: p.cantidad || 1,
                        observaciones: p.observaciones || "",
                    })),
                    medicamentos: (consulta.value.medicamentos || []).map((m) => ({
                        producto_id: m.producto_id,
                        cod_cups: m.cod_cups,
                        nombre_medicamento: m.nombre_medicamento,
                        dosis: m.dosis || "",
                        frecuencia_horario: m.frecuencia_horario || "",
                        via_administracion: m.via_administracion || "Oftálmica",
                        duracion_tratamiento: m.duracion_tratamiento || "",
                        cantidad_entregar: m.cantidad_entregar || 1,
                        indicaciones: m.indicaciones || "",
                    })),
                });

                if (consulta.value.remision) {
                    form.remision = {
                        institucion_receptora: consulta.value.remision.institucion_receptora || "",
                        especialidad_solicitada:
                            consulta.value.remision.especialidad_solicitada || "",
                        motivo_remision: consulta.value.remision.motivo_remision || "",
                        resumen_clinico: consulta.value.remision.resumen_clinico || "",
                        observaciones: consulta.value.remision.observaciones || "",
                    };
                }

                if (consulta.value.incapacidad) {
                    form.incapacidad = {
                        fec_inicio:
                            consulta.value.incapacidad.fec_inicio ||
                            new Date().toISOString().slice(0, 10),
                        dias_incapacidad: consulta.value.incapacidad.dias_incapacidad || 0,
                        tipo_incapacidad:
                            consulta.value.incapacidad.tipo_incapacidad || "Ambulatoria",
                        origen_incapacidad:
                            consulta.value.incapacidad.origen_incapacidad || "Enfermedad General",
                        empresa: consulta.value.incapacidad.empresa || "",
                        observaciones: consulta.value.incapacidad.observaciones || "",
                        es_prorroga: Boolean(consulta.value.incapacidad.es_prorroga),
                    };
                }
            }
        }
    } catch (e) {
        alertError(e.response?.data?.message || "No fue posible cargar la atención.", "Error");
    }
};

const guardarBorrador = async (silencioso = false) => {
    guardando.value = true;
    try {
        const payload = { ...form, finalizar: false };
        const res = await axios.post("/api/oftalmologia/consultas", payload);
        if (res.data?.success) {
            consulta.value = res.data.data;
            const hora = new Date().toLocaleTimeString("es-CO", {
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit",
            });
            autoguardadoActivo.value = true;
            mensajeAutoguardado.value = `Guardado a las ${hora}`;

            if (!silencioso) {
                toastMessage({
                    heading: "Borrador Guardado",
                    text: "Información clínica guardada correctamente.",
                    icon: "success",
                });
            }
        }
    } catch (e) {
        if (!silencioso) {
            alertError(e.response?.data?.message || "No fue posible guardar el borrador.", "Error");
        }
    } finally {
        guardando.value = false;
    }
};

const confirmarFinalizar = async () => {
    if (form.diagnosticos.length === 0) {
        alertError(
            "Debe agregar al menos un diagnóstico principal antes de finalizar la consulta.",
            "Diagnóstico Requerido"
        );
        tabActiva.value = "diagnosticos";
        return;
    }

    const confirmed = await confirmAction({
        title: "¿Finalizar Consulta Médica?",
        text: "La atención quedará marcada como atendida y se registrará la historia clínica definitiva.",
        type: "warning",
    });

    if (confirmed) {
        guardando.value = true;
        try {
            const payload = { ...form, finalizar: true };
            const res = await axios.post("/api/oftalmologia/consultas", payload);
            if (res.data?.success) {
                alertSuccess(
                    "Consulta médica de oftalmología finalizada con éxito.",
                    "Atención Finalizada"
                );
                consulta.value = res.data.data;
            }
        } catch (e) {
            alertError(
                e.response?.data?.message || "No fue posible finalizar la consulta.",
                "Error"
            );
        } finally {
            guardando.value = false;
        }
    }
};

const buscarDiagnosticos = async () => {
    const term = busquedaDiag.value?.trim();
    if (!term || term.length < 2) return;
    buscandoDiag.value = true;
    try {
        const res = await axios.get(
            `/api/oftalmologia/diagnosticos/buscar/${encodeURIComponent(term)}`
        );
        resultadosDiag.value = res.data?.data || [];
    } catch (e) {
        console.error("Error buscando diagnósticos:", e);
    } finally {
        buscandoDiag.value = false;
    }
};

const buscarProductosProcedimientos = async () => {
    const term = busquedaProdProc.value?.trim();
    if (!term || term.length < 2) return;
    buscandoProdProc.value = true;
    try {
        const res = await axios.get(
            `/api/oftalmologia/productos/buscar/${encodeURIComponent(term)}`
        );
        resultadosProdProc.value = res.data?.data || [];
    } catch (e) {
        console.error("Error buscando procedimientos:", e);
    } finally {
        buscandoProdProc.value = false;
    }
};

const agregarProcedimiento = (prod) => {
    const yaExiste = form.procedimientos.some((p) => p.producto_id === prod.id);
    if (yaExiste) {
        toastMessage({
            heading: "Aviso",
            text: "El procedimiento ya está en la lista de órdenes.",
            icon: "warning",
        });
        return;
    }

    form.procedimientos.push({
        producto_id: prod.id,
        cod_cups: prod.cod_cups,
        nombre_procedimiento: prod.nom_produc,
        lateralidad: "NO_APLICA",
        cantidad: 1,
        observaciones: "",
    });

    resultadosProdProc.value = [];
    busquedaProdProc.value = "";
};

const buscarProductosMedicamentos = async () => {
    const term = busquedaProdMed.value?.trim();
    if (!term || term.length < 2) return;
    buscandoProdMed.value = true;
    try {
        const res = await axios.get(
            `/api/oftalmologia/productos/buscar/${encodeURIComponent(term)}`
        );
        resultadosProdMed.value = res.data?.data || [];
    } catch (e) {
        console.error("Error buscando medicamentos:", e);
    } finally {
        buscandoProdMed.value = false;
    }
};

const agregarMedicamento = (prod) => {
    const yaExiste = form.medicamentos.some((m) => m.producto_id === prod.id);
    if (yaExiste) {
        toastMessage({
            heading: "Aviso",
            text: "El medicamento ya está en la fórmula.",
            icon: "warning",
        });
        return;
    }

    form.medicamentos.push({
        producto_id: prod.id,
        cod_cups: prod.cod_cups,
        nombre_medicamento: prod.nom_produc,
        dosis: "1 gota",
        frecuencia_horario: "Cada 8 horas",
        via_administracion: "Oftálmica",
        duracion_tratamiento: "7 días",
        cantidad_entregar: 1,
        indicaciones: "",
    });

    resultadosProdMed.value = [];
    busquedaProdMed.value = "";
};

const agregarDiagnostico = (diag) => {
    const yaExiste = form.diagnosticos.some(
        (d) => d.diagnostico_id === diag.id || d.cod_cie10 === diag.cod_diagnos
    );
    if (yaExiste) {
        toastMessage({
            heading: "Aviso",
            text: "El diagnóstico ya está en la lista.",
            icon: "warning",
        });
        return;
    }

    form.diagnosticos.push({
        diagnostico_id: diag.id,
        cod_cie10: diag.cod_diagnos,
        nombre_diagnostico: diag.nom_diagnos,
        es_principal: form.diagnosticos.length === 0,
        tipo_diagnostico: "CONFIRMADO_NUEVO",
        lateralidad: "NO_APLICA",
        observaciones: "",
    });

    resultadosDiag.value = [];
    busquedaDiag.value = "";
};

const marcarPrincipal = (idx) => {
    form.diagnosticos.forEach((d, i) => {
        d.es_principal = i === idx;
    });
};

const eliminarDiagnostico = (idx) => {
    form.diagnosticos.splice(idx, 1);
    if (form.diagnosticos.length > 0 && !form.diagnosticos.some((d) => d.es_principal)) {
        form.diagnosticos[0].es_principal = true;
    }
};

const calcularEdad = () => {
    const fech = atencion.value?.paciente?.fec_naci;
    if (!fech) return "-";
    const d = new Date(fech);
    const diff = Date.now() - d.getTime();
    const ageDate = new Date(diff);
    return `${Math.abs(ageDate.getUTCFullYear() - 1970)} años`;
};

onMounted(async () => {
    await cargarDatos();
    // Autoguardado cada 60 segundos
    timerAutoguardado = setInterval(() => {
        if (form.atencion_id && consulta.value?.estado_consulta !== "FINALIZADA") {
            guardarBorrador(true);
        }
    }, 60000);
});

onBeforeUnmount(() => {
    if (timerAutoguardado) {
        clearInterval(timerAutoguardado);
    }
});
</script>

<style scoped>
.thead-light th {
    background-color: #f8f9fa;
    font-size: 0.85rem;
}
</style>
