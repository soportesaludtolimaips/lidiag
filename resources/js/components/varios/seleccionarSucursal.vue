<template>
    <div>
        <a class="nav-link dropdown-toggle waves-effect waves-dark" href="" data-toggle="dropdown" aria-haspopup="true"
            aria-expanded="false">
            <i class="flag-icon flag-icon-us"></i> {{ nomSucursal }}
        </a>
        <div class="dropdown-menu dropdown-menu-right animated bounceInDown">
            <a class="dropdown-item" href="#" v-for="(item, index) in registros" :key="index"
                @click="establecerSucursal(item)">
                <i class="flag-icon flag-icon-in"></i> {{ item.sucursal }}
            </a>
        </div>
    </div>
</template>
<script>

//import sucursalActual from '../../mixins/sucursal';

export default {
    mounted() {
        this.listarSucursales();
    },
    data() {
        return {
            sucursalActual: 1,
            nomSucursal: '',
            registros: []
        }
    },
    //mixins: [sucursalActual],
    methods: {
        async listarSucursales() {
            const res = await axios.get('api/config-sucursales');
            this.registros = res.data;
        },
        establecerSucursal(data = {}) {
            this.nomSucursal = data.sucursal;
            this.sucursalActual = data.id;
        },
    }
}
</script>