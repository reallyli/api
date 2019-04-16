<template>
    <div>
        <heading class="mb-6">Report</heading>

        <card class="flex flex-col p-6 justify-center" style="min-height: 300px">
            <table class="table table-striped table-bordered" width="100%">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Business Count</th>
                    </tr>
                </thead>
                <tbody v-if="categories.length">
                    <tr v-for="category in categories">
                        <td>{{category.name}}</td>
                        <td align="center">{{category.businessCount}}</td>
                    </tr>
                </tbody>
            </table>
        </card>
    </div>
</template>

<script>
export default {
    props: {
        
    },
    data(){
        return {
            categories: []
        }
    },
    methods: {
        getCategories(){
            axios
                .get('/api/v1/categories/business-stats')
                .then(response => {
                    this.categories = response.data.data;
                });
        }
    },
    mounted() {
        this.getCategories()
    },
}
</script>

<style>
/* Scoped Styles */
</style>
