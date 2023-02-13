let sucursalActua = document.head.querySelector('meta[name="sucursalActua"]');

export default {
    computed: {
        sucursalActual() {
            return JSON.parse(sucursalActua.content)
        }
    }
}