<template>
    <footer class="footer">
        © 2017 Adminwrap by wrappixel.com

        <div class="float-right">
            <h4 class="text-info">Usted esta en la sede: {{ nomSede }}</h4>
            <h4 class="text-info" v-if="mostrarMedico">
                {{ nomMedico ? 'Usted esta en el médico: ' + nomMedico : 'No ha seleccionado médico' }}
            </h4>
        </div>
    </footer>
</template>
<script>

export default {
    mounted() {
        this.establecerSede();
        this.emitter.on("sedeSeleccionada", (data) => {
            sessionStorage.setItem('ST-sede', data.idSede)
            sessionStorage.setItem('ST-nomsede', data.nomSede)
            this.nomSede = data.nomSede;

        });

        this.emitter.on("medicoSeleccionado", (data) => {
            sessionStorage.setItem('ST-medico', data.idMedico)
            sessionStorage.setItem('ST-nomMedico', data.nomMedico)
            this.nomMedico = data.nomMedico;
        });

        const url = window.location.pathname;
        if (url != '/ecografias-pendientes-lecturas' && url != '/estudios.leer.estudio.listar') {
            this.mostrarMedico = false;
        }
    },
    data() {
        return {
            nomSede: sessionStorage.getItem('ST-nomsede'),
            nomMedico: sessionStorage.getItem('ST-nomMedico'),
            mostrarMedico: '',
        }
    },
    methods: {
        establecerSede() {
            this.nomSede = sessionStorage.getItem('ST-nomsede');
        },
        establecerMedico() {
            this.nomMedico = sessionStorage.getItem('ST-nomMedico');
        }
    }
};
</script>