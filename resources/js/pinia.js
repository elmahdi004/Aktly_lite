import { defineStore } from 'pinia';
import axiosInstance from './axios';
export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        isAdmin: false
    }),
    actions: {
        setUser(userData) {
            this.user = userData;
            this.isAdmin = userData.is_admin == true;
        },
        async fetchUser() {
            return this.checkAuthStatus();
        },
        async checkAuthStatus() {
            this.user = null;
            this.isAdmin = false;
            
            const response = await axiosInstance.get('user');
            this.setUser(response.data);
           
        },
        // ...
    }
});