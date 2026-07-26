import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

const paginas = import.meta.glob('./pages/**/*.vue', { eager: true });

createInertiaApp({
    resolve: (nome) => {
        const pagina = paginas[`./pages/${nome}.vue`];

        if (!pagina) {
            throw new Error(`Página Inertia não encontrada: ${nome}`);
        }

        return pagina.default;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
