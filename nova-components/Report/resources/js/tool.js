Nova.booting((Vue, router) => {
    router.addRoutes([
        {
            name: 'report',
            path: '/report',
            component: require('./components/Tool'),
        },
    ])
})
