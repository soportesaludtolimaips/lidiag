<template>
    <div>
        <a
            class="nav-link dropdown-toggle waves-effect waves-dark"
            href=""
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
        >
            <i class="flag-icon flag-icon-us"></i> {{ nomSucursal }}
        </a>
        <div class="dropdown-menu dropdown-menu-right animated bounceInDown">
            <a
                class="dropdown-item"
                href="#"
                v-for="(item, index) in registros"
                :key="index"
                @click="establecerSucursal(item)"
            >
                <i class="flag-icon flag-icon-in"></i> {{ item.sucursal }}
            </a>
        </div>
    </div>
</template>
<script>
//import sucursalActual from '../../mixins/sucursal';
import { handleAxiosErrorToast } from "@/helpers/alerts";

export default {
    mounted() {
        this.listarSucursales();
    },
    data() {
        return {
            sucursalActual: 1,
            nomSucursal: "",
            registros: [],
        };
    },
    //mixins: [sucursalActual],
    methods: {
        async listarSucursales() {
            try {
                const res = await axios.get("/api/config-sedes");
                this.registros = res.data;
                if (
                    Array.isArray(this.registros) &&
                    this.registros.length > 0 &&
                    !this.nomSucursal
                ) {
                    this.nomSucursal = this.registros[0].sucursal || "";
                    sessionStorage.setItem("ST-sede", this.registros[0].id);
                    this.emitter.emit("sedeSeleccionada", { idSede: this.registros[0].id });
                }
            } catch (error) {
                handleAxiosErrorToast(error, "No fue posible listar sucursales.");
                this.registros = [];
            }
        },
        establecerSucursal(data = {}) {
            this.nomSucursal = data.sucursal;
            this.sucursalActual = data.id;
            sessionStorage.setItem("ST-sede", data.id);
            this.emitter.emit("sedeSeleccionada", { idSede: data.id });
        },
    },
};
</script>
