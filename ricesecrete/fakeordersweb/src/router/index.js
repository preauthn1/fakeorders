import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/Home.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      redirect:'/home',
      children:[
        {
          path:'home',
          name:'Home',
          component:HomeView,
        }
      ]
      // component: HomeView,
    },
  ],
})

export default router
