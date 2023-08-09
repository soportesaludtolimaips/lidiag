<template>
    <footer class="footer">
        © 2017 Adminwrap by wrappixel.com

        <div class="float-right">
            <h4 class="text-info">Usted esta el la sede: {{ nomSede }}</h4>
        </div>
    </footer>
</template>
<script>
export default {
    mounted() {
        this.establecerSede();
        this.emitter.on("sedeSeleccionada", (data) => {
            console.log('********* Footer emitter ***********');
            console.log(data.idSede)
            console.log(data.nomSede)
            sessionStorage.setItem('ST-sede', data.idSede)
            sessionStorage.setItem('ST-nomsede', data.nomSede)
            this.nomSede = data.nomSede;

        });
    },
    data() {
        return {
            nomSede: sessionStorage.getItem('ST-nomsede')
        }
    },
    methods: {
        establecerSede() {
            this.nomSede = sessionStorage.getItem('ST-nomsede');
        },
    }
};
</script>