<template>
    <div>
        <div class="navbar-header">
            <a class="navbar-brand" href="index.html">
                <!-- Logo icon --><b>
                    <!--You can put here icon as well // <i class="wi wi-sunset"></i> //-->
                    <!-- Dark Logo icon -->
                    <img :src="ruta + '/admin-wrap/assets/images/logo-icon.png'" alt="homepage" class="dark-logo" />
                    <!-- Light Logo icon -->
                    <img :src="ruta + '/admin-wrap/assets/images/logo-light-icon.png'" alt="homepage"
                        class="light-logo" />
                </b>
                <!--End Logo icon -->
                <!-- Logo text --><span>
                    <!-- dark Logo text -->
                    <img :src="ruta + '/admin-wrap/assets/images/logo-text.png'" alt="homepage" class="dark-logo" />
                    <!-- Light Logo text -->
                    <img :src="ruta + '/admin-wrap/assets/images/logo-light-text.png'" class="light-logo"
                        alt="homepage" />
                </span>
            </a>
        </div>
    </div>
</template>
<script>
export default {
    props: ['ruta']
}
</script>