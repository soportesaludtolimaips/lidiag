<template>
    <ul class="navbar-nav my-lg-0">
        <!-- ============================================================== -->
        <!-- Notificaciones (Helpdesk) -->
        <!-- ============================================================== -->
        <li class="nav-item dropdown" v-if="hdDisponible">
            <a
                class="nav-link dropdown-toggle waves-effect waves-dark"
                href=""
                id="hd_bell"
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
                @click.prevent="onOpenHdDropdown()"
            >
                <i class="icon-Bell"></i>
                <div class="notify" v-if="hdUnreadCount > 0">
                    <span class="heartbit"></span>
                    <span class="point"></span>
                </div>
            </a>
            <div
                class="dropdown-menu dropdown-menu-right mailbox animated bounceInDown"
                aria-labelledby="hd_bell"
            >
                <ul>
                    <li>
                        <div class="drop-title d-flex justify-content-between align-items-center">
                            <span>Helpdesk</span>
                            <a
                                href="/helpdesk-notificaciones"
                                class="text-info"
                                @click.prevent="goHdNotificaciones()"
                            >
                                Ver todas
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="message-center">
                            <div v-if="hdLoading" class="p-10 text-muted">
                                <i class="fa fa-spinner fa-spin"></i> Cargando...
                            </div>

                            <a
                                v-for="n in hdItems"
                                :key="n.id"
                                href="#"
                                @click.prevent="abrirHdNotificacion(n)"
                            >
                                <div
                                    class="btn btn-circle"
                                    :class="n.read_at ? 'btn-secondary' : 'btn-info'"
                                >
                                    <i class="fa" :class="n.read_at ? 'fa-check' : 'fa-bell'"></i>
                                </div>
                                <div class="mail-contnet">
                                    <h5 class="m-0">{{ n.data?.folio || "Ticket" }}</h5>
                                    <span class="mail-desc">{{ n.data?.mensaje || "" }}</span>
                                    <span class="time">{{ formatHdTime(n.created_at) }}</span>
                                </div>
                            </a>

                            <div v-if="!hdLoading && hdItems.length === 0" class="p-10 text-muted">
                                Sin notificaciones.
                            </div>
                        </div>
                    </li>
                    <li v-if="hdUnreadCount > 0">
                        <a class="nav-link text-center" href="#" @click.prevent="marcarHdTodas()">
                            <strong>Marcar todas como leídas</strong>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- ============================================================== -->
        <!-- Comment (plantilla) -->
        <!-- ============================================================== -->
        <noscript>
            <li class="nav-item dropdown">
                <a
                    class="nav-link dropdown-toggle waves-effect waves-dark"
                    href=""
                    data-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    <i class="icon-Bell"></i>
                    <div class="notify">
                        <span class="heartbit"></span>
                        <span class="point"></span>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-right mailbox animated bounceInDown">
                    <ul>
                        <li>
                            <div class="drop-title">Notificaciones</div>
                        </li>
                        <li>
                            <div class="message-center">
                                <!-- Message -->
                                <a href="#">
                                    <div class="btn btn-danger btn-circle">
                                        <i class="fa fa-link"></i>
                                    </div>
                                    <div class="mail-contnet">
                                        <h5>Luanch Admin</h5>
                                        <span class="mail-desc">Just see the my new admin!</span>
                                        <span class="time">9:30 AM</span>
                                    </div>
                                </a>
                                <!-- Message -->
                                <a href="#">
                                    <div class="btn btn-success btn-circle">
                                        <i class="ti-calendar"></i>
                                    </div>
                                    <div class="mail-contnet">
                                        <h5>Event today</h5>
                                        <span class="mail-desc"
                                            >Just a reminder that you have event</span
                                        >
                                        <span class="time">9:10 AM</span>
                                    </div>
                                </a>
                                <!-- Message -->
                                <a href="#">
                                    <div class="btn btn-info btn-circle">
                                        <i class="ti-settings"></i>
                                    </div>
                                    <div class="mail-contnet">
                                        <h5>Settings</h5>
                                        <span class="mail-desc"
                                            >You can customize this template as you want</span
                                        >
                                        <span class="time">9:08 AM</span>
                                    </div>
                                </a>
                                <!-- Message -->
                                <a href="#">
                                    <div class="btn btn-primary btn-circle">
                                        <i class="ti-user"></i>
                                    </div>
                                    <div class="mail-contnet">
                                        <h5>Pavan kumar</h5>
                                        <span class="mail-desc">Just see the my admin!</span>
                                        <span class="time">9:02 AM</span>
                                    </div>
                                </a>
                            </div>
                        </li>
                        <li>
                            <a class="nav-link text-center" href="javascript:void(0);">
                                <strong>Check all notifications</strong>
                                <i class="fa fa-angle-right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </noscript>
        <!-- ============================================================== -->
        <!-- End Comment -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Messages -->
        <!-- ============================================================== -->
        <noscript>
            <li class="nav-item dropdown">
                <a
                    class="nav-link dropdown-toggle waves-effect waves-dark"
                    href=""
                    id="2"
                    data-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    <i class="icon-Mail"></i>
                    <div class="notify">
                        <span class="heartbit"></span>
                        <span class="point"></span>
                    </div>
                </a>
                <div
                    class="dropdown-menu mailbox dropdown-menu-right animated bounceInDown"
                    aria-labelledby="2"
                >
                    <ul>
                        <li>
                            <div class="drop-title">You have 4 new messages</div>
                        </li>
                        <li>
                            <div class="message-center">
                                <!-- Message -->
                                <a href="#">
                                    <div class="user-img">
                                        <img
                                            src="@/admin-wrap/assets/images/users/1.jpg"
                                            alt="user"
                                            class="img-circle"
                                        />
                                        <span class="profile-status online pull-right"></span>
                                    </div>
                                    <div class="mail-contnet">
                                        <h5>Pavan kumar</h5>
                                        <span class="mail-desc">Just see the my admin!</span>
                                        <span class="time">9:30 AM</span>
                                    </div>
                                </a>
                                <!-- Message -->
                                <a href="#">
                                    <div class="user-img">
                                        <img
                                            src="@/admin-wrap/assets/images/users/2.jpg"
                                            alt="user"
                                            class="img-circle"
                                        />
                                        <span class="profile-status busy pull-right"></span>
                                    </div>
                                    <div class="mail-contnet">
                                        <h5>Sonu Nigam</h5>
                                        <span class="mail-desc">I've sung a song! See you at</span>
                                        <span class="time">9:10 AM</span>
                                    </div>
                                </a>
                                <!-- Message -->
                                <a href="#">
                                    <div class="user-img">
                                        <img
                                            src="@/admin-wrap/assets/images/users/3.jpg"
                                            alt="user"
                                            class="img-circle"
                                        />
                                        <span class="profile-status away pull-right"></span>
                                    </div>
                                    <div class="mail-contnet">
                                        <h5>Arijit Sinh</h5>
                                        <span class="mail-desc">I am a singer!</span>
                                        <span class="time">9:08 AM</span>
                                    </div>
                                </a>
                                <!-- Message -->
                                <a href="#">
                                    <div class="user-img">
                                        <img
                                            src="@/admin-wrap/assets/images/users/4.jpg"
                                            alt="user"
                                            class="img-circle"
                                        />
                                        <span class="profile-status offline pull-right"></span>
                                    </div>
                                    <div class="mail-contnet">
                                        <h5>Pavan kumar</h5>
                                        <span class="mail-desc">Just see the my admin!</span>
                                        <span class="time">9:02 AM</span>
                                    </div>
                                </a>
                            </div>
                        </li>
                        <li>
                            <a class="nav-link text-center" href="javascript:void(0);">
                                <strong>See all e-Mails</strong>
                                <i class="fa fa-angle-right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </noscript>
        <!-- ============================================================== -->
        <!-- End Messages -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- mega menu -->
        <!-- ============================================================== -->
        <li class="nav-item dropdown mega-dropdown">
            <a
                class="nav-link dropdown-toggle waves-effect waves-dark"
                href=""
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
            >
                <i class="icon-Box-Close"></i
            ></a>
            <div class="dropdown-menu animated bounceInDown">
                <ul class="mega-dropdown-menu row">
                    <li class="col-lg-3 m-b-30">
                        <!-- Contact -->
                        <form>
                            <div class="form-group">
                                <h4 class="m-b-20">Sedes</h4>
                                <select
                                    class="form-control custom-select"
                                    @change="estableserSede()"
                                    style="width: 100%; height: 36px"
                                    v-model="sedeSeleccionada"
                                    name="sedes"
                                >
                                    <option>Seleccione la sede</option>
                                    <option
                                        v-for="ItemSede in sedes"
                                        :key="ItemSede"
                                        :value="ItemSede.id"
                                    >
                                        {{ ItemSede.nom_sede }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group" v-if="mostrarMedico">
                                <h4 class="m-b-20">Médico</h4>
                                <select
                                    class="form-control custom-select"
                                    @change="estableserMedico()"
                                    style="width: 100%; height: 36px"
                                    v-model="medicoSeleccionado"
                                    name="medicos"
                                >
                                    <option>Seleccione el médico</option>
                                    <option
                                        v-for="ItemMedico in medicos"
                                        :key="ItemMedico"
                                        :value="ItemMedico.id"
                                    >
                                        {{ ItemMedico.name }}
                                    </option>
                                </select>
                            </div>
                        </form>
                    </li>
                </ul>
            </div>
        </li>
        <!-- ============================================================== -->
        <!-- End mega menu -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Language -->
        <!-- ============================================================== -->
        <!-- <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle waves-effect waves-dark" href="" data-toggle="dropdown" aria-haspopup="true"
                aria-expanded="false">
                <i class="flag-icon flag-icon-us"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right animated bounceInDown">
                <a class="dropdown-item" href="#">
                    <i class="flag-icon flag-icon-in"></i> India
                </a>
                <a class="dropdown-item" href="#">
                    <i class="flag-icon flag-icon-fr"></i> French</a>
                <a class="dropdown-item" href="#">
                    <i class="flag-icon flag-icon-cn"></i> China</a>
                <a class="dropdown-item" href="#">
                    <i class="flag-icon flag-icon-de"></i> Dutch
                </a>
            </div>
        </li> -->
        <!-- ============================================================== -->
        <!-- Profile -->
        <!-- ============================================================== -->
        <li class="nav-item dropdown u-pro">
            <a
                class="nav-link dropdown-toggle waves-effect waves-dark profile-pic"
                href=""
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
            >
                <img src="@/admin-wrap/assets/images/users/1.jpg" alt="user" class="" />
                <span class="hidden-md-down">
                    &nbsp; {{ usuarioactual.name }}
                    <i class="fa fa-angle-down"></i>
                </span>
            </a>
            <div class="dropdown-menu dropdown-menu-right animated flipInY">
                <ul class="dropdown-user">
                    <li>
                        <div class="dw-user-box">
                            <div class="u-img">
                                <img src="@/admin-wrap/assets/images/users/1.jpg" alt="user" />
                            </div>
                            <div class="u-text">
                                <h4>{{ usuarioactual.name }}</h4>
                                <p class="text-muted">{{ usuarioactual.email }}</p>
                                <!-- <router-link class="btn btn-rounded btn-danger btn-sm" :to="{
                                    name: 'seguridad.usuario.perfil', params: { id: usuarioactual.id }
                                }">
                                    Ver Perfil
                                </router-link> -->
                            </div>
                        </div>
                    </li>
                    <li role="separator" class="divider"></li>
                    <li>
                        <a :href="perfilUrl || '/seguridad.perfil'">
                            <i class="ti-user"></i> Mi Perfil
                        </a>
                    </li>
                    <li role="separator" class="divider"></li>
                    <li>
                        <a href="#"> <i class="ti-settings"></i> Configuración de cuenta </a>
                    </li>
                    <li role="separator" class="divider"></li>
                    <li>
                        <a href="#" @click.prevent="Logout()">
                            <i class="fa fa-power-off"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </li>
    </ul>
