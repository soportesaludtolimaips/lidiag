let user = document.head.querySelector('meta[name="user"]');

export default {
    computed: {
        usuarioActua() {
            return JSON.parse(user.content)
        },
        isAutenticado() {
            return !!user.content
        }
    },
};