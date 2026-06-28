import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('token'))

  const isAuthenticated = () => !!token.value

  async function login(email: string, password: string): Promise<void> {
    const { data } = await api.post('/api/login', { email, password })
    token.value = data.token
    localStorage.setItem('token', data.token)
  }

  function logout(): void {
    token.value = null
    localStorage.removeItem('token')
  }

  return { token, isAuthenticated, login, logout }
})