</template>
<script>
import { handleAxiosErrorToast } from "@/helpers/alerts.js";

export default {
    props: ["usuarioactual", "perfilUrl"],
    mounted() {
        this.establecerModuloActual();
        this.listarSedes();
        this.listarMedicos();
        this.cargarHdNotificaciones();
        this.hdIntervalId = setInterval(() => this.cargarHdNotificaciones(true), 30000);

        const medicoSeleccionado = sessionStorage.getItem("ST-medico");
        const sedeSeleccionada = sessionStorage.getItem("ST-sede");

        const url = window.location.pathname;
        if (
            url === "/ecografias.admisiones" ||
            url === "/ecografias-pendientes-lecturas" ||
            url === "/estudios.leer.estudio.listar"
        ) {
            this.mostrarMedico = true;
        }
    },
    beforeUnmount() {
        try {
            if (this.hdIntervalId) clearInterval(this.hdIntervalId);
        } catch (_) {}
    },
    data() {
        return {
            sedes: [], //Listo las sedes
            sedeSeleccionada: this.sedeSeleccionada
                ? this.sedeSeleccionada
                : sessionStorage.getItem("ST-sede"),
            medicoSeleccionado: this.medicoSeleccionado
                ? this.medicoSeleccionado
                : sessionStorage.getItem("ST-medico"),
            medicos: [],
            nomMedicoSeleccionado: null,
            mostrarMedico: true,

            // Helpdesk notificaciones
            hdDisponible: true,
            hdLoading: false,
            hdItems: [],
            hdIntervalId: null,
        };
    },
    computed: {
        hdUnreadCount() {
            return (this.hdItems || []).filter((x) => !x.read_at).length;
        },
        medicoOptions() {
            // Opciones para el Select2 local de medicos.
            return (this.medicos || []).map((p) => ({ id: String(p.id), name: p.name }));
        },
    },
    methods: {
        establecerModuloActual() {
            try {
                const pathname = String(window?.location?.pathname || "").toLowerCase();
                let moduloId = sessionStorage.getItem("ST-modulo");
                if (moduloId) return String(moduloId);

                // Heurística del proyecto (mismos IDs que se ven en llamadas existentes):
                // - ecografías: 2
                // - estudios: 1
                if (pathname.includes("ecografia")) moduloId = "2";
                else if (pathname.includes("estudios") || pathname.includes("study"))
                    moduloId = "1";
                else moduloId = "1";

                sessionStorage.setItem("ST-modulo", moduloId);
                return String(moduloId);
            } catch (_) {
                return "1";
            }
        },
        async Logout() {
            try {
                // Usa el logout estándar de Laravel UI (Auth::routes)
                await axios.post("/logout");
            } finally {
                // Asegura salida al login aunque el response sea 204 sin body
                window.location.href = "/";
            }
        },
        async listarSedes() {
            try {
                const res = await axios.get("/api/config-sedes/listar/sedes/activas");
                this.sedes = res.data;
            } catch (error) {
                console.log(error);
            }
        },
        async listarMedicos() {
            try {
                const moduloId =
                    sessionStorage.getItem("ST-modulo") || this.establecerModuloActual();
                const res = await axios.get(`/api/user.listarUsuarios/Medico/${moduloId}`);
                this.medicos = res.data;
            } catch (error) {
                console.log(error);
            }
        },
        async estableserSede() {
            const res = await axios.get(
                "/api/config-sedes/" + this.sedeSeleccionada + "/buscarPorId"
            );

            this.nomSedeSeleccionada = $('select[name="sedes"] option:selected').text();

            sessionStorage.setItem("ST-sede", this.sedeSeleccionada);
            sessionStorage.setItem("ST-nomsede", this.nomSedeSeleccionada);
            sessionStorage.setItem("ST-urlsede", res.data.url_oviyam);
            sessionStorage.setItem("ST-servername", res.data.tap_oviyam);
            sessionStorage.setItem("ST-email_repor", res.data.email_repor);

            this.emitter.emit("sedeSeleccionada", {
                idSede: this.sedeSeleccionada,
                nomSede: this.nomSedeSeleccionada,
                url: this.urlSedeSeleccionada,
                serverName: this.serverNameSeleccionada,
                email_repor: res.data.email_repor,
            });
        },
        async estableserMedico() {
            this.nomMedicoSeleccionado = $('select[name="medicos"] option:selected').text();

            sessionStorage.setItem("ST-medico", this.medicoSeleccionado);
            sessionStorage.setItem("ST-nomMedico", this.nomMedicoSeleccionado);

            this.emitter.emit("medicoSeleccionado", {
                idMedico: this.medicoSeleccionado,
                nomMedico: this.nomMedicoSeleccionado,
            });
        },

        onOpenHdDropdown() {
            // Recarga rápida al abrir
            this.cargarHdNotificaciones(true);
        },
        formatHdTime(dt) {
            if (!dt) return "";
            try {
                return window.dayjs(dt).tz("America/Bogota").format("YYYY-MM-DD HH:mm");
            } catch (_) {
                return String(dt);
            }
        },
        async cargarHdNotificaciones(silent = false) {
            if (!this.hdDisponible) return;
            if (this.hdLoading) return;
            this.hdLoading = true;
            try {
                const res = await axios.get("/api/helpdesk-notificaciones", {
                    params: { limit: 10 },
                });
                this.hdItems = res.data?.items || [];
            } catch (error) {
                const status = error?.response?.status;
                // Si no tiene permisos o no existe el endpoint, ocultar el ícono.
                if (status === 403 || status === 404) {
                    this.hdDisponible = false;
                } else if (!silent) {
                    handleAxiosErrorToast(error, "No se pudieron cargar notificaciones.");
                }
            } finally {
                this.hdLoading = false;
            }
        },
        async marcarHdLeida(n) {
            try {
                await axios.post(`/api/helpdesk-notificaciones/${n.id}/read`);
                n.read_at = new Date().toISOString();
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudo marcar como leída.");
            }
        },
        async marcarHdTodas() {
            try {
                await axios.post("/api/helpdesk-notificaciones/read-all");
                await this.cargarHdNotificaciones(true);
            } catch (error) {
                handleAxiosErrorToast(error, "No se pudieron marcar todas.");
            }
        },
        async abrirHdNotificacion(n) {
            if (!n.read_at) await this.marcarHdLeida(n);
            if (n.data?.ticket_id) window.location.href = `/helpdesk-tickets/${n.data.ticket_id}`;
        },
        goHdNotificaciones() {
            window.location.href = "/helpdesk-notificaciones";
        },
    },
};
</script>
