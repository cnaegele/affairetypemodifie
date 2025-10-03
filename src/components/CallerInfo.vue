<template></template>

<script setup lang="ts">
import { ref, defineEmits, onMounted } from 'vue'
import axios from 'axios'
import type { AxiosResponse } from 'axios'
interface Props {
  ssServer?: string
  ssPage?: string
}
export interface UserInfo {
  id: number
  nom: string
  prenom: string
  login: string
  email: string
  bactif: boolean
  bhomme: boolean
  idunite: number
  idservice: number
  iddirection: number
  unite: string
  service: string
  direction: string
}
export interface ApiResponseUI {
  success: boolean
  message: string
  data?: UserInfo
}

const props = withDefaults(defineProps<Props>(), {
  ssServer: '',
  ssPage: '/goeland/gestion_spec/g_caller_info_f5.php'
})

const emit = defineEmits(['callerinfo'])

const ssServer = ref<string>(props.ssServer)
const ssPage = ref<string>(props.ssPage)

onMounted(async () => {
  const userInfo: ApiResponseUI = await getUserInfo(ssServer.value, ssPage.value)
  emit('callerinfo', JSON.stringify(userInfo))
})

async function getUserInfo(server: string = '', page: string): Promise<ApiResponseUI> {
  const urlui: string = `${server}${page}`
  try {
    const response: AxiosResponse<UserInfo> = await axios.get(urlui)
    const respData: ApiResponseUI = {
      "success": true,
      "message": `ok`,
      "data": response.data
    }
    //console.log(respData)
    return respData
  } catch (error: unknown) {
    let msgErr: string = ''
    if (axios.isAxiosError(error)) {
      if (error.response) {
        msgErr = `${error.response.data}<br>${error.response.status}<br>${error.response.headers}`
      } else if (error.request?.responseText) {
        msgErr = error.request.responseText
      } else {
        msgErr = error.message
      }
    } else if (error instanceof Error) {
      msgErr = error.message
    } else {
      msgErr = 'Une erreur inconnue est survenue'
    }

    const respData: ApiResponseUI = {
      "success": false,
      "message": `ERREUR. ${msgErr}`,
    }
    return respData
  }
}
</script>